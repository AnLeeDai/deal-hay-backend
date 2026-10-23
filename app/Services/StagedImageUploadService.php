<?php

namespace App\Services;

use App\Jobs\DeleteR2Images;
use App\Models\StagedImageUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

class StagedImageUploadService
{
    public const int MAX_IMAGE_BYTES = ImageService::MAX_IMAGE_BYTES;

    private const string TOKEN_FORMAT = 'static-webp-v1';

    public const int TOKEN_TTL_MINUTES = 45;

    private const int CLAIM_TIMEOUT_MINUTES = 3;

    public function __construct(
        private readonly ImageService $images,
        private readonly StagedImageUploadQuota $quota,
    ) {}

    /**
     * @param  list<array{size: int, content_type: string}>  $uploads
     * @return list<array{token: string, url?: string, headers?: array<string, list<string>>}>
     */
    public function issueBatch(UploadOwner $user, array $uploads, bool $presign = true): array
    {
        foreach ($uploads as $upload) {
            if (($upload['content_type'] ?? null) !== 'image/webp' || (int) ($upload['size'] ?? 0) < 1 || (int) $upload['size'] > self::MAX_IMAGE_BYTES) {
                $this->invalidUpload('Vui lòng gửi ảnh WebP tĩnh đã xử lý, tối đa 512 KB.');
            }
        }
        $sizes = array_map(
            static fn (array $upload): int => (int) $upload['size'],
            $uploads,
        );
        $this->quota->reserve($user, $sizes);

        $issuedUploads = [];

        try {
            foreach ($uploads as $upload) {
                $issuedUploads[] = $this->issueWithRecord(
                    $user,
                    (int) $upload['size'],
                    $upload['content_type'],
                    $presign,
                );
            }
        } catch (Throwable $exception) {
            try {
                StagedImageUpload::query()
                    ->whereKey(array_map(
                        static fn (array $issued): string => (string) $issued['upload']->getKey(),
                        $issuedUploads,
                    ))
                    ->whereNull('consumed_at')
                    ->delete();
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            try {
                $this->quota->release($user, $sizes);
            } catch (Throwable $quotaException) {
                report($quotaException);
            }

            throw $exception;
        }

        return array_map(
            static fn (array $issued): array => $issued['payload'],
            $issuedUploads,
        );
    }

    /**
     * @return array{
     *     upload: StagedImageUpload,
     *     payload: array{token: string, url?: string, headers?: array<string, list<string>>}
     * }
     */
    private function issueWithRecord(UploadOwner $user, int $expectedSize, string $contentType, bool $presign): array
    {
        $path = 'staged-images/'.$user->user_code.'/'.Str::uuid().'.source';
        $expiresAt = now()->addMinutes(self::TOKEN_TTL_MINUTES);
        $upload = StagedImageUpload::query()->create([
            'user_id' => $user->getKey(),
            'path' => $path,
            'expected_size' => $expectedSize,
            'expires_at' => $expiresAt,
        ]);

        try {
            $signed = $presign ? $this->images->temporaryUploadUrl(
                $path,
                $expiresAt,
                $contentType,
                $expectedSize,
            ) : [];

            $payload = [
                'token' => Crypt::encryptString(json_encode([
                    'upload_id' => (string) $upload->getKey(),
                    'user_id' => (string) $user->getKey(),
                    'path' => $path,
                    'expires_at' => $expiresAt->getTimestamp(),
                    'expected_size' => $expectedSize,
                    'format' => self::TOKEN_FORMAT,
                ], JSON_THROW_ON_ERROR)),
                ...$signed,
            ];
        } catch (Throwable $exception) {
            $upload->delete();

            throw $exception;
        }

        return ['upload' => $upload, 'payload' => $payload];
    }

    public function prepareImage(UploadOwner $user, string $token, UploadedFile $file): void
    {
        [$upload, $validatePrepared] = $this->claimToken($user, $token, $user->user_code.'/products', retryIfBusy: true);

        try {
            $this->prepareClaimedUpload($upload, $file, $validatePrepared);
            $this->releaseClaim($upload);
        } catch (ValidationException $exception) {
            $this->finishClaim($upload);

            throw $exception;
        } catch (Throwable $exception) {
            $this->releaseClaim($upload);

            throw $exception;
        }
    }

    public function consume(UploadOwner $user, string $token, string $folder): string
    {
        [$upload, $validatePrepared] = $this->claimToken($user, $token, $folder);

        try {
            $destination = $this->prepareClaimedUpload($upload, validatePrepared: $validatePrepared);
            $this->finishClaim($upload);

            return $destination;
        } catch (ValidationException $exception) {
            $this->finishClaim($upload);

            throw $exception;
        } catch (Throwable $exception) {
            $this->releaseClaim($upload);

            throw $exception;
        }
    }

    /** @return array{StagedImageUpload, bool} */
    private function claimToken(UploadOwner $user, string $token, string $folder, bool $retryIfBusy = false): array
    {
        try {
            /** @var array{upload_id?: mixed, user_id?: mixed, path?: mixed, expires_at?: mixed, expected_size?: mixed} $payload */
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $this->invalidUpload('Mã xác nhận ảnh tải lên không hợp lệ.');
        }

        $path = is_string($payload['path'] ?? null) ? $payload['path'] : '';
        $uploadId = is_string($payload['upload_id'] ?? null) ? $payload['upload_id'] : '';
        if (! Str::isUuid($uploadId)
            || (string) ($payload['user_id'] ?? '') !== (string) $user->getKey()
            || (int) ($payload['expires_at'] ?? 0) < now()->getTimestamp()
            || ! Str::startsWith($path, 'staged-images/'.$user->user_code.'/')
            || ! Str::endsWith($path, '.source')) {
            $this->invalidUpload('Ảnh tải lên đã hết hạn hoặc không thuộc tài khoản này.');
        }

        $expectedSize = (int) ($payload['expected_size'] ?? 0);

        return [$this->claimUpload(
            $user,
            $uploadId,
            $path,
            $expectedSize,
            $folder,
            $retryIfBusy,
        ), ($payload['format'] ?? null) !== self::TOKEN_FORMAT];
    }

    private function prepareClaimedUpload(StagedImageUpload $upload, ?UploadedFile $file = null, bool $validatePrepared = false): string
    {
        $destination = (string) $upload->destination_path;
        if ($upload->prepared_at !== null) {
            // Earlier grants could point at server-normalized images up to 3 MiB.
            // New signed grants already passed this policy during preparation.
            if ($validatePrepared) {
                $stream = $this->readSource($destination);
                try {
                    $this->images->validateStream($stream, fstat($stream)['size']);
                } catch (ValidationException $exception) {
                    $this->discardSource($destination);
                    throw $exception;
                } finally {
                    fclose($stream);
                }
            }

            return $destination;
        }

        $path = (string) $upload->path;
        $expectedSize = (int) $upload->expected_size;
        if ($expectedSize < 1 || $expectedSize > self::MAX_IMAGE_BYTES) {
            if ($file === null) {
                $this->discardSource($path);
            }
            $this->invalidUpload('Kích thước ảnh tải lên không khớp hoặc vượt quá 512 KB.');
        }
        $stream = null;
        try {
            if ($file !== null) {
                if ($file->getSize() !== $expectedSize) {
                    $this->invalidUpload('Kích thước ảnh tải lên không khớp hoặc vượt quá 512 KB.');
                }
                $this->images->validateFile($file);
                $stream = fopen($file->getRealPath(), 'rb');
                if ($stream === false) {
                    throw new RuntimeException('Không thể đọc tệp ảnh tải lên.');
                }
            } else {
                $stream = $this->readSource($path);
                if (fstat($stream)['size'] !== $expectedSize) {
                    $this->invalidUpload('Kích thước ảnh tải lên không khớp hoặc vượt quá 512 KB.');
                }
                $this->images->validateStream($stream, $expectedSize);
            }
            try {
                if (! $this->images->writeStream($destination, $stream)) {
                    throw new RuntimeException('R2 từ chối ghi tệp.');
                }
            } catch (Throwable $exception) {
                report($exception);
                throw new ServiceUnavailableHttpException(2, 'Kho lưu trữ ảnh đang bận. Vui lòng thử lại sau ít giây.', $exception);
            }
        } catch (ValidationException $exception) {
            if ($file === null) {
                $this->discardSource($path);
            }
            throw $exception;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
        $upload->forceFill(['prepared_at' => now()])->save();
        if ($file === null) {
            $this->discardSource($path);
        }

        return $destination;
    }

    /** @return resource A bounded, seekable stream containing the unchanged source. */
    private function readSource(string $path)
    {
        $source = null;
        $temporary = null;
        try {
            $source = $this->images->readStream($path);
            $temporary = tmpfile();
            if ($temporary === false || stream_copy_to_stream($source, $temporary, self::MAX_IMAGE_BYTES + 1) === false) {
                throw new RuntimeException('Không thể đọc đầy đủ nội dung ảnh tải lên.');
            }
            rewind($temporary);

            return $temporary;
        } catch (Throwable $exception) {
            if (is_resource($temporary)) {
                fclose($temporary);
            }
            report($exception);
            throw new ServiceUnavailableHttpException(2, 'Kho lưu trữ ảnh đang bận. Vui lòng thử lại sau ít giây.', $exception);
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
        }
    }

    private function discardSource(string $path): void
    {
        try {
            if (! $this->images->delete($path)) {
                throw new RuntimeException('Không thể xóa ảnh tải lên không hợp lệ.');
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** @param iterable<array-key, mixed> $tokens */
    public function discard(UploadOwner $user, iterable $tokens): void
    {
        foreach ($tokens as $token) {
            if (! is_string($token)) {
                continue;
            }

            try {
                /** @var array{upload_id?: mixed, user_id?: mixed, path?: mixed} $payload */
                $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                continue;
            }

            $path = is_string($payload['path'] ?? null) ? $payload['path'] : '';
            $uploadId = is_string($payload['upload_id'] ?? null) ? $payload['upload_id'] : '';
            if (Str::isUuid($uploadId)
                && (string) ($payload['user_id'] ?? '') === (string) $user->getKey()
                && Str::startsWith($path, 'staged-images/'.$user->user_code.'/')) {
                DB::transaction(function () use ($uploadId, $user, $path): void {
                    $upload = StagedImageUpload::query()
                        ->whereKey($uploadId)
                        ->where('user_id', $user->getKey())
                        ->where('path', $path)
                        ->whereNull('claimed_at')
                        ->whereNull('consumed_at')
                        ->lockForUpdate()
                        ->first();

                    if ($upload === null) {
                        return;
                    }

                    $upload->forceFill(['consumed_at' => now()])->save();

                    DeleteR2Images::dispatch(array_values(array_filter([$path, $upload->destination_path])))
                        ->onConnection('database')->onQueue('images')->beforeCommit();
                }, attempts: 5);

            }
        }

    }

    private function claimUpload(
        UploadOwner $user,
        string $uploadId,
        string $path,
        int $expectedSize,
        string $folder,
        bool $retryIfBusy,
    ): StagedImageUpload {
        $folder = trim($folder, '/');

        if ($folder === '' || Str::contains($folder, ['..', '\\'])) {
            throw new \InvalidArgumentException('Thư mục lưu ảnh không hợp lệ.');
        }

        return DB::transaction(function () use ($expectedSize, $folder, $path, $uploadId, $user, $retryIfBusy): StagedImageUpload {
            $upload = StagedImageUpload::query()->lockForUpdate()->find($uploadId);

            if ($upload === null
                || (string) $upload->user_id !== (string) $user->getKey()
                || ! hash_equals((string) $upload->path, $path)
                || (int) $upload->expected_size !== $expectedSize
                || $upload->expires_at->isPast()
                || $upload->consumed_at !== null) {
                $this->invalidUpload('Ảnh tải lên đã được sử dụng, hết hạn hoặc không hợp lệ.');
            }

            if ($upload->claimed_at !== null
                && $upload->claimed_at->isAfter(now()->subMinutes(self::CLAIM_TIMEOUT_MINUTES))) {
                if ($retryIfBusy) {
                    throw new ConflictHttpException(
                        'Ảnh đang được xử lý. Vui lòng chờ một chút.',
                        headers: ['Retry-After' => '2'],
                    );
                }

                $this->invalidUpload('Ảnh đang được một yêu cầu khác xử lý.');
            }

            $upload->forceFill([
                'claimed_at' => now(),
                'destination_path' => $upload->destination_path
                    ?: $folder.'/'.Str::uuid().'.webp',
            ])->save();

            return $upload->refresh();
        }, attempts: 5);
    }

    private function finishClaim(StagedImageUpload $upload): void
    {
        StagedImageUpload::query()
            ->whereKey($upload->getKey())
            ->whereNull('consumed_at')
            ->update([
                'claimed_at' => null,
                'consumed_at' => now(),
            ]);
    }

    private function releaseClaim(StagedImageUpload $upload): void
    {
        StagedImageUpload::query()
            ->whereKey($upload->getKey())
            ->whereNull('consumed_at')
            ->update(['claimed_at' => null]);
    }

    private function invalidUpload(string $message): never
    {
        throw ValidationException::withMessages([
            'image' => $message,
        ]);
    }
}

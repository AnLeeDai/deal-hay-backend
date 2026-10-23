<?php

namespace App\Services;

use App\Enums\ImagePurposeEnum;
use App\Support\StrictUploadSignatureV4;
use Aws\S3\S3Client;
use DateTimeInterface;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ImageService
{
    public const string DISK = 'r2';

    public const int MAX_IMAGE_BYTES = 512 * 1024;

    public const int MAX_DIMENSION = 1280;

    public function upload(
        UploadedFile $file,
        string $folder = '',
        string $errorField = 'image',
        string $diskName = self::DISK,
        ImagePurposeEnum $purpose = ImagePurposeEnum::Product,
    ): string {
        $this->validateFile($file, $purpose, $errorField);
        $stream = fopen($file->getRealPath(), 'rb');
        if ($stream === false) {
            throw ValidationException::withMessages([$errorField => 'Không thể đọc tệp ảnh tải lên.']);
        }
        try {
            $path = collect([trim($folder, '/'), Str::uuid().'.webp'])->filter()->implode('/');
            if (! $this->writeStream($path, $stream, $diskName)) {
                throw new RuntimeException('R2 từ chối ghi tệp.');
            }

            return $path;
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([
                $errorField => 'Không thể tải ảnh lên kho lưu trữ. Vui lòng thử lại sau.',
            ]);
        } finally {
            fclose($stream);
        }
    }

    public function validateFile(UploadedFile $file, ImagePurposeEnum $purpose = ImagePurposeEnum::Product, string $errorField = 'image'): void
    {
        $stream = fopen($file->getRealPath(), 'rb');
        if ($stream === false) {
            throw ValidationException::withMessages([$errorField => 'Không thể đọc tệp ảnh tải lên.']);
        }
        try {
            $size = (int) $file->getSize();
            if (($stat = fstat($stream)) === false || $stat['size'] !== $size) {
                throw ValidationException::withMessages([$errorField => 'Kích thước tệp ảnh không khớp.']);
            }
            $this->validateStream($stream, $size, $purpose, $errorField);
        } finally {
            fclose($stream);
        }
    }

    /**
     * Validate the bounded WebP container and dimensions without decoding pixels.
     *
     * @param  resource  $stream  A seekable stream, rewound before and after validation.
     */
    public function validateStream($stream, int $size, ImagePurposeEnum $purpose = ImagePurposeEnum::Product, string $errorField = 'image'): void
    {
        $invalid = static function (string $message) use ($errorField): never {
            throw ValidationException::withMessages([$errorField => $message]);
        };
        if ($size < 1 || $size > $purpose->maxKilobytes() * 1024) {
            $invalid('Ảnh đã xử lý phải có kích thước từ 1 byte đến '.$purpose->maxKilobytes().' KB.');
        }
        try {
            rewind($stream);
            $header = fread($stream, 12);
            if (strlen($header) !== 12 || substr($header, 0, 4) !== 'RIFF' || substr($header, 8, 4) !== 'WEBP'
                || unpack('V', substr($header, 4, 4))[1] !== $size - 8) {
                $invalid('Vui lòng gửi ảnh WebP tĩnh đã được xử lý trên thiết bị.');
            }
            $imageDimensions = null;
            $canvasDimensions = null;
            $position = 12;
            while ($position < $size) {
                $chunk = fread($stream, 8);
                if (strlen($chunk) !== 8) {
                    $invalid('Cấu trúc ảnh WebP không hợp lệ.');
                }
                $type = substr($chunk, 0, 4);
                $length = unpack('V', substr($chunk, 4, 4))[1];
                $position += 8;
                $next = $position + $length + ($length % 2);
                if ($next > $size || in_array($type, ['ANIM', 'ANMF'], true)) {
                    $invalid('Ảnh WebP phải là ảnh tĩnh và có cấu trúc đầy đủ.');
                }
                $data = $length > 0 ? fread($stream, min(10, $length)) : '';
                if ($type === 'VP8X') {
                    if ($position !== 20 || $length !== 10 || (ord($data[0]) & 0xC3) !== 0 || substr($data, 1, 3) !== "\0\0\0") {
                        $invalid('Ảnh WebP phải là ảnh tĩnh và có cấu trúc đầy đủ.');
                    }
                    $canvasDimensions = [1 + unpack('V', substr($data, 4, 3)."\0")[1], 1 + unpack('V', substr($data, 7, 3)."\0")[1]];
                } elseif ($type === 'VP8 ' || $type === 'VP8L') {
                    if ($imageDimensions !== null) {
                        $invalid('Ảnh WebP chỉ được chứa một khung hình tĩnh.');
                    }
                    if ($type === 'VP8 ') {
                        if ($length < 10 || (ord($data[0]) & 1) !== 0 || substr($data, 3, 3) !== "\x9d\x01\x2a") {
                            $invalid('Cấu trúc ảnh WebP không hợp lệ.');
                        }
                        $imageDimensions = [unpack('v', substr($data, 6, 2))[1] & 0x3FFF, unpack('v', substr($data, 8, 2))[1] & 0x3FFF];
                    } else {
                        if ($length < 5 || $data[0] !== "\x2f") {
                            $invalid('Cấu trúc ảnh WebP không hợp lệ.');
                        }
                        $bits = unpack('V', substr($data, 1, 4))[1];
                        if (($bits & 0xE0000000) !== 0) {
                            $invalid('Cấu trúc ảnh WebP không hợp lệ.');
                        }
                        $imageDimensions = [1 + ($bits & 0x3FFF), 1 + (($bits >> 14) & 0x3FFF)];
                    }
                }
                if (fseek($stream, $next) !== 0) {
                    $invalid('Không thể đọc cấu trúc ảnh WebP.');
                }
                $position = $next;
            }
            if ($imageDimensions === null || ($canvasDimensions !== null && $canvasDimensions !== $imageDimensions)) {
                $invalid('Cấu trúc ảnh WebP không hợp lệ.');
            }
            $maximum = $purpose === ImagePurposeEnum::Avatar ? 640 : self::MAX_DIMENSION;
            if (min($imageDimensions) < 1 || max($imageDimensions) > $maximum) {
                $invalid('Ảnh đã xử lý không được vượt quá '.$maximum.' pixel mỗi chiều.');
            }
        } finally {
            rewind($stream);
        }
    }

    public function defaultAvatar(): string
    {
        $available = array_values(array_diff(range(1, 100), [40, 72, 91]));

        return sprintf('systems_avatar/systems_users_AV%d.webp', $available[array_rand($available)]);
    }

    public function delete(?string $path, string $diskName = self::DISK): bool
    {
        $storagePath = $this->storagePath($path);

        if ($storagePath === null || Str::startsWith($storagePath, 'systems_avatar/')) {
            return false;
        }

        return $this->disk($diskName)->delete($storagePath);
    }

    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $configuredUrl = config('filesystems.disks.'.self::DISK.'.url');

        if (is_string($configuredUrl) && filled($configuredUrl)) {
            return rtrim($configuredUrl, '/').'/'.ltrim($path, '/');
        }

        return $this->disk()->url($path);
    }

    public function exists(string $path, string $diskName = self::DISK): bool
    {
        $storagePath = $this->storagePath($path);

        return $storagePath !== null && $this->disk($diskName)->exists($storagePath);
    }

    public function size(string $path, string $diskName = self::DISK): int
    {
        return $this->disk($diskName)->size($this->requiredStoragePath($path));
    }

    public function mimeType(string $path): string
    {
        return $this->disk()->mimeType($this->requiredStoragePath($path));
    }

    /** @return array{url: string, headers: array<string, list<string>>} */
    public function temporaryUploadUrl(
        string $path,
        DateTimeInterface $expiration,
        string $contentType,
        int $contentLength,
    ): array {
        if ($contentLength < 1) {
            throw new RuntimeException('Kích thước tệp tải lên không hợp lệ.');
        }

        $disk = $this->disk();

        if (! $disk instanceof AwsS3V3Adapter) {
            throw new RuntimeException('Ổ đĩa tải lên phải sử dụng S3 API.');
        }

        $client = $disk->getClient();
        $bucket = (string) config('filesystems.disks.'.self::DISK.'.bucket');
        $storagePath = $this->requiredStoragePath($path);
        $publicEndpoint = config('filesystems.disks.'.self::DISK.'.public_endpoint');

        if (is_string($publicEndpoint) && filled($publicEndpoint)) {
            return $this->minioTemporaryUploadUrl(
                $client,
                $bucket,
                $storagePath,
                $expiration,
                $contentType,
                $contentLength,
            );
        }

        $uploadUrl = $client->getObjectUrl($bucket, $storagePath);
        $request = new PsrRequest('PUT', $uploadUrl, [
            'Content-Length' => (string) $contentLength,
            'Content-Type' => $contentType,
        ]);
        $signedRequest = (new StrictUploadSignatureV4('s3', (string) config('filesystems.disks.'.self::DISK.'.region'), [
            'unsigned-body' => true,
        ]))->presign(
            $request,
            $client->getCredentials()->wait(),
            $expiration,
        );

        return [
            'url' => (string) $signedRequest->getUri(),
            'headers' => $signedRequest->getHeaders(),
        ];
    }

    /** @return array{url: string, headers: array<string, list<string>>} */
    private function minioTemporaryUploadUrl(
        S3Client $client,
        string $bucket,
        string $path,
        DateTimeInterface $expiration,
        string $contentType,
        int $contentLength,
    ): array {
        $publicClient = new S3Client([
            'version' => 'latest',
            'region' => (string) config('filesystems.disks.'.self::DISK.'.region'),
            'credentials' => $client->getCredentials()->wait(),
            'endpoint' => config('filesystems.disks.'.self::DISK.'.public_endpoint'),
            'use_path_style_endpoint' => true,
        ]);
        $request = new PsrRequest('PUT', $publicClient->getObjectUrl($bucket, $path), [
            'Content-Length' => (string) $contentLength,
            'Content-Type' => $contentType,
            'X-Amz-Content-Sha256' => StrictUploadSignatureV4::UNSIGNED_PAYLOAD,
        ]);
        $signedRequest = (new StrictUploadSignatureV4('s3', (string) config('filesystems.disks.'.self::DISK.'.region'), [
            'unsigned-body' => true,
        ]))->presign(
            $request,
            $publicClient->getCredentials()->wait(),
            $expiration,
        );

        return [
            'url' => (string) $signedRequest->getUri(),
            'headers' => $signedRequest->getHeaders(),
        ];
    }

    public function moveImage(string $source, string $destination): bool
    {
        return $this->disk()->move(
            $this->requiredStoragePath($source),
            $this->requiredStoragePath($destination),
        );
    }

    /** @param resource $stream */
    public function writeStream(string $path, $stream, string $diskName = self::DISK): bool
    {
        rewind($stream);

        return $this->disk($diskName)->put($this->requiredStoragePath($path), $stream, [
            'ContentType' => 'image/webp',
        ]);
    }

    /** @return resource */
    public function readStream(string $path, string $diskName = self::DISK)
    {
        $stream = $this->disk($diskName)->readStream($this->requiredStoragePath($path));

        if ($stream === false) {
            throw new RuntimeException('Không thể đọc hình ảnh từ R2.');
        }

        return $stream;
    }

    /** @param iterable<array-key, string|null> $paths */
    public function deleteMany(iterable $paths): void
    {
        foreach ($paths as $path) {
            if (! filled($path)) {
                continue;
            }

            $storagePath = $this->storagePath($path);
            if ($storagePath === null || Str::startsWith($storagePath, 'systems_avatar/')) {
                continue;
            }

            if (! $this->delete($path)) {
                throw new RuntimeException('Không thể xóa ảnh từ kho lưu trữ.');
            }
        }
    }

    private function disk(string $diskName = self::DISK): FilesystemAdapter
    {
        $disk = Storage::disk($diskName);

        if (! $disk instanceof FilesystemAdapter) {
            throw new RuntimeException('Ổ đĩa hệ thống tệp đã cấu hình không hỗ trợ tạo URL.');
        }

        return $disk;
    }

    private function requiredStoragePath(string $path): string
    {
        return $this->storagePath($path)
            ?? throw new RuntimeException('Đường dẫn hình ảnh R2 không hợp lệ.');
    }

    private function storagePath(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $path = trim((string) $path);

        if (! Str::startsWith($path, ['http://', 'https://'])) {
            return ltrim($path, '/');
        }

        $configuredUrl = config('filesystems.disks.'.self::DISK.'.url');

        if (! is_string($configuredUrl) || ! filled($configuredUrl)) {
            return null;
        }

        $prefix = rtrim($configuredUrl, '/').'/';

        return Str::startsWith($path, $prefix)
            ? ltrim(Str::after($path, $prefix), '/')
            : null;
    }
}

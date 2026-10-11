<?php

namespace App\Services;

use App\Enums\ImageUploadTypeEnum;
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
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Exceptions\ImageDecoderException;
use Intervention\Image\ImageManager;
use RuntimeException;
use Throwable;

class ImageService
{
    public const string DISK = 'r2';

    public const int MAX_PIXEL_COUNT = 16_000_000;

    public const int MAX_DIMENSION = 8_192;

    public const int OUTPUT_MAX_DIMENSION = 2_400;

    public const int MAX_OUTPUT_BYTES = 3 * 1024 * 1024;

    public const int MAX_SOURCE_BYTES = 30 * 1024 * 1024;

    private const array SUPPORTED_MIME_TYPES = [
        ImageUploadTypeEnum::JPEG,
        ImageUploadTypeEnum::PNG,
        ImageUploadTypeEnum::GIF,
        ImageUploadTypeEnum::WEBP,
        ImageUploadTypeEnum::AVIF,
        ImageUploadTypeEnum::BMP,
    ];

    public function __construct(private readonly ImageManager $manager) {}

    public function upload(
        UploadedFile $file,
        string $folder = '',
        string $errorField = 'image',
        string $diskName = self::DISK,
    ): string {
        try {
            $webpContent = $this->convertToWebp($file);
            $path = collect([trim($folder, '/'), Str::uuid().'.webp'])
                ->filter()
                ->implode('/');

            if (! $this->disk($diskName)->put($path, $webpContent, [
                'ContentType' => 'image/webp',
            ])) {
                throw new RuntimeException('R2 từ chối ghi tệp.');
            }

            return $path;
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                $errorField => 'Không thể tải ảnh lên kho lưu trữ. Vui lòng thử lại sau.',
            ]);
        }
    }

    public function convertToWebp(UploadedFile $file, int $quality = 75): string
    {
        if (! in_array($file->getMimeType(), self::SUPPORTED_MIME_TYPES, strict: true)) {
            throw new ImageDecoderException(
                "Không hỗ trợ định dạng hình ảnh [{$file->getMimeType()}]. Định dạng hỗ trợ: "
                    .implode(', ', self::SUPPORTED_MIME_TYPES),
            );
        }

        $imageInfo = @getimagesize($file->getRealPath());

        if (! is_array($imageInfo)) {
            throw new ImageDecoderException('Không thể đọc kích thước hình ảnh tải lên.');
        }

        $this->assertSafeDimensions((int) $imageInfo[0], (int) $imageInfo[1]);

        $encoded = (string) $this->manager
            ->decodeSplFileInfo($file)
            ->removeAnimation()
            ->scaleDown(self::OUTPUT_MAX_DIMENSION, self::OUTPUT_MAX_DIMENSION)
            ->encode(new WebpEncoder($quality));

        return $this->assertSafeEncodedSize($encoded);
    }

    public function normalizeWebpBinary(string $content, int $quality = 75): string
    {
        $imageInfo = @getimagesizefromstring($content);

        if (! is_array($imageInfo) || ($imageInfo['mime'] ?? null) !== ImageUploadTypeEnum::WEBP) {
            throw new ImageDecoderException('Nội dung tải lên không phải là ảnh WebP hợp lệ.');
        }

        return $this->normalizeBinary($content, $quality);
    }

    public function normalizeBinary(string $content, int $quality = 75): string
    {
        $imageInfo = @getimagesizefromstring($content);

        if (! is_array($imageInfo) || ! in_array($imageInfo['mime'] ?? null, self::SUPPORTED_MIME_TYPES, true)) {
            throw new ImageDecoderException('Nội dung tải lên không phải là hình ảnh hợp lệ.');
        }

        $this->assertSafeDimensions((int) $imageInfo[0], (int) $imageInfo[1]);

        $encoded = (string) $this->manager
            ->decodeBinary($content)
            ->removeAnimation()
            ->scaleDown(self::OUTPUT_MAX_DIMENSION, self::OUTPUT_MAX_DIMENSION)
            ->encode(new WebpEncoder($quality));

        return $this->assertSafeEncodedSize($encoded);
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

    public function write(string $path, string $content): bool
    {
        return $this->disk()->put($this->requiredStoragePath($path), $content, [
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
            $this->delete($path);
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

    private function assertSafeDimensions(int $width, int $height): void
    {
        if (
            $width < 1
            || $height < 1
            || $width > self::MAX_DIMENSION
            || $height > self::MAX_DIMENSION
            || $width * $height > self::MAX_PIXEL_COUNT
        ) {
            throw new ImageDecoderException('Kích thước hoặc tổng số điểm ảnh vượt quá giới hạn an toàn.');
        }
    }

    private function assertSafeEncodedSize(string $content): string
    {
        if (strlen($content) > self::MAX_OUTPUT_BYTES) {
            throw new ImageDecoderException('Ảnh sau khi chuẩn hóa vượt quá giới hạn 3 MB.');
        }

        return $content;
    }
}

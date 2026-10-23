<?php

use App\Enums\ImagePurposeEnum;
use App\Services\ImageService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

it('streams browser processed WebP bytes to storage unchanged', function (): void {
    $file = preparedImage();
    $source = file_get_contents($file->getRealPath());
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('put')->once()->withArgs(function (string $path, mixed $stream, array $options) use ($source): bool {
        expect(is_resource($stream))->toBeTrue();
        expect(stream_get_contents($stream))->toBe($source);

        return str_starts_with($path, 'owner/products/') && str_ends_with($path, '.webp') && $options['ContentType'] === 'image/webp';
    })->andReturn(true);
    Storage::shouldReceive('disk')->once()->with('r2')->andReturn($disk);

    expect(app(ImageService::class)->upload($file, 'owner/products'))->toStartWith('owner/products/')->toEndWith('.webp');
});

it('rejects raw formats and MIME spoofing without storing or transforming', function (string $content, string $name): void {
    $disk = Storage::fake('r2');
    $file = UploadedFile::fake()->createWithContent($name, $content);
    expect(fn () => app(ImageService::class)->upload($file, 'owner/products'))
        ->toThrow(ValidationException::class, 'Vui lòng gửi ảnh WebP tĩnh đã được xử lý trên thiết bị.');
    expect($disk->allFiles())->toBeEmpty();
})->with([
    'raw PNG' => [base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLbtAAAAABJRU5ErkJggg=='), 'photo.png'],
    'raw GIF' => [base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 'photo.gif'],
    'spoofed WebP' => ['<svg xmlns="http://www.w3.org/2000/svg"/>', 'photo.webp'],
]);

it('rejects final images outside the product 512 KiB bound', function (string $content): void {
    $file = UploadedFile::fake()->createWithContent('photo.webp', $content);
    expect(fn () => app(ImageService::class)->validateFile($file))
        ->toThrow(ValidationException::class, 'Ảnh đã xử lý phải có kích thước từ 1 byte đến 512 KB.');
})->with(['empty' => '', 'over limit' => fn (): string => str_repeat('x', ImageService::MAX_IMAGE_BYTES + 1)]);

it('preserves detailed product images above 20 KiB while retaining smaller limits for other purposes', function (): void {
    $source = file_get_contents(base_path('tests/Fixtures/detailed-product.webp'));
    expect(strlen($source))->toBeGreaterThan(20 * 1024)->toBeLessThanOrEqual(512 * 1024);
    $file = UploadedFile::fake()->createWithContent('detailed.webp', $source);
    $disk = Storage::fake('r2');
    $path = app(ImageService::class)->upload($file, 'owner/products', purpose: ImagePurposeEnum::Product);
    expect($disk->get($path))->toBe($source);
    foreach ([ImagePurposeEnum::Avatar, ImagePurposeEnum::Review, ImagePurposeEnum::Chat] as $purpose) {
        expect(fn () => app(ImageService::class)->validateFile($file, $purpose))
            ->toThrow(ValidationException::class, 'Ảnh đã xử lý phải có kích thước từ 1 byte đến 20 KB.');
    }
});

it('checks container boundaries static frames and purpose dimensions without raster decoding', function (string $case): void {
    $source = file_get_contents(base_path('tests/Fixtures/prepared.webp'));
    $chunk = substr($source, 12);
    $make = static fn (string $chunks): string => 'RIFF'.pack('V', strlen($chunks) + 4).'WEBP'.$chunks;
    $content = match ($case) {
        'truncated RIFF' => substr($source, 0, -1),
        'extra trailing data' => $source.'x',
        'oversized chunk' => substr_replace($source, pack('V', 0xFFFFFFFF), 16, 4),
        'animation flag' => $make('VP8X'.pack('V', 10)."\x02\0\0\0\x3f\0\0\x3f\0\0".$chunk),
        'animation chunk' => $make('ANIM'.pack('V', 6).str_repeat("\0", 6).$chunk),
        'duplicate frame' => $make($chunk.$chunk),
        'missing frame' => $make('EXIF'.pack('V', 4).'meta'),
        'oversized dimensions' => substr_replace($source, pack('v', 1281), 26, 2),
    };
    expect(fn () => app(ImageService::class)->validateFile(UploadedFile::fake()->createWithContent('invalid.webp', $content)))
        ->toThrow(ValidationException::class);
})->with(['truncated RIFF', 'extra trailing data', 'oversized chunk', 'animation flag', 'animation chunk', 'duplicate frame', 'missing frame', 'oversized dimensions']);

it('applies the smaller avatar limit without resizing or modifying a product image', function (): void {
    $source = file_get_contents(base_path('tests/Fixtures/prepared.webp'));
    $source = substr_replace($source, pack('v', 641), 26, 2);
    $file = UploadedFile::fake()->createWithContent('wide.webp', $source);
    app(ImageService::class)->validateFile($file, ImagePurposeEnum::Product);
    expect(fn () => app(ImageService::class)->validateFile($file, ImagePurposeEnum::Avatar))
        ->toThrow(ValidationException::class, 'Ảnh đã xử lý không được vượt quá 640 pixel mỗi chiều.');
    expect(file_get_contents($file->getRealPath()))->toBe($source);
});

it('keeps shared system avatars when bulk image cleanup runs', function (): void {
    $disk = Storage::fake('r2');
    $disk->put('systems_avatar/systems_users_AV1.webp', 'Shared avatar.');
    $disk->put('owner/products/removed.webp', 'Product image.');

    app(ImageService::class)->deleteMany(['systems_avatar/systems_users_AV1.webp', 'owner/products/removed.webp']);

    $disk->assertExists('systems_avatar/systems_users_AV1.webp');
    $disk->assertMissing('owner/products/removed.webp');
});

it('fails bulk image cleanup when storage refuses deletion', function (): void {
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('delete')->once()->with('owner/products/removed.webp')->andReturn(false);
    Storage::shouldReceive('disk')->once()->with('r2')->andReturn($disk);

    expect(fn () => app(ImageService::class)->deleteMany(['owner/products/removed.webp']))
        ->toThrow(RuntimeException::class, 'Không thể xóa ảnh từ kho lưu trữ.');
});

it('skips external media URLs and continues deleting owned image keys', function (): void {
    config()->set('filesystems.disks.r2.url', 'https://media.example.test');
    $disk = Storage::fake('r2');
    $path = 'owner/products/removed.webp';
    $disk->put($path, 'Product image.');

    app(ImageService::class)->deleteMany(['https://outside.example.test/image.webp', $path]);

    $disk->assertMissing($path);
    expect($disk->allFiles())->toBeEmpty();
});

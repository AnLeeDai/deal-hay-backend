<?php

use App\Services\ImageService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Exceptions\ImageDecoderException;

it('rejects binary sources outside the safe range before decoding', function (string $content): void {
    expect(fn () => app(ImageService::class)->normalizeBinary($content))
        ->toThrow(ImageDecoderException::class, 'Ảnh tải lên phải có kích thước từ 1 byte đến 30 MB.');
})->with([
    'empty source' => '',
    'over 30 MB' => fn (): string => str_repeat('x', ImageService::MAX_SOURCE_BYTES + 1),
]);

it('rejects oversized uploaded files before decoding', function (): void {
    $file = UploadedFile::fake()->image('photo.jpg', 64, 64)->size(30_721);

    expect(fn () => app(ImageService::class)->convertToWebp($file))
        ->toThrow(ImageDecoderException::class, 'Ảnh tải lên phải có kích thước từ 1 byte đến 30 MB.');
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

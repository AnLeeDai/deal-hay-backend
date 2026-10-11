<?php

use App\Models\StagedImageUpload;
use App\Models\User;
use App\Services\StagedImageUploadService;
use App\Services\UploadOwner;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

it('normalizes a direct upload and consumes its token exactly once', function (): void {
    $disk = Storage::fake('r2');
    $user = User::factory()->customer()->create();
    $owner = new UploadOwner((string) $user->getKey(), $user->user_code);
    $file = UploadedFile::fake()->image('photo.png', 64, 64);
    $source = file_get_contents($file->getRealPath());
    $service = app(StagedImageUploadService::class);
    $token = $service->issueBatch($owner, [['size' => strlen($source), 'content_type' => 'image/png']], presign: false)[0]['token'];
    $upload = StagedImageUpload::query()->sole();
    $disk->put($upload->path, $source);

    $destination = $service->consume($owner, $token, $user->user_code.'/products');

    expect($destination)->toStartWith($user->user_code.'/products/')->toEndWith('.webp');
    $disk->assertExists($destination);
    $disk->assertMissing($upload->path);
    expect(getimagesizefromstring($disk->get($destination))['mime'])->toBe('image/webp');
    expect($upload->refresh()->prepared_at)->not->toBeNull();
    expect($upload->consumed_at)->not->toBeNull();
    expect($upload->claimed_at)->toBeNull();
    expect(fn () => $service->consume($owner, $token, $user->user_code.'/products'))
        ->toThrow(ValidationException::class, 'Ảnh tải lên đã được sử dụng, hết hạn hoặc không hợp lệ.');
    expect($disk->allFiles())->toHaveCount(1);
});

it('keeps a staged token retryable when R2 cannot read its source', function (): void {
    Exceptions::fake();
    $user = User::factory()->customer()->create();
    $owner = new UploadOwner((string) $user->getKey(), $user->user_code);
    $file = UploadedFile::fake()->image('photo.png', 64, 64);
    $source = file_get_contents($file->getRealPath());
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $source);
    rewind($stream);
    $service = app(StagedImageUploadService::class);
    $token = $service->issueBatch($owner, [['size' => strlen($source), 'content_type' => 'image/png']], presign: false)[0]['token'];
    $upload = StagedImageUpload::query()->sole();
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('readStream')->twice()->with($upload->path)->andReturn(false, $stream);
    $disk->shouldReceive('put')->once()->withArgs(fn (string $path, string $content, array $options): bool => str_starts_with($path, $user->user_code.'/products/')
        && getimagesizefromstring($content)['mime'] === 'image/webp'
        && $options['ContentType'] === 'image/webp')->andReturn(true);
    $disk->shouldReceive('delete')->once()->with($upload->path)->andReturn(true);
    Storage::shouldReceive('disk')->with('r2')->andReturn($disk);

    try {
        $service->consume($owner, $token, $user->user_code.'/products');
        $this->fail('An unreadable R2 source must return a retryable error.');
    } catch (ServiceUnavailableHttpException $exception) {
        expect($exception->getStatusCode())->toBe(503);
        expect($exception->getHeaders()['Retry-After'])->toBe(2);
    }

    expect($upload->refresh()->consumed_at)->toBeNull();
    expect($upload->prepared_at)->toBeNull();
    expect($upload->claimed_at)->toBeNull();
    Exceptions::assertReported(RuntimeException::class);

    $destination = $service->consume($owner, $token, $user->user_code.'/products');

    expect($destination)->toBe($upload->destination_path);
    expect($upload->refresh()->consumed_at)->not->toBeNull();
});

it('keeps a staged token retryable when R2 refuses its normalized image', function (): void {
    Exceptions::fake();
    $user = User::factory()->customer()->create();
    $owner = new UploadOwner((string) $user->getKey(), $user->user_code);
    $file = UploadedFile::fake()->image('photo.png', 64, 64);
    $service = app(StagedImageUploadService::class);
    $token = $service->issueBatch($owner, [['size' => $file->getSize(), 'content_type' => 'image/png']], presign: false)[0]['token'];
    $upload = StagedImageUpload::query()->sole();
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('put')->twice()->andReturn(false, true);
    Storage::shouldReceive('disk')->with('r2')->andReturn($disk);

    expect(fn () => $service->prepareImage($owner, $token, $file))->toThrow(ServiceUnavailableHttpException::class);

    expect($upload->refresh()->consumed_at)->toBeNull();
    expect($upload->claimed_at)->toBeNull();
    Exceptions::assertReported(RuntimeException::class);

    $service->prepareImage($owner, $token, $file);
    $destination = $service->consume($owner, $token, $user->user_code.'/products');

    expect($destination)->toBe($upload->refresh()->destination_path);
    expect($upload->prepared_at)->not->toBeNull();
    expect($upload->consumed_at)->not->toBeNull();
});

it('discards a direct source and consumes the token when its size is wrong', function (): void {
    $disk = Storage::fake('r2');
    $user = User::factory()->customer()->create();
    $owner = new UploadOwner((string) $user->getKey(), $user->user_code);
    $service = app(StagedImageUploadService::class);
    $token = $service->issueBatch($owner, [['size' => 100, 'content_type' => 'image/png']], presign: false)[0]['token'];
    $upload = StagedImageUpload::query()->sole();
    $disk->put($upload->path, str_repeat('x', 101));

    expect(fn () => $service->consume($owner, $token, $user->user_code.'/products'))
        ->toThrow(ValidationException::class, 'Kích thước ảnh tải lên không khớp hoặc vượt quá 30 MB.');

    $disk->assertMissing($upload->path);
    expect($upload->refresh()->consumed_at)->not->toBeNull();
    expect($upload->claimed_at)->toBeNull();
    expect($upload->prepared_at)->toBeNull();
});

it('refuses staged tokens belonging to another account', function (): void {
    $disk = Storage::fake('r2');
    $user = User::factory()->customer()->create();
    $owner = new UploadOwner((string) $user->getKey(), $user->user_code);
    $outsider = User::factory()->customer()->create();
    $service = app(StagedImageUploadService::class);
    $token = $service->issueBatch($owner, [['size' => 100, 'content_type' => 'image/png']], presign: false)[0]['token'];
    $upload = StagedImageUpload::query()->sole();

    expect(fn () => $service->consume(new UploadOwner((string) $outsider->getKey(), $outsider->user_code), $token, $outsider->user_code.'/products'))
        ->toThrow(ValidationException::class, 'Ảnh tải lên đã hết hạn hoặc không thuộc tài khoản này.');

    expect($upload->refresh()->claimed_at)->toBeNull();
    expect($upload->consumed_at)->toBeNull();
    expect($disk->allFiles())->toBeEmpty();
});

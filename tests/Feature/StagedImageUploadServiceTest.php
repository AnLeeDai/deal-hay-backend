<?php

use App\Models\StagedImageUpload;
use App\Models\User;
use App\Services\StagedImageUploadService;
use App\Services\UploadOwner;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

it('stores a direct prepared upload unchanged and consumes its token exactly once', function (): void {
    $disk = Storage::fake('r2');
    $user = User::factory()->customer()->create();
    $owner = new UploadOwner((string) $user->getKey(), $user->user_code);
    $file = preparedImage('photo.webp');
    $source = file_get_contents($file->getRealPath());
    $service = app(StagedImageUploadService::class);
    $token = $service->issueBatch($owner, [['size' => strlen($source), 'content_type' => 'image/webp']], presign: false)[0]['token'];
    $upload = StagedImageUpload::query()->sole();
    $disk->put($upload->path, $source);

    $destination = $service->consume($owner, $token, $user->user_code.'/products');

    expect($destination)->toStartWith($user->user_code.'/products/')->toEndWith('.webp');
    $disk->assertExists($destination);
    $disk->assertMissing($upload->path);
    expect($disk->get($destination))->toBe($source);
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
    $file = preparedImage('photo.webp');
    $source = file_get_contents($file->getRealPath());
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $source);
    rewind($stream);
    $service = app(StagedImageUploadService::class);
    $token = $service->issueBatch($owner, [['size' => strlen($source), 'content_type' => 'image/webp']], presign: false)[0]['token'];
    $upload = StagedImageUpload::query()->sole();
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('readStream')->twice()->with($upload->path)->andReturn(false, $stream);
    $disk->shouldReceive('put')->once()->withArgs(fn (string $path, mixed $content, array $options): bool => str_starts_with($path, $user->user_code.'/products/')
        && is_resource($content) && stream_get_contents($content) === $source
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

it('keeps a staged token retryable when R2 refuses its prepared image', function (): void {
    Exceptions::fake();
    $user = User::factory()->customer()->create();
    $owner = new UploadOwner((string) $user->getKey(), $user->user_code);
    $file = preparedImage('photo.webp');
    $service = app(StagedImageUploadService::class);
    $token = $service->issueBatch($owner, [['size' => $file->getSize(), 'content_type' => 'image/webp']], presign: false)[0]['token'];
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
    $token = $service->issueBatch($owner, [['size' => 100, 'content_type' => 'image/webp']], presign: false)[0]['token'];
    $upload = StagedImageUpload::query()->sole();
    $disk->put($upload->path, str_repeat('x', 101));

    expect(fn () => $service->consume($owner, $token, $user->user_code.'/products'))
        ->toThrow(ValidationException::class, 'Kích thước ảnh tải lên không khớp hoặc vượt quá 512 KB.');

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
    $token = $service->issueBatch($owner, [['size' => 100, 'content_type' => 'image/webp']], presign: false)[0]['token'];
    $upload = StagedImageUpload::query()->sole();

    expect(fn () => $service->consume(new UploadOwner((string) $outsider->getKey(), $outsider->user_code), $token, $outsider->user_code.'/products'))
        ->toThrow(ValidationException::class, 'Ảnh tải lên đã hết hạn hoặc không thuộc tài khoản này.');

    expect($upload->refresh()->claimed_at)->toBeNull();
    expect($upload->consumed_at)->toBeNull();
    expect($disk->allFiles())->toBeEmpty();
});

it('revalidates legacy prepared tokens while preserving valid historical objects unchanged', function (bool $valid): void {
    $disk = Storage::fake('r2');
    $user = User::factory()->customer()->create();
    $owner = new UploadOwner((string) $user->getKey(), $user->user_code);
    $service = app(StagedImageUploadService::class);
    $file = preparedImage();
    $issued = $service->issueBatch($owner, [['size' => $file->getSize(), 'content_type' => 'image/webp']], presign: false)[0]['token'];
    $payload = json_decode(Crypt::decryptString($issued), true);
    unset($payload['format']);
    // Legacy declarations could describe a large original even though its prepared image was small.
    $payload['expected_size'] = 3 * 1024 * 1024;
    $legacy = Crypt::encryptString(json_encode($payload));
    $path = $user->user_code.'/products/historical.webp';
    $upload = StagedImageUpload::query()->sole();
    $upload->update(['expected_size' => $payload['expected_size'], 'prepared_at' => now(), 'destination_path' => $path]);
    $content = $valid ? file_get_contents($file->getRealPath()) : str_repeat('x', 513 * 1024);
    $disk->put($path, $content);

    if ($valid) {
        expect($service->consume($owner, $legacy, $user->user_code.'/products'))->toBe($path);
        expect($disk->get($path))->toBe($content);
    } else {
        expect(fn () => $service->consume($owner, $legacy, $user->user_code.'/products'))->toThrow(ValidationException::class);
        $disk->assertMissing($path);
    }
    expect($upload->refresh()->consumed_at)->not->toBeNull();
    expect($upload->claimed_at)->toBeNull();
})->with(['valid final WebP' => true, 'old oversized prepared image' => false]);

it('refuses raw declarations and raw images even when a scoped token declares WebP', function (): void {
    $disk = Storage::fake('r2');
    $user = User::factory()->customer()->create();
    $owner = new UploadOwner((string) $user->getKey(), $user->user_code);
    $service = app(StagedImageUploadService::class);
    expect(fn () => $service->issueBatch($owner, [['size' => 100, 'content_type' => 'image/png']], presign: false))
        ->toThrow(ValidationException::class);
    $source = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLbtAAAAABJRU5ErkJggg==');
    $token = $service->issueBatch($owner, [['size' => strlen($source), 'content_type' => 'image/webp']], presign: false)[0]['token'];
    $upload = StagedImageUpload::query()->sole();
    $disk->put($upload->path, $source);
    expect(fn () => $service->consume($owner, $token, $user->user_code.'/products'))->toThrow(ValidationException::class);
    expect($disk->allFiles())->toBeEmpty();
    expect($upload->refresh()->consumed_at)->not->toBeNull();
    expect($upload->prepared_at)->toBeNull();
});

it('isolates account quota locks and returns a retryable error for a busy account', function (): void {
    $first = User::factory()->customer()->create();
    $second = User::factory()->customer()->create();
    $service = app(StagedImageUploadService::class);
    $lock = Cache::lock('dealhay:staged-upload-quota:'.$first->getKey().':lock', 10);
    expect($lock->get())->toBeTrue();
    $declaration = [['size' => 100, 'content_type' => 'image/webp']];
    try {
        expect($service->issueBatch(new UploadOwner((string) $second->getKey(), $second->user_code), $declaration, presign: false))->toHaveCount(1);
        expect(fn () => $service->issueBatch(new UploadOwner((string) $first->getKey(), $first->user_code), $declaration, presign: false))
            ->toThrow(ServiceUnavailableHttpException::class);
    } finally {
        $lock->release();
    }
    expect(StagedImageUpload::where('user_id', $first->getKey())->count())->toBe(0);
    expect(StagedImageUpload::where('user_id', $second->getKey())->count())->toBe(1);
});

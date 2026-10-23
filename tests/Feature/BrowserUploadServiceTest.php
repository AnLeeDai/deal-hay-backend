<?php

use App\Models\User;
use App\Services\BrowserUploadService;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

it('uses a browser grant once to normalize the declared image', function (): void {
    $user = User::factory()->customer()->create();
    $file = UploadedFile::fake()->image('avatar.png', 64, 64);
    config()->set('images.browser_url', 'https://api.example.test');
    $service = app(BrowserUploadService::class);
    $grant = $service->grant((string) $user->getKey(), $file->getSize(), 'avatar');

    $content = $service->prepare($grant['token'], $file);

    expect($grant['url'])->toBe('https://api.example.test/api/media/browser/transform');
    expect(getimagesizefromstring($content)['mime'])->toBe('image/webp');
    expect(strlen($content))->toBeLessThanOrEqual(20 * 1024);
    expect(fn () => $service->prepare($grant['token'], $file))->toThrow(HttpException::class);
});

it('rejects source sizes outside the safe range before reserving a grant', function (int $size): void {
    $user = User::factory()->customer()->create();

    expect(fn () => app(BrowserUploadService::class)->grant((string) $user->getKey(), $size, 'product'))
        ->toThrow(ValidationException::class, 'Ảnh tải lên phải có kích thước từ 1 byte đến 30 MB.');
})->with(['empty source' => 0, 'negative source' => -1, 'over 30 MB' => ImageService::MAX_SOURCE_BYTES + 1]);

it('rejects unrecognized image purposes before reserving a grant', function (): void {
    $user = User::factory()->customer()->create();

    expect(fn () => app(BrowserUploadService::class)->grant((string) $user->getKey(), 100, 'unsupported'))
        ->toThrow(ValidationException::class, 'Mục đích tải ảnh không hợp lệ.');
});

it('refuses a browser grant for a locked account', function (): void {
    $user = User::factory()->customer()->create(['is_active' => false]);

    expect(fn () => app(BrowserUploadService::class)->grant((string) $user->getKey(), 100, 'product'))
        ->toThrow(HttpException::class);
});

it('rejects a file whose byte length differs from its browser grant', function (): void {
    $user = User::factory()->customer()->create();
    $file = UploadedFile::fake()->image('photo.jpg', 64, 64);
    $service = app(BrowserUploadService::class);
    $grant = $service->grant((string) $user->getKey(), $file->getSize() + 1, 'product');

    expect(fn () => $service->prepare($grant['token'], $file))
        ->toThrow(ValidationException::class, 'Kích thước ảnh không khớp với phiên tải lên.');
});

it('refuses a browser grant after its two minute lifetime', function (): void {
    $this->freezeTime();
    $user = User::factory()->customer()->create();
    $file = UploadedFile::fake()->image('photo.jpg', 64, 64);
    $service = app(BrowserUploadService::class);
    $grant = $service->grant((string) $user->getKey(), $file->getSize(), 'product');
    $this->travel(121)->seconds();

    expect(fn () => $service->prepare($grant['token'], $file))->toThrow(HttpException::class);
});

it('returns a retryable error when the same browser grant is already locked', function (): void {
    $user = User::factory()->customer()->create();
    $file = UploadedFile::fake()->image('photo.png', 64, 64);
    $service = app(BrowserUploadService::class);
    $grant = $service->grant((string) $user->getKey(), $file->getSize(), 'product');
    $lock = Cache::lock('browser-upload:'.hash('sha256', $grant['token']).':lock', 10);
    expect($lock->get())->toBeTrue();

    try {
        $service->prepare($grant['token'], $file);
        $this->fail('A busy grant should return a retryable error.');
    } catch (ServiceUnavailableHttpException $exception) {
        expect($exception->getStatusCode())->toBe(503);
        expect($exception->getHeaders()['Retry-After'])->toBe(2);
    } finally {
        $lock->release();
    }

    expect(getimagesizefromstring($service->prepare($grant['token'], $file))['mime'])->toBe('image/webp');
});

it('isolates account quota locks and returns a retryable error instead of an unhandled failure', function (): void {
    $first = User::factory()->customer()->create();
    $second = User::factory()->customer()->create();
    $service = app(BrowserUploadService::class);
    $lock = Cache::lock('dealhay:browser-upload-quota:'.$first->getKey().':lock', 10);
    expect($lock->get())->toBeTrue();

    try {
        expect($service->grant((string) $second->getKey(), 100, 'product'))->toHaveKeys(['url', 'token']);
        $service->grant((string) $first->getKey(), 100, 'product');
        $this->fail('A busy account quota should return a retryable error.');
    } catch (ServiceUnavailableHttpException $exception) {
        expect($exception->getStatusCode())->toBe(503);
        expect($exception->getHeaders()['Retry-After'])->toBe(2);
    } finally {
        $lock->release();
    }
});

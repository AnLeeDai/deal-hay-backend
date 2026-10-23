<?php

namespace App\Services;

use App\Enums\ImagePurposeEnum;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class BrowserUploadService
{
    public function __construct(private readonly BrowserImageService $images, private readonly StagedImageUploadQuota $quota) {}

    public function grant(string $userId, int $size, string $purpose): array
    {
        $user = User::query()->findOrFail($userId);
        abort_unless($user->is_active, 403);
        if ($size < 1 || $size > ImageService::MAX_SOURCE_BYTES) {
            throw ValidationException::withMessages(['size' => 'Ảnh tải lên phải có kích thước từ 1 byte đến 30 MB.']);
        }
        if (ImagePurposeEnum::tryFrom($purpose) === null) {
            throw ValidationException::withMessages(['purpose' => 'Mục đích tải ảnh không hợp lệ.']);
        }
        $this->quota->reserve(new UploadOwner($userId, $user->user_code), [$size], scope: 'browser');
        $token = Str::random(64);
        Cache::put('browser-upload:'.hash('sha256', $token), ['size' => $size, 'purpose' => $purpose], now()->addMinutes(2));

        return ['url' => rtrim((string) config('images.browser_url'), '/').'/api/media/browser/transform', 'token' => $token];
    }

    public function prepare(string $token, UploadedFile $file): string
    {
        $key = 'browser-upload:'.hash('sha256', $token);
        try {
            $grant = Cache::lock($key.':lock', 5)->block(2, fn () => Cache::pull($key));
        } catch (LockTimeoutException $exception) {
            throw new ServiceUnavailableHttpException(2, 'Phiên tải ảnh đang được xử lý. Vui lòng thử lại sau ít giây.', $exception);
        }
        abort_unless(is_array($grant), 401);
        if ($file->getSize() !== $grant['size']) {
            throw ValidationException::withMessages(['image' => 'Kích thước ảnh không khớp với phiên tải lên.']);
        }

        return $this->images->prepare($file, $grant['purpose']);
    }
}

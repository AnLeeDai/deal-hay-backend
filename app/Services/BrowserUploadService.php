<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BrowserUploadService
{
    public function __construct(private readonly BrowserImageService $images, private readonly StagedImageUploadQuota $quota) {}

    public function grant(string $userId, int $size, string $purpose): array
    {
        $user = User::query()->findOrFail($userId);
        abort_unless($user->is_active, 403);
        $this->quota->reserve(new UploadOwner($userId, $user->user_code), [$size], scope: 'browser');
        $token = Str::random(64);
        Cache::put('browser-upload:'.hash('sha256', $token), ['size' => $size, 'purpose' => $purpose], now()->addMinutes(2));

        return ['url' => rtrim((string) config('images.browser_url'), '/').'/api/media/browser/transform', 'token' => $token];
    }

    public function prepare(string $token, UploadedFile $file): string
    {
        $key = 'browser-upload:'.hash('sha256', $token);
        $grant = Cache::lock($key.':lock', 5)->block(2, fn () => Cache::pull($key));
        abort_unless(is_array($grant), 401);
        if ($file->getSize() !== $grant['size']) {
            throw ValidationException::withMessages(['image' => 'Kích thước ảnh không khớp với phiên tải lên.']);
        }

        return $this->images->prepare($file, $grant['purpose']);
    }
}

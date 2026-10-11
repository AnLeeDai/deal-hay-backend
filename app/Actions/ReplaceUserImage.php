<?php

namespace App\Actions;

use App\Models\User;
use App\Services\ImageService;
use App\Services\R2ImageCleanupService;
use Illuminate\Http\UploadedFile;
use Throwable;

class ReplaceUserImage
{
    public function __construct(
        private readonly ImageService $images,
        private readonly R2ImageCleanupService $cleanup,
    ) {}

    public function replace(string $userId, string $attribute, UploadedFile $file): void
    {
        $user = User::query()->findOrFail($userId);
        abort_unless($user->is_active && ($attribute !== 'shop_avatar_url' || $user->role === 'shop_owner'), 403);
        $this->handle($user, $attribute, $user->user_code.($attribute === 'shop_avatar_url' ? '/shop-avatar' : '/avatar'), $file);
    }

    public function handle(User $user, string $attribute, string $folder, UploadedFile $file): void
    {
        $previousPath = $user->getRawOriginal($attribute);
        $path = $this->images->upload($file, $folder, 'avatar');

        try {
            $user->setAttribute($attribute, $path)->save();
        } catch (Throwable $exception) {
            $this->images->delete($path);

            throw $exception;
        }

        $this->cleanup->deleteLater([$previousPath]);
    }
}

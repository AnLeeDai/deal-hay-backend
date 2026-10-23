<?php

namespace App\Services;

use App\Actions\ReplaceUserImage;
use App\Enums\ImagePurposeEnum;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaClient
{
    public function __construct(
        private readonly ChatAttachmentService $chatAttachments,
        private readonly ImageService $images,
        private readonly R2ImageCleanupService $cleanup,
        private readonly ReplaceUserImage $replaceUserImage,
        private readonly StagedImageUploadService $stagedUploads,
    ) {}

    public function defaultAvatarPath(): string
    {
        return $this->images->defaultAvatar();
    }

    public function upload(?UploadedFile $file, string $folder, string $errorField = 'image', string $disk = 'r2', ImagePurposeEnum $purpose = ImagePurposeEnum::Product): string
    {
        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                $errorField => 'Vui lòng tải lên một tệp hợp lệ.',
            ]);
        }

        try {
            return $this->images->upload($file, $folder, $errorField, $disk, $purpose);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([$errorField => Arr::flatten($exception->errors())]);
        }
    }

    public function replaceAvatar(User $user, string $attribute, UploadedFile $file): void
    {
        $folder = $user->user_code.($attribute === 'shop_avatar_url' ? '/shop-avatar' : '/avatar');
        $this->replaceUserImage->handle($user, $attribute, $folder, $file);
    }

    public function url(?string $path): ?string
    {
        if (! $path || Str::startsWith($path, ['http://', 'https://'])) {
            return $path ?: null;
        }

        return route('media.asset', ['path' => ltrim($path, '/')]);
    }

    public function assetUrl(string $path): ?string
    {
        return $this->images->url($path);
    }

    public function delete(?string $path): bool
    {
        return $path ? $this->images->delete($path) : false;
    }

    /** @param iterable<array-key, string|null> $paths */
    public function deleteLater(iterable $paths): void
    {
        $this->cleanup->deleteLater($paths);
    }

    /** @param list<array{size: int, content_type: string}> $uploads */
    public function issueBatch(User $user, array $uploads, bool $presign = true): array
    {
        return $this->stagedUploads->issueBatch(new UploadOwner((string) $user->getKey(), (string) $user->user_code), $uploads, $presign);
    }

    public function prepareImage(User $user, string $token, UploadedFile $file): void
    {
        $this->stagedUploads->prepareImage(new UploadOwner((string) $user->getKey(), (string) $user->user_code), $token, $file);
    }

    public function consume(User $user, string $token, string $folder): string
    {
        return $this->stagedUploads->consume(new UploadOwner((string) $user->getKey(), (string) $user->user_code), $token, $folder);
    }

    public function discard(User $user, iterable $tokens): void
    {
        $tokens = collect($tokens)->filter(fn (mixed $token): bool => is_string($token) && $token !== '')->values()->all();

        if ($tokens === []) {
            return;
        }

        $this->stagedUploads->discard(new UploadOwner((string) $user->getKey(), (string) $user->user_code), $tokens);
    }

    public function deleteAttachmentLater(string $path, string $disk): void
    {
        if ($disk === ImageService::DISK) {
            $this->cleanup->deleteLater([$path]);

            return;
        }

        $this->chatAttachments->deleteStoredAttachment($path, $disk);
    }
}

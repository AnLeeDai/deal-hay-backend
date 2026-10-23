<?php

namespace App\Services;

use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class StagedImageUploadQuota
{
    public const int MAX_UPLOADS_PER_WINDOW = 200;

    public const int MAX_BYTES_PER_WINDOW = 536_870_912;

    public const int WINDOW_SECONDS = 3_600;

    public function __construct(private readonly CacheManager $cache) {}

    /** @param list<int> $sizes */
    public function reserve(UploadOwner $user, array $sizes, string $scope = 'staged'): void
    {
        $requestedUploads = count($sizes);
        $requestedBytes = array_sum($sizes);

        if ($requestedUploads < 1 || $requestedBytes < 1) {
            throw new RuntimeException('The staged upload quota requires at least one byte.');
        }

        /** @var Repository $repository */
        $repository = $this->cache->store();
        $store = $repository->getStore();

        if (! $store instanceof LockProvider) {
            throw new RuntimeException('The configured cache store does not support distributed locks.');
        }

        try {
            $store->lock($this->lockKey($user, $scope), 10)->block(3, function () use (
                $store,
                $user,
                $scope,
                $requestedUploads,
                $requestedBytes,
            ): void {
                $quotaKey = $this->quotaKey($user, $scope);
                $quota = $store->get($quotaKey);
                $now = now()->getTimestamp();

                if (! is_array($quota) || (int) ($quota['expires_at'] ?? 0) <= $now) {
                    $quota = [
                        'uploads' => 0,
                        'bytes' => 0,
                        'expires_at' => $now + self::WINDOW_SECONDS,
                    ];
                }

                $uploads = (int) ($quota['uploads'] ?? 0) + $requestedUploads;
                $bytes = (int) ($quota['bytes'] ?? 0) + $requestedBytes;

                if ($uploads > self::MAX_UPLOADS_PER_WINDOW
                    || $bytes > self::MAX_BYTES_PER_WINDOW) {
                    throw ValidationException::withMessages([
                        'uploads' => sprintf(
                            'Bạn đã đạt giới hạn tải ảnh trong 60 phút. Vui lòng thử lại sau %d phút.',
                            max(1, (int) ceil(((int) $quota['expires_at'] - $now) / 60)),
                        ),
                    ]);
                }

                $store->put($quotaKey, [
                    'uploads' => $uploads,
                    'bytes' => $bytes,
                    'expires_at' => (int) $quota['expires_at'],
                ], max(1, (int) $quota['expires_at'] - $now));
            });
        } catch (LockTimeoutException $exception) {
            throw new ServiceUnavailableHttpException(2, 'Phiên tải ảnh đang bận. Vui lòng thử lại sau ít giây.', $exception);
        }
    }

    /** @param list<int> $sizes */
    public function release(UploadOwner $user, array $sizes, string $scope = 'staged'): void
    {
        $releasedUploads = count($sizes);
        $releasedBytes = array_sum($sizes);

        if ($releasedUploads < 1 || $releasedBytes < 1) {
            return;
        }

        /** @var Repository $repository */
        $repository = $this->cache->store();
        $store = $repository->getStore();

        if (! $store instanceof LockProvider) {
            throw new RuntimeException('The configured cache store does not support distributed locks.');
        }

        try {
            $store->lock($this->lockKey($user, $scope), 10)->block(3, function () use (
                $releasedBytes,
                $releasedUploads,
                $store,
                $user,
                $scope,
            ): void {
                $quotaKey = $this->quotaKey($user, $scope);
                $quota = $store->get($quotaKey);
                $now = now()->getTimestamp();

                if (! is_array($quota) || (int) ($quota['expires_at'] ?? 0) <= $now) {
                    return;
                }

                $uploads = max(0, (int) ($quota['uploads'] ?? 0) - $releasedUploads);
                $bytes = max(0, (int) ($quota['bytes'] ?? 0) - $releasedBytes);

                if ($uploads === 0 && $bytes === 0) {
                    $store->forget($quotaKey);

                    return;
                }

                $store->put($quotaKey, [
                    'uploads' => $uploads,
                    'bytes' => $bytes,
                    'expires_at' => (int) $quota['expires_at'],
                ], max(1, (int) $quota['expires_at'] - $now));
            });
        } catch (LockTimeoutException $exception) {
            throw new ServiceUnavailableHttpException(2, 'Phiên tải ảnh đang bận. Vui lòng thử lại sau ít giây.', $exception);
        }
    }

    private function quotaKey(UploadOwner $user, string $scope): string
    {
        return 'dealhay:'.$scope.'-upload-quota:'.(string) $user->getKey();
    }

    private function lockKey(UploadOwner $user, string $scope): string
    {
        return $this->quotaKey($user, $scope).':lock';
    }
}

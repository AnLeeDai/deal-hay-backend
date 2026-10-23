<?php

namespace App\Services;

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class SpeedtestSession
{
    public const int TTL_SECONDS = 120;

    public const int MAX_DOWNLOAD_BYTES = 4 * 1024 * 1024;

    public const int MAX_UPLOAD_BYTES = 2 * 1024 * 1024;

    public const int MAX_SESSION_BYTES = 64 * 1024 * 1024;

    public const int MAX_REQUESTS = 64;

    public function issue(string $userId): array
    {
        $token = Str::random(64);
        $expires = now()->addSeconds(self::TTL_SECONDS);
        $this->cache()->put($this->key($token), [
            'scope' => 'speedtest', 'user_id' => $userId, 'expires_at' => $expires->getTimestamp(),
            'bytes' => 0, 'requests' => 0,
        ], self::TTL_SECONDS);

        return [
            'token' => $token, 'expires_at' => $expires->toIso8601String(),
            'endpoint_path' => '/diagnostics/speedtest',
            'limits' => [
                'max_download_bytes' => self::MAX_DOWNLOAD_BYTES,
                'max_upload_bytes' => self::MAX_UPLOAD_BYTES,
                'max_session_bytes' => self::MAX_SESSION_BYTES,
                'max_requests' => self::MAX_REQUESTS,
            ],
        ];
    }

    /** Reserve transfer bytes before reading a body or sending any probe data. */
    public function consume(?string $token, int $bytes = 0): void
    {
        if (! is_string($token) || strlen($token) !== 64 || ! ctype_alnum($token)) {
            $this->unauthorized();
        }
        $key = $this->key($token);
        $cache = $this->cache();
        try {
            $cache->lock($key.':lock', 5)->block(1, function () use ($cache, $key, $bytes): void {
                $grant = $cache->get($key);
                $remaining = is_array($grant) ? (int) ($grant['expires_at'] ?? 0) - now()->getTimestamp() : 0;
                if (! is_array($grant) || ($grant['scope'] ?? null) !== 'speedtest' || $remaining <= 0) {
                    $this->unauthorized();
                }
                $requests = (int) ($grant['requests'] ?? 0) + 1;
                $transferred = (int) ($grant['bytes'] ?? 0) + max(0, $bytes);
                if ($requests > self::MAX_REQUESTS || $transferred > self::MAX_SESSION_BYTES) {
                    throw new HttpException(429, 'Phiên đo đã đạt giới hạn. Vui lòng bắt đầu lượt đo mới.', headers: ['Retry-After' => (string) $remaining]);
                }
                $cache->put($key, [...$grant, 'bytes' => $transferred, 'requests' => $requests], $remaining);
            });
        } catch (LockTimeoutException $exception) {
            throw new ServiceUnavailableHttpException(1, 'Phiên đo đang bận. Vui lòng thử lại.', $exception);
        }
    }

    private function key(string $token): string
    {
        return 'speedtest:session:'.hash('sha256', $token);
    }

    private function cache(): Repository
    {
        return Cache::store(config('cache.limiter') ?: config('cache.default'));
    }

    private function unauthorized(): never
    {
        throw new HttpException(401, 'Phiên đo không hợp lệ hoặc đã hết hạn. Vui lòng bắt đầu lượt đo mới.');
    }
}

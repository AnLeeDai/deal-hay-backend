<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UserPresenceService
{
    private const int LAST_SEEN_TTL_DAYS = 30;

    /** @return array{expires_in: int, server_time: string} */
    public function heartbeat(User $user, ?ChatConversation $conversation = null): array
    {
        $now = now();
        $store = $this->store();
        $ttl = $this->ttlSeconds();

        $store->put($this->onlineKey((string) $user->getKey()), $now->timestamp, $ttl);
        $store->put(
            $this->lastSeenKey((string) $user->getKey()),
            $now->timestamp,
            now()->addDays(self::LAST_SEEN_TTL_DAYS),
        );

        if ($conversation !== null) {
            $store->put(
                $this->viewingKey((string) $user->getKey()),
                (string) $conversation->getKey(),
                $ttl,
            );
        }

        return [
            'expires_in' => $ttl,
            'server_time' => $now->toISOString(),
        ];
    }

    public function clearViewing(User $user): void
    {
        $this->store()->forget($this->viewingKey((string) $user->getKey()));
    }

    public function isOnline(string $userId): bool
    {
        return $this->store()->has($this->onlineKey($userId));
    }

    public function lastSeenAt(string $userId): ?int
    {
        $lastSeenAt = $this->store()->get($this->lastSeenKey($userId));

        return is_numeric($lastSeenAt) ? (int) $lastSeenAt : null;
    }

    /** @param iterable<array-key, string> $userIds @return Collection<string, true> */
    public function onlineUserIds(iterable $userIds): Collection
    {
        $userIds = collect($userIds)->map(fn (mixed $userId): string => (string) $userId)->unique()->values();
        $keysByUser = $userIds->mapWithKeys(fn (string $userId): array => [
            $userId => $this->onlineKey($userId),
        ]);
        $values = $this->store()->many($keysByUser->values()->all());

        return $keysByUser
            ->filter(fn (string $key): bool => array_key_exists($key, $values) && $values[$key] !== null)
            ->map(fn (): true => true);
    }

    public function ttlSeconds(): int
    {
        return max(30, (int) config('realtime.presence_ttl_seconds', 90));
    }

    private function store(): Repository
    {
        return Cache::store((string) config('realtime.presence_store', config('cache.default')));
    }

    private function onlineKey(string $userId): string
    {
        return 'presence:online:'.$userId;
    }

    private function lastSeenKey(string $userId): string
    {
        return 'presence:last-seen:'.$userId;
    }

    private function viewingKey(string $userId): string
    {
        return 'presence:viewing:'.$userId;
    }
}

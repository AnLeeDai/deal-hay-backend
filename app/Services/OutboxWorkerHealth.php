<?php

namespace App\Services;

use Illuminate\Cache\CacheManager;
use RuntimeException;

class OutboxWorkerHealth
{
    private const int HEARTBEAT_TTL_SECONDS = 30;

    private const int MAX_HEARTBEAT_AGE_SECONDS = 20;

    public function __construct(private readonly CacheManager $cache) {}

    public function record(): void
    {
        $this->cache->store()->put(
            $this->key(),
            now()->getTimestamp(),
            self::HEARTBEAT_TTL_SECONDS,
        );
    }

    public function assertHealthy(): void
    {
        $lastHeartbeat = (int) $this->cache->store()->get($this->key(), 0);

        if ($lastHeartbeat < now()->subSeconds(self::MAX_HEARTBEAT_AGE_SECONDS)->getTimestamp()) {
            throw new RuntimeException('The realtime outbox worker is unavailable.');
        }
    }

    private function key(): string
    {
        return 'dealhay:health:outbox-worker';
    }
}

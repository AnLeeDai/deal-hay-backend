<?php

namespace App\Services;

use Illuminate\Cache\CacheManager;
use RuntimeException;

class OutboxWorkerHealth
{
    private const int HEARTBEAT_TTL_SECONDS = 180;

    private const int MAX_HEARTBEAT_AGE_SECONDS = 120;

    private const int HEARTBEAT_INTERVAL_SECONDS = 30;

    private ?int $lastRecordedAt = null;

    public function __construct(private readonly CacheManager $cache) {}

    public function record(): void
    {
        $timestamp = now()->getTimestamp();

        if ($this->lastRecordedAt !== null
            && $timestamp >= $this->lastRecordedAt
            && $timestamp - $this->lastRecordedAt < self::HEARTBEAT_INTERVAL_SECONDS) {
            return;
        }

        $recorded = $this->cache->store()->put(
            $this->key(),
            $timestamp,
            self::HEARTBEAT_TTL_SECONDS,
        );

        if ($recorded) {
            $this->lastRecordedAt = $timestamp;
        }
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

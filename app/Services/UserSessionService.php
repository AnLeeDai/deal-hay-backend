<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Cache\Repository;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserSessionService
{
    public const string VERSION_KEY = 'auth_session_version';

    public function __construct(private readonly SessionManager $sessions) {}

    public function isCurrent(User $user, mixed $sessionVersion): bool
    {
        $currentVersion = max(1, (int) $user->session_version);

        // Sessions created before this migration are valid only while the account is
        // still on version one. The first revoke invalidates every legacy session.
        return $sessionVersion === null
            ? $currentVersion === 1
            : is_numeric($sessionVersion) && (int) $sessionVersion === $currentVersion;
    }

    public function markCurrent(User $user, string $sessionId): int
    {
        $version = max(1, (int) $user->session_version);
        $this->register($user, $sessionId);

        return $version;
    }

    public function register(User $user, string $sessionId): void
    {
        if ($sessionId === '') {
            return;
        }

        $store = $this->registryStore();
        $key = $this->registryKey((string) $user->getKey());
        $expiresAt = now()->addMinutes((int) config('session.lifetime', 120) + 5);

        try {
            $store->lock($key.':lock', 5)->block(2, function () use ($expiresAt, $key, $sessionId, $store): void {
                $sessionIds = collect($store->get($key, []))
                    ->filter(fn (mixed $expires): bool => is_numeric($expires) && (int) $expires >= now()->timestamp)
                    ->all();
                $sessionIds[$sessionId] = $expiresAt->timestamp;
                $store->put($key, $sessionIds, $expiresAt);
            });
        } catch (Throwable $exception) {
            // The version check remains authoritative. Registry contention must not
            // turn an otherwise valid application request into a 500 response.
            report($exception);
        }
    }

    public function remove(User $user, string $sessionId): void
    {
        try {
            $store = $this->registryStore();
            $key = $this->registryKey((string) $user->getKey());
            $sessionIds = (array) $store->get($key, []);
            unset($sessionIds[$sessionId]);
            $store->put($key, $sessionIds, now()->addMinutes((int) config('session.lifetime', 120) + 5));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function revoke(User $user, ?string $exceptSessionId = null): void
    {
        User::query()->whereKey($user->getKey())->increment('session_version');
        $user->refresh();

        $driver = (string) config('session.driver');

        try {
            if ($driver === 'database') {
                $query = DB::connection(config('session.connection'))
                    ->table((string) config('session.table', 'sessions'))
                    ->where('user_id', $user->getKey());

                if ($exceptSessionId !== null) {
                    $query->where('id', '!=', $exceptSessionId);
                }

                $query->delete();
            } elseif (! in_array($driver, ['array', 'cookie'], true)) {
                $this->destroyRegisteredSessions($user, $exceptSessionId);
            }
        } catch (Throwable $exception) {
            // session_version is authoritative even if physical cleanup fails.
            report($exception);
        }

        try {
            $this->registryStore()->forget($this->registryKey((string) $user->getKey()));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function destroyRegisteredSessions(User $user, ?string $exceptSessionId): void
    {
        try {
            $sessionIds = array_keys((array) $this->registryStore()->get(
                $this->registryKey((string) $user->getKey()),
                [],
            ));
            $handler = $this->sessions->driver()->getHandler();
        } catch (Throwable $exception) {
            report($exception);

            return;
        }

        foreach ($sessionIds as $sessionId) {
            if (! is_string($sessionId) || $sessionId === $exceptSessionId) {
                continue;
            }

            try {
                $handler->destroy($sessionId);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    private function registryStore(): Repository
    {
        return Cache::store((string) config('realtime.session_registry_store', config('cache.default')));
    }

    private function registryKey(string $userId): string
    {
        return 'auth:sessions:'.$userId;
    }
}

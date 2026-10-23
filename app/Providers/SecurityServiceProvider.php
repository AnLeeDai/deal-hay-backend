<?php

namespace App\Providers;

use App\Models\User;
use App\Services\UserSessionService;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class SecurityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            $request = request();

            if ($request instanceof Request && $request->hasSession() && $event->user instanceof User) {
                $request->session()->put(
                    UserSessionService::VERSION_KEY,
                    app(UserSessionService::class)->markCurrent($event->user, $request->session()->getId()),
                );
            }
        });

        RateLimiter::for('broadcast-auth', fn (Request $request): Limit => Limit::perMinute(120)
            ->by((string) ($request->user()?->getKey() ?: $request->ip())));
        RateLimiter::for('diagnostics', fn (Request $request): Limit => Limit::perMinute(6)
            ->by((string) ($request->user()?->getKey() ?: $request->ip())));
        RateLimiter::for('speedtest-session', fn (Request $request): Limit => Limit::perMinute(3)
            ->by((string) ($request->user()?->getKey() ?: $request->ip())));
        RateLimiter::for('presence-heartbeat', fn (Request $request): Limit => Limit::perMinute(12)
            ->by((string) ($request->user()?->getKey() ?: $request->ip())));
        RateLimiter::for('start-chat', fn (Request $request): Limit => Limit::perMinute(10)
            ->by((string) ($request->user()?->getKey() ?: $request->ip())));
        RateLimiter::for('email-change', fn (Request $request): Limit => Limit::perMinute(10)
            ->by((string) ($request->user()?->getKey() ?: $request->ip())));
        RateLimiter::for('image-upload-presign', fn (Request $request): Limit => Limit::perMinute(6)
            ->by((string) ($request->user()?->getKey() ?: $request->ip())));
        RateLimiter::for('product-write', fn (Request $request): Limit => Limit::perMinute(10)
            ->by((string) ($request->user()?->getKey() ?: $request->ip())));
    }
}

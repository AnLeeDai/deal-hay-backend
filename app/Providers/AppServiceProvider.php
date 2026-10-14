<?php

namespace App\Providers;

use App\Models\User;
use App\Services\OutboxWorkerHealth;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ImageManager::class, fn () => new ImageManager(new Driver, decodeAnimation: false));
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureOutboxWorkerHeartbeat();
        $this->configureProductionHealthChecks();
    }

    private function configureOutboxWorkerHeartbeat(): void
    {
        Event::listen(Looping::class, function (Looping $event): void {
            $realtimeQueue = (string) config('queue.connections.database_outbox.queue', 'realtime');

            if ($event->connectionName !== 'database_outbox'
                || ! in_array($realtimeQueue, explode(',', $event->queue), true)) {
                return;
            }

            try {
                app(OutboxWorkerHealth::class)->record();
            } catch (Throwable) {
            }
        });
    }

    private function configureProductionHealthChecks(): void
    {
        Event::listen(DiagnosingHealth::class, function (): void {
            if (! app()->isProduction()) {
                return;
            }

            DB::connection()->getServerVersion();
            Cache::store((string) config('cache.default'))->get('__deal_hay_health__');
        });
    }

    protected function configureDefaults(): void
    {
        ResetPasswordNotification::createUrlUsing(function (User $user, string $token): string {
            $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

            return $frontendUrl.'/reset-password/'.$token.'?email='.urlencode((string) $user->email);
        });

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction() || config('database.default') !== 'sqlite',
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)->mixedCase()->letters()->numbers()->symbols()->uncompromised()
            : Password::min(8)->mixedCase()->letters()->numbers()->symbols());
    }
}

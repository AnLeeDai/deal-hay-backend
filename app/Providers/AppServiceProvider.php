<?php

namespace App\Providers;

use App\Models\User;
use App\Services\NotificationService;
use App\Services\OutboxWorkerHealth;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSent;
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
        $this->app->singleton(OutboxWorkerHealth::class);
    }

    public function boot(): void
    {
        $this->configureDefaults();
        Event::listen(NotificationSent::class, function (NotificationSent $event): void {
            if ($event->channel === 'database' && $event->response instanceof DatabaseNotification) {
                app(NotificationService::class)->broadcast($event->response);
            }
        });
        $this->configureOutboxWorkerHeartbeat();
        $this->configureProductionHealthChecks();
        $this->configureDiagnosticsQueryTiming();
    }

    private function configureDiagnosticsQueryTiming(): void
    {
        Event::listen(QueryExecuted::class, function (QueryExecuted $event): void {
            if (! $this->app->bound('request')) {
                return;
            }

            $request = $this->app['request'];
            $metrics = $request->attributes->get('diagnostics.sql');
            if (! is_array($metrics)) {
                return;
            }

            $metrics['queries']++;
            $metrics['milliseconds'] += $event->time;
            $request->attributes->set('diagnostics.sql', $metrics);
        });
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

            return $frontendUrl.'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => (string) $user->email,
            ]);
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

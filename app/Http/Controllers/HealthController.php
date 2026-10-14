<?php

namespace App\Http\Controllers;

use App\Services\OutboxWorkerHealth;
use App\Services\ReverbProtocolHealth;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function __construct(
        private readonly OutboxWorkerHealth $outboxWorkerHealth,
        private readonly ReverbProtocolHealth $reverbProtocolHealth,
    ) {}

    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check(function (): void {
                DB::connection()->getServerVersion();
            }),
            'cache' => $this->check(function (): void {
                Cache::store((string) config('cache.default'))->get('__deal_hay_health__');
            }),
            'outbox_worker' => $this->check(function (): void {
                $this->outboxWorkerHealth->assertHealthy();
            }),
            'reverb' => config('broadcasting.default') === 'reverb'
                ? $this->check(function (): void {
                    $this->reverbProtocolHealth->assertHealthy();
                })
                : 'skipped',
        ];

        $healthy = ! in_array('failed', $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503, ['Cache-Control' => 'no-store']);
    }

    private function check(Closure $callback): string
    {
        try {
            $callback();

            return 'ok';
        } catch (Throwable) {
            return 'failed';
        }
    }
}

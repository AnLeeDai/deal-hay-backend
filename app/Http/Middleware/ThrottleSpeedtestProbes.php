<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class ThrottleSpeedtestProbes
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $limiter = new RateLimiter(Cache::store((string) config('speedtest.cache_store')));
        $key = 'speedtest:probe-ip:'.hash('sha256', (string) $request->ip());
        $maximum = (int) config('speedtest.probes_per_ip_per_minute');

        if ($limiter->tooManyAttempts($key, $maximum)) {
            throw new TooManyRequestsHttpException(max(1, $limiter->availableIn($key)), 'Có quá nhiều yêu cầu đo tốc độ. Vui lòng thử lại sau.');
        }

        // Grants separately reserve bytes and all 64 lifetime requests under
        // an atomic lock; this counter also protects against invalid grants.
        $limiter->hit($key, 60);

        return $next($request);
    }
}

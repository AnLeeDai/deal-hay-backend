<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddRequestContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedRequestId = trim((string) $request->header('X-Request-ID'));
        $requestId = Str::isUuid($providedRequestId)
            ? Str::lower($providedRequestId)
            : (string) Str::uuid();

        Context::add('request_id', $requestId);
        $request->headers->set('X-Request-ID', $requestId);

        $isDiagnostics = $request->is('api/diagnostics') && $request->isMethod('GET');
        $isSpeedtestProbe = ($request->isMethod('GET') && $request->is('api/diagnostics/speedtest/ping', 'api/diagnostics/speedtest/download'))
            || ($request->isMethod('POST') && $request->is('api/diagnostics/speedtest/upload'));
        $startedAt = $isDiagnostics || $isSpeedtestProbe ? hrtime(true) : null;
        if ($startedAt !== null) {
            $request->attributes->set('diagnostics.sql', ['queries' => 0, 'milliseconds' => 0.0]);
        }

        try {
            $response = $next($request);
            $response->headers->set('X-Request-ID', $requestId);

            if ($startedAt !== null && $response->isSuccessful()) {
                $sql = $request->attributes->get('diagnostics.sql');
                $duration = round((hrtime(true) - $startedAt) / 1_000_000, 2);
                if ($isDiagnostics && $response instanceof JsonResponse) {
                    $data = $response->getData(true);
                    $data['request'] = [
                        'duration_ms' => $duration,
                        'database_queries' => $sql['queries'],
                        'database_ms' => round($sql['milliseconds'], 2),
                        'probes_cached' => (bool) $request->attributes->get('diagnostics.probes_cached', false),
                        'limiter_store' => (string) (config('cache.limiter') ?: config('cache.default')),
                    ];
                    $response->setData($data);
                }
                $timing = sprintf('app;dur=%.2F, db;dur=%.2F', $duration, $sql['milliseconds']);
                $sessionTiming = $request->attributes->get('speedtest.session_timing');
                if ($isSpeedtestProbe && is_array($sessionTiming)) {
                    $timing .= sprintf(', lock;dur=%.2F, cache;dur=%.2F', $sessionTiming['lock_ms'], $sessionTiming['cache_ms']);
                }
                $response->headers->set('Server-Timing', $timing);
            }

            return $response;
        } finally {
            $request->attributes->remove('diagnostics.sql');
            $request->attributes->remove('speedtest.session_timing');
        }
    }
}

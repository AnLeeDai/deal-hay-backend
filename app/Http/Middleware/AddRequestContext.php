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

        $startedAt = $request->is('api/diagnostics') && $request->isMethod('GET') ? hrtime(true) : null;
        if ($startedAt !== null) {
            $request->attributes->set('diagnostics.sql', ['queries' => 0, 'milliseconds' => 0.0]);
        }

        try {
            $response = $next($request);
            $response->headers->set('X-Request-ID', $requestId);

            if ($startedAt !== null && $response instanceof JsonResponse && $response->isSuccessful()) {
                $sql = $request->attributes->get('diagnostics.sql');
                $duration = round((hrtime(true) - $startedAt) / 1_000_000, 2);
                $data = $response->getData(true);
                $data['request'] = [
                    'duration_ms' => $duration,
                    'database_queries' => $sql['queries'],
                    'database_ms' => round($sql['milliseconds'], 2),
                    'probes_cached' => (bool) $request->attributes->get('diagnostics.probes_cached', false),
                    'limiter_store' => (string) (config('cache.limiter') ?: config('cache.default')),
                ];
                $response->setData($data);
                $response->headers->set('Server-Timing', sprintf('app;dur=%.2F, db;dur=%.2F', $duration, $sql['milliseconds']));
            }

            return $response;
        } finally {
            $request->attributes->remove('diagnostics.sql');
        }
    }
}

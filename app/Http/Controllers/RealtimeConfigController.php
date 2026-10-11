<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class RealtimeConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        /** @var array<string, mixed> $application */
        $application = config('reverb.apps.apps.0', []);
        /** @var array<string, mixed> $options */
        $options = $application['options'] ?? [];
        /** @var array<string, mixed> $public */
        $public = config('reverb.public', []);

        $key = $application['key'] ?? null;
        $scheme = $public['scheme'] ?: ($options['scheme'] ?? 'https');
        $host = $public['host'] ?: ($options['host'] ?? parse_url((string) config('app.url'), PHP_URL_HOST));
        $port = $public['port'] ?: ($options['port'] ?? ($scheme === 'https' ? 443 : 80));

        abort_unless(filled($key) && filled($host), 503, 'Realtime chưa được cấu hình.');

        $presenceTtl = max(30, (int) config('realtime.presence_ttl_seconds', 90));

        return response()->json([
            'broadcaster' => 'reverb',
            'key' => (string) $key,
            'host' => (string) $host,
            'port' => (int) $port,
            'scheme' => $scheme === 'https' ? 'https' : 'http',
            'heartbeat_interval_seconds' => max(15, min(60, intdiv($presenceTtl, 2))),
        ]);
    }
}

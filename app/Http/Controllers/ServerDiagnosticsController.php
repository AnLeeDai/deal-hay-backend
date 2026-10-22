<?php

namespace App\Http\Controllers;

use App\Services\ServerDiagnostics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ServerDiagnosticsController extends Controller
{
    public function __invoke(Request $request, ServerDiagnostics $diagnostics): JsonResponse
    {
        $cache = Cache::store((string) config('cache.reference_store', 'file'));
        $result = $cache->get('diagnostics:servers:v1');
        $request->attributes->set('diagnostics.probes_cached', is_array($result));

        if (! is_array($result)) {
            $result = $diagnostics->measure();
            $cache->put('diagnostics:servers:v1', $result, 15);
        }

        return response()->json($result, headers: ['Cache-Control' => 'no-store']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\ServerDiagnostics;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ServerDiagnosticsController extends Controller
{
    public function __invoke(ServerDiagnostics $diagnostics): JsonResponse
    {
        $result = Cache::store((string) config('cache.reference_store', 'file'))
            ->remember('diagnostics:servers:v1', 15, fn (): array => $diagnostics->measure());

        return response()->json($result, headers: ['Cache-Control' => 'no-store']);
    }
}

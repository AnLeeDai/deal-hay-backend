<?php

return [
    'presence_store' => env('PRESENCE_CACHE_STORE', env('CACHE_STORE', 'database')),
    'presence_ttl_seconds' => (int) env('PRESENCE_TTL_SECONDS', 90),
    'session_registry_store' => env('SESSION_REGISTRY_CACHE_STORE', env('CACHE_STORE', 'database')),
];

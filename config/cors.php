<?php

return [
    'paths' => ['api/*', 'broadcasting/auth', 'up'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:3000')))))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['X-Request-ID', 'Idempotency-Replayed'],
    'max_age' => (int) env('CORS_MAX_AGE', 600),
    'supports_credentials' => false,
];

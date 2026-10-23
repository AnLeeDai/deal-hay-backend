<?php

return [
    // The current deployment has one API instance. Probes must not wait for
    // remote SQL cache round trips. Set a shared store (for example redis) on
    // every API instance before scaling; grants and the IP limiter share it.
    'cache_store' => env('SPEEDTEST_CACHE_STORE', env('CACHE_LIMITER_STORE') ?: 'file'),

    // A frontend proxy can share one egress IP across several signed-in users.
    // Only the trusted request IP is used; account and grant limits still apply.
    'probes_per_ip_per_minute' => max(1, (int) env('SPEEDTEST_PROBES_PER_IP_PER_MINUTE', 240)),
];

<?php

return [
    'provider' => (string) env('REWARDED_TASK_PROVIDER', 'adsterra'),
    'reward_amount' => (int) env('REWARDED_TASK_COUPON_AMOUNT', 1000),
    'session_days' => 90,
    'coupon_days' => 30,
    'adgem' => [
        'enabled' => (bool) env('ADGEM_ENABLED', false),
        'reward_mapping_confirmed' => (bool) env('ADGEM_REWARD_MAPPING_CONFIRMED', false),
        'app_id' => (string) env('ADGEM_APP_ID', ''),
        'postback_key' => (string) env('ADGEM_POSTBACK_KEY', ''),
        'postback_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('ADGEM_POSTBACK_IPS', ''))))),
    ],
];

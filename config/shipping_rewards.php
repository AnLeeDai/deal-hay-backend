<?php

return [
    'enabled' => (bool) env('SHIPPING_REWARDS_ENABLED', false),
    'google_ad_unit_path' => env('GOOGLE_AD_MANAGER_REWARDED_AD_UNIT_PATH', ''),
    'session_minutes' => (int) env('SHIPPING_REWARDS_SESSION_MINUTES', 15),
    'coupon_days' => (int) env('SHIPPING_REWARDS_COUPON_DAYS', 7),
];

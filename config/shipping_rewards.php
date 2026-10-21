<?php

return [
    'enabled' => (bool) env('SHIPPING_REWARDS_ENABLED', false),
    'session_minutes' => (int) env('SHIPPING_REWARDS_SESSION_MINUTES', 15),
    'coupon_days' => (int) env('SHIPPING_REWARDS_COUPON_DAYS', 7),
];

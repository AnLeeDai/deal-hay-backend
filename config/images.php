<?php

return [
    'browser_url' => env('IMAGE_BROWSER_URL', env('APP_URL')),
    'disks' => ['r2', 'r2_private'],
];

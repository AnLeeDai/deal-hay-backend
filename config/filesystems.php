<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),
    'chat_media_disk' => env('CHAT_MEDIA_DISK', 'r2_private'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        'r2' => [
            'driver' => env('R2_DRIVER', 'local'),
            'root' => env('R2_DRIVER', 'local') === 'local' ? storage_path('app/r2') : '',
            'url' => env('R2_URL', rtrim(env('APP_URL', 'http://localhost'), '/').'/storage/r2'),
            'visibility' => 'public',
            'key' => env('R2_ACCESS_KEY_ID'),
            'secret' => env('R2_SECRET_ACCESS_KEY'),
            'region' => env('R2_REGION', 'auto'),
            'bucket' => env('R2_BUCKET'),
            'endpoint' => env('R2_ENDPOINT'),
            'public_endpoint' => env('R2_PUBLIC_ENDPOINT'),
            'retries' => 0,
            'http' => [
                'connect_timeout' => 3,
                'timeout' => 10,
            ],
            'use_path_style_endpoint' => true,
            'throw' => true,
            'report' => true,
        ],

        'r2_private' => [
            'driver' => env('R2_PRIVATE_DRIVER', env('R2_DRIVER', 'local')),
            'root' => env('R2_PRIVATE_DRIVER', env('R2_DRIVER', 'local')) === 'local' ? storage_path('app/r2-private') : '',
            'key' => env('R2_ACCESS_KEY_ID'),
            'secret' => env('R2_SECRET_ACCESS_KEY'),
            'region' => env('R2_REGION', 'auto'),
            'bucket' => env('R2_BUCKET'),
            'endpoint' => env('R2_ENDPOINT'),
            'retries' => 0,
            'http' => [
                'connect_timeout' => 3,
                'timeout' => 10,
            ],
            'use_path_style_endpoint' => true,
            'visibility' => 'private',
            'throw' => true,
            'report' => true,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
        public_path('storage/r2') => storage_path('app/r2'),
    ],
];

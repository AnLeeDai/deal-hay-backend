<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'message' => 'Deal Hay backend is API-only. Use the /api endpoints.',
]));

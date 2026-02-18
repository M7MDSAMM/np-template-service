<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Template Service — API Routes (v1)
|--------------------------------------------------------------------------
|
| Prefix: /api/v1
| All routes here are stateless and expect JSON.
|
*/

Route::get('/health', fn () => ApiResponse::success([
    'service'   => 'template-service',
    'status'    => 'ok',
    'timestamp' => now()->toIso8601String(),
    'version'   => env('APP_VERSION') ?: trim((string) shell_exec('git rev-parse --short HEAD')) ?: 'unknown',
]));

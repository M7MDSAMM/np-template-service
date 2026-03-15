<?php

use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TemplateRenderController;
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
    'service'     => 'template-service',
    'status'      => 'healthy',
    'timestamp'   => now()->toIso8601String(),
    'version'     => config('app.version', '1.0.0'),
    'environment' => app()->environment(),
]));

Route::prefix('templates')
    ->middleware('jwt.admin')
    ->group(function () {
        Route::middleware('admin.super')->group(function () {
            Route::post('/', [TemplateController::class, 'store']);
            Route::get('/', [TemplateController::class, 'index']);
            Route::get('/{template}', [TemplateController::class, 'show']);
            Route::put('/{template}', [TemplateController::class, 'update']);
            Route::delete('/{template}', [TemplateController::class, 'destroy']);
        });

        Route::post('/{key}/render', TemplateRenderController::class);
    });

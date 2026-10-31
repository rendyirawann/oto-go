<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Stateless JSON endpoints for the OtoGo mobile app and partners.
| Auth: Laravel Sanctum bearer tokens. Docs: /api/documentation (Swagger).
| Every route here is rate limited by the "api" limiter and passes through
| SanitizeInput; errors are always rendered as JSON.
|
*/

Route::middleware('throttle:api')->group(function () {

    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'version' => 'v1',
        'time' => now()->toIso8601String(),
    ]))->name('api.health');

    Route::prefix('v1')->name('api.v1.')->group(function () {
        require __DIR__ . '/api/v1.php';
        require __DIR__ . '/api/blog.php';
        require __DIR__ . '/api/ai.php';
    });
});

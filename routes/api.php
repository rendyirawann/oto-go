<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Stateless JSON endpoints. Everything here is rate limited by the "api"
| limiter defined in AppServiceProvider and passes through SanitizeInput.
|
*/

Route::middleware('throttle:api')->group(function () {
    //
});

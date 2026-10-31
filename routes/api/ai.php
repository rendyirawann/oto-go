<?php

use App\Http\Controllers\Api\V1\AssistantController;
use Illuminate\Support\Facades\Route;

/*
| API module: AI assistant. Loaded inside the /api/v1 prefix (name: api.v1.)
| by routes/api.php.
*/

Route::post('/assistant/chat', [AssistantController::class, 'chat'])
    ->middleware(['auth:sanctum', 'throttle:ai'])
    ->name('assistant.chat');

<?php

use App\Http\Controllers\Backend\Ai\AiAssistantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin: AI agent (web)
|--------------------------------------------------------------------------
| Full-page assistant plus the JSON endpoints used by the page and by the
| floating widget on every admin page. Conversations are private per user.
*/

Route::middleware(['auth', 'forbid-banned-user', 'backoffice'])
    ->prefix('admin/ai')
    ->name('admin.ai.')
    ->group(function () {
        Route::get('/', [AiAssistantController::class, 'index'])->name('index');

        Route::post('/chat', [AiAssistantController::class, 'chat'])
            ->middleware('throttle:ai')
            ->name('chat');

        Route::get('/conversations/{id}', [AiAssistantController::class, 'show'])
            ->where('id', '[0-9A-Za-z]{26}')
            ->name('conversations.show');

        Route::delete('/conversations/{id}', [AiAssistantController::class, 'destroy'])
            ->where('id', '[0-9A-Za-z]{26}')
            ->name('conversations.destroy');
    });

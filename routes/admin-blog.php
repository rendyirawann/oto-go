<?php

use App\Http\Controllers\Backend\Blog\PostController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin: blog management
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'forbid-banned-user', 'backoffice', 'can:view_data_master'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
        Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
        Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');

        Route::middleware('throttle:write')->group(function () {
            Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
            Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
            Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
        });
    });

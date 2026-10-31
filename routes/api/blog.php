<?php

use App\Http\Controllers\Api\V1\BlogController;
use Illuminate\Support\Facades\Route;

/*
| API module: blog (public, read-only). Loaded inside the /api/v1 prefix with
| the "api.v1." name prefix by routes/api.php.
*/

Route::get('/posts', [BlogController::class, 'index'])->name('posts.index');
Route::get('/posts/{slug}', [BlogController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('posts.show');
Route::get('/post-categories', [BlogController::class, 'categories'])->name('post-categories.index');

<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website (landing page, blog, sitemap)
|--------------------------------------------------------------------------
| Owned by the frontend module. Until the landing page ships, "/" sends
| visitors to the admin login so the root URL never 404s.
*/

Route::redirect('/', '/admin/login')->name('home');

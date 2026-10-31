<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {

    // --- REGISTER ---
    // Self-registration is off unless "allow_registration" is enabled in
    // Settings; the controller enforces that, these routes stay mapped so the
    // toggle takes effect without a deploy.
    Route::get('/admin/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/admin/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:password');

    // --- LOGIN ---
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login');

    // --- FORGOT PASSWORD ---
    Route::get('/admin/forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('/admin/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password')
        ->name('password.email');

    // --- RESET PASSWORD ---
    Route::get('/admin/reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/admin/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password')
        ->name('password.store');

    // --- SOCIAL LOGIN ---
    Route::get('/admin/auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])
        ->name('social.redirect');
    Route::get('/admin/auth/{provider}/callback', [SocialLoginController::class, 'callback'])
        ->name('social.callback');
});

Route::middleware('auth')->group(function () {

    // --- VERIFY EMAIL ---
    Route::get('/admin/verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('/admin/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/admin/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // --- CONFIRM PASSWORD ---
    Route::get('/admin/confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');
    Route::post('/admin/confirm-password', [ConfirmablePasswordController::class, 'store'])
        ->middleware('throttle:password');

    // --- UPDATE PASSWORD ---
    Route::put('/admin/password', [PasswordController::class, 'update'])
        ->middleware('throttle:password')
        ->name('password.update');

    // --- LOGOUT ---
    Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

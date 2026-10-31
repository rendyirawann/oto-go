<?php

use Illuminate\Support\Facades\Route;

// Dashboard
use App\Http\Controllers\Backend\Dashboard\DashboardAdminController;

// My profile
use App\Http\Controllers\Backend\MyProfile\AccountController;
use App\Http\Controllers\Backend\MyProfile\ActivityController;
use App\Http\Controllers\Backend\MyProfile\LoginSessionController;
use App\Http\Controllers\Backend\MyProfile\ProfileController;
use App\Http\Controllers\Backend\MyProfile\SecurityController;

// User management
use App\Http\Controllers\Backend\UserManagement\RoleController;
use App\Http\Controllers\Backend\UserManagement\UserController;

// Help / system
use App\Http\Controllers\Backend\Help\LogActivityController;
use App\Http\Controllers\Backend\Settings\SettingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// The app has no public front page — everything lives behind the admin login.
Route::redirect('/', '/admin/login');

Route::middleware(['auth', 'forbid-banned-user'])->group(function () {

    // --- Dashboard (every authenticated role) ---
    Route::get('/admin/dashboard', [DashboardAdminController::class, 'index'])->name('dashboard');

    // --- My account (every authenticated user) ---
    Route::get('/admin/my-account', [AccountController::class, 'index'])->name('account.index');
    Route::get('/admin/my-account/{id}/avatar', [AccountController::class, 'editAvatar'])->name('avatar-edit');

    Route::get('/admin/my-activity', [ActivityController::class, 'index'])->name('my-activity.index');
    Route::get('/admin/mget-my-activity', [ActivityController::class, 'getActivity'])->name('get-my-activity');

    Route::get('/admin/mmy-login-session', [LoginSessionController::class, 'index'])->name('my-login-session.index');
    Route::get('/admin/mget-my-login-session', [LoginSessionController::class, 'getLoginSession'])->name('get-my-login-session');

    // --- Activity log ---
    // Accessible to every authenticated user; the controller scopes the query
    // so a non-Superadmin only ever sees the entries they caused.
    Route::get('/admin/log-activity', [LogActivityController::class, 'index'])->name('log-activity.index');
    Route::get('/admin/get-datalogactivity', [LogActivityController::class, 'getDataLogActivity'])->name('get-datalogactivity');
    Route::get('/admin/log-activity/{id}/detail', [LogActivityController::class, 'detail'])->name('log-activity.detail');
    Route::get('/admin/log-activity/{id}', [LogActivityController::class, 'show'])->name('log-activity.show');

    // Shared helpers used by the role/user forms.
    Route::get('/admin/select/role', [RoleController::class, 'select'])->name('role.select');

    /*
    |----------------------------------------------------------------------
    | Write operations — tighter rate limit than plain page reads.
    |----------------------------------------------------------------------
    */
    Route::middleware('throttle:write')->group(function () {

        Route::post('/admin/my-account/{id}/update-avatar', [AccountController::class, 'updateAvatar'])->name('avatar-update');
        Route::post('/admin/my-security', [SecurityController::class, 'store'])->name('change.password');
        Route::post('/admin/my-security/logout-other-devices', [SecurityController::class, 'logoutOtherDevices'])
            ->name('security.logout-other-devices');

        // --- Settings (Superadmin) ---
        Route::middleware('can:view_resources')->group(function () {
            Route::post('/admin/settings/update', [SettingController::class, 'update'])->name('settings.update');
            Route::post('/admin/settings/regenerate-branding', [SettingController::class, 'regenerateBranding'])
                ->name('settings.branding');

            Route::post('/admin/roles/generate-permissions', [RoleController::class, 'generatePermissions'])->name('roles.generate');
            Route::post('/admin/users/mass-delete', [UserController::class, 'massDelete'])->name('users.mass-delete');
            Route::post('/admin/users/{id}/ban', [UserController::class, 'ban'])->name('users.ban');
            Route::post('/admin/users/{id}/unban', [UserController::class, 'unban'])->name('users.unban');
            Route::post('/admin/roles/mass-delete', [RoleController::class, 'massDelete'])->name('roles.mass-delete');
        });
    });

    // --- Profile & security pages (resource controllers keep their own verbs) ---
    Route::resource('/admin/my-profile', ProfileController::class);
    Route::resource('/admin/my-security', SecurityController::class);

    // --- Settings page (read) ---
    Route::get('/admin/settings', [SettingController::class, 'index'])
        ->middleware('can:view_resources')
        ->name('settings.index');

    /*
    |----------------------------------------------------------------------
    | User & role management — Superadmin only.
    |----------------------------------------------------------------------
    */
    Route::middleware('can:view_resources')->group(function () {
        Route::resource('/admin/users', UserController::class);
        Route::get('/admin/get-datauser', [UserController::class, 'getDataUsers'])->name('get-users');
        Route::get('/admin/get-user-show-log/{id}', [UserController::class, 'getLoginSession'])->name('get-user-show-log');
        Route::get('/admin/get-user-show-log-activity/{id}', [UserController::class, 'getActivity'])->name('get-user-show-log-activity');

        Route::resource('/admin/roles', RoleController::class);
        Route::get('/admin/get-datarole', [RoleController::class, 'getDataRoles'])->name('get-datarole');
    });
});

require __DIR__ . '/auth.php';

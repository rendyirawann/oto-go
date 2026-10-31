<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerVehicleController;
use App\Http\Controllers\Api\V1\MasterDataController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

/*
| API module: v1 core (auth, profile, master data, customer vehicles).
| Loaded inside Route::prefix('v1')->name('api.v1.') under throttle:api by
| routes/api.php. Documented in Swagger (/api/documentation).
*/

// --- Auth (public) ---------------------------------------------------------
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:password')
        ->name('register');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum')
        ->name('logout');
});

// --- Master data (public, cached) -----------------------------------------
Route::get('/vehicle-types', [MasterDataController::class, 'vehicleTypes'])->name('vehicle-types');
Route::get('/vehicle-categories', [MasterDataController::class, 'vehicleCategories'])->name('vehicle-categories');
Route::get('/vehicle-brands', [MasterDataController::class, 'vehicleBrands'])->name('vehicle-brands');
Route::get('/vehicle-models', [MasterDataController::class, 'vehicleModels'])->name('vehicle-models');
Route::get('/service-areas', [MasterDataController::class, 'serviceAreas'])->name('service-areas');
Route::get('/price-estimates', [MasterDataController::class, 'priceEstimates'])->name('price-estimates');

// --- Signed-in customer ----------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [ProfileController::class, 'show'])->name('me.show');
    Route::put('/me', [ProfileController::class, 'update'])->name('me.update');
    Route::put('/me/password', [ProfileController::class, 'password'])
        ->middleware('throttle:password')
        ->name('me.password');

    Route::get('/me/vehicles', [CustomerVehicleController::class, 'index'])->name('me.vehicles.index');
    Route::post('/me/vehicles', [CustomerVehicleController::class, 'store'])->name('me.vehicles.store');
    Route::get('/me/vehicles/{vehicle}', [CustomerVehicleController::class, 'show'])->name('me.vehicles.show');
    Route::put('/me/vehicles/{vehicle}', [CustomerVehicleController::class, 'update'])->name('me.vehicles.update');
    Route::delete('/me/vehicles/{vehicle}', [CustomerVehicleController::class, 'destroy'])->name('me.vehicles.destroy');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\Api\AuthApiController;
use Modules\BeautySaloons\Http\Controllers\Api\CustomerApiController;
use Modules\BeautySaloons\Http\Controllers\Api\AppointmentApiController;
use Modules\BeautySaloons\Http\Controllers\Api\CatalogueApiController;
use Modules\BeautySaloons\Http\Controllers\Api\WalletApiController;
use Modules\BeautySaloons\Http\Controllers\Api\MembershipApiController;
use Modules\BeautySaloons\Http\Controllers\Api\VoucherApiController;
use Modules\BeautySaloons\Http\Controllers\Api\LoyaltyApiController;

Route::middleware(['api'])->prefix('api/v1/beauty-saloons')->name('beautysaloons.api.v1.')->group(function () {
    Route::post('login', [AuthApiController::class, 'login'])->name('login');
    Route::post('logout', [AuthApiController::class, 'logout'])->middleware('auth:sanctum')->name('logout');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [CustomerApiController::class, 'profile'])->name('me');
        Route::get('dashboard', [CustomerApiController::class, 'dashboard'])->name('dashboard');
        Route::get('branches', [CatalogueApiController::class, 'branches'])->name('branches');
        Route::get('services', [CatalogueApiController::class, 'services'])->name('services');
        Route::get('staff-availability', [CatalogueApiController::class, 'staffAvailability'])->name('staff-availability');
        Route::apiResource('appointments', AppointmentApiController::class)->only(['index', 'store', 'show', 'update']);
        Route::post('appointments/{appointment}/cancel', [AppointmentApiController::class, 'cancel'])->name('appointments.cancel');
        Route::get('wallet', [WalletApiController::class, 'index'])->name('wallet');
        Route::get('membership', [MembershipApiController::class, 'index'])->name('membership');
        Route::get('packages', [MembershipApiController::class, 'packages'])->name('packages');
        Route::get('vouchers', [VoucherApiController::class, 'index'])->name('vouchers');
        Route::get('loyalty', [LoyaltyApiController::class, 'index'])->name('loyalty');
    });
});

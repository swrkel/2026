<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\Portal\AuthController;
use Modules\BeautySaloons\Http\Controllers\Portal\PortalDashboardController;
use Modules\BeautySaloons\Http\Controllers\Portal\PortalAppointmentController;
use Modules\BeautySaloons\Http\Controllers\Portal\PortalProfileController;
use Modules\BeautySaloons\Http\Controllers\Portal\PortalWalletController;
use Modules\BeautySaloons\Http\Controllers\Portal\PortalMembershipController;
use Modules\BeautySaloons\Http\Controllers\Portal\PortalPackageController;
use Modules\BeautySaloons\Http\Controllers\Portal\PortalVoucherController;
use Modules\BeautySaloons\Http\Controllers\Portal\PortalLoyaltyController;
use Modules\BeautySaloons\Http\Middleware\EnsureBeautyPortalCustomer;

Route::middleware(['web'])->prefix('beauty-saloons/portal')->name('beautysaloons.portal.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware([EnsureBeautyPortalCustomer::class])->group(function () {
        Route::get('/', [PortalDashboardController::class, 'index'])->name('dashboard');
        Route::resource('appointments', PortalAppointmentController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::post('appointments/{appointment}/cancel', [PortalAppointmentController::class, 'cancel'])->name('appointments.cancel');
        Route::get('profile', [PortalProfileController::class, 'edit'])->name('profile.edit');
        Route::post('profile', [PortalProfileController::class, 'update'])->name('profile.update');
        Route::get('wallet', [PortalWalletController::class, 'index'])->name('wallet.index');
        Route::get('membership', [PortalMembershipController::class, 'index'])->name('membership.index');
        Route::get('packages', [PortalPackageController::class, 'index'])->name('packages.index');
        Route::get('vouchers', [PortalVoucherController::class, 'index'])->name('vouchers.index');
        Route::get('loyalty', [PortalLoyaltyController::class, 'index'])->name('loyalty.index');
    });
});

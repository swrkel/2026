<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\DashboardController;
use Modules\BeautySaloons\Http\Controllers\AppointmentController;
use Modules\BeautySaloons\Http\Controllers\CustomerController;
use Modules\BeautySaloons\Http\Controllers\ServiceController;
use Modules\BeautySaloons\Http\Controllers\StaffController;
use Modules\BeautySaloons\Http\Controllers\RoomController;
use Modules\BeautySaloons\Http\Controllers\ProductController;
use Modules\BeautySaloons\Http\Controllers\SaleController;
use Modules\BeautySaloons\Http\Controllers\ReportController;
use Modules\BeautySaloons\Http\Controllers\SettingController;
use Modules\BeautySaloons\Http\Controllers\Audit\StandaloneAuditController;

Route::middleware(['web', 'auth'])->prefix(config('beautysaloons.route_prefix', 'beauty-saloons'))->name('beautysaloons.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('appointments', AppointmentController::class);
    Route::resource('customers', CustomerController::class);
    Route::resource('services', ServiceController::class);
    Route::resource('staff', StaffController::class);
    Route::resource('rooms', RoomController::class);
    Route::resource('products', ProductController::class);
    Route::resource('sales', SaleController::class);
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/daily-sales', [ReportController::class, 'dailySales'])->name('reports.daily-sales');
    Route::get('reports/staff-performance', [ReportController::class, 'staffPerformance'])->name('reports.staff-performance');
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'store'])->name('settings.store');

    Route::get('final-audit', [StandaloneAuditController::class, 'index'])->name('audit.index');
    Route::get('release-candidate', [StandaloneAuditController::class, 'releaseCandidate'])->name('release-candidate');
});

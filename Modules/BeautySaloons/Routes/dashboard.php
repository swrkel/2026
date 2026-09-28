<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\Dashboard\ExecutiveDashboardController;
use Modules\BeautySaloons\Http\Controllers\Dashboard\BranchDashboardController;
use Modules\BeautySaloons\Http\Controllers\Dashboard\ReceptionDashboardController;
use Modules\BeautySaloons\Http\Controllers\Dashboard\StaffDashboardController;
use Modules\BeautySaloons\Http\Controllers\Dashboard\FinanceDashboardController;

Route::middleware(['web', 'auth'])
    ->prefix(config('beautysaloons.route_prefix', 'beauty-saloons') . '/dashboards')
    ->name('beautysaloons.dashboards.')
    ->group(function () {
        Route::get('executive', [ExecutiveDashboardController::class, 'index'])->name('executive');
        Route::get('executive/data', [ExecutiveDashboardController::class, 'data'])->name('executive.data');
        Route::get('branch', [BranchDashboardController::class, 'index'])->name('branch');
        Route::get('branch/data', [BranchDashboardController::class, 'data'])->name('branch.data');
        Route::get('reception', [ReceptionDashboardController::class, 'index'])->name('reception');
        Route::get('staff', [StaffDashboardController::class, 'index'])->name('staff');
        Route::get('finance', [FinanceDashboardController::class, 'index'])->name('finance');
    });

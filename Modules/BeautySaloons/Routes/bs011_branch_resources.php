<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\BranchController;
use Modules\BeautySaloons\Http\Controllers\ChairController;
use Modules\BeautySaloons\Http\Controllers\ResourceController;
use Modules\BeautySaloons\Http\Controllers\BranchReportController;

Route::middleware(['web', 'auth'])
    ->prefix(config('beautysaloons.route_prefix', 'beauty-saloons'))
    ->name('beautysaloons.')
    ->group(function () {
        Route::resource('branches', BranchController::class);
        Route::resource('chairs', ChairController::class);
        Route::resource('resources', ResourceController::class);
        Route::get('branch-reports', [BranchReportController::class, 'index'])->name('branch-reports.index');
        Route::get('branch-reports/branch-sales', [BranchReportController::class, 'branchSales'])->name('branch-reports.branch-sales');
        Route::get('branch-reports/resource-utilization', [BranchReportController::class, 'resourceUtilization'])->name('branch-reports.resource-utilization');
    });

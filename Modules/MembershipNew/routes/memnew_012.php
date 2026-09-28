<?php

use Illuminate\Support\Facades\Route;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewAdminToolController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewErrorLogController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewImportController;

Route::middleware(['web', 'auth'])
    ->prefix('membership-new')
    ->name('membership-new.')
    ->group(function () {
        Route::get('admin-tools', [MembershipNewAdminToolController::class, 'index'])
            ->name('admin-tools.index')
            ->middleware('permission:membership_new.admin_tools.view');

        Route::post('admin-tools/reset-demo-data', [MembershipNewAdminToolController::class, 'resetDemoData'])
            ->name('admin-tools.reset-demo-data')
            ->middleware('permission:membership_new.admin_tools.run');

        Route::get('error-logs', [MembershipNewErrorLogController::class, 'index'])
            ->name('error-logs.index')
            ->middleware('permission:membership_new.error_logs.view');

        Route::get('imports', [MembershipNewImportController::class, 'index'])
            ->name('imports.index')
            ->middleware('permission:membership_new.imports.view');
    });

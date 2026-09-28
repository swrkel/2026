<?php

use Illuminate\Support\Facades\Route;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewCommandCenterController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewReportCenterController;
use Modules\MembershipNew\app\Http\Controllers\MembershipNewFinalAuditController;

Route::middleware(['web', 'auth'])
    ->prefix('membership-new')
    ->name('membership-new.')
    ->group(function () {
        Route::get('command-center', [MembershipNewCommandCenterController::class, 'index'])
            ->name('command-center.index')
            ->middleware('permission:membership_new.dashboard.view');

        Route::get('report-center', [MembershipNewReportCenterController::class, 'index'])
            ->name('report-center.index')
            ->middleware('permission:membership_new.reports.view');

        Route::get('final-audit', [MembershipNewFinalAuditController::class, 'index'])
            ->name('final-audit.index')
            ->middleware('permission:membership_new.health.view');
    });

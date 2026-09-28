<?php

use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\Analytics\DisnewAnalyticsDashboardController;
use Modules\DistributionNew\Http\Controllers\Analytics\DisnewScheduledReportController;

Route::prefix('distribution-new')->middleware(['web','auth'])->group(function () {
    Route::get('/analytics/executive-dashboard', [DisnewAnalyticsDashboardController::class, 'executive'])->name('distribution-new.analytics.executive');
    Route::get('/analytics/kpi-dashboard', [DisnewAnalyticsDashboardController::class, 'kpi'])->name('distribution-new.analytics.kpi');
    Route::get('/analytics/scheduled-reports', [DisnewScheduledReportController::class, 'index'])->name('distribution-new.analytics.scheduled-reports');
    Route::post('/analytics/scheduled-reports', [DisnewScheduledReportController::class, 'store'])->name('distribution-new.analytics.scheduled-reports.store');
    Route::post('/analytics/scheduled-reports/{scheduledReport}/queue', [DisnewScheduledReportController::class, 'queue'])->name('distribution-new.analytics.scheduled-reports.queue');
});

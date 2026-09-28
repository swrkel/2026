<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Analytics\MyHealthAnalyticsDashboardController;
use Modules\MyHealthMembers\Http\Controllers\Analytics\MyHealthAnalyticsReportController;

Route::prefix('my-health/analytics')->name('myhealth.analytics.')->group(function () {
    Route::get('/', [MyHealthAnalyticsDashboardController::class, 'index'])->name('dashboard');
    Route::get('/executive', [MyHealthAnalyticsReportController::class, 'executive'])->name('executive');
    Route::get('/clinical', [MyHealthAnalyticsReportController::class, 'clinical'])->name('clinical');
    Route::get('/operational', [MyHealthAnalyticsReportController::class, 'operational'])->name('operational');
    Route::get('/financial', [MyHealthAnalyticsReportController::class, 'financial'])->name('financial');
});

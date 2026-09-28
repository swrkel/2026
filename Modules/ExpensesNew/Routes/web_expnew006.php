<?php

use Illuminate\Support\Facades\Route;
use Modules\ExpensesNew\Http\Controllers\Analytics\CommandCenterController;
use Modules\ExpensesNew\Http\Controllers\Analytics\AnalyticsController;
use Modules\ExpensesNew\Http\Controllers\Analytics\ExpenseIntelligenceController;
use Modules\ExpensesNew\Http\Controllers\Reports\ExpenseReportCenterController;
use Modules\ExpensesNew\Http\Controllers\Reports\ExpenseReportRunController;

Route::middleware(['web','auth'])->prefix('expenses-new')->name('expenses-new.')->group(function () {
    Route::get('/command-center', [CommandCenterController::class, 'index'])->name('command-center');
    Route::get('/command-center/data', [CommandCenterController::class, 'data'])->name('command-center.data');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/analytics/data', [AnalyticsController::class, 'data'])->name('analytics.data');
    Route::get('/intelligence', [ExpenseIntelligenceController::class, 'index'])->name('intelligence');
    Route::get('/reports', [ExpenseReportCenterController::class, 'index'])->name('reports');
    Route::get('/reports/run/{code?}', [ExpenseReportRunController::class, 'index'])->name('reports.run');
});

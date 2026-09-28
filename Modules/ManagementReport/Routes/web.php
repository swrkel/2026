<?php

use Illuminate\Support\Facades\Route;
use Modules\ManagementReport\Http\Controllers\DashboardController;
use Modules\ManagementReport\Http\Controllers\DailyReportController;
use Modules\ManagementReport\Http\Controllers\ReviewController;
use Modules\ManagementReport\Http\Controllers\SavedReportController;
use Modules\ManagementReport\Http\Controllers\SettingsController;
use Modules\ManagementReport\Http\Controllers\ShareHistoryController;
use Modules\ManagementReport\Http\Middleware\EnsureManagementReportAccess;
use Modules\ManagementReport\Http\Middleware\EnsureManagementReportPermission;
use Modules\ManagementReport\Http\Middleware\EnsureManagementReportTables;

// RouteServiceProvider initializes the dynamic tenant database before auth.
Route::middleware([
    EnsureManagementReportTables::class,
    EnsureManagementReportAccess::class,
    EnsureManagementReportPermission::class,
])->prefix('management-report')->as('managementreport.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/daily', [DailyReportController::class, 'index'])->name('daily.index');
    Route::post('/daily/preview', [DailyReportController::class, 'preview'])->name('daily.preview');
    Route::post('/daily/generate', [DailyReportController::class, 'generate'])->name('daily.generate');
    Route::get('/daily/{run}/print', [DailyReportController::class, 'print'])->name('daily.print');
    Route::get('/daily/{run}/pdf', [DailyReportController::class, 'pdf'])->name('daily.pdf');
    Route::post('/daily/{run}/share', [DailyReportController::class, 'share'])->name('daily.share');
    Route::get('/saved', [SavedReportController::class, 'index'])->name('saved.index');
    Route::get('/saved/{run}', [SavedReportController::class, 'show'])->name('saved.show');
    Route::delete('/saved/{run}', [SavedReportController::class, 'destroy'])->name('saved.destroy');
    Route::post('/saved/{run}/review', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/delivery-history', [ShareHistoryController::class, 'index'])->name('shares.index');
    Route::post('/delivery-history/{share}/revoke', [ShareHistoryController::class, 'revoke'])->name('shares.revoke');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

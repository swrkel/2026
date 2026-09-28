<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingTesterUI\Http\Controllers\BankingTesterDashboardController;
use Modules\BankingTesterUI\Http\Controllers\BankingTesterIssueController;
use Modules\BankingTesterUI\Http\Controllers\BankingTesterCheckController;

Route::middleware(['web', 'auth'])->prefix('banking/tester-ui')->name('banking.tester-ui.')->group(function () {
    Route::get('/', [BankingTesterDashboardController::class, 'index'])->name('index');
    Route::get('/modules', [BankingTesterDashboardController::class, 'modules'])->name('modules');
    Route::get('/module/{slug}', [BankingTesterDashboardController::class, 'module'])->name('module');
    Route::get('/checklist', [BankingTesterDashboardController::class, 'checklist'])->name('checklist');
    Route::get('/route-health', [BankingTesterDashboardController::class, 'routeHealth'])->name('route-health');
    Route::get('/reports', [BankingTesterDashboardController::class, 'reports'])->name('reports');
    Route::get('/settings', [BankingTesterDashboardController::class, 'settings'])->name('settings');
    Route::get('/issues', [BankingTesterIssueController::class, 'index'])->name('issues.index');
    Route::get('/issues/create', [BankingTesterIssueController::class, 'create'])->name('issues.create');
    Route::post('/issues', [BankingTesterIssueController::class, 'store'])->name('issues.store');
    Route::post('/issues/{issue}/status', [BankingTesterIssueController::class, 'updateStatus'])->name('issues.status');
    Route::get('/checks', [BankingTesterCheckController::class, 'index'])->name('checks.index');
    Route::post('/checks', [BankingTesterCheckController::class, 'store'])->name('checks.store');
    Route::view('/handover', 'bankingtesterui::handover.index')->name('handover');
});

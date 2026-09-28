<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingUI\Http\Controllers\BankingUiSmokeTestController;
use Modules\BankingUI\Http\Controllers\BankingTestManagerController;

Route::middleware(['web', 'auth'])->prefix('banking/ui/smoke')->name('banking.ui.smoke.')->group(function () {
    Route::get('/', [BankingUiSmokeTestController::class, 'index'])->name('index');
    Route::get('/routes', [BankingUiSmokeTestController::class, 'routes'])->name('routes');
    Route::get('/permissions', [BankingUiSmokeTestController::class, 'permissions'])->name('permissions');
    Route::get('/sidebar', [BankingUiSmokeTestController::class, 'sidebar'])->name('sidebar');
    Route::get('/release', [BankingUiSmokeTestController::class, 'index'])->name('release');
});

Route::middleware(['web', 'auth'])->prefix('banking/test-manager')->name('banking.test-manager.')->group(function () {
    Route::get('/', [BankingTestManagerController::class, 'index'])->name('index');
    Route::get('/coverage', [BankingTestManagerController::class, 'coverage'])->name('coverage');
    Route::post('/coverage', [BankingTestManagerController::class, 'updateCoverage'])->name('coverage.update');
    Route::get('/issues', [BankingTestManagerController::class, 'issues'])->name('issues');
    Route::post('/issues', [BankingTestManagerController::class, 'storeIssue'])->name('issues.store');
    Route::get('/routes', [BankingTestManagerController::class, 'routes'])->name('routes');
});

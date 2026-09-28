<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])->prefix('finance')->name('finance.')->group(function () {
    Route::any('reports', [\Modules\Finance\Http\Controllers\Reports\AccountReportsController::class, 'index'])->name('reports.index');
});


Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])->group(function () {
    Route::get('finance/reports/trial-balance-rewrite-data', [\Modules\Finance\Http\Controllers\Reports\AccountReportsController::class, 'trialBalanceRewriteData'])
        ->name('finance.trial-balance.rewrite-data');
});

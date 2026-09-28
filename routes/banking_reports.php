<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingUI\Http\Controllers\BankingReportController;

Route::middleware(['web', 'auth'])->prefix('banking/reports')->name('banking.reports.')->group(function () {
    Route::get('/', [BankingReportController::class, 'index'])->name('index');
    Route::get('/{reportKey}', [BankingReportController::class, 'show'])->name('show');
    Route::get('/{reportKey}/export/{type}', [BankingReportController::class, 'export'])->name('export');
});

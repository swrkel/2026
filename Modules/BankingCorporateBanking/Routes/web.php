<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingCorporateBanking\Http\Controllers\CorporateBankingController;

Route::middleware(['web', 'auth'])->prefix('banking/corporate')->name('banking.corporate.')->group(function () {
    Route::get('/', [CorporateBankingController::class, 'dashboard'])->name('dashboard');
    Route::get('/corporates', [CorporateBankingController::class, 'corporates'])->name('corporates.index');
    Route::get('/signatories', [CorporateBankingController::class, 'signatories'])->name('signatories.index');
    Route::get('/approvals', [CorporateBankingController::class, 'approvals'])->name('approvals.index');
    Route::get('/bulk-payments', [CorporateBankingController::class, 'bulkPayments'])->name('bulk_payments.index');
    Route::get('/payroll', [CorporateBankingController::class, 'payroll'])->name('payroll.index');
    Route::get('/cash-management', [CorporateBankingController::class, 'cashManagement'])->name('cash_management.index');
    Route::get('/collections', [CorporateBankingController::class, 'collections'])->name('collections.index');
    Route::get('/reports', [CorporateBankingController::class, 'reports'])->name('reports.index');
    Route::get('/settings', [CorporateBankingController::class, 'settings'])->name('settings.index');
});

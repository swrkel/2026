<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingMobileBanking\Http\Controllers\MobileBankingController;
use Modules\BankingMobileBanking\Http\Controllers\MobileBankingReportController;

Route::middleware(['web', 'auth'])->prefix('banking/mobile-banking')->as('banking.mobile.')->group(function () {
    Route::get('/', [MobileBankingController::class, 'dashboard'])->name('dashboard');
    Route::get('/registrations', [MobileBankingController::class, 'registrations'])->name('registrations.index');
    Route::get('/devices', [MobileBankingController::class, 'devices'])->name('devices.index');
    Route::get('/transfers', [MobileBankingController::class, 'transfers'])->name('transfers.index');
    Route::get('/beneficiaries', [MobileBankingController::class, 'beneficiaries'])->name('beneficiaries.index');
    Route::get('/bill-payments', [MobileBankingController::class, 'bills'])->name('bills.index');
    Route::get('/qr-payments', [MobileBankingController::class, 'qr'])->name('qr.index');
    Route::get('/notifications', [MobileBankingController::class, 'notifications'])->name('notifications.index');
    Route::get('/settings', [MobileBankingController::class, 'settings'])->name('settings.index');
    Route::get('/reports', [MobileBankingReportController::class, 'index'])->name('reports.index');
});

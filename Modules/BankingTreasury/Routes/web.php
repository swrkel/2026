<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingTreasury\Http\Controllers\TreasuryDashboardController;
use Modules\BankingTreasury\Http\Controllers\VaultController;
use Modules\BankingTreasury\Http\Controllers\TreasuryTransferController;
use Modules\BankingTreasury\Http\Controllers\TreasuryDealController;
use Modules\BankingTreasury\Http\Controllers\TreasuryReportController;
use Modules\BankingTreasury\Http\Controllers\TreasurySettingController;

Route::middleware(['web', 'auth'])->prefix('banking/treasury')->name('banking.treasury.')->group(function () {
    Route::get('/', [TreasuryDashboardController::class, 'index'])->name('index');
    Route::get('/vaults', [VaultController::class, 'index'])->name('vaults.index');
    Route::get('/transfers', [TreasuryTransferController::class, 'index'])->name('transfers.index');
    Route::get('/deals', [TreasuryDealController::class, 'index'])->name('deals.index');
    Route::get('/reports', [TreasuryReportController::class, 'index'])->name('reports.index');
    Route::get('/settings', [TreasurySettingController::class, 'index'])->name('settings.index');
});

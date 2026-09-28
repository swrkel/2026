<?php

use Illuminate\Support\Facades\Route;
use Modules\DigitalWallet\Http\Controllers\DigitalWalletController;
use Modules\DigitalWallet\Http\Controllers\DigitalWalletApprovalController;
use Modules\DigitalWallet\Http\Controllers\DigitalWalletHierarchyController;
use Modules\DigitalWallet\Http\Controllers\DigitalWalletTransferController;
use Modules\DigitalWallet\Http\Controllers\DigitalWalletTypeController;
use Modules\DigitalWallet\Http\Controllers\DigitalWalletLedgerController;
use Modules\DigitalWallet\Http\Controllers\DigitalWalletReportController;
use Modules\DigitalWallet\Http\Controllers\DigitalWalletRuleController;
use Modules\DigitalWallet\Http\Controllers\DigitalWalletSettingController;
use Modules\DigitalWallet\Http\Controllers\DigitalWalletTransactionController;
use Modules\DigitalWallet\Http\Controllers\DigitalWalletWalletController;
use Modules\DigitalWallet\Http\Controllers\Financial\DigitalWalletFinancialEngineController;

Route::middleware(['web', 'auth'])->prefix('digital-wallet')->as('digitalwallet.')->group(function () {
    Route::get('/', [DigitalWalletController::class, 'dashboard'])->name('dashboard');
    Route::resource('wallets', DigitalWalletWalletController::class)->except(['show']);

    Route::get('hierarchy', [DigitalWalletHierarchyController::class, 'index'])->name('hierarchy.index');
    Route::resource('types', DigitalWalletTypeController::class)->except(['show', 'destroy']);
    Route::post('types/seed-defaults', [DigitalWalletTypeController::class, 'seedDefaults'])->name('types.seed-defaults');
    Route::get('transfers', [DigitalWalletTransferController::class, 'index'])->name('transfers.index');
    Route::get('transfers/create', [DigitalWalletTransferController::class, 'create'])->name('transfers.create');
    Route::post('transfers', [DigitalWalletTransferController::class, 'store'])->name('transfers.store');
    Route::get('approvals', [DigitalWalletApprovalController::class, 'index'])->name('approvals.index');
    Route::post('approvals/{approval}/approve', [DigitalWalletApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('approvals/{approval}/reject', [DigitalWalletApprovalController::class, 'reject'])->name('approvals.reject');
    Route::get('ledger', [DigitalWalletLedgerController::class, 'index'])->name('ledger.index');
    Route::get('transactions', [DigitalWalletTransactionController::class, 'index'])->name('transactions.index');
    Route::post('wallets/{wallet}/topup', [DigitalWalletTransactionController::class, 'topup'])->name('transactions.topup');
    Route::post('wallets/{wallet}/charge', [DigitalWalletTransactionController::class, 'charge'])->name('transactions.charge');

    Route::get('financial-engine', [DigitalWalletFinancialEngineController::class, 'index'])->name('financial.index');
    Route::post('financial-engine/wallets/{wallet}/recharge', [DigitalWalletFinancialEngineController::class, 'recharge'])->name('financial.recharge');
    Route::post('financial-engine/wallets/{wallet}/reserve', [DigitalWalletFinancialEngineController::class, 'reserve'])->name('financial.reserve');
    Route::post('financial-engine/wallets/{wallet}/adjustment', [DigitalWalletFinancialEngineController::class, 'adjustment'])->name('financial.adjustment');
    Route::post('financial-engine/reservations/{reservation}/commit', [DigitalWalletFinancialEngineController::class, 'commit'])->name('financial.commit');
    Route::post('financial-engine/reservations/{reservation}/release', [DigitalWalletFinancialEngineController::class, 'release'])->name('financial.release');
    Route::resource('rules', DigitalWalletRuleController::class)->except(['show']);
    Route::get('reports', [DigitalWalletReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [DigitalWalletReportController::class, 'export'])->name('reports.export');
    Route::get('settings', [DigitalWalletSettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [DigitalWalletSettingController::class, 'store'])->name('settings.store');
});

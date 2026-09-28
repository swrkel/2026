<?php
use Illuminate\Support\Facades\Route;
use Modules\BankingAtmDebitCard\Http\Controllers\DashboardController;
use Modules\BankingAtmDebitCard\Http\Controllers\CardInventoryController;
use Modules\BankingAtmDebitCard\Http\Controllers\DebitCardController;
use Modules\BankingAtmDebitCard\Http\Controllers\CardLimitController;
use Modules\BankingAtmDebitCard\Http\Controllers\AtmTransactionController;
use Modules\BankingAtmDebitCard\Http\Controllers\CardDisputeController;
use Modules\BankingAtmDebitCard\Http\Controllers\ReportController;

Route::middleware(['web','auth'])->prefix('banking/cards')->name('banking.cards.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('inventory', CardInventoryController::class);
    Route::resource('debit-cards', DebitCardController::class);
    Route::post('debit-cards/{debit_card}/activate', [DebitCardController::class, 'activate'])->name('debit-cards.activate');
    Route::post('debit-cards/{debit_card}/hotlist', [DebitCardController::class, 'hotlist'])->name('debit-cards.hotlist');
    Route::post('debit-cards/{debit_card}/replace', [DebitCardController::class, 'replace'])->name('debit-cards.replace');
    Route::resource('limits', CardLimitController::class);
    Route::resource('atm-transactions', AtmTransactionController::class)->only(['index','show','store']);
    Route::resource('disputes', CardDisputeController::class);
    Route::get('reports/card-register', [ReportController::class, 'cardRegister'])->name('reports.card-register');
    Route::get('reports/hotlisted-cards', [ReportController::class, 'hotlistedCards'])->name('reports.hotlisted-cards');
    Route::get('reports/atm-reconciliation', [ReportController::class, 'atmReconciliation'])->name('reports.atm-reconciliation');
    Route::get('reports/disputes', [ReportController::class, 'disputes'])->name('reports.disputes');
});

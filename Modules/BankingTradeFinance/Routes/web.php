<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingTradeFinance\Http\Controllers\TradeFinanceController;

Route::middleware(['web','auth'])->prefix('banking/trade-finance')->name('banking.trade-finance.')->group(function () {
    Route::get('/', [TradeFinanceController::class, 'dashboard'])->name('dashboard');
    Route::get('/letters-of-credit', [TradeFinanceController::class, 'lettersOfCredit'])->name('letters-of-credit.index');
    Route::get('/bank-guarantees', [TradeFinanceController::class, 'bankGuarantees'])->name('bank-guarantees.index');
    Route::get('/import-bills', [TradeFinanceController::class, 'importBills'])->name('import-bills.index');
    Route::get('/export-bills', [TradeFinanceController::class, 'exportBills'])->name('export-bills.index');
    Route::get('/documentary-collections', [TradeFinanceController::class, 'documentaryCollections'])->name('documentary-collections.index');
    Route::get('/shipping-documents', [TradeFinanceController::class, 'shippingDocuments'])->name('shipping-documents.index');
    Route::get('/reports', [TradeFinanceController::class, 'reports'])->name('reports.index');
    Route::get('/settings', [TradeFinanceController::class, 'settings'])->name('settings.index');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\Financial\BalanceSheetController;
use Modules\Finance\Http\Controllers\Financial\BalanceSheetComparisonController;
use Modules\Finance\Http\Controllers\Reports\TradingProfitController;
use Modules\Finance\Http\Controllers\Financial\GeneralLedgerController;
use Modules\Finance\Http\Controllers\Financial\ProfitLossController;
use Modules\Finance\Http\Controllers\Financial\TrialBalanceController;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])
    ->prefix('finance/reports')
    ->group(function () {
        Route::get('/trial-balance', [TrialBalanceController::class, 'index'])
            ->name('finance.trial_balance');

        Route::get('/balance-sheet', [BalanceSheetController::class, 'index'])
            ->name('finance.balance_sheet');
        Route::get('/balance-sheet/comparison', [BalanceSheetComparisonController::class, 'index'])
            ->name('finance.balance_sheet.comparison');

        Route::get('/profit-loss', [ProfitLossController::class, 'branch'])
            ->name('finance.profit_loss');
        Route::get('/profit-loss/consolidated', [ProfitLossController::class, 'consolidated'])
            ->name('finance.profit_loss.consolidated');

        // Income Statement is the same accounting statement as Profit & Loss.
        // Keep dedicated URLs so permissions/sidebar items can use either wording.
        Route::get('/income-statement', [ProfitLossController::class, 'incomeStatement'])
            ->name('finance.income_statement');
        Route::get('/income-statement/consolidated', [ProfitLossController::class, 'consolidated'])
            ->name('finance.income_statement.consolidated');

        Route::get('/general-ledger', [GeneralLedgerController::class, 'index'])
            ->name('finance.general_ledger');
        Route::get('/general-ledger/account/{account_id}', [GeneralLedgerController::class, 'accountLedger'])
            ->whereNumber('account_id')
            ->name('finance.general_ledger.account');
    });


// Override the legacy Accounting Module report URLs with Finance-owned
// controllers. This also avoids the malformed financial-year month string
// that caused Carbon to receive values such as `2026--1`.
Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])
    ->prefix('finance')
    ->group(function () {
        Route::get('/balance-sheet', [BalanceSheetController::class, 'index'])
            ->name('finance.legacy.balance-sheet');
        Route::get('/balance-sheet-comparison', [BalanceSheetComparisonController::class, 'index'])
            ->name('finance.legacy.balance-sheet-comparison');
        Route::get('/trading-profit/{by}', [TradingProfitController::class, 'data'])
            ->where('by', 'product|category|sub-category|brand|location|invoice|date|customer|day')
            ->name('finance.trading-profit.data');
    });

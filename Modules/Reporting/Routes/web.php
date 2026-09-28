<?php

use Illuminate\Support\Facades\Route;

Route::middleware([
    'web',
    'auth'
])
->prefix('reporting')
->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Enterprise Reporting Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/',
        'ReportingController@index'
    )->name(
        'reporting.dashboard'
    );

    Route::get(
        '/dashboard',
        'ReportingController@index'
    )->name(
        'reporting.dashboard.index'
    );

    /*
    |--------------------------------------------------------------------------
    | Branch Wise Financial Reports
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/branch/profit-loss',
        'Financial\ProfitLossController@branch'
    )->name(
        'reporting.branch.pnl'
    );

    Route::get(
        '/branch/balance-sheet',
        'Financial\BalanceSheetController@branch'
    )->name(
        'reporting.branch.balance_sheet'
    );

    Route::get(
        '/branch/trial-balance',
        'Financial\TrialBalanceController@branch'
    )->name(
        'reporting.branch.trial_balance'
    );

    /*
    |--------------------------------------------------------------------------
    | Consolidated Financial Reports
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/consolidated/profit-loss',
        'Financial\ProfitLossController@consolidated'
    )->name(
        'reporting.consolidated.pnl'
    );

    Route::get(
        '/consolidated/balance-sheet',
        'Financial\BalanceSheetController@consolidated'
    )->name(
        'reporting.consolidated.balance_sheet'
    );

    Route::get(
        '/consolidated/trial-balance',
        'Financial\TrialBalanceController@consolidated'
    )->name(
        'reporting.consolidated.trial_balance'
    );

    /*
    |--------------------------------------------------------------------------
    | General Ledger Reports
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/general-ledger',
        'Financial\GeneralLedgerController@index'
    )->name(
        'reporting.general_ledger'
    );

    Route::get(
        '/general-ledger/account/{account_id}',
        'Financial\GeneralLedgerController@accountLedger'
    )->name(
        'reporting.general_ledger.account'
    );

    /*
    |--------------------------------------------------------------------------
    | Existing Financial Report Compatibility
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/financial/profit-loss',
        'Financial\ProfitLossController@index'
    )->name(
        'reporting.financial.profit_loss'
    );

    /*
    |--------------------------------------------------------------------------
    | Loan Recovery Reports
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/recovery',
        'Recovery\RecoveryReportController@index'
    )->name(
        'reporting.recovery'
    );

    /*
    |--------------------------------------------------------------------------
    | Compliance Reports
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/compliance',
        'Compliance\ComplianceReportController@index'
    )->name(
        'reporting.compliance'
    );

    /*
    |--------------------------------------------------------------------------
    | Governance Reports
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/governance',
        'Governance\GovernanceReportController@index'
    )->name(
        'reporting.governance'
    );

});
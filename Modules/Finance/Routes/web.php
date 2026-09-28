<?php

// Finance module route loader. Keep individual route files small.
// accounting_module must load first so /accounting-module/account resolves
// to the standalone Finance module controller/view instead of the legacy
// main-system AccountController/view.
foreach ([
    'accounting_module',
    'bank_reconciliation',
    'accounts',
    'account_reports',
    'financial',
    'finance_reports',
    'settings',
    'journal',
    'fixed_assets',
    'cheques',
    'deposits',
    // Owns /deposits-module, moved out of routes/web.php so the module is standalone.
    'deposits_module',
    'expenses',
    'payments',
] as $finance_route_file) {
    $path = __DIR__ . '/' . $finance_route_file . '.php';
    if (file_exists($path)) {
        require $path;
    }
}


/*
|--------------------------------------------------------------------------
| List Accounts compatibility URLs
|--------------------------------------------------------------------------
| Some older/master-database businesses still have sidebar/menu rows saved
| with the historical /accounting-module/account URL.  Keep those businesses
| working while /finance/account remains the canonical Finance URL.
*/
\Illuminate\Support\Facades\Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])
    ->get('/accounting-module/account', function () { return redirect()->route('finance.list-accounts.live'); })
    ->name('finance.compat.accounting_module.account');

\Illuminate\Support\Facades\Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])
    ->get('/accounting-module/accounts', function () { return redirect()->route('finance.list-accounts.live'); })
    ->name('finance.compat.accounting_module.accounts');

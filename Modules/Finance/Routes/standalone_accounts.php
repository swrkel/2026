<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\StandaloneAccountController;

/*
|--------------------------------------------------------------------------
| Finance - Standalone List Accounts
|--------------------------------------------------------------------------
| /finance/account is authoritative.  The legacy /accounting-module/account
| URL is handled only as a compatibility redirect by the global sidebar guard.
*/
Route::prefix('finance')->group(function () {
    Route::get('/account', [StandaloneAccountController::class, 'index'])->name('finance.account.index');

    // Compatibility owner for the existing Finance sidebar/bookmarks.
    // The old Finance module registered /finance/accounts to AccountController@index;
    // because this standalone route file is loaded last, this route replaces that
    // legacy entry and always forwards users to the authoritative standalone page.
    Route::get('/accounts', function () {
        return redirect()->to(url('/finance/account'));
    })->name('finance.accounts.index');

    // Historical Finance sidebar builds referenced this route name.  Keep a
    // Finance-owned alias instead of sending them back to Accounting.
    Route::get('/list-accounts', function () {
        return redirect()->to(url('/finance/account'));
    })->name('finance.account.list_alias');

    /*
     * Collision-proof Account Book workflow for central-database businesses
     * and Stancl tenants.  These names match the Finance module's full route
     * loader so List Accounts never depends on the legacy cached
     * /finance/account-book matcher.
     */
    Route::match(['GET', 'HEAD', 'POST'], '/list-accounts-live/account-book', [\Modules\Finance\Http\Controllers\AccountController::class, 'accountBookRedirect'])
        ->name('finance.list-accounts.live.account_book.redirect');
    Route::get('/list-accounts-live/account-book/contact-options', [\Modules\Finance\Http\Controllers\AccountController::class, 'accountBookContactOptions'])
        ->name('finance.list-accounts.live.account_book.contact-options');
    Route::get('/list-accounts-live/account-book/{id}', [\Modules\Finance\Http\Controllers\AccountController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('finance.list-accounts.live.account_book.show');
    Route::get('/list-accounts-live/account-book/{id}/data', [\Modules\Finance\Http\Controllers\AccountController::class, 'accountBookData'])
        ->where('id', '[0-9]+')
        ->name('finance.list-accounts.live.account_book.data');
    Route::get('/list-accounts-live/account-book/{id}/balance', [\Modules\Finance\Http\Controllers\AccountController::class, 'getAccountBalance'])
        ->where('id', '[0-9]+')
        ->name('finance.list-accounts.live.account_book.balance');
    Route::get('/list-accounts-live/account-book/{id}/main-data', [\Modules\Finance\Http\Controllers\AccountController::class, 'getMainAccountBook'])
        ->where('id', '[0-9]+')
        ->name('finance.list-accounts.live.main_account_book.data');
    Route::get('/list-accounts-live/account-book/{id}/main-balance', [\Modules\Finance\Http\Controllers\AccountController::class, 'getAccountBalanceMain'])
        ->where('id', '[0-9]+')
        ->name('finance.list-accounts.live.main_account_book.balance');

    /*
     * Finance Account Settings endpoints used by the List Accounts page.
     *
     * The normal Finance route loader wraps /finance/settings in the legacy
     * InitializeFinanceTenantContext stack. On tenant subdomains the DataTables
     * AJAX request can then reach auth without the same scoped tenant session
     * used by /finance/account, producing HTTP 401. This standalone route file
     * is registered last inside the proven List Accounts tenant middleware
     * stack, so these exact routes intentionally take final ownership.
     */
    Route::get('/settings', [\Modules\Finance\Http\Controllers\Settings\AccountSettingController::class, 'index'])->name('finance.settings.index');
    Route::post('/settings', [\Modules\Finance\Http\Controllers\Settings\AccountSettingController::class, 'store'])->name('finance.settings.store');
    Route::get('/settings/default-date-range', [\Modules\Finance\Http\Controllers\Settings\AccountSettingController::class, 'getDefaultDateRange'])->name('finance.settings.default-date-range.show');
    Route::post('/settings/default-date-range', [\Modules\Finance\Http\Controllers\Settings\AccountSettingController::class, 'saveDefaultDateRange'])->name('finance.settings.default-date-range.store');
    Route::get('/settings/account-numbers', [\Modules\Finance\Http\Controllers\Settings\AccountSettingController::class, 'getAccountNumbers'])->name('finance.settings.account-numbers.show');
    Route::post('/settings/account-numbers', [\Modules\Finance\Http\Controllers\Settings\AccountSettingController::class, 'saveAccountNumbers'])->name('finance.settings.account-numbers.store');
    Route::get('/settings/{id}/edit', [\Modules\Finance\Http\Controllers\Settings\AccountSettingController::class, 'edit'])->where('id', '[0-9]+')->name('finance.settings.edit');
    Route::put('/settings/{id}', [\Modules\Finance\Http\Controllers\Settings\AccountSettingController::class, 'update'])->where('id', '[0-9]+')->name('finance.settings.update');
    Route::delete('/settings/{id}', [\Modules\Finance\Http\Controllers\Settings\AccountSettingController::class, 'destroy'])->where('id', '[0-9]+')->name('finance.settings.destroy');

    // Finance-owned Account Group endpoints used by List Accounts.
    Route::get('/account-groups/data', [\Modules\Finance\Http\Controllers\Accounts\AccountGroupController::class, 'data'])->name('finance.account_groups.data');
    Route::get('/account-groups/create', [\Modules\Finance\Http\Controllers\Accounts\AccountGroupController::class, 'create'])->name('finance.account_groups.create');
    Route::post('/account-groups', [\Modules\Finance\Http\Controllers\Accounts\AccountGroupController::class, 'store'])->name('finance.account_groups.store');
    Route::get('/account-groups/{id}/edit', [\Modules\Finance\Http\Controllers\Accounts\AccountGroupController::class, 'edit'])->where('id', '[0-9]+')->name('finance.account_groups.edit');
    Route::put('/account-groups/{id}', [\Modules\Finance\Http\Controllers\Accounts\AccountGroupController::class, 'update'])->where('id', '[0-9]+')->name('finance.account_groups.update');
    Route::delete('/account-groups/{id}', [\Modules\Finance\Http\Controllers\Accounts\AccountGroupController::class, 'destroy'])->where('id', '[0-9]+')->name('finance.account_groups.destroy');

    // Legacy Finance-owned Account Book routes kept for old bookmarks.
    Route::match(['GET', 'HEAD', 'POST'], '/account-book', [\Modules\Finance\Http\Controllers\AccountController::class, 'accountBookRedirect'])
        ->name('finance.account_book.redirect');
    Route::get('/account-book/contact-options', [\Modules\Finance\Http\Controllers\AccountController::class, 'accountBookContactOptions'])
        ->name('finance.account_book.contact-options');
    Route::get('/account/cheque-opening-filter-options', [\Modules\Finance\Http\Controllers\AccountController::class, 'chequeOpeningFilterOptions'])
        ->name('finance.account.cheque-opening-filter-options');
    Route::get('/account-book/{id}', [\Modules\Finance\Http\Controllers\AccountController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('finance.account_book.show');
    Route::get('/account-book/{id}/data', [\Modules\Finance\Http\Controllers\AccountController::class, 'accountBookData'])
        ->where('id', '[0-9]+')
        ->name('finance.account_book.data');
    Route::get('/account-book/{id}/balance', [\Modules\Finance\Http\Controllers\AccountController::class, 'getAccountBalance'])
        ->where('id', '[0-9]+')
        ->name('finance.account_book.balance');
    Route::get('/account-book/{id}/main-data', [\Modules\Finance\Http\Controllers\AccountController::class, 'getMainAccountBook'])
        ->where('id', '[0-9]+')
        ->name('finance.main_account_book.data');
    Route::get('/account-book/{id}/main-balance', [\Modules\Finance\Http\Controllers\AccountController::class, 'getAccountBalanceMain'])
        ->where('id', '[0-9]+')
        ->name('finance.main_account_book.balance');

    // Account CRUD used by the List Accounts modal/actions.
    Route::get('/account/create', [StandaloneAccountController::class, 'create'])->name('finance.account.create');
    Route::post('/account', [StandaloneAccountController::class, 'store'])->name('finance.account.store');
    Route::get('/account/{id}/edit', [StandaloneAccountController::class, 'edit'])->where('id', '[0-9]+')->name('finance.account.edit');
    Route::match(['put', 'patch'], '/account/{id}', [StandaloneAccountController::class, 'update'])->where('id', '[0-9]+')->name('finance.account.update');
    Route::get('/account/{id}', [StandaloneAccountController::class, 'show'])->where('id', '[0-9]+')->name('finance.account.show');

    // List Accounts actions and AJAX helpers.
    Route::get('/fund-transfer/{id}', [StandaloneAccountController::class, 'getFundTransfer'])->name('finance.account.fund_transfer.form');
    Route::post('/fund-transfer', [StandaloneAccountController::class, 'postFundTransfer'])->name('finance.account.fund_transfer.store');
    Route::get('/account-number/{id}', [StandaloneAccountController::class, 'getAccNo'])->name('finance.account.number');
    Route::get('/get-parent-account-by-type/{type_id}', [StandaloneAccountController::class, 'getParentAccountsByType'])->name('finance.account.parent_by_type');
    Route::get('/cheque-list', [StandaloneAccountController::class, 'getChequeList'])->name('finance.account.cheque_list');
    Route::get('/cheque-deposit', [StandaloneAccountController::class, 'getChequeDeposit'])->name('finance.account.cheque_deposit.form');
    Route::post('/cheque-deposit', [StandaloneAccountController::class, 'postChequeDeposit'])->name('finance.account.cheque_deposit.store');
    Route::get('/realize-cheque-deposit', [StandaloneAccountController::class, 'getRealizeChequeDeposit'])->name('finance.account.realize_cheque.form');
    Route::get('/realize-cheque-list', [StandaloneAccountController::class, 'getRealizeChequeList'])->name('finance.account.realize_cheque.list');
    Route::post('/realize-cheque-deposit', [StandaloneAccountController::class, 'postRealizeChequeDeposit'])->name('finance.account.realize_cheque.store');
    Route::get('/deposit/{id}', [StandaloneAccountController::class, 'getDeposit'])->name('finance.account.deposit.form');
    Route::post('/deposit', [StandaloneAccountController::class, 'postDeposit'])->name('finance.account.deposit.store');
    Route::get('/notes/{id}', [StandaloneAccountController::class, 'getNotes'])->name('finance.account.notes');
    Route::get('/reconcile/{id}', [StandaloneAccountController::class, 'reconcile'])->name('finance.account.reconcile');
    Route::get('/close/{id}', [StandaloneAccountController::class, 'close'])->name('finance.account.close');
    Route::get('/disabled-status/{id}', [StandaloneAccountController::class, 'disabledStatus'])->name('finance.account.disabled-status');
    Route::get('/check_account_number', [StandaloneAccountController::class, 'checkAccountNumber'])->name('finance.account.check_number');
    Route::post('/check_account_names', [StandaloneAccountController::class, 'getAccountNames'])->name('finance.account.check_names');
    Route::get('/list-deposit-transfer', [StandaloneAccountController::class, 'listDepositTransfer'])->name('finance.account.deposit_transfer.list');
    Route::get('/cheques-ob-details', [StandaloneAccountController::class, 'chequeObTransfer'])->name('finance.account.cheques_ob');
    Route::get('/get-account-balance/{id}', [StandaloneAccountController::class, 'getAccountBalance'])->name('finance.account.balance');
    Route::get('/main-account-book/{id}', [StandaloneAccountController::class, 'getMainAccountBook'])->name('finance.account.main_book');
    Route::get('/main-account-balance/{id}', [StandaloneAccountController::class, 'getAccountBalanceMain'])->name('finance.account.main_balance');

    Route::get('/account-settings/default-date-range', [\App\Http\Controllers\AccountSettingController::class, 'getDefaultDateRange'])->name('finance.account.settings.default_date_range.get');
    Route::post('/account-settings/default-date-range', [\App\Http\Controllers\AccountSettingController::class, 'saveDefaultDateRange'])->name('finance.account.settings.default_date_range.save');
});

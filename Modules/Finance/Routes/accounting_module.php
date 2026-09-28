<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\AccountController;
use Modules\Finance\Http\Controllers\Accounts\DisabledAccountController;
use Modules\Finance\Http\Controllers\Accounts\ListDepositTransferController;
use Modules\Finance\Http\Controllers\Accounts\AccountTypeController;
use Modules\Finance\Http\Controllers\Accounts\AccountGroupController;
use Modules\Finance\Http\Controllers\Settings\AccountSettingController;

/*
|--------------------------------------------------------------------------
| Finance Standalone Accounting Module Routes
|--------------------------------------------------------------------------
| These routes keep the existing live URL (/accounting-module/account) but
| serve the Account pages from Modules/Finance only. Do not place these
| routes in the main routes/web.php or routes/tenant.php files.
*/

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])
    ->prefix('finance')
    ->group(function () {
        // 15 Sep 2026: collision-proof List Accounts entry point.
        // Some installations still have a compiled legacy /finance/account route
        // that returns 404 before the module's replacement route can win. This
        // unique URI is owned only by Finance and is safe for both master/system
        // businesses and separately-tenanted businesses.
        Route::match(['GET', 'HEAD'], '/list-accounts-live', [AccountController::class, 'index'])
            ->name('finance.list-accounts.live');

        /*
         * 17 Sep 2026 - collision-proof Account Book workflow.
         *
         * Older route caches can still own /finance/account-book/{id} with the
         * tenant-only PreventAccessFromCentralDomains middleware.  That route
         * deliberately returns 404 for businesses which live in the central
         * database.  Keep the complete Account Book workflow below the unique
         * List Accounts URI so both the initial page and every AJAX request use
         * this Finance-owned, mixed central/tenant route group.
         */
        Route::match(['GET', 'HEAD', 'POST'], '/list-accounts-live/account-book', [AccountController::class, 'accountBookRedirect'])
            ->name('finance.list-accounts.live.account_book.redirect');
        Route::get('/list-accounts-live/account-book/contact-options', [AccountController::class, 'accountBookContactOptions'])
            ->name('finance.list-accounts.live.account_book.contact-options');
        Route::get('/list-accounts-live/account-book/{id}', [AccountController::class, 'show'])
            ->where('id', '[0-9]+')
            ->name('finance.list-accounts.live.account_book.show');
        Route::get('/list-accounts-live/account-book/{id}/data', [AccountController::class, 'accountBookData'])
            ->where('id', '[0-9]+')
            ->name('finance.list-accounts.live.account_book.data');
        Route::get('/list-accounts-live/account-book/{id}/balance', [AccountController::class, 'getAccountBalance'])
            ->where('id', '[0-9]+')
            ->name('finance.list-accounts.live.account_book.balance');
        Route::get('/list-accounts-live/account-book/{id}/main-data', [AccountController::class, 'getMainAccountBook'])
            ->where('id', '[0-9]+')
            ->name('finance.list-accounts.live.main_account_book.data');
        Route::get('/list-accounts-live/account-book/{id}/main-balance', [AccountController::class, 'getAccountBalanceMain'])
            ->where('id', '[0-9]+')
            ->name('finance.list-accounts.live.main_account_book.balance');


        /*
         * 15 Sep 2026 - List Accounts live auxiliary endpoints.
         *
         * The collision-proof List Accounts page must not fall back to legacy
         * /finance/settings or /finance/account-groups/data routes.  Some older
         * route caches still point those URIs at core/legacy controllers and the
         * page then loads while its DataTables fail with Ajax errors.  Keep the
         * whole Account Groups + Account Settings AJAX workflow under the same
         * unique Finance-owned URI namespace as the page itself.
         */
        Route::get('/list-accounts-live/account-groups-data', [AccountGroupController::class, 'data'])
            ->name('finance.list-accounts.live.account-groups.data');
        Route::get('/list-accounts-live/account-groups/create', [AccountGroupController::class, 'create'])
            ->name('finance.list-accounts.live.account-groups.create');
        Route::post('/list-accounts-live/account-groups', [AccountGroupController::class, 'store'])
            ->name('finance.list-accounts.live.account-groups.store');
        Route::get('/list-accounts-live/account-groups/{id}/edit', [AccountGroupController::class, 'edit'])
            ->where('id', '[0-9]+')
            ->name('finance.list-accounts.live.account-groups.edit');
        Route::put('/list-accounts-live/account-groups/{id}', [AccountGroupController::class, 'update'])
            ->where('id', '[0-9]+')
            ->name('finance.list-accounts.live.account-groups.update');
        Route::delete('/list-accounts-live/account-groups/{id}', [AccountGroupController::class, 'destroy'])
            ->where('id', '[0-9]+')
            ->name('finance.list-accounts.live.account-groups.destroy');

        Route::get('/list-accounts-live/settings-data', [AccountSettingController::class, 'data'])
            ->name('finance.list-accounts.live.settings.data');
        Route::post('/list-accounts-live/settings', [AccountSettingController::class, 'store'])
            ->name('finance.list-accounts.live.settings.store');
        Route::get('/list-accounts-live/settings/{id}/edit', [AccountSettingController::class, 'edit'])
            ->where('id', '[0-9]+')
            ->name('finance.list-accounts.live.settings.edit');
        Route::put('/list-accounts-live/settings/{id}', [AccountSettingController::class, 'update'])
            ->where('id', '[0-9]+')
            ->name('finance.list-accounts.live.settings.update');
        Route::delete('/list-accounts-live/settings/{id}', [AccountSettingController::class, 'destroy'])
            ->where('id', '[0-9]+')
            ->name('finance.list-accounts.live.settings.destroy');
        Route::get('/list-accounts-live/default-date-range', [AccountSettingController::class, 'getDefaultDateRange'])
            ->name('finance.list-accounts.live.settings.default-date-range.show');
        Route::post('/list-accounts-live/default-date-range', [AccountSettingController::class, 'saveDefaultDateRange'])
            ->name('finance.list-accounts.live.settings.default-date-range.store');
        Route::get('/list-accounts-live/account-numbers', [AccountSettingController::class, 'getAccountNumbers'])
            ->name('finance.list-accounts.live.settings.account-numbers.show');
        Route::post('/list-accounts-live/account-numbers', [AccountSettingController::class, 'saveAccountNumbers'])
            ->name('finance.list-accounts.live.settings.account-numbers.store');

        /*
         * List Accounts Cheque Deposit endpoints.
         *
         * Keep the popup, its rows, its filter options and its save action in the
         * same collision-proof namespace as the page which owns them. Several
         * installations still have older Finance/core cheque routes in route
         * cache; using these unique URIs prevents an otherwise valid request from
         * reaching a legacy controller or a different tenant middleware stack.
         */
        Route::get('/list-accounts-live/cheque-deposit', [AccountController::class, 'getChequeDeposit'])
            ->name('finance.list-accounts.live.cheque-deposit.form');
        Route::post('/list-accounts-live/cheque-deposit', [AccountController::class, 'postChequeDeposit'])
            ->name('finance.list-accounts.live.cheque-deposit.store');
        Route::get('/list-accounts-live/cheque-list', [AccountController::class, 'getChequeList'])
            ->name('finance.list-accounts.live.cheque-list');
        Route::get('/list-accounts-live/cheque-deposit/filter-options', [AccountController::class, 'getChequeDepositFilterOptions'])
            ->name('finance.list-accounts.live.cheque-deposit.filter-options');


        /*
         * 15 Sep 2026 - Cheques in Hand opening-balance live endpoints.
         * Keep this workflow under the collision-proof List Accounts namespace.
         * Some older installations have a legacy /finance/cheques-ob-details
         * route/controller in route cache; using a unique URI avoids that route.
         */
        Route::get('/list-accounts-live/cheques-ob-details', [AccountController::class, 'chequeObTransfer'])
            ->name('finance.list-accounts.live.cheques-ob-details');
        Route::get('/list-accounts-live/cheque-opening-filter-options', [AccountController::class, 'chequeOpeningFilterOptions'])
            ->name('finance.list-accounts.live.cheque-opening-filter-options');
        Route::get('/list-accounts-live/cheque-opening/{id}/edit', [AccountController::class, 'editChequeOb'])
            ->where('id', '[0-9]+')
            ->name('finance.list-accounts.live.cheque-opening.edit');
        Route::post('/list-accounts-live/cheque-opening/{id}', [AccountController::class, 'updateChequeOb'])
            ->where('id', '[0-9]+')
            ->name('finance.list-accounts.live.cheque-opening.update');
        Route::delete('/list-accounts-live/cheque-opening/{id}', [AccountController::class, 'deleteChequeOb'])
            ->where('id', '[0-9]+')
            ->name('finance.list-accounts.live.cheque-opening.delete');

        Route::get('/check-insufficient-balance-for-accounts', [AccountController::class, 'getAccsForWhichToCheckInsufficientBalances']);
        Route::get('/get-profit-loss-report', [AccountController::class, 'getProfitLossReport']);
        Route::delete('/delete-account-transaction/{transaction_id}', [AccountController::class, 'deleteAccountTransaction']);
        Route::post('/update-account-transaction/{transaction_id}', [AccountController::class, 'updateAccountTransaction']);
        Route::get('/eidt-account-transaction/{transaction_id}', [AccountController::class, 'editAccountTransaction']);
        Route::get('/get-account-dp', [AccountController::class, 'getBankAccountDropDown']);
        Route::get('/get-account-group-name-dp', [AccountController::class, 'getBankAccountByGroupDP']);
        // S673: {group_id?} is optional. A dependent dropdown reset to
        // "Please Select" posts an empty value, and a required parameter
        // then produced a 404 that surfaced to the user as "Failed".
        Route::get('/get-account-by-group-id/{group_id?}', [AccountController::class, 'getAccountByGroupId']);

        Route::get('/get-account-group-by-account/{type_id}', [AccountController::class, 'getAccountGroupByAccount']);
        // S724 #3: Finance-owned Add Account group lookup. Avoid the root
        // /get-account-groups endpoint, whose route/controller can vary by module load order.
        Route::get('/account-groups/by-type/{type_id}', [AccountController::class, 'getAccountGroupsByType'])
            ->where('type_id', '[0-9]+')
            ->name('finance.account.account-groups-by-type');
        Route::get('/get-parent-account-by-type/{type_id}', [AccountController::class, 'getParentAccountsByType']);
        Route::get('/account/image-modal', [AccountController::class, 'imageModal']);

        Route::get('/account/fix-sales-accounts', [AccountController::class, 'fixDecemberSalesAccounts'])->name('finance.accounts.fixDecemberSalesAccounts');
        Route::get('/account/fix-sales-accounts/{id}', [AccountController::class, 'updateDecemberSalesAccounts'])->name('finance.accounts.updateDecemberSalesAccounts');
        Route::get('/account/correct-sale-income-accounts-tax', [AccountController::class, 'correctSaleIncomeAccountsTax'])->name('finance.accounts.correctSaleIncomeAccountsTax');
        Route::get('/account/correct-sale-income-accounts-tax/{id}', [AccountController::class, 'updateSaleIncomeAccountsTax'])->name('finance.accounts.updateSaleIncomeAccountsTax');
        Route::get('/correct-sell-lines-tax', [AccountController::class, 'correctSellLinesTax'])->name('finance.accounts.correctSellLinesTax');
        Route::get('/update-sell-lines-tax', [AccountController::class, 'updateSellLinesTax'])->name('finance.accounts.updateSellLinesTax');
        Route::get('/correct-sell-lines-decimal-difference', [AccountController::class, 'correctSellLinesDecimalDifference'])->name('finance.accounts.correctSellLinesDecimalDifference');
        Route::get('/update-sell-lines-decimal-difference', [AccountController::class, 'updateSellLinesDecimalDifference'])->name('finance.accounts.updateSellLinesDecimalDifference');
        Route::get('/account/correct-cogs-accounts-tax', [AccountController::class, 'correctCOGSAccountsTax'])->name('finance.accounts.correctCOGSAccountsTax');
        Route::get('/account/correct-cogs-accounts-tax/{id}', [AccountController::class, 'updateCOGSAccountsTax'])->name('finance.accounts.updateCOGSAccountsTax');
        Route::get('/account/update-accounts-receivable-settlement-customer-payment-to-credit', [AccountController::class, 'getAccountsReceivableSettlementCustomerPaymentToCredit'])->name('finance.accounts.getAccountsReceivableSettlementCustomerPaymentToCredit');
        Route::get('/account/update-accounts-receivable-settlement-customer-payment-to-credit/{id}', [AccountController::class, 'updateAccountsReceivableSettlementCustomerPaymentToCredit'])->name('finance.accounts.updateAccountsReceivableSettlementCustomerPaymentToCredit');
        Route::get('/account/update-finished-goods-account-pos-sale-tax', [AccountController::class, 'getFinishedGoodsAccountPosSaleTax'])->name('finance.accounts.getFinishedGoodsAccountPosSaleTax');
        Route::get('/account/update-finished-goods-account-pos-sale-tax/{id}', [AccountController::class, 'updateFinishedGoodsAccountPosSaleTax'])->name('finance.accounts.updateFinishedGoodsAccountPosSaleTax');
        Route::get('/account/update-cash-account-pos-sale-tax', [AccountController::class, 'getCashAccountPosSaleTax'])->name('finance.accounts.getCashAccountPosSaleTax');
        Route::get('/account/update-cash-account-pos-sale-tax/{id}', [AccountController::class, 'updateCashAccountPosSaleTax'])->name('finance.accounts.updateCashAccountPosSaleTax');
        Route::get('/account/correct-accounts-discount', [AccountController::class, 'correctAccountsProductWiseDiscount'])->name('finance.accounts.correctAccountsProductWiseDiscount');
        Route::get('/account/update-accounts-discount/{id}', [AccountController::class, 'updateAccountsProductWiseDiscount'])->name('finance.accounts.updateAccountsProductWiseDiscount');

        // Finance-owned Account Type endpoints used by the List Accounts tabs.
        Route::get('/account-types/create', [AccountTypeController::class, 'create'])->name('finance.account_types.create');
        Route::post('/account-types', [AccountTypeController::class, 'store'])->name('finance.account_types.store');
        Route::get('/account-types/{id}/edit', [AccountTypeController::class, 'edit'])->where('id', '[0-9]+')->name('finance.account_types.edit');
        Route::put('/account-types/{id}', [AccountTypeController::class, 'update'])->where('id', '[0-9]+')->name('finance.account_types.update');
        Route::delete('/account-types/{id}', [AccountTypeController::class, 'destroy'])->where('id', '[0-9]+')->name('finance.account_types.destroy');

        // Finance-owned Account Group endpoints used by the List Accounts tabs.
        Route::get('/account-groups/data', [AccountGroupController::class, 'data'])->name('finance.account_groups.data');
        Route::get('/account-groups/create', [AccountGroupController::class, 'create'])->name('finance.account_groups.create');
        Route::post('/account-groups', [AccountGroupController::class, 'store'])->name('finance.account_groups.store');
        Route::get('/account-groups/{id}/edit', [AccountGroupController::class, 'edit'])->where('id', '[0-9]+')->name('finance.account_groups.edit');
        Route::put('/account-groups/{id}', [AccountGroupController::class, 'update'])->where('id', '[0-9]+')->name('finance.account_groups.update');
        Route::delete('/account-groups/{id}', [AccountGroupController::class, 'destroy'])->where('id', '[0-9]+')->name('finance.account_groups.destroy');

        // Standalone Finance List Deposits & Transfers AJAX report.
        // Keep before resource and legacy-compatible paths.
        /*
         | Removed when this group moved to the /finance prefix.
         | Modules/Finance/Routes/deposits.php already registers
         | /finance/list-deposit-transfer against
         | AccountController@listDepositTransfer. Two handlers on one path
         | means whichever route file loads first wins, silently - the exact
         | problem this migration exists to remove.
         | /finance/account/list-deposit-transfer below is unaffected.
        */
        Route::get('/account/list-deposit-transfer', [ListDepositTransferController::class, 'index'])->name('finance.account.list-deposit-transfer');

        /*
         * Finance-owned Account Book URLs.
         *
         * Do not use /account/{id} here because the legacy application registers
         * the same URI in root route files. These unique paths always resolve to
         * Modules\Finance\Http\Controllers\AccountController and Finance views.
         */
        // GET fallback prevents old/cached Account Book buttons from resolving
        // to an unrelated POST-only route when the account id is missing.
        Route::match(['GET', 'HEAD', 'POST'], '/account-book', [AccountController::class, 'accountBookRedirect'])
            ->name('finance.account_book.redirect');
        Route::get('/account-book/contact-options', [AccountController::class, 'accountBookContactOptions'])
            ->name('finance.account_book.contact-options');
        Route::get('/account/cheque-opening-filter-options', [AccountController::class, 'chequeOpeningFilterOptions'])
            ->name('finance.account.cheque-opening-filter-options');
        Route::get('/account-book/{id}', [AccountController::class, 'show'])
            ->where('id', '[0-9]+')
            ->name('finance.account_book.show');
        Route::get('/account-book/{id}/data', [AccountController::class, 'accountBookData'])
            ->where('id', '[0-9]+')
            ->name('finance.account_book.data');
        Route::get('/account-book/{id}/balance', [AccountController::class, 'getAccountBalance'])
            ->where('id', '[0-9]+')
            ->name('finance.account_book.balance');
        Route::get('/account-book/{id}/main-data', [AccountController::class, 'getMainAccountBook'])
            ->where('id', '[0-9]+')
            ->name('finance.main_account_book.data');
        Route::get('/account-book/{id}/main-balance', [AccountController::class, 'getAccountBalanceMain'])
            ->where('id', '[0-9]+')
            ->name('finance.main_account_book.balance');

        // Dedicated Finance-owned JSON endpoint for the Account Book DataTable.
        // The legacy application also registers /accounting-module/account/{id}; using
        // this unique endpoint prevents the browser from receiving the legacy controller's
        // incompatible row schema (for example, rows without realize_date).
        Route::get('/account/{id}/book-data', [AccountController::class, 'accountBookData'])
            ->where('id', '[0-9]+')
            ->name('finance.account.book-data');

        /*
         * Dedicated Finance-owned account edit endpoints.
         *
         * The root application also registers the resource-style account edit/update
         * URLs.  These unique paths prevent a cached/legacy route from handling the
         * Finance modal request or update submission.
         */
        Route::get('/account/edit-form/{id}', [AccountController::class, 'edit'])
            ->where('id', '[0-9]+')
            ->name('finance.account.edit-form');
        Route::put('/account/edit-form/{id}', [AccountController::class, 'update'])
            ->where('id', '[0-9]+')
            ->name('finance.account.update-form');

        Route::resource('/account', AccountController::class)->names('finance.account');

        /*
         * Unique Finance-owned deposit/transfer endpoints.
         *
         * The root application also has legacy /accounting-module routes with the
         * same old paths.  These unique URIs guarantee that the List Accounts
         * modal, cheque rows and save actions always reach this Finance module,
         * even when route loading order differs between servers.
         */
        /*
         * S-627 #4: the List Accounts toolbar Transfer button.
         *
         * Declared BEFORE the {id} form route. It is a distinct URI rather
         * than an optional {id?} parameter so that the existing named route
         * 'finance.account.fund-transfer.form' keeps requiring an id - every
         * row-level Action menu builds its url from that name, and making the
         * parameter optional there would let a missing id pass silently.
         *
         * The POST on this same URI is the store action below; different verb,
         * no collision.
         */
        Route::get('/finance-fund-transfer', [AccountController::class, 'getFundTransfer'])
            ->name('finance.account.fund-transfer.select');

        Route::get('/finance-fund-transfer/{id}', [AccountController::class, 'getFundTransfer'])
            ->where('id', '[0-9]+')
            ->name('finance.account.fund-transfer.form');
        Route::post('/finance-fund-transfer', [AccountController::class, 'postFundTransfer'])
            ->name('finance.account.fund-transfer.store');

        Route::get('/finance-cheque-list', [AccountController::class, 'getChequeList'])
            ->name('finance.account.cheque-list');
        Route::get('/finance-cheque-deposit/filter-options', [AccountController::class, 'getChequeDepositFilterOptions'])
            ->name('finance.account.cheque-deposit.filter-options');
        Route::get('/finance-cheque-deposit', [AccountController::class, 'getChequeDeposit'])
            ->name('finance.account.cheque-deposit.form');
        Route::post('/finance-cheque-deposit', [AccountController::class, 'postChequeDeposit'])
            ->name('finance.account.cheque-deposit.store');

        /*
         * IS2207 #4: Finance-owned Cheques to Realize endpoints.
         *
         * Keep these on unique URIs for the List Accounts modal. The legacy
         * aliases below remain for old bookmarks, but the live Finance view no
         * longer depends on route-loading order or another module registering
         * the same historical path first.
         */
        Route::get('/finance-realize-cheque-deposit', [AccountController::class, 'getRealizeChequeDeposit'])
            ->name('finance.account.realize-cheque-deposit.form');
        Route::get('/finance-realize-cheque-list', [AccountController::class, 'getRealizeChequeList'])
            ->name('finance.account.realize-cheque-list');
        Route::post('/finance-realize-cheque-deposit', [AccountController::class, 'postRealizeChequeDeposit'])
            ->name('finance.account.realize-cheque-deposit.store');

        Route::get('/finance-deposit/{id}', [AccountController::class, 'getDeposit'])
            // Cash/Card toolbar deposits intentionally pass a symbolic type;
            // row-level deposits still pass a numeric account id.
            ->where('id', 'cash|card|[0-9]+')
            ->name('finance.account.deposit.form');
        Route::post('/finance-deposit', [AccountController::class, 'postDeposit'])
            ->name('finance.account.deposit.store');

        // Backward-compatible legacy aliases.  New Finance views do not submit
        // to these aliases, so a legacy root route cannot intercept the request.
        Route::get('/fund-transfer/{id}', [AccountController::class, 'getFundTransfer']);
        Route::get('/account-number/{id}', [AccountController::class, 'getAccNo']);
        Route::post('/fund-transfer', [AccountController::class, 'postFundTransfer']);
        Route::get('/cheque-list', [AccountController::class, 'getChequeList']);
        Route::post('/cheque-deposit', [AccountController::class, 'postChequeDeposit']);
        Route::get('/cheque-deposit', [AccountController::class, 'getChequeDeposit']);
        Route::get('/realize-cheque-deposit', [AccountController::class, 'getRealizeChequeDeposit']);
        Route::get('/realize-cheque-list', [AccountController::class, 'getRealizeChequeList']);
        Route::post('/realize-cheque-deposit', [AccountController::class, 'postRealizeChequeDeposit']);
        Route::get('/deposit/{id}', [AccountController::class, 'getDeposit']);
        Route::post('/deposit', [AccountController::class, 'postDeposit']);
        Route::get('/notes/{id}', [AccountController::class, 'getNotes'])->name('finance.account.notes');
        Route::get('/reconcile/{id}', [AccountController::class, 'reconcile'])->name('finance.account.reconcile');
        Route::get('/close/{id}', [AccountController::class, 'close'])->name('finance.account.close');
        Route::get('/disabled-account', [DisabledAccountController::class, 'index'])->name('finance.account.disabled');
        Route::get('/disabled-status/{id}', [AccountController::class, 'disabledStatus'])->name('finance.account.disabled-status');
        Route::get('/delete-account-transaction/{id}', [AccountController::class, 'destroyAccountTransaction']);
        Route::get('/get-account-balance/{id}', [AccountController::class, 'getAccountBalance']);
        Route::get('/get-description/{id}', [AccountController::class, 'getDescription']);
        Route::get('/check_account_number', [AccountController::class, 'checkAccountNumber']);
        Route::post('/check_account_names', [AccountController::class, 'getAccountNames']);
        Route::get('/cash-flow', [AccountController::class, 'cashFlow'])->name('finance.account.cash-flow');
        Route::post('/import', [AccountController::class, 'postImportAccounts'])->name('finance.accounts.import.post');
        Route::get('/import', [AccountController::class, 'getImportAccounts'])->name('finance.accounts.import');
        Route::get('/edit-cheque-ob/{id}', [AccountController::class, 'editChequeOb'])->name('finance.accounts.edit.cheque.ob');
        Route::delete('/delete-cheque-ob/{id}', [AccountController::class, 'deleteChequeOb'])->name('finance.accounts.delete.cheque.ob');
        Route::post('/edit-cheque-ob/{id}', [AccountController::class, 'updateChequeOb'])->name('finance.accounts.update.cheque.ob');
        Route::get('/cheques-ob-details', [AccountController::class, 'chequeObTransfer'])->name('finance.accounts.cheque-ob-details');
        Route::get('/main-account-book/{id}', [AccountController::class, 'getMainAccountBook']);
        Route::get('/main-account-balance/{id}', [AccountController::class, 'getAccountBalanceMain']);
        Route::get('/edit-deposit-transfer/{id}', [AccountController::class, 'editDepositTransfer']);
        Route::post('/update-deposit-transfer/{id}', [AccountController::class, 'updateDepositTransfer']);
    });

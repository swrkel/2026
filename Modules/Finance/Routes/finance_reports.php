<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MA-002: Finance report routes now REDIRECT to the FinanceReports module
|--------------------------------------------------------------------------
|
| WHY THIS FILE CHANGED
|
| This file previously registered its report pages under the prefix
| 'finance-reports' - the SAME prefix the FinanceReports module uses. Both
| modules are priority 0, and 'Finance' sorts before 'FinanceReports', so
| Finance registered first and Laravel matched its routes first.
|
| It also registered a catch-all inside that prefix:
|
|     Route::get('/{report}', ...)->where('report', '[A-Za-z0-9_-]+');
|
| 'trial-balance-new' matches that pattern, so every one of the 92 URLs
| belonging to the FinanceReports module was being captured here and served
| by Finance's legacyPage() instead. The new module was effectively
| unreachable.
|
| FinanceReports is now the single source of truth for reports, so:
|
|   1. Finance no longer registers ANYTHING under the 'finance-reports'
|      prefix. That prefix belongs entirely to the FinanceReports module.
|
|   2. Every finance.reports.* route NAME is preserved. There are 72
|      references to those names across menus, views and controllers; they
|      all keep working. The names now live under the 'finance/reports'
|      prefix and redirect to the FinanceReports equivalent.
|
|   3. The old compatibility prefixes ('finance/reports', 'finance-report',
|      'finance_reports') also redirect, so stored menu URLs and bookmarks
|      keep working.
|
| DESIGN NOTES
|
|   Route::redirect() is used rather than closures because closures cannot be
|   serialised by 'php artisan route:cache'. These redirects are cacheable.
|
|   302 (temporary), NOT 301. A 301 is cached by browsers indefinitely and
|   would be painful to undo if you ever want Finance's reports back. 302
|   keeps this fully reversible - restore this one file and the old pages
|   return.
|
|   Nothing is deleted. Finance's report controllers, services and views are
|   all still present; they are simply no longer routed. Deleting them is a
|   later step, once you have compared the two engines' numbers.
|
| TARGET SLUGS
|   Verified present in Modules/FinanceReports/Routes/web.php before mapping.
|   day-book and cash-flow-statement exist only as '-new' there, so those two
|   map to the -new slug. The rest map to the plain slug.
*/

$financeReportMiddleware = ['web', 'auth', 'SetSessionData', 'language', 'timezone'];

/*
|--------------------------------------------------------------------------
| Canonical named routes - preserved, now redirecting
|--------------------------------------------------------------------------
| Registered under 'finance/reports' so the 'finance-reports' prefix is left
| free for the FinanceReports module.
*/
Route::middleware($financeReportMiddleware)
    ->prefix('finance/reports')
    ->name('finance.reports.')
    ->group(function () {
        Route::redirect('/', '/finance-reports', 302)->name('dashboard');
        Route::redirect('/dashboard', '/finance-reports', 302)->name('dashboard.page');

        Route::redirect('/trial-balance', '/finance-reports/trial-balance', 302)
            ->name('trial_balance');

        Route::redirect('/general-ledger', '/finance-reports/general-ledger', 302)
            ->name('general_ledger');
        Route::redirect('/general-ledger/account/{account_id}', '/finance-reports/general-ledger', 302)
            ->name('general_ledger.account');

        Route::redirect('/account-ledger', '/finance-reports/account-ledger', 302)
            ->name('account_ledger');
        Route::redirect('/account-ledger/{accountId}', '/finance-reports/account-ledger', 302)
            ->name('account_ledger.account');

        // FinanceReports publishes these two only under the -new slug.
        Route::redirect('/day-book', '/finance-reports/day-book-new', 302)
            ->name('day_book');
        Route::redirect('/cash-flow-statement', '/finance-reports/cash-flow-statement-new', 302)
            ->name('cash_flow_statement');

        Route::redirect('/income-statement', '/finance-reports/income-statement', 302)
            ->name('income_statement');
        Route::redirect('/balance-sheet', '/finance-reports/balance-sheet', 302)
            ->name('balance_sheet');
        Route::redirect('/profit-loss', '/finance-reports/profit-loss', 302)
            ->name('profit_loss');

        /*
         * Catch-all for old underscore slugs such as trial_balance.
         * Declared LAST so every explicit route above wins, and scoped to this
         * prefix only - it can no longer capture FinanceReports URLs.
         */
        Route::redirect('/{report}', '/finance-reports', 302)
            ->where('report', '[A-Za-z0-9_-]+')
            ->name('legacy');
    });

/*
|--------------------------------------------------------------------------
| Compatibility prefixes from earlier menu builds
|--------------------------------------------------------------------------
*/
Route::middleware($financeReportMiddleware)
    ->prefix('finance-report')
    ->group(function () {
        Route::redirect('/', '/finance-reports', 302)->name('finance.report.compat.index');
        Route::redirect('/{report}', '/finance-reports', 302)
            ->where('report', '[A-Za-z0-9_-]+')
            ->name('finance.report.compat.legacy');
    });

Route::middleware($financeReportMiddleware)
    ->prefix('finance_reports')
    ->group(function () {
        Route::redirect('/', '/finance-reports', 302)->name('finance.reports.underscore.index');
        Route::redirect('/{report}', '/finance-reports', 302)
            ->where('report', '[A-Za-z0-9_-]+')
            ->name('finance.reports.underscore.legacy');
    });

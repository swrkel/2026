<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Loan Module Routes - LOAN-36 Final Cleanup & Optimization
|--------------------------------------------------------------------------
|
| Purpose:
| - Keep the Loan Module standalone and clean.
| - Remove temporary/test recovery routes.
| - Remove obsolete governance routes from the final testing sidebar cycle.
| - Keep only production Loan Module routes required for UAT.
|
*/

Route::group([
    'middleware' => [
        'web',
        'auth',
        'SetSessionData',
        'language',
        'timezone',
        'tenant.context',
    ],
    'prefix' => 'loan',
], function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/', 'DashboardController@index')->name('loan.dashboard.root');
    Route::get('dashboard', 'DashboardController@index')->name('loan.dashboard');

    /*
    |--------------------------------------------------------------------------
    | Loan Setup
    |--------------------------------------------------------------------------
    */

    Route::get('loan-settings', 'LoanSettingsController@index')->name('loan.settings.index');
    Route::post('loan-settings/update', 'LoanSettingsController@update')->name('loan.settings.update');

    Route::prefix('loan-categories')->group(function () {
        Route::post('store', 'LoanCategoryController@store')->name('loan.categories.store');
        Route::post('{id}/update', 'LoanCategoryController@update')->name('loan.categories.update');
        Route::post('{id}/delete', 'LoanCategoryController@destroy')->name('loan.categories.delete');
    });

    Route::prefix('loan-fees')->group(function () {
        Route::post('store', 'LoanFeeController@store')->name('loan.fees.store');
        Route::post('{id}/update', 'LoanFeeController@update')->name('loan.fees.update');
        Route::post('{id}/delete', 'LoanFeeController@destroy')->name('loan.fees.delete');
    });

    Route::prefix('loan-penalties')->group(function () {
        Route::post('store', 'LoanPenaltyController@store')->name('loan.penalties.store');
        Route::post('{id}/update', 'LoanPenaltyController@update')->name('loan.penalties.update');
        Route::post('{id}/delete', 'LoanPenaltyController@destroy')->name('loan.penalties.delete');
    });

    /*
    |--------------------------------------------------------------------------
    | Loan Customers
    |--------------------------------------------------------------------------
    */

    Route::prefix('customers')->group(function () {
        Route::get('/', 'LoanCustomerController@index')->name('loan.customers.index');
        Route::get('create', 'LoanCustomerController@create')->name('loan.customers.create');
        Route::post('store', 'LoanCustomerController@store')->name('loan.customers.store');
        Route::get('{id}/show', 'LoanCustomerController@show')->name('loan.customers.show');
        Route::get('{id}/edit', 'LoanCustomerController@edit')->name('loan.customers.edit');
        Route::post('{id}/update', 'LoanCustomerController@update')->name('loan.customers.update');
        Route::post('{id}/delete', 'LoanCustomerController@destroy')->name('loan.customers.delete');
    });

    /*
    |--------------------------------------------------------------------------
    | Loan Products
    |--------------------------------------------------------------------------
    */

    Route::prefix('loan-products')->group(function () {
        Route::get('/', 'LoanProductController@index')->name('loan.products.index');
        Route::get('create', 'LoanProductController@create')->name('loan.products.create');
        Route::post('store', 'LoanProductController@store')->name('loan.products.store');
        Route::get('{id}/show', 'LoanProductController@show')->name('loan.products.show');
        Route::get('{id}/edit', 'LoanProductController@edit')->name('loan.products.edit');
        Route::post('{id}/update', 'LoanProductController@update')->name('loan.products.update');
        Route::post('{id}/activate', 'LoanProductController@activate')->name('loan.products.activate');
        Route::post('{id}/deactivate', 'LoanProductController@deactivate')->name('loan.products.deactivate');
        Route::post('{id}/duplicate', 'LoanProductController@duplicate')->name('loan.products.duplicate');
        Route::post('{id}/delete', 'LoanProductController@destroy')->name('loan.products.delete');
    });


    /*
    |--------------------------------------------------------------------------
    | Consistent Approval Queue
    |--------------------------------------------------------------------------
    */

    Route::prefix('approval-queue')->group(function () {
        Route::get('/', 'LoanApprovalQueueController@index')->name('loan.approval_queue.index');
        Route::post('{id}/submit', 'LoanApprovalQueueController@submit')->name('loan.approval_queue.submit');
        Route::post('{id}/review', 'LoanApprovalQueueController@review')->name('loan.approval_queue.review');
        Route::post('{id}/approve', 'LoanApprovalQueueController@approve')->name('loan.approval_queue.approve');
        Route::post('{id}/reject', 'LoanApprovalQueueController@reject')->name('loan.approval_queue.reject');
    });

    /*
    |--------------------------------------------------------------------------
    | Loan Applications & Approval Workflow
    |--------------------------------------------------------------------------
    */

    Route::prefix('loan-applications')->group(function () {
        Route::get('/', 'LoanApplicationController@index')->name('loan.applications.index');
        Route::get('create', 'LoanApplicationController@create')->name('loan.applications.create');
        Route::post('store', 'LoanApplicationController@store')->name('loan.applications.store');
        Route::get('{id}/show', 'LoanApplicationController@show')->name('loan.applications.show');
        Route::get('{id}/edit', 'LoanApplicationController@edit')->name('loan.applications.edit');
        Route::post('{id}/update', 'LoanApplicationController@update')->name('loan.applications.update');
        Route::post('{id}/approve', 'LoanApplicationController@approve')->name('loan.applications.approve');
        Route::post('{id}/reject', 'LoanApplicationController@reject')->name('loan.applications.reject');
        Route::post('{id}/disburse', 'LoanApplicationController@disburse')->name('loan.applications.disburse');
    });

    /*
    |--------------------------------------------------------------------------
    | Active Loans / Customer Loan Ledger
    |--------------------------------------------------------------------------
    */

    Route::prefix('loans')->group(function () {
        Route::get('/', 'LoanController@index')->name('loan.loans.index');
        Route::get('{id}/show', 'LoanController@show')->name('loan.loans.show');
        Route::get('{id}/ledger', 'LoanLedgerController@show')->name('loan.ledger.show');
        Route::post('{id}/write-off', 'LoanController@writeOff')->name('loan.loans.write_off');
    });

    /*
    |--------------------------------------------------------------------------
    | Disbursement Workflow
    |--------------------------------------------------------------------------
    */

    Route::prefix('disbursements')->group(function () {
        Route::get('/', 'LoanDisbursementController@index')->name('loan.disbursements.index');
        Route::get('{id}/show', 'LoanDisbursementController@show')->name('loan.disbursements.show');
        Route::post('{id}/approve', 'LoanDisbursementController@approve')->name('loan.disbursements.approve');
        Route::post('{id}/reject', 'LoanDisbursementController@reject')->name('loan.disbursements.reject');
        Route::post('{id}/post-accounting', 'LoanDisbursementController@postAccounting')->name('loan.disbursements.post_accounting');
    });

    /*
    |--------------------------------------------------------------------------
    | Repayment Processing
    |--------------------------------------------------------------------------
    */

    Route::prefix('repayments')->group(function () {
        Route::get('/', 'LoanRepaymentManagementController@index')->name('loan.repayments.index');
        Route::post('store', 'LoanRepaymentController@store')->name('loan.repayments.store');
        Route::get('{id}/receipt', 'LoanRepaymentController@receipt')->name('loan.repayments.receipt');
    });

    /*
    |--------------------------------------------------------------------------
    | Collections & Recovery Management
    |--------------------------------------------------------------------------
    */

    Route::get('collections/dashboard', 'CollectionsDashboardController@index')->name('loan.collections.dashboard');
    Route::post('loan-collections/store', 'LoanCollectionController@store')->name('loan.collections.store');

    Route::prefix('promise-to-pay')->group(function () {
        Route::get('/', 'LoanPromiseToPayController@index')->name('loan.promise_to_pay.index');
        Route::get('ajax-data', 'LoanPromiseToPayController@ajaxData')->name('loan.promise_to_pay.ajax_data');
        Route::get('create', 'LoanPromiseToPayController@create')->name('loan.promise_to_pay.create');
        Route::post('store', 'LoanPromiseToPayController@store')->name('loan.promise_to_pay.store');
        Route::get('{id}/show', 'LoanPromiseToPayController@show')->name('loan.promise_to_pay.show');
        Route::get('{id}/edit', 'LoanPromiseToPayController@edit')->name('loan.promise_to_pay.edit');
        Route::post('{id}/update', 'LoanPromiseToPayController@update')->name('loan.promise_to_pay.update');
        Route::post('{id}/mark-kept', 'LoanPromiseToPayController@markKept')->name('loan.promise_to_pay.mark_kept');
        Route::post('{id}/mark-broken', 'LoanPromiseToPayController@markBroken')->name('loan.promise_to_pay.mark_broken');
    });

    Route::prefix('recovery')->group(function () {
        Route::get('assignments', 'RecoveryAssignmentController@index')->name('loan.recovery.assignments');
        Route::post('assignments/store', 'RecoveryAssignmentController@store')->name('loan.recovery.assignments.store');
    });

    Route::get('write-offs', 'LoanWriteOffController@index')->name('loan.write_offs.index');



    /*
    |--------------------------------------------------------------------------
    | Loan Stabilization & Arrears Monitoring
    |--------------------------------------------------------------------------
    */

    Route::get('stabilization', 'LoanStabilizationController@index')->name('loan.stabilization.index');
    Route::get('stabilization/arrears-aging', 'LoanStabilizationController@arrearsAging')->name('loan.stabilization.arrears_aging');

    /*
    |--------------------------------------------------------------------------
    | Loan Operations, Schedule Preview & Statements
    |--------------------------------------------------------------------------
    */

    Route::get('operations', 'LoanOperationsController@index')->name('loan.operations.index');
    Route::post('operations/schedule-preview', 'LoanOperationsController@schedulePreview')->name('loan.operations.schedule_preview');
    Route::get('loans/{id}/statement', 'LoanStatementController@show')->name('loan.statements.show');

    /*
    |--------------------------------------------------------------------------
    | Reports & Analytics
    |--------------------------------------------------------------------------
    */

    Route::prefix('reports')->group(function () {
        Route::get('/', 'LoanReportController@index')->name('loan.reports.index');
        Route::get('portfolio-summary', 'Reports\PortfolioSummaryReportController@index')->name('loan.reports.portfolio_summary');
        Route::get('disbursements', 'LoanReportController@disbursements')->name('loan.reports.disbursements');
        Route::get('repayments', 'LoanReportController@repayments')->name('loan.reports.repayments');
        Route::get('overdue', 'LoanReportController@overdue')->name('loan.reports.overdue');
        Route::get('collection-report', 'LoanReportController@collectionReport')->name('loan.reports.collection_report');
        Route::get('loan-status-report', 'LoanReportController@loanStatusReport')->name('loan.reports.loan_status_report');
    });

    Route::get('mis', 'LoanMisController@index')->name('loan.mis');
    Route::get('portfolio-intelligence', 'LoanPortfolioIntelligenceController@index')->name('loan.portfolio_intelligence');
    Route::get('par-analytics', 'LoanParAnalyticsController@index')->name('loan.par_analytics');
    Route::get('risk-analytics', 'LoanRiskAnalyticsController@index')->name('loan.risk_analytics');
});

<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web', 'authh', 'auth', 'SetSessionData', 'language','tenant.context', 'timezone', \Modules\Loan\Http\Middleware\CheckLoanLocationAccess::class], 'prefix' => 'loan', 'namespace' => 'Modules\Loan\Http\Controllers'], function () {



    /** Standalone Loan Setup - Application Starting No and Loan Officers */
    Route::prefix('setup')->group(function () {
        Route::get('/', 'LoanSetupController@index')->name('loan.setup.index');
        Route::post('application-no/save', 'LoanSetupController@saveApplicationNo')->name('loan.setup.application_no.save');
        Route::post('loan-officers/store', 'LoanSetupController@storeOfficer')->name('loan.setup.officers.store');
        Route::post('loan-officers/{id}/update', 'LoanSetupController@updateOfficer')->name('loan.setup.officers.update');
        Route::post('loan-officers/{id}/delete', 'LoanSetupController@deleteOfficer')->name('loan.setup.officers.delete');
        Route::post('setup-items/store', 'LoanSetupController@storeSetupItem')->name('loan.setup.items.store');
        Route::post('setup-items/{id}/update', 'LoanSetupController@updateSetupItem')->name('loan.setup.items.update');
        Route::post('setup-items/{id}/delete', 'LoanSetupController@deleteSetupItem')->name('loan.setup.items.delete');
    });

    /** Backward compatible URL used by sidebar: /loan/loan-settings */
    Route::prefix('loan-settings')->group(function () {
        Route::get('/', 'LoanSetupController@index')->name('loan.loan_settings.index');
        Route::post('application-no/save', 'LoanSetupController@saveApplicationNo')->name('loan.loan_settings.application_no.save');
        Route::post('loan-officers/store', 'LoanSetupController@storeOfficer')->name('loan.loan_settings.officers.store');
        Route::post('loan-officers/{id}/update', 'LoanSetupController@updateOfficer')->name('loan.loan_settings.officers.update');
        Route::post('loan-officers/{id}/delete', 'LoanSetupController@deleteOfficer')->name('loan.loan_settings.officers.delete');
        Route::post('setup-items/store', 'LoanSetupController@storeSetupItem')->name('loan.loan_settings.items.store');
        Route::post('setup-items/{id}/update', 'LoanSetupController@updateSetupItem')->name('loan.loan_settings.items.update');
        Route::post('setup-items/{id}/delete', 'LoanSetupController@deleteSetupItem')->name('loan.loan_settings.items.delete');
    });

    /** Standalone Loan Customers */
    Route::prefix('customers')->group(function () {
        Route::get('/', 'LoanCustomerController@index')->name('loan.customers.index');
        Route::get('create', 'LoanCustomerController@create')->name('loan.customers.create');
        Route::post('store', 'LoanCustomerController@store')->name('loan.customers.store');
        Route::get('{id}/show', 'LoanCustomerController@show')->name('loan.customers.show');
        Route::get('{id}/edit', 'LoanCustomerController@edit')->name('loan.customers.edit');
        Route::post('{id}/update', 'LoanCustomerController@update')->name('loan.customers.update');
        Route::get('{id}/ledger', 'LoanCustomerController@ledger')->name('loan.customers.ledger');
        Route::get('{id}/applications', 'LoanCustomerController@applications')->name('loan.customers.applications');
        Route::get('{id}/active-loans', 'LoanCustomerController@activeLoans')->name('loan.customers.active_loans');
        Route::get('{id}/documents', 'LoanCustomerController@documents')->name('loan.customers.documents');
        Route::get('{id}/notes', 'LoanCustomerController@notes')->name('loan.customers.notes');
        Route::get('{id}/audit-log', 'LoanCustomerController@auditLog')->name('loan.customers.audit_log');
        Route::get('{id}/destroy', 'LoanCustomerController@destroy')->name('loan.customers.destroy');
    });


    /** Standalone Loan Officers - Loan Settings */
    Route::prefix('settings/loan-officers')->group(function () {
        Route::get('/', 'LoanOfficerController@index')->name('loan.officers.index');
        Route::post('store', 'LoanOfficerController@store')->name('loan.officers.store');
        Route::post('{id}/update', 'LoanOfficerController@update')->name('loan.officers.update');
        Route::post('{id}/delete', 'LoanOfficerController@destroy')->name('loan.officers.destroy');
    });

    /** Standalone Loan Applications */
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




    /** Loan Customer Ledger & Statements */
    Route::prefix('ledger')->group(function () {
        Route::get('/', 'LoanLedgerController@index')->name('loan.ledger.index');
        Route::get('statement', 'LoanLedgerController@statement')->name('loan.ledger.statement');
        Route::get('print', 'LoanLedgerController@print')->name('loan.ledger.print');
        Route::get('export', 'LoanLedgerController@export')->name('loan.ledger.export');
    });

    /** Standalone Loan Products */
    Route::prefix('loan-products')->group(function () {
        Route::get('/', 'LoanProductController@index')->name('loan.products.index');
        Route::get('create', 'LoanProductController@create')->name('loan.products.create');
        Route::post('store', 'LoanProductController@store')->name('loan.products.store');
        Route::get('{id}', 'LoanProductController@show')->name('loan.products.show')->where('id', '[0-9]+');
        Route::get('{id}/edit', 'LoanProductController@edit')->name('loan.products.edit')->where('id', '[0-9]+');
        Route::post('{id}/update', 'LoanProductController@update')->name('loan.products.update')->where('id', '[0-9]+');
        Route::post('{id}/activate', 'LoanProductController@activate')->name('loan.products.activate')->where('id', '[0-9]+');
        Route::post('{id}/deactivate', 'LoanProductController@deactivate')->name('loan.products.deactivate')->where('id', '[0-9]+');
        Route::post('{id}/duplicate', 'LoanProductController@duplicate')->name('loan.products.duplicate')->where('id', '[0-9]+');
        Route::post('{id}/delete', 'LoanProductController@destroy')->name('loan.products.delete')->where('id', '[0-9]+');
    });

    Route::get('/install', 'InstallController@index');
    Route::post('/install', 'InstallController@install');
    Route::get('/install/uninstall', 'InstallController@uninstall');
    Route::get('/install/update', 'InstallController@update');

    /** Dashboard Routes */
    Route::prefix('dashboard')->group(function () {
        Route::get('/', 'DashboardController@index');
        Route::get('get_totals', 'DashboardController@get_totals');
        Route::get('get_loans_awaiting_disbursement_chart', 'DashboardController@get_loans_awaiting_disbursement_chart');
        Route::get('get_loans_rejected_chart', 'DashboardController@get_loans_rejected_chart');
        Route::get('get_principal_projected_chart', 'DashboardController@get_principal_projected_chart');
        Route::get('get_principal_collected_chart', 'DashboardController@get_principal_collected_chart');
        Route::get('get_interest_projected_chart', 'DashboardController@get_interest_projected_chart');
        Route::get('get_interest_collected_chart', 'DashboardController@get_interest_collected_chart');
        Route::get('get_penalties_projected_chart', 'DashboardController@get_penalties_projected_chart');
        Route::get('get_penalties_collected_chart', 'DashboardController@get_penalties_collected_chart');
        Route::get('get_fees_projected_chart', 'DashboardController@get_fees_projected_chart');
        Route::get('get_fees_collected_chart', 'DashboardController@get_fees_collected_chart');
        Route::get('get_total_paid_chart', 'DashboardController@get_total_paid_chart');
    });

    /** Loan Routes */
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    Route::get('/', 'LoanController@index');
    Route::get('{loan_id}/activity-log', 'LoanController@activity_log');
    Route::get('get_loans', 'LoanController@get_loans');
    Route::get('create', 'LoanController@create');
    Route::get('repaymentbulk', 'LoanController@bulk');
    Route::get('import', 'LoanController@getImportLoan');
    Route::post('post_import_loan', 'LoanController@postImportLoan');
    Route::get('create_client_loan', 'LoanController@create_client_loan');
    Route::post('store', 'LoanController@store');
    
    Route::get('calculator', 'LoanController@create_loan_calculator');
    Route::post('calculator', 'LoanController@process_loan_calculator');
    
    Route::get('{id}/show', 'LoanController@show');
    Route::get('{id}/edit', 'LoanController@edit');
    Route::post('{id}/update', 'LoanController@update');
    Route::get('{id}/destroy', 'LoanController@destroy');
    Route::post('{id}/approve_loan', 'LoanController@approve_loan');
    Route::post('{id}/approve_loan_top', 'LoanController@approve_loan_top');
    Route::get('{id}/undo_approval', 'LoanController@undo_approval');
    Route::post('{id}/reject_loan', 'LoanController@reject_loan');
    Route::get('{id}/undo_rejection', 'LoanController@undo_rejection');
    Route::post('{id}/withdraw_loan', 'LoanController@withdraw_loan');
    Route::get('{id}/undo_withdrawn', 'LoanController@undo_withdrawn');
    Route::post('{id}/disburse_loan', 'LoanController@disburse_loan');
    Route::get('{id}/undo_disbursement', 'LoanController@undo_disbursement');
    Route::post('{id}/write_off_loan', 'LoanController@write_off_loan');
    Route::get('{id}/undo_write_off', 'LoanController@undo_write_off');
    Route::post('{id}/close_loan', 'LoanController@close_loan');
    Route::get('{id}/undo_loan_close', 'LoanController@undo_loan_close');
    Route::post('{id}/reschedule_loan', 'LoanController@reschedule_loan');
    Route::get('{id}/undo_reschedule', 'LoanController@undo_reschedule');
    Route::post('{id}/change_loan_officer', 'LoanController@change_loan_officer');
    Route::post('{id}/waive_interest', 'LoanController@waive_interest');
    Route::get('{id}/transfer', 'LoanController@transfer');
    Route::post('{id}/store_transfer', 'LoanController@store_transfer');
    Route::post('store_bulk_repayment', 'LoanController@store_bulk_repayment');
    Route::get('export', 'LoanController@export');
    Route::get('bulk_import_repayments', 'LoanController@bulk_import_repayments');
    Route::post('store_import_repayments', 'LoanController@store_import_repayments');
    Route::get('{id}/get_pending_dues', 'LoanController@get_pending_dues');
    //contact_loan files
    Route::get('{id}/file/create', 'LoanFileController@create');
    Route::post('{id}/file/store', 'LoanFileController@store');
    Route::get('{id}/file/show', 'LoanFileController@show');
    Route::get('file/{id}/edit', 'LoanFileController@edit');
    Route::post('file/{id}/update', 'LoanFileController@update');
    Route::get('file/{id}/destroy', 'LoanFileController@destroy');
    //collateral
    Route::get('{id}/collateral/create', 'LoanCollateralController@create');
    Route::post('{id}/collateral/store', 'LoanCollateralController@store');
    Route::get('{id}/collateral/show', 'LoanCollateralController@show');
    Route::get('collateral/{id}/edit', 'LoanCollateralController@edit');
    Route::post('collateral/{id}/update', 'LoanCollateralController@update');
    Route::get('collateral/{id}/destroy', 'LoanCollateralController@destroy');
    //notes
    Route::get('{id}/note/create', 'LoanNoteController@create');
    Route::post('{id}/note/store', 'LoanNoteController@store');
    Route::get('{id}/note/show', 'LoanNoteController@show');
    Route::get('note/{id}/edit', 'LoanNoteController@edit');
    Route::post('note/{id}/update', 'LoanNoteController@update');
    Route::get('note/{id}/destroy', 'LoanNoteController@destroy');
    //contact_loan transactions
    Route::get('transaction/{id}/show', 'LoanController@show_transaction');
    Route::get('transaction/{id}/pdf', 'LoanController@pdf_transaction');
    Route::get('transaction/{id}/print', 'LoanController@print_transaction');

    //schedules
    Route::get('{id}/schedule/show', 'LoanController@show_schedule');
    Route::get('{id}/schedule/pdf', 'LoanController@pdf_schedule');
    Route::get('{id}/schedule/print', 'LoanController@print_schedule');
    //repayments
    Route::get('{id}/repayment/create', 'LoanController@create_repayment');
    Route::post('{id}/repayment/store', 'LoanController@store_repayment');
    Route::get('repayment/{id}/edit', 'LoanController@edit_repayment');
    Route::get('repayment/{id}/reverse', 'LoanController@reverse_repayment');
    Route::post('repayment/{id}/update', 'LoanController@update_repayment');
    Route::get('repayment/{id}/destroy', 'LoanController@destroy_repayment');
    //charges
    Route::get('charge/{id}/waive', 'LoanController@waive_charge');
    Route::get('{id}/charge/create', 'LoanController@create_loan_linked_charge');
    Route::post('{id}/charge/store', 'LoanController@store_loan_linked_charge');
    //purposes
    Route::prefix('purpose')->group(function () {
        Route::get('/', 'LoanPurposeController@index');
        Route::get('get_purposes', 'LoanPurposeController@get_purposes');
        Route::get('create', 'LoanPurposeController@create');
        Route::post('store', 'LoanPurposeController@store');
        Route::get('{id}/show', 'LoanPurposeController@show');
        Route::get('{id}/edit', 'LoanPurposeController@edit');
        Route::post('{id}/update', 'LoanPurposeController@update');
        Route::get('{id}/destroy', 'LoanPurposeController@destroy');
    });
    //collateral types
    Route::prefix('collateral_type')->group(function () {
        Route::get('/', 'LoanCollateralTypeController@index');
        Route::get('get_collateral_types', 'LoanCollateralTypeController@get_collateral_types');
        Route::get('create', 'LoanCollateralTypeController@create');
        Route::post('store', 'LoanCollateralTypeController@store');
        Route::get('{id}/show', 'LoanCollateralTypeController@show');
        Route::get('{id}/edit', 'LoanCollateralTypeController@edit');
        Route::post('{id}/update', 'LoanCollateralTypeController@update');
        Route::get('{id}/destroy', 'LoanCollateralTypeController@destroy');
    });
    //credit checks
    Route::prefix('credit_check')->group(function () {
        Route::get('/', 'LoanCreditCheckController@index');
        Route::get('{id}/show', 'LoanCreditCheckController@show');
        Route::get('{id}/edit', 'LoanCreditCheckController@edit');
        Route::post('{id}/update', 'LoanCreditCheckController@update');
    });
    //charges
    Route::prefix('charge')->group(function () {
        Route::get('/', 'LoanChargeController@index');
        Route::get('get_charges', 'LoanChargeController@get_charges');
        Route::get('get_charge_types', 'LoanChargeController@get_charge_types');
        Route::get('get_charge_options', 'LoanChargeController@get_charge_options');
        Route::get('create', 'LoanChargeController@create');
        Route::post('store', 'LoanChargeController@store');
        Route::get('{id}/edit', 'LoanChargeController@edit');
        Route::post('{id}/update', 'LoanChargeController@update');
        Route::get('{id}/destroy', 'LoanChargeController@destroy');
    });
    //contact_loan status
    Route::prefix('status')->group(function () {
        Route::get('/', 'LoanController@status');
        Route::get('create', 'LoanController@create_status');
        Route::post('store', 'LoanController@store_status');
        Route::get('{id}/edit', 'LoanController@edit_status');
        Route::put('{id}/update', 'LoanController@update_status');
        Route::delete('{id}/destroy', 'LoanController@destroy_status');
    });
});

//report
Route::group(['middleware' => ['web', 'authh', 'auth', 'SetSessionData', 'language','tenant.context', 'timezone', \Modules\Loan\Http\Middleware\CheckLoanLocationAccess::class], 'prefix' => 'report', 'namespace' => 'Modules\Loan\Http\Controllers'], function () {
    Route::prefix('contact_loan')->group(function () {
        Route::get('/', 'LoanReportController@index');
        Route::get('collection_sheet', 'LoanReportController@collection_sheet');
        Route::get('repayment', 'LoanReportController@repayment');
        Route::get('expected_repayment', 'LoanReportController@expected_repayment');
        Route::get('arrears', 'LoanReportController@arrears');
        Route::get('disbursement', 'LoanReportController@disbursement');
        Route::get('account_statement', 'LoanReportController@account_statement');
        Route::get('awaiting_disbursement', 'LoanReportController@awaiting_disbursement');
        Route::get('pending_approval', 'LoanReportController@pending_approval');
        Route::get('rescheduled_loans', 'LoanReportController@rescheduled_loans');
        Route::get('written_off_loans', 'LoanReportController@written_off_loans');
        Route::get('fully_paid_loans', 'LoanReportController@fully_paid_loans');
        Route::get('active_past_maturity_loans', 'LoanReportController@active_past_maturity_loans');
        Route::get('active_loans_in_last_installment', 'LoanReportController@active_loans_in_last_installment');
        Route::get('active_loan_summary_per_branch', 'LoanReportController@active_loan_summary_per_branch');
        Route::get('active_loans_by_disbursal_period', 'LoanReportController@active_loans_by_disbursal_period');
        Route::get('closed_loans', 'LoanReportController@closed_loans');
        Route::get('aging_detail', 'LoanReportController@aging_detail');
        Route::get('loans_awaiting_disbursal_summary', 'LoanReportController@loans_awaiting_disbursal_summary');
        Route::get('loans_awaiting_disbursal_by_month', 'LoanReportController@loans_awaiting_disbursal_by_month');
        Route::get('active_loans_details', 'LoanReportController@active_loans_details');
        Route::get('active_loans_summary', 'LoanReportController@active_loans_summary');
        Route::get('overdue_mature_loans', 'LoanReportController@overdue_mature_loans');
        Route::get('loan_transactions_detailed', 'LoanReportController@loan_transactions_detailed');
        Route::get('loan_transactions_summary', 'LoanReportController@loan_transactions_summary');
        Route::get('loan_funds_movement', 'LoanReportController@loan_funds_movement');
        Route::get('loan_classification_by_product', 'LoanReportController@loan_classification_by_product');
        Route::get('active_past_maturity_loans_summary', 'LoanReportController@active_past_maturity_loans_summary');
        Route::get('aging_summary_in_months', 'LoanReportController@aging_summary_in_months');
        Route::get('aging_summary_in_weeks', 'LoanReportController@aging_summary_in_weeks');
        Route::get('balance_outstanding', 'LoanReportController@balance_outstanding');
        Route::get('branch_expected_cash_flow', 'LoanReportController@branch_expected_cash_flow');
        Route::get('basic_expected_payment_by_date', 'LoanReportController@basic_expected_payment_by_date');
        Route::get('formatted_expected_payment_by_date', 'LoanReportController@formatted_expected_payment_by_date');
        Route::get('loan_trends_by_month_by_created', 'LoanReportController@loan_trends_by_month_by_created');
        Route::get('loan_trends_by_month_by_disbursed', 'LoanReportController@loan_trends_by_month_by_disbursed');
        Route::get('obligation_met_loans_details', 'LoanReportController@obligation_met_loans_details');
        Route::get('portfolio_at_risk', 'LoanReportController@portfolio_at_risk');
        Route::get('portfolio_at_risk_by_branch', 'LoanReportController@portfolio_at_risk_by_branch');
    });
});

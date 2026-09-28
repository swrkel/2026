<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customers Module Routes
|--------------------------------------------------------------------------
|
| Standalone Customers module routes.
| These routes are protected by the module security middleware registered by
| CustomersServiceProvider. Direct URL access is blocked when the business
| subscription does not enable Customers or when the user role lacks permission.
|
*/

Route::prefix('customers')->group(function () {

    Route::get('/', 'CustomerController@index')
        ->middleware('customers.access:view')
        ->name('customers.index');

    Route::get('/dashboard', 'CustomerDashboardController@index')
        ->middleware('customers.access:dashboard')
        ->name('customers.dashboard');

    Route::get('/payment-accounts-by-method', 'CustomerPaymentController@accountsByMethod')
        ->middleware('customers.access:view')
        ->name('customers.payments.accounts_by_method');

    /*
    |--------------------------------------------------------------------------
    | Customer Register Menu Separation - CUS_SEP_007
    |--------------------------------------------------------------------------
    | /customers remains the safe legacy register URL. /customers/register is
    | added as a Customers-owned menu URL for future cleanup without breaking
    | existing links/bookmarks.
    */
    Route::get('/register', 'CustomerRegisterController@index')
        ->middleware('customers.access:view')
        ->name('customers.register');

    Route::get('/register-total-due', 'CustomerController@registerTotalDue')
        ->middleware('customers.access:view')
        ->name('customers.register.total_due');


    Route::get('/register-page-balances', 'CustomerController@registerPageBalances')
        ->middleware('customers.access:view')
        ->name('customers.register.page_balances');

    Route::prefix('reports')->middleware('customers.access:reports')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Customer Report Separation - CUS_SEP_004
        |--------------------------------------------------------------------------
        | Each report now has its own controller so report logic does not keep
        | growing inside one large CustomerReportController file.
        */
        Route::get('/', 'CustomerReportIndexController@index')->name('customers.reports.index');

        Route::get('/customer-list', 'CustomerListReportController@index')->name('customers.reports.list');
        Route::get('/customer-ledger', 'CustomerLedgerReportController@index')->name('customers.reports.ledger');
        Route::get('/customer-statement', 'CustomerStatementReportController@index')->name('customers.reports.statement');
        Route::get('/customer-aging', 'CustomerAgeingReportController@index')->name('customers.reports.aging');
        Route::get('/inactive-customers', 'CustomerInactiveCustomerReportController@index')->name('customers.reports.inactive');

        Route::get('/customer-balance', 'CustomerBalanceReportController@index')->name('customers.reports.balance');
        Route::get('/customer-transactions', 'CustomerTransactionReportController@index')->name('customers.reports.transactions');
        Route::get('/customer-payments', 'CustomerPaymentReportController@index')->name('customers.reports.payments');

        Route::get('/customer-list/export', 'CustomerListReportController@export')
            ->middleware('customers.access:export')
            ->name('customers.reports.list.export');
        Route::get('/customer-ledger/export', 'CustomerLedgerReportController@export')
            ->middleware('customers.access:export')
            ->name('customers.reports.ledger.export');
        Route::get('/customer-statement/export', 'CustomerStatementReportController@export')
            ->middleware('customers.access:export')
            ->name('customers.reports.statement.export');
        Route::get('/customer-aging/export', 'CustomerAgeingReportController@export')
            ->middleware('customers.access:export')
            ->name('customers.reports.aging.export');
        Route::get('/inactive-customers/export', 'CustomerInactiveCustomerReportController@export')
            ->middleware('customers.access:export')
            ->name('customers.reports.inactive.export');
        Route::get('/customer-balance/export', 'CustomerBalanceReportController@export')
            ->middleware('customers.access:export')
            ->name('customers.reports.balance.export');
        Route::get('/customer-transactions/export', 'CustomerTransactionReportController@export')
            ->middleware('customers.access:export')
            ->name('customers.reports.transactions.export');
        Route::get('/customer-payments/export', 'CustomerPaymentReportController@export')
            ->middleware('customers.access:export')
            ->name('customers.reports.payments.export');
    });


    /*
    |--------------------------------------------------------------------------
    | Customer Master Data Separation - CUS_SEP_005
    |--------------------------------------------------------------------------
    | Master-data pages now live inside Modules/Customers with short, separate
    | controllers/services/views. These routes do not use Contact module views.
    */
    Route::prefix('master-data')->middleware('customers.access:settings')->group(function () {
        Route::get('/', [\Modules\Customers\Http\Controllers\RouteClosures\WebRouteController::class, 'handle1'])->name('customers.master.index');

        Route::resource('groups', 'CustomerGroupController')->names('customers.master.groups')->except(['show']);
        Route::resource('types', 'CustomerTypeController')->names('customers.master.types')->except(['show']);
        Route::resource('categories', 'CustomerCategoryController')->names('customers.master.categories')->except(['show']);
        Route::resource('classifications', 'CustomerClassificationController')->names('customers.master.classifications')->except(['show']);
        Route::resource('custom-fields', 'CustomerCustomFieldController')->names('customers.master.custom_fields')->except(['show']);
        Route::resource('settings', 'CustomerSettingsController')->names('customers.master.settings')->except(['show']);
        Route::resource('opening-balances', 'CustomerOpeningBalanceController')->names('customers.master.opening_balances')->except(['show']);
    });



    /*
    |--------------------------------------------------------------------------
    | Customer Settings & Configuration Separation - CUS_SEP_008
    |--------------------------------------------------------------------------
    | Numbering, defaults, preferences, portal settings, credit settings and
    | notification settings are now owned by the Customers module.
    */
    Route::prefix('settings')->middleware('customers.access:settings')->group(function () {
        Route::get('/', [\Modules\Customers\Http\Controllers\RouteClosures\WebRouteController::class, 'handle2'])->name('customers.settings.index');

        Route::get('/payment-references', 'CustomerPaymentReferenceSettingsController@index')->name('customers.settings.payment_references.index');
        Route::post('/payment-references', 'CustomerPaymentReferenceSettingsController@store')->name('customers.settings.payment_references.store');
        Route::get('/payment-references/{id}/edit', 'CustomerPaymentReferenceSettingsController@edit')->whereNumber('id')->name('customers.settings.payment_references.edit');
        Route::put('/payment-references/{id}', 'CustomerPaymentReferenceSettingsController@update')->whereNumber('id')->name('customers.settings.payment_references.update');
        Route::delete('/payment-references/{id}', 'CustomerPaymentReferenceSettingsController@destroy')->whereNumber('id')->name('customers.settings.payment_references.destroy');

        Route::get('/numbering', 'CustomerNumberingController@index')->name('customers.settings.numbering.index');
        Route::put('/numbering', 'CustomerNumberingController@update')->name('customers.settings.numbering.update');

        Route::get('/defaults', 'CustomerDefaultsController@index')->name('customers.settings.defaults.index');
        Route::put('/defaults', 'CustomerDefaultsController@update')->name('customers.settings.defaults.update');

        Route::get('/preferences', 'CustomerPreferencesController@index')->name('customers.settings.preferences.index');
        Route::put('/preferences', 'CustomerPreferencesController@update')->name('customers.settings.preferences.update');

        Route::get('/portal', 'CustomerPortalSettingsController@index')->name('customers.settings.portal.index');
        Route::put('/portal', 'CustomerPortalSettingsController@update')->name('customers.settings.portal.update');

        Route::get('/credit', 'CustomerCreditSettingsController@index')->name('customers.settings.credit.index');
        Route::put('/credit', 'CustomerCreditSettingsController@update')->name('customers.settings.credit.update');

        Route::get('/notifications', 'CustomerNotificationSettingsController@index')->name('customers.settings.notifications.index');
        Route::put('/notifications', 'CustomerNotificationSettingsController@update')->name('customers.settings.notifications.update');
    });



    /*
    |--------------------------------------------------------------------------
    | RC10 Contacts Customer Compatibility URLs
    |--------------------------------------------------------------------------
    | These endpoints keep old Contact-customer links/AJAX calls alive while
    | routing execution through the standalone Customers module.
    */
    Route::get('/import', 'CustomerController@import')->middleware('customers.access:create')->name('customers.import');
    Route::post('/import', 'CustomerController@postImport')->middleware('customers.access:create')->name('customers.import.post');
    Route::get('/import-balance', 'CustomerCompatibilityController@importBalance')->middleware('customers.access:create')->name('customers.import_balance');
    Route::post('/import-balance', 'CustomerCompatibilityController@postImportBalance')->middleware('customers.access:create')->name('customers.import_balance.post');
    Route::get('/export', 'CustomerController@export')->middleware('customers.access:export')->name('customers.export');
    Route::post('/quick-add-sub-customer', 'CustomerCompatibilityController@quickAddSubCustomer')->middleware('customers.access:create')->name('customers.quick_add_sub_customer');
    Route::get('/get-sub-customers', 'CustomerCompatibilityController@getSubCustomers')->middleware('customers.access:view')->name('customers.get_sub_customers');

    Route::get('/create', 'CustomerController@create')
        ->middleware('customers.access:create')
        ->name('customers.create');
    Route::post('/store', 'CustomerController@store')
        ->middleware('customers.access:create')
        ->name('customers.store');



    /*
    |--------------------------------------------------------------------------
    | Customer Workflow & Approval Separation - CUS_SEP_006
    |--------------------------------------------------------------------------
    | Workflow, approval, credit approval, status changes and audit pages live
    | inside Modules/Customers. These routes intentionally do not call Contact
    | module controllers or Contact module views.
    */
    Route::prefix('workflow')->middleware('customers.access:edit')->group(function () {
        // The dashboard must always open. When optional workflow tables are not
        // installed it shows an installation notice instead of redirecting the
        // user back to the Customer Register page.
        Route::get('/', 'CustomerWorkflowDashboardController@index')->name('customers.workflow.index');

        Route::middleware('customers.feature:workflow')->group(function () {
            Route::get('/approvals', 'CustomerApprovalController@index')->name('customers.workflow.approvals.index');
            Route::get('/approvals/create', 'CustomerApprovalController@create')->name('customers.workflow.approvals.create');
            Route::post('/approvals', 'CustomerApprovalController@store')->name('customers.workflow.approvals.store');
            Route::get('/approvals/{approval}', 'CustomerApprovalController@show')->name('customers.workflow.approvals.show');
            Route::post('/approvals/{approval}/approve', 'CustomerApprovalController@approve')->name('customers.workflow.approvals.approve');
            Route::post('/approvals/{approval}/reject', 'CustomerApprovalController@reject')->name('customers.workflow.approvals.reject');

            Route::get('/credit-approvals', 'CustomerCreditApprovalController@index')->name('customers.workflow.credit_approvals.index');
            Route::post('/credit-approvals/{approval}/approve', 'CustomerCreditApprovalController@approve')->name('customers.workflow.credit_approvals.approve');
            Route::post('/credit-approvals/{approval}/reject', 'CustomerCreditApprovalController@reject')->name('customers.workflow.credit_approvals.reject');

            Route::get('/status', 'CustomerStatusController@index')->name('customers.workflow.status.index');
            Route::post('/status/change', 'CustomerStatusController@change')->name('customers.workflow.status.change');

            Route::post('/activation/{customer}/activate', 'CustomerActivationController@activate')->name('customers.workflow.activation.activate');
            Route::post('/activation/{customer}/deactivate', 'CustomerActivationController@deactivate')->name('customers.workflow.activation.deactivate');

            Route::get('/history', 'CustomerWorkflowHistoryController@index')->name('customers.workflow.history.index');
            Route::get('/approval-audit', 'CustomerApprovalAuditController@index')->name('customers.workflow.audit.index');
        });
    });




    /*
    |--------------------------------------------------------------------------
    | RC12 Standalone Verification
    |--------------------------------------------------------------------------
    | Safe read-only audit page to identify remaining legacy Contacts customer
    | route/view/controller references inside the Customers module.
    */
    Route::get('/standalone-audit', 'CustomerStandaloneAuditController@index')
        ->middleware('customers.access:settings')
        ->name('customers.standalone_audit');



    /*
    |--------------------------------------------------------------------------
    | Customer Reference - Task 8046
    |--------------------------------------------------------------------------
    | List Customer Reference page, the Add popup's multi-row save, row edit /
    | delete / status toggle, and the QR Action popup with its Print, PDF,
    | WhatsApp and Email actions.
    |
    | Registered BEFORE the /{id} catch-all further down this file. That
    | catch-all matches any single segment, so a block placed after it would
    | never be reached - the same reason the S350 parity routes sit where they
    | do.
    */
    Route::prefix('customer-references')->group(function () {

        Route::get('/', 'CustomerReferenceController@index')
            ->middleware('customers.access:view')
            ->name('customers.customer_references.index');

        // Static runtime script, served from the module and cached by the
        // browser, matching the Bulk Payment runtime pattern.
        Route::get('/runtime-script', 'CustomerReferenceController@runtime')
            ->middleware('customers.access:view')
            ->name('customers.customer_references.runtime');

        Route::get('/data', 'CustomerReferenceController@data')
            ->middleware('customers.access:view')
            ->name('customers.customer_references.data');

        Route::post('/', 'CustomerReferenceController@store')
            ->middleware('customers.access:create')
            ->name('customers.customer_references.store');

        /*
         * Every id-bound route is constrained with whereNumber so a literal
         * segment such as /data or /runtime-script can never be swallowed by
         * the {reference} placeholder.
         */
        Route::get('/{reference}', 'CustomerReferenceController@show')
            ->whereNumber('reference')
            ->middleware('customers.access:view')
            ->name('customers.customer_references.show');

        Route::get('/{reference}/edit', 'CustomerReferenceController@edit')
            ->whereNumber('reference')
            ->middleware('customers.access:edit')
            ->name('customers.customer_references.edit');

        Route::put('/{reference}', 'CustomerReferenceController@update')
            ->whereNumber('reference')
            ->middleware('customers.access:edit')
            ->name('customers.customer_references.update');

        Route::post('/{reference}/toggle-status', 'CustomerReferenceController@toggleStatus')
            ->whereNumber('reference')
            ->middleware('customers.access:edit')
            ->name('customers.customer_references.toggle_status');

        Route::delete('/{reference}', 'CustomerReferenceController@destroy')
            ->whereNumber('reference')
            ->middleware('customers.access:delete')
            ->name('customers.customer_references.destroy');

        // QR Action popup and its four actions.
        Route::get('/{reference}/qr', 'CustomerReferenceQrController@modal')
            ->whereNumber('reference')
            ->middleware('customers.access:view')
            ->name('customers.customer_references.qr.modal');

        Route::get('/{reference}/qr/print', 'CustomerReferenceQrController@print')
            ->whereNumber('reference')
            ->middleware('customers.access:view')
            ->name('customers.customer_references.qr.print');

        Route::get('/{reference}/qr/pdf', 'CustomerReferenceQrController@pdf')
            ->whereNumber('reference')
            ->middleware('customers.access:view')
            ->name('customers.customer_references.qr.pdf');

        Route::get('/{reference}/qr/whatsapp', 'CustomerReferenceQrController@whatsapp')
            ->whereNumber('reference')
            ->middleware('customers.access:view')
            ->name('customers.customer_references.qr.whatsapp');

        Route::post('/{reference}/qr/email', 'CustomerReferenceQrController@email')
            ->whereNumber('reference')
            ->middleware('customers.access:view')
            ->name('customers.customer_references.qr.email');
    });



    /*
    |--------------------------------------------------------------------------
    | S350 Customer Contact-Module Parity Pages (Standalone)
    |--------------------------------------------------------------------------
    | These routes keep the old customer Contact pages functional inside the
    | Customers module using Customers module controllers and Customers module
    | views only. They are intentionally registered before the /{id} catch-all.
    */
    Route::get('/customer-interest', 'CustomerStandalonePaymentController@CustomerInterest')
        ->middleware('customers.access:view')
        ->name('customers.customer_interest');

    Route::get('/customer-payment-information/{customer}/{type}', 'CustomerStandalonePaymentController@customerPaymentInformations')
        ->middleware('customers.access:view')
        ->name('customers.customer_payment_information');
    Route::get('/customer-payment-view/{id}', 'CustomerStandalonePaymentController@viewPayment')
        ->middleware('customers.access:view')
        ->name('customers.customer_payment.view');
    Route::get('/customer-payment-print/{id}', 'CustomerStandalonePaymentController@printPayment')
        ->middleware('customers.access:view')
        ->name('customers.customer_payment.print');
    Route::get('/customer-info-for/{for}/{data}', 'CustomerStandalonePaymentController@customerInfoFor')
        ->middleware('customers.access:view')
        ->name('customers.customer_info_for');

    Route::resource('/customer-payments', 'CustomerStandalonePaymentController')
        ->middleware('customers.access:view')
        ->names('customers.customer_payments');

    /*
    |--------------------------------------------------------------------------
    | Customers-owned Bulk Payment
    |--------------------------------------------------------------------------
    | Separate page with controller, service, views, JavaScript and CSS inside
    | Modules/Customers. It does not call Contact or Accounting module routes.
    */
    Route::get('/bulk-payment', 'CustomerBulkPaymentController@index')
        ->middleware('customers.access:payment')
        ->name('customers.bulk_payment.index');
    Route::get('/bulk-payment/runtime-script', 'CustomerBulkPaymentController@runtime')
        ->middleware('customers.access:payment')
        ->name('customers.bulk_payment.runtime');
    Route::post('/bulk-payment', 'CustomerBulkPaymentController@store')
        ->middleware('customers.access:payment')
        ->name('customers.bulk_payment.store');
    Route::get('/bulk-payment/customer/{customer}/summary', 'CustomerBulkPaymentController@customerSummary')
        ->whereNumber('customer')
        ->middleware('customers.access:payment')
        ->name('customers.bulk_payment.customer_summary');
    Route::get('/bulk-payment/customer/{customer}/invoices', 'CustomerBulkPaymentController@customerInvoices')
        ->whereNumber('customer')
        ->middleware('customers.access:payment')
        ->name('customers.bulk_payment.customer_invoices');
    Route::get('/bulk-payment/customer/{customer}', 'CustomerBulkPaymentController@customerData')
        ->whereNumber('customer')
        ->middleware('customers.access:payment')
        ->name('customers.bulk_payment.customer_data');
    Route::get('/bulk-payment/accounts/{group}', 'CustomerBulkPaymentController@accounts')
        ->middleware('customers.access:payment')
        ->name('customers.bulk_payment.accounts');
    Route::get('/bulk-payment/receipt/{reference}', 'CustomerBulkPaymentController@receipt')
        ->where('reference', '[A-Za-z0-9\-]+')
        ->middleware('customers.access:payment')
        ->name('customers.bulk_payment.receipt');

    Route::get('/customer-payment-bulk/customer-details/{customer}', 'CustomerStandalonePaymentBulkController@customerDetails')
        ->middleware('customers.access:payment')
        ->name('customers.customer_payment_bulk.customer_details');
    Route::get('/customer-payment-bulk/get-payment-table', 'CustomerStandalonePaymentBulkController@bulkPaymentTable')
        ->middleware('customers.access:payment')
        ->name('customers.customer_payment_bulk.table');
    Route::get('/customer-payment-bulk', 'CustomerStandalonePaymentBulkController@index')
        ->middleware('customers.access:payment')
        ->name('customers.customer_payment_bulk.index');
    Route::post('/customer-payment-bulk', 'CustomerStandalonePaymentBulkController@store')
        ->middleware('customers.access:payment')
        ->name('customers.customer_payment_bulk.store');

    Route::resource('/customer-payment-simple', 'CustomerStandalonePaymentSimpleController')
        ->middleware('customers.access:view')
        ->names('customers.customer_payment_simple');

    Route::get('/issued-payment-details', 'CustomerCompatibilityController@issuedPaymentDetails')
        ->middleware('customers.access:view')
        ->name('customers.issued_payment_details');
    Route::get('/returned-cheque-details', 'CustomerCompatibilityController@returnedCheques')
        ->middleware('customers.access:view')
        ->name('customers.returned_cheque_details');

    Route::get('/outstanding-received-report', 'CustomerStandaloneOutstandingController@index')
        ->middleware('customers.access:view')
        ->name('customers.outstanding_received_report');
    Route::get('/outstanding-received-report/filters', 'CustomerStandaloneOutstandingController@filters')
        ->middleware('customers.access:view')
        ->name('customers.outstanding_received_report.filters');
    Route::get('/outstanding-received-report/data', 'CustomerStandaloneOutstandingController@data')
        ->middleware('customers.access:view')
        ->name('customers.outstanding_received_report.data');
    Route::get('/edit-received-outstanding/{id}', 'CustomerStandalonePaymentController@edit')
        ->middleware('customers.access:edit')
        ->name('customers.received_outstanding.edit');

    // Customer Statement - Pymts: same Customer Statement workspace with payment rows included.
    Route::get('/customer-statement-pymts', 'CustomerStandaloneStatementController@indexPymts')
        ->middleware('customers.access:view')
        ->name('customers.customer_statement.pymts');

    Route::get('/customer-statement/list-payments', 'CustomerStandaloneStatementController@listStatementPayments')
        ->middleware('customers.access:view')
        ->name('customers.customer_statement.list_payments');
    Route::get('/customer-statement/pay-total/{statement_id}', 'CustomerStandaloneStatementController@payTotalStatement')
        ->middleware('customers.access:view')
        ->name('customers.customer_statement.pay_total');
    Route::post('/customer-statement/pay-total/{statement_id}', 'CustomerStandaloneStatementController@postPayTotalStatement')
        ->middleware('customers.access:edit')
        ->name('customers.customer_statement.pay_total.post');
    Route::get('/customer-statement/get-statement-list', 'CustomerStandaloneStatementController@getCustomerStatementList')
        ->middleware('customers.access:view')
        ->name('customers.customer_statement.get_statement_list');
    Route::get('/customer-statement/get-statement-list-pmts', 'CustomerStandaloneStatementController@getCustomerStatementListPmt')
        ->middleware('customers.access:view')
        ->name('customers.customer_statement.get_statement_list_pmts');
    Route::get('/customer-statement/reprint/{statement_id}', 'CustomerStandaloneStatementController@rePrint')
        ->middleware('customers.access:view')
        ->name('customers.customer_statement.reprint');
    Route::get('/customer-statement/export-excel/{statement_id}', 'CustomerStandaloneStatementController@exportExcel')
        ->middleware('customers.access:export')
        ->name('customers.customer_statement.export_excel');
    Route::get('/customer-statement/export-excel-pmt/{statement_id}', 'CustomerStandaloneStatementController@exportExcelPmt')
        ->middleware('customers.access:export')
        ->name('customers.customer_statement.export_excel_pmt');
    Route::get('/customer-statement/reprint-pmt/{statement_id}', 'CustomerStandaloneStatementController@rePrintPmt')
        ->middleware('customers.access:view')
        ->name('customers.customer_statement.reprint_pmt');
    Route::get('/customer-statement/show-pmt/{statement_id}', 'CustomerStandaloneStatementController@showPmt')
        ->middleware('customers.access:view')
        ->name('customers.customer_statement.show_pmt');
    Route::post('/download-pdf', 'CustomerStandaloneStatementController@downloadPdf')
        ->middleware('customers.access:view')
        ->name('customers.customer_statement.download_pdf');
    Route::get('/customer-statement/delete/{id}', 'CustomerStandaloneStatementController@destroyPayments')
        ->middleware('customers.access:delete')
        ->name('customers.customer_statement.delete_payment');
    Route::get('/list-customer-statement/show/{id}', 'CustomerStandaloneStatementController@showCustomerStatement')
        ->middleware('customers.access:view')
        ->name('customers.customer_statement.list_show');
    Route::get('/list-customer-statement/rePrint/{id}', 'CustomerStandaloneStatementController@rePrintListCustomerState')
        ->middleware('customers.access:view')
        ->name('customers.customer_statement.list_reprint');
    Route::get('/list-customer-statement/export-excel/{id}', 'CustomerStandaloneStatementController@exportExcelListCustomerStatement')
        ->middleware('customers.access:export')
        ->name('customers.customer_statement.list_export_excel');

    Route::get('/customer-statement/user-activity', 'CustomerStandaloneStatementController@getUserActivityReport')
        ->middleware('customers.access:view')
        ->name('customers.customer_statement.user_activity');
    Route::resource('/customer-statement', 'CustomerStandaloneStatementController')
        ->middleware('customers.access:view')
        ->names('customers.customer_statement');
    Route::resource('/customer-statement-logos', 'CustomerStandaloneStatementLogoController')
        ->except(['show'])
        ->middleware('customers.access:settings')
        ->names('customers.customer_statement_logos');
    Route::resource('/customer-statement-settings', 'CustomerStandaloneStatementSettingController')
        ->middleware('customers.access:settings')
        ->names('customers.customer_statement_settings');

    Route::resource('/interest-settings', 'CustomerStandaloneInterestSettingController')
        ->middleware('customers.access:settings')
        ->names('customers.interest_settings');
    Route::resource('/ledger-discount', 'CustomerStandaloneLedgerDiscountController')
        ->middleware('customers.access:view')
        ->names('customers.ledger_discount');

    /*
    |--------------------------------------------------------------------------
    | Customer Register Standalone Action Routes - CUS-005 Phase 1
    |--------------------------------------------------------------------------
    | These action routes intentionally point to Customers module controllers
    | and views instead of Contact module controllers/views.
    */
    Route::post('/{customer}/deactivate', 'CustomerActivationController@deactivate')
        ->middleware('customers.access:edit')
        ->name('customers.deactivate');

    Route::prefix('{id}')->group(function () {
        Route::get('/pay-due', 'CustomerPaymentController@payDue')->middleware('customers.access:view')->name('customers.payments.due');
        Route::post('/pay-due', 'CustomerPaymentController@store')->middleware('customers.access:edit')->name('customers.payments.due.store');

        Route::get('/advance-payment', 'CustomerAdvancePaymentController@create')->middleware('customers.access:view')->name('customers.payments.advance');
        Route::post('/advance-payment', 'CustomerAdvancePaymentController@store')->middleware('customers.access:edit')->name('customers.payments.advance.store');

        Route::get('/loan', 'CustomerLoanController@create')->middleware('customers.access:view')->name('customers.loans.create');
        Route::post('/loan', 'CustomerLoanController@store')->middleware('customers.access:edit')->name('customers.loans.store');

        Route::get('/refund-deposit', 'CustomerDepositRefundController@create')->middleware('customers.access:view')->name('customers.refunds.deposit');
        Route::post('/refund-deposit', 'CustomerDepositRefundController@store')->middleware('customers.access:edit')->name('customers.refunds.deposit.store');

        Route::get('/refund-payment', 'CustomerRefundController@refundPayment')->middleware('customers.access:view')->name('customers.refunds.payment');
        Route::post('/refund-payment', 'CustomerRefundController@store')->middleware('customers.access:edit')->name('customers.refunds.payment.store');

        Route::get('/cheque-return', 'CustomerChequeReturnController@create')->middleware('customers.access:view')->name('customers.refunds.cheque_return');
        Route::post('/cheque-return', 'CustomerChequeReturnController@store')->middleware('customers.access:edit')->name('customers.refunds.cheque_return.store');

        Route::get('/security-deposit', 'CustomerDepositController@securityDeposit')->middleware('customers.access:view')->name('customers.deposits.security');
        Route::post('/security-deposit', 'CustomerDepositController@storeSecurityDeposit')->middleware('customers.access:edit')->name('customers.deposits.security.store');

        Route::get('/ledger', 'CustomerLedgerController@ledger')->middleware('customers.access:view')->name('customers.ledger');
        Route::get('/statement', 'CustomerStatementController@show')->middleware('customers.access:view')->name('customers.statement.show');
        Route::get('/balance-details', 'CustomerBalanceController@show')->middleware('customers.access:view')->name('customers.balance');
        Route::get('/contact-info', 'CustomerInfoController@show')->middleware('customers.access:view')->name('customers.info.show');
        Route::get('/documents', 'CustomerDocumentController@index')->middleware('customers.access:view')->name('customers.documents.index');
        Route::post('/documents', 'CustomerDocumentController@store')->middleware('customers.access:edit')->name('customers.documents.store');
        Route::get('/documents/{attachmentId}/download', 'CustomerDocumentController@download')->middleware('customers.access:view')->name('customers.documents.download');
        Route::delete('/documents/{attachmentId}', 'CustomerDocumentController@destroy')->middleware('customers.access:edit')->name('customers.documents.destroy');
        Route::get('/audit', 'CustomerAuditController@index')->middleware('customers.access:view')->name('customers.audit.index');
        Route::get('/notes', 'CustomerNotesController@index')->middleware('customers.access:view')->name('customers.notes.index');
        Route::post('/notes', 'CustomerNotesController@store')->middleware('customers.access:edit')->name('customers.notes.store');
        Route::delete('/notes/{noteId}', 'CustomerNotesController@destroy')->middleware('customers.access:edit')->name('customers.notes.destroy');
        Route::get('/activity', 'CustomerActivityController@index')->middleware('customers.access:view')->name('customers.activity.index');
    });

    Route::get('/{id}/profile', 'CustomerProfileController@show')
        ->middleware('customers.access:view')
        ->name('customers.profile.show');
    Route::get('/{id}/edit', 'CustomerController@edit')
        ->middleware('customers.access:edit')
        ->name('customers.edit');
    Route::put('/{id}', 'CustomerController@update')
        ->middleware('customers.access:edit')
        ->name('customers.update');
    Route::delete('/{id}', 'CustomerController@destroy')
        ->middleware('customers.access:delete')
        ->name('customers.destroy');
    Route::get('/{id}', 'CustomerController@show')
        ->middleware('customers.access:view')
        ->name('customers.show');
});

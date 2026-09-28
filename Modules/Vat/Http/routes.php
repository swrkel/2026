<?php


Route::get('update-statement-nos', 'Modules\Vat\Http\Controllers\CustomerStatementController@updatePrefixes');

Route::group(['middleware' => ['web','tenant.context','IsSubscribed:vat_module_main'], 'prefix' => 'vat-module', 'namespace' => 'Modules\Vat\Http\Controllers'], function () {
    
    /*
     * MA-002 TEMPORARY DIAGNOSTIC.
     * Receives one report per page load when a click fails to reach the
     * control it was aimed at, and writes it to laravel.log. Remove this
     * route, Ma002DiagnosticController and the ma002_click_diagnostic
     * partial once the cause is identified.
     */
    Route::post('ma002-click-report', 'Ma002DiagnosticController@clickReport')
        ->name('vat.ma002.click-report');

    Route::get('get-customer-statement-no', 'CustomerStatementController@getCustomerStatementNo');  
    Route::get('customer-statement/get-statement-list', 'CustomerStatementController@getCustomerStatementList');
    Route::get('customer-statement/reprint/{statement_id}', 'CustomerStatementController@rePrint');
    Route::get('customer-statement/print126Statement/{statement_id}', 'CustomerStatementController@print126Statement');
    Route::get('customer-statement/export-excel/{statement_id}', 'CustomerStatementController@exportExcel');
    Route::get('customer-statement/{statement_id}/pay-statement-amount', 'CustomerStatementController@payStatementAmountForm')
        ->name('vat.customer-statement.pay-statement-amount.form');
    Route::post('customer-statement/{statement_id}/pay-statement-amount', 'CustomerStatementController@payStatementAmount')
        ->name('vat.customer-statement.pay-statement-amount.store');
    Route::post('/download-pdf', 'CustomerStatementController@downloadPdf');
    Route::get('customer-date', 'CustomerStatementController@getMinimumDate');
    
    Route::delete('customer-statement-payments/{id}', 'CustomerStatementController@destroyPayments');
    
    

    Route::delete('customer-statement/delete-transaction/{id}', 'CustomerStatementController@deleteTransuction')
    ->name('customer-statement.deleteTransaction');
    
    
    
    Route::get('convert-to-vat/{id}', 'CustomerStatementController@convertVAT');
    
    Route::resource('customer-statement', 'CustomerStatementController');
    
    Route::resource('vat-statement-logo', 'VatStatementLogoController');
    
    
    
    Route::get('/get-prefix/{id}', 'VatInvoiceController@getPrefixes');
    Route::get('/print/{id}', 'VatInvoiceController@print');
    Route::get('/vat-invoice/products-sold', 'VatInvoiceController@productsSold');
    
    Route::get('/get-route-ops/{id}', 'VatInvoice2Controller@getRouteOperations');
    Route::get('/get-ro-details/{id}', 'VatInvoice2Controller@routeOperationDetails');
    
    Route::get('/get-prefix2/{id}', 'VatInvoice2Controller@getPrefixes');
    Route::get('/print2/{id}', 'VatInvoice2Controller@print');
    
    Route::get('/fleet-print2/{id}', 'FleetVatInvoice2Controller@print');
    
    Route::get('/invoices-127', 'VatInvoice2Controller@index127');
    Route::get('/invoices-127/create', 'VatInvoice2Controller@create127');
    Route::post('/invoices-127', 'VatInvoice2Controller@store127');
    Route::get('/print127/{id}', 'VatInvoice2Controller@print127');
    Route::get('/printdesign2026/{id}', 'VatInvoice2Controller@print_design_2026');
    Route::get('/print163/{id}', 'VatInvoice2Controller@print_163');
    Route::get('/printdesign2026/{id}/127', 'VatInvoice2Controller@print_design_2026_127');
    
    Route::get('/statement-126', 'VatStatement126Controller@index');
    Route::get('/statement-126/create', 'VatStatement126Controller@create');
    Route::post('/statement-126', 'VatStatement126Controller@store');
    Route::get('/statement-126/edit/{id}', 'VatStatement126Controller@edit');
    Route::put('/statement-126/update/{id}', 'VatStatement126Controller@update');
    Route::delete('/statement-126/{id}', 'VatStatement126Controller@destroy');
    Route::get('/print126/{id}', 'VatStatement126Controller@print');
    Route::get('/statement/print-design-126/{id}', 'VatStatement126Controller@print_design_2026');
    Route::get('/statement-126/126-print/{id}', 'VatStatement126Controller@print_126');
    Route::get('/statement-126/products-sold', 'VatStatement126Controller@productsSold');
    Route::get('/statement-126/invoices-setting', 'VatStatement126Controller@invoicesSetting');
    Route::post('/statement-126/invoices-setting', 'VatStatement126Controller@invoicesSetting');
    Route::post('/statement-126/invoices-setting/updateSetting', 'VatStatement126Controller@updateSetting');
    Route::get('/statement-126/assign-prefix', 'VatStatement126Controller@assignPrefix');
    Route::post('/statement-126/assign-prefix', 'VatStatement126Controller@storeAssignPrefix');
    Route::delete('/statement-126/assign-prefix/{id}', 'VatStatement126Controller@deleteAssignPrefix');


    Route::resource('/statement-126-prefix', 'VatStatement126PrefixController');
    
    Route::get('/quick-add-customer', 'VatInvoice2Controller@customerQuickAdd');
    Route::post('/quick-add-customer', 'VatInvoice2Controller@storeQuickCustomer');
    
    Route::get('/quick-add-reference', 'VatInvoice2Controller@referenceQuickAdd');
    Route::post('/quick-add-reference', 'VatInvoice2Controller@storeQuickReference');
    
    // VAT-owned AJAX endpoints used by Add VAT Invoice-2. These routes must
    // remain before the resource route so they are not captured as a show ID.
    Route::get('/vat-invoice2/customer-details/{customer_id}', 'VatInvoice2Controller@getCustomerAjaxDetails');
    Route::get('/vat-invoice2/customer-vat-number/{customer_id}', 'VatInvoice2Controller@getCustomerAjaxDetails');
    Route::get('/vat-invoice2/product-details/{product_id}', 'VatInvoice2Controller@getProductAjaxDetails');

    Route::get('/vat-invoice2/products-sold', 'VatInvoice2Controller@productsSold');
    Route::get('/invoices-127/edit127/{id}', 'VatInvoice2Controller@edit127');
    Route::put('/invoices-127/update127/{id}', 'VatInvoice2Controller@update127');
    Route::get('vat-invoice2/logo-upload', 'VatInvoice2Controller@logoUpload');
    Route::post('vat-invoice2/logo-upload', 'VatInvoice2Controller@logoUploadSave');
    
    Route::get('/vat-invoice2/invoices_setting', 'VatInvoice2Controller@invoicesSetting');
    Route::post('/vat-invoice2/invoices_setting/updateSetting', 'VatInvoice2Controller@updateSetting');
    
    Route::get('/vat-statement/setting', 'CustomerStatementController@invoicesSetting');
    Route::post('/vat-statement/setting/updateSetting', 'CustomerStatementController@updateSetting'); 
    
    Route::get('/vat-invoice2/vat-invoice-to-transactions', 'VatInvoiceToTransactionController@index');
    Route::post('/vat-invoice2/update-vat-invoice-to-transactions', 'VatInvoiceToTransactionController@store');
    Route::get('/vat-invoice2/toggle-vat-invoice-to-transactions/{id}', 'VatInvoiceToTransactionController@toggleStatus');
    Route::get('/vat-invoice2/vat-invoice-to-transactions-history', 'VatInvoiceToTransactionController@history');

    Route::resource('/vat-invoice2', 'VatInvoice2Controller');
    
    Route::resource('/fleet-vat-invoice2', 'FleetVatInvoice2Controller');
    
    Route::resource('/vat-settings', 'SettingsController');
    Route::resource('/vat-invoice', 'VatInvoiceController');
    Route::resource('/vat-discount', 'VatPenaltyController');
    Route::resource('/vat-prefix', 'VatPrefixController');
    
    Route::resource('/vat-statement-prefix', 'VatStatementPrefixController');
    Route::resource('/vat-invoice2-prefix', 'VatInvoice2PrefixController');
    
    Route::resource('/vat-user-prefix', 'VatUserInvoicePrefixController');
    Route::resource('/vat-sms-type', 'VatInvoiceSmsTypeController');
    
    Route::resource('/vat-creditbill', 'VatCreditBillController');
    
    Route::resource('/vat-category', 'CategoryController');
    
    Route::resource('/vat-concerns', 'VatConcernController');
    Route::resource('/vat-bank-details', 'VatBankDetailController');
    Route::resource('/vat-supply-from', 'VatSupplyFromController');
    
    Route::get('/import-products', 'ImportProductsController@index');
    Route::post('/import-products/store', 'ImportProductsController@store');
    
    Route::get('/import-contacts', 'ImportContactsController@index');
    Route::post('/import-contacts/store', 'ImportContactsController@store');
    
    Route::resource('/vat-payable', 'VatPayableToAccountController');
    
    Route::post('/vat-products/mass-delete', 'VatProductController@massDestroy');
    Route::post('/vat-products/mass-deactivate', 'VatProductController@massDeactivate');
    Route::post('/vat-products/check_product_sku', 'VatProductController@checkProductSku');
    Route::get('/vat-products/view/{id}', 'VatProductController@view');
    Route::post('/vat-products/product_form_part', 'VatProductController@getProductVariationFormPart');
    Route::get('/vat-product-search', 'VatProductController@getProducts');
    Route::post('/vat-purchases/get_purchase_entry_row', 'VatPurchaseController@getPurchaseEntryRow');
    Route::get('/vat-purchases/supplier-details/{id}', 'VatPurchaseController@getSupplierDetails');
    Route::get('/vat-purchases/print/{id}', 'VatPurchaseController@printInvoice');
    Route::post('/vat-purchases/update-status', 'VatPurchaseController@updateStatus');
     
    
    Route::resource('/vat-expense-categories', 'VatExpenseCategoryController');
    Route::get('/vat-expense/prefix-settings', 'VatExpenseController@prefixSettings');
    Route::get('/vat-expense/prefix-settings/edit', 'VatExpenseController@editPrefixSettings');
    Route::post('/vat-expense/prefix-settings', 'VatExpenseController@storePrefixSettings');
    Route::resource('/vat-expense', 'VatExpenseController');
    Route::resource('/vat-payments', 'VatPaymentController');
    Route::resource('/vat-units', 'VatUnitController');
    Route::get('/vat-toggle-activate/{id}', 'VatContactController@toggleActivate');
    Route::post('/contact-massdestroy', 'VatContactController@massDestroy');
    Route::get('/vat-contacts/direct-create/{type?}', 'VatContactController@directCreate')->name('vat.contacts.direct_create');
    Route::get('/vat-contacts/create-page/{type?}', 'VatContactController@directCreate')->name('vat.contacts.create_page');
    Route::resource('/vat-contacts', 'VatContactController');
    Route::resource('/vat-products', 'VatProductController');
    Route::resource('/vat-purchases', 'VatPurchaseController');
    
    Route::get('/reports-get-ledger', 'VatReportController@getLedger');
    
    Route::resource('/reports-ledger', 'VatReportController');
    
   Route::get('/customer-vat-schedule', 'VatController@getCustomerVatSchedule');
   Route::get('/supplier-vat-schedule', 'VatController@getSupplierVatSchedule');
   
   Route::post('/update-range-vats', 'VatController@updateVats');
   Route::get('/update-single-vats', 'VatController@updateSingleVats');
   
   Route::get('/reports-summary', 'VatController@getVatReportSummary');
   Route::get('/reports', 'VatController@getVatReport');
   Route::get('/print', 'VatController@printVatReport');
   
   
   
   
   
    Route::get('/settlement/get_balance_stock/{id}', 'VatSettlementController@getBalanceStock');
    Route::get('/settlement/update-credit-sales', 'VatSettlementController@updateCreditSales');
    
    Route::get('/settlement/get_balance_stock_by_id/{id}', 'VatSettlementController@getBalanceStockById');
    
    Route::delete('/settlement/delete-other-sale/{id}', 'VatSettlementController@deleteOtherSale');
    Route::delete('/settlement/delete-meter-sale/{id}', 'VatSettlementController@deleteMeterSale');
    
    Route::post('/settlement/save-customer-payment', 'VatSettlementController@saveCustomerPayment');
    Route::post('/settlement/save-other-sale', 'VatSettlementController@saveOtherSale');
    Route::post('/settlement/save-meter-sale', 'VatSettlementController@saveMeterSale');
    
    Route::get('/settlement/get-pump-details/{pump_id}', 'VatSettlementController@getPumpDetails');
    Route::get('/settlement/print/{id}', 'VatSettlementController@print');
    Route::resource('/settlement', 'VatSettlementController')->names('vat.settlement');
    
    Route::get('/settlement/payment/get-product-price', 'VatAddPaymentController@getProductPrice');


    Route::delete('/settlement/payment/delete-credit-sale-payment/{id}', 'VatAddPaymentController@deleteCreditSalePayment');
    Route::post('/settlement/payment/save-credit-sale-payment', 'VatAddPaymentController@saveCreditSalePayment');
    
    Route::delete('/settlement/payment/delete-card-payment/{id}', 'VatAddPaymentController@deleteCardPayment');
    Route::post('/settlement/payment/save-card-payment', 'VatAddPaymentController@saveCardPayment');
    
    Route::delete('/settlement/payment/delete-cash-payment/{id}', 'VatAddPaymentController@deleteCashPayment');
    Route::post('/settlement/payment/save-cash-payment', 'VatAddPaymentController@saveCashPayment');
    
    
    // Keep the existing GET endpoint/controller behaviour, but use VAT-specific
    // route names so route:cache does not collide with payment.* routes from
    // other modules.
    Route::get('/settlement/payment', 'VatAddPaymentController@create')
        ->name('vat.settlement.payment.index');

    Route::resource('/settlement/payment', 'VatAddPaymentController')
        ->except(['index'])
        ->names([
            'create' => 'vat.settlement.payment.create',
            'store' => 'vat.settlement.payment.store',
            'show' => 'vat.settlement.payment.show',
            'edit' => 'vat.settlement.payment.edit',
            'update' => 'vat.settlement.payment.update',
            'destroy' => 'vat.settlement.payment.destroy',
        ]);
   
   
});

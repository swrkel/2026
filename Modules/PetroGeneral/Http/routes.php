<?php

/*
|--------------------------------------------------------------------------
| PG007 - Petro General final integration route loader
|--------------------------------------------------------------------------
| Small functional route files are loaded first for the Petro General pages
| listed in document 8000. Older copied/fallback routes remain below to avoid
| disrupting any functions that are already working during user testing.
*/

Route::group([
    // IS2338: authenticate and refresh the shared user/business session BEFORE
    // Petro General switches the operational DB connection. Initializing tenancy
    // before auth can make the session's numeric user id resolve against another
    // users table (where the same id may belong to a different person), which then
    // contaminates the shared header/session used by other modules as well.
    // Tenant initialization still runs before the controller, so Petro General
    // data queries continue to execute in the correct operational database.
    'middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone', \Modules\PetroGeneral\Http\Middleware\InitializePetroGeneralTenantContext::class, \Modules\PetroGeneral\Http\Middleware\EnsurePetroGeneralModuleEnabled::class, \Modules\PetroGeneral\Http\Middleware\RenderPetroGeneralStatusMessage::class],
    'prefix' => 'petro-general',
    'namespace' => 'Modules\PetroGeneral\Http\Controllers'
], function () {
    Route::get('/assets/js/{file}', 'AssetController@javascript')
        ->where('file', '[A-Za-z0-9._-]+')
        ->name('petrogeneral.assets.js');

    require module_path('PetroGeneral', 'Routes/dashboard.php');
    require module_path('PetroGeneral', 'Routes/tanks.php');
    require module_path('PetroGeneral', 'Routes/pumps.php');
    require module_path('PetroGeneral', 'Routes/pumpers.php');
    require module_path('PetroGeneral', 'Routes/dips.php');
    require module_path('PetroGeneral', 'Routes/daily_status.php');
    require module_path('PetroGeneral', 'Routes/transfers.php');
    require module_path('PetroGeneral', 'Routes/user_activity.php');
    require module_path('PetroGeneral', 'Routes/sms.php');
    require module_path('PetroGeneral', 'Routes/settings.php');
});

Route::group(['middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone', \Modules\PetroGeneral\Http\Middleware\InitializePetroGeneralTenantContext::class, \Modules\PetroGeneral\Http\Middleware\EnsurePetroGeneralModuleEnabled::class, \Modules\PetroGeneral\Http\Middleware\RenderPetroGeneralStatusMessage::class], 'prefix' => 'petro-general', 'namespace' => 'Modules\PetroGeneral\Http\Controllers'], function () {
    Route::get('/adjust-dates', 'SettlementController@adjustMeterSalesDates');

});
//Settlement SW routes
Route::group(['middleware' => ['web', 'auth', 'language', 'SetSessionData', 'DayEnd', \Modules\PetroGeneral\Http\Middleware\InitializePetroGeneralTenantContext::class, \Modules\PetroGeneral\Http\Middleware\EnsurePetroGeneralModuleEnabled::class, \Modules\PetroGeneral\Http\Middleware\RenderPetroGeneralStatusMessage::class], 'prefix' => 'petro-general'], function () {

    // Settlement SW routes - using full namespace to avoid namespace conflicts
    Route::get('/settlement-sw', '\Modules\SettlementSW\Http\Controllers\SettlementSWController@index')->name('settlement_sw.index');
    Route::get('/settlement-sw/create', '\Modules\SettlementSW\Http\Controllers\SettlementSWController@create')->name('settlement_sw.create');
    Route::post('/settlement-sw/store', '\Modules\SettlementSW\Http\Controllers\SettlementSWController@store')->name('settlement_sw.store');
    Route::get('/settlement-sw/show/{id}', '\Modules\SettlementSW\Http\Controllers\SettlementSWController@show')->name('settlement_sw.show');
    Route::get('/settlement-sw/edit/{id}', '\Modules\SettlementSW\Http\Controllers\SettlementSWController@edit')->name('settlement_sw.edit');
    Route::post('/settlement-sw/update/{id}', '\Modules\SettlementSW\Http\Controllers\SettlementSWController@update')->name('settlement_sw.update');
    Route::delete('/settlement-sw/delete/{id}', '\Modules\SettlementSW\Http\Controllers\SettlementSWController@destroy')->name('settlement_sw.destroy');
// End Settlement SW routes

});
// end  Settlement SW routes
Route::group(['middleware' => ['web', 'auth', 'language', 'SetSessionData', 'DayEnd', \Modules\PetroGeneral\Http\Middleware\InitializePetroGeneralTenantContext::class, \Modules\PetroGeneral\Http\Middleware\EnsurePetroGeneralModuleEnabled::class, \Modules\PetroGeneral\Http\Middleware\RenderPetroGeneralStatusMessage::class], 'prefix' => 'petro-general', 'namespace' => 'Modules\PetroGeneral\Http\Controllers'], function () {
    /*
     * MA-002 (IS-1909): this duplicate route is REMOVED.
     *
     * Two routes claimed GET /petro-general/dashboard:
     *     Routes/dashboard.php  ->  Dashboard\DashboardController@index   (new)
     *     here                  ->  PetroController@index                 (old)
     *
     * dashboard.php is required at the top of this file, so the line below was
     * registered LAST - and in Laravel the last registration of a method+uri
     * pair wins. The old controller was therefore serving the page, rendering
     * petrogeneral::dashboard (the copied Petro view) instead of
     * petrogeneral::dashboard.index with its summary cards, tank overview and
     * pump overview.
     *
     * That is why the dashboard showed only "Welcome ..." and nothing else: the
     * old view loops over $fuel_tanks, and its controller reads
     * session('business.id'), which is not always populated - so the loop had
     * nothing to draw.
     *
     * Removing this line lets the new dashboard serve the page. PetroController
     * itself is untouched and still handles its other actions.
     *
     * // Route::get('/dashboard', 'PetroController@index');
     */

    Route::post('/tank/save-import', 'FuelTankController@saveImport');
    Route::get('/tank/import', 'FuelTankController@import');

    Route::get('/tank-management/get-tank-product', 'FuelTankController@getTankProduct');
    Route::resource('/tank-management', 'FuelTankController');

    Route::resource('/prefixes', 'CustomerBillVatPrefixController');

    Route::resource('/notification-templates', 'PetroNotificationTemplateController');
    Route::resource('/whatsapp-templates', 'PetroWhatsAppTemplateController');
    Route::get('/day-end-settlement-pumps', 'DayEndSettlementController@pendingPumps')
        ->name('petrogeneral.day_end_settlement.pumps');
    Route::get('/day-end-settlement-pos-totals', 'DayEndSettlementController@posTotals')
        ->name('petrogeneral.day_end_settlement.pos_totals');
    Route::resource('/day-end-settlement', 'DayEndSettlementController')->names([
        'index' => 'petrogeneral.day_end_settlement.index',
        'create' => 'petrogeneral.day_end_settlement.create',
        'store' => 'petrogeneral.day_end_settlement.store',
        'show' => 'petrogeneral.day_end_settlement.show',
        'edit' => 'petrogeneral.day_end_settlement.edit',
        'update' => 'petrogeneral.day_end_settlement.update',
        'destroy' => 'petrogeneral.day_end_settlement.destroy',
    ]);

    Route::resource('/tank-transfer', 'TankTransferController');

    Route::get('/tanks-transaction-summary', 'TanksTransactionDetailController@tankTransactionSummary');
    Route::resource('/tanks-transaction-details', 'TanksTransactionDetailController');
    Route::post('/pumps/save-import', 'PumpController@saveImport');
    Route::get('/pumps/import', 'PumpController@importPumps');
    Route::get('/pump-management/get-meter-readings', 'PumpController@getMeterReadings');
    Route::get('/pump-management/get-testing-details', 'PumpController@getTestingDetails');
    Route::resource('/pump-management', 'PumpController');

    Route::get('/pump-operators/get-settings', 'PumpOperatorController@dashboard_settings');
    Route::post('/pump-operators/get-settings', 'PumpOperatorController@store_settings');

    Route::get('/pump-operators/update-passcode', 'PumpOperatorController@update_passcode');
    Route::post('/pump-operators/update-passcode', 'PumpOperatorController@store_passcode');
    Route::post('/daily-shifts', 'DailyShiftController@store');
    Route::post('/open-shifts', 'DailyShiftController@OpenShift');
    Route::post('/save-shift', 'DailyShiftController@saveShift');
    Route::get('/fetch/openShift', 'DailyShiftController@fetchOpenShift')->name('fetch.openShift');
    Route::post('/edit-shift/{petroDailyShift}', 'DailyShiftController@editShift')->name('daily-shift.edit-save-shift');

    Route::get('/pump-operators/get-pumpter-excess-shortage-payments', 'PumpOperatorController@getPumperExcessShortagePayments');
    Route::post('/pump-operators/save-import', 'PumpOperatorController@saveImport');
    Route::get('/pump-operators/import', 'PumpOperatorController@importPumps');
    // Superseded by Routes/pumpers.php, which names this route. An unnamed
    // duplicate registered first wins the URL, so the named one was discarded.
    //Route::get('/pump-operators/ledger', 'PumpOperatorController@getLedger');
    Route::get('/pump-operators/list-commission/{id}', 'PumpOperatorController@listCommission');
    Route::resource('/recover-shortage', 'RecoverShortageController');
    Route::resource('/excess-comission', 'ExcessComissionController');
    Route::get('/pump-operators/toggle-active/{id}', 'PumpOperatorController@toggleActivate');
    Route::get('/pump-operators/get-dashboard-data', 'PumpOperatorController@getDashboardData');

    Route::get('/day-entries', 'PumperDayEntryController@index')->name('petrogeneral.day_entries.index');
    Route::get('/day-entry-summary', 'PumperDayEntryController@getPumperDayEntrySummary')->name('petrogeneral.day_entries.summary');

    Route::get('/pump-operators/setting_dash', 'PumpOperatorController@setting_dash');
    Route::get('/pump-operators/dashboard', 'PumpOperatorController@dashboard');
    // Superseded by Routes/pumpers.php, which names this route. An unnamed
    // duplicate registered first wins the URL, so the named one was discarded.
    //Route::get('/pump-operators/check-passcode', 'PumpOperatorController@checPasscode');
    // Superseded by Routes/pumpers.php, which names this route. An unnamed
    // duplicate registered first wins the URL, so the named one was discarded.
    //Route::get('/pump-operators/check-username', 'PumpOperatorController@checUsername');
    // PUMPER-MGMT-LIVE-ROUTE-20260821:
    // These routes are registered once in Routes/pumpers.php. Do not duplicate the
    // pumper-day-entries resource here because it can shadow get-daily-collection.
    
    // Payment edit route for Payment Summary - use different path to avoid conflict with pump operator edit
    Route::get('/pump-operators/payment/{id}/edit', 'PumpOperatorPaymentController@edit')->name('pump-operators.payments.edit');
    
    Route::get('/pump-operators/set-main-system-session', 'PumpOperatorController@setMainSystemSession');
    // Superseded by Routes/pumpers.php, which names this route. An unnamed
    // duplicate registered first wins the URL, so the named one was discarded.
    //Route::get('/pump-operators/unblock-pumper-login-attempt/{id}', 'PumpOperatorController@unblockPumperLoginAttempt');
    // Superseded by Routes/pumpers.php, which names this route. An unnamed
    // duplicate registered first wins the URL, so the named one was discarded.
    //Route::get('/pump-operators/unblock-pumper-login-attempts', 'PumpOperatorController@blockedPumperLoginAttempt');
    // Restore 'edit' for pump operators resource route
    Route::resource('/pump-operators', 'PumpOperatorController');
    Route::resource('/opening-meter', 'OpeningMeterController');

    Route::delete('/pump-operator/delete-other-sale/{sale_id}', 'PumpOperatorPaymentController@deleteOtherSaleItem');
    Route::post('/pump-operator/update-other-sale-quantity/{sale_id}', 'PumpOperatorPaymentController@updateOtherSaleItem');

    Route::post('/pump-operator-actions/get-colsing-meter/{pump_id}', 'PumpOperatorActionsController@postClosingMeter');
    Route::get('/pump-operator-actions/get-colsing-meter/{pump_id}', 'PumpOperatorActionsController@getClosingMeter');
    Route::get('/pump-operator-actions/get-colsing-meter-modal', 'PumpOperatorActionsController@getClosingMeterModal');
    Route::get('/pump-operator-actions/get-receive-pump', 'PumpOperatorActionsController@getReceivePump');

    Route::post('/pump-operator-pmts/save-credit', 'PumpOperatorPaymentController@saveCredit');
    Route::get('/pump-operator-pmts/print-credit-sale/{id}', 'PumpOperatorPaymentController@printCreditSale');

    Route::post('/pump-operator-pmts/save-cheque', 'PumpOperatorPaymentController@saveChequePayment');

    Route::post('/pump-operator-pmts/save-other-sale', 'PumpOperatorPaymentController@saveOtherSale');

    Route::post('/pump-operator-pmts/save-other-sale-items', 'PumpOperatorPaymentController@saveOtherSaleItems');

    Route::post('/pump-operator-pmts/save-cash-denom', 'PumpOperatorPaymentController@saveCashDenom');

    Route::post('/pump-operator-pmts/save-card-pmt', 'PumpOperatorPaymentController@saveCardPayment');

    Route::post('/pump-operator-pmts/save-meter-sale', 'PumpOperatorPaymentController@saveMeterSale');

    Route::get('/pump-operator-pmts/get-other-sale', 'PumpOperatorPaymentController@getOtherSale');
    Route::get('/pump-operator/get-payment-summary-dashboard', 'PumpOperatorPaymentController@summarypaymnetdashboard');

    Route::get('/dailycollection/summary', 'DailyCollectionController@collectionSummary');

    Route::get('/dailycollection/shortage-excess', 'DailyCollectionController@indexShortageExcess');
    Route::delete('/dailycollection/shortage-excess/{id}', 'DailyCollectionController@destroyShortageExcess');

    Route::get('/dailycollection/cheques', 'DailyCollectionController@indexCheque');

    Route::get('/dailycollection/others', 'DailyCollectionController@indexOther');

    Route::get('/pump-operator-payments/get-payment-modal', 'PumpOperatorPaymentController@getPaymentModal');
    Route::get('/pump-operator-payments/meters-with-payments', 'PumpOperatorPaymentController@metersWithPayments');
    Route::get('/pump-operator-payments/get-other-sales/{id}', 'PumpOperatorPaymentController@otherSales');
    Route::get('/pump-operator-payments/othersale', 'PumpOperatorPaymentController@othersalespage');
    Route::get('/pump-operator-payments/othersale/getproducts', 'PumpOperatorPaymentController@getProducts');
    Route::delete('/pump-operator-payments/delete-other-sale/{id}', 'PumpOperatorPaymentController@deleteOtherSale');
    Route::get('/pump-operator-payments/othersale-list', 'PumpOperatorPaymentController@otherSalesList');
    Route::get('/pump-operator-payments/othersales-list', 'PumpOperatorPaymentController@pumpOtherSalesList');
    Route::get('/pump-operator-payments/metersale-list', 'PumpOperatorPaymentController@meterSalesList');

    Route::get('/pump-operator-payments/get-modal', 'PumpOperatorPaymentController@getPaymentSummaryModal');
    Route::get('/pump-operator-payments/balance-to-operator/{pump_operator}', 'PumpOperatorPaymentController@balanceToOperator');
    
    // Explicit edit route - must be defined before resource route to take precedence
    Route::get('/pump-operator-payments/{id}/edit', 'PumpOperatorPaymentController@edit')->name('pump-operator-payments.edit');
    
    // Resource route - exclude 'edit' to prevent conflict with explicit route above
    Route::resource('/pump-operator-payments', 'PumpOperatorPaymentController', ['except' => ['edit']]);
    Route::get('/closing-shift/close-shift/{pump_operator_id}', 'ClosingShiftController@closeShift');
    Route::resource('/closing-shift', 'ClosingShiftController');
    Route::get('/current-meter/get-modal', 'CurrentMeterController@getModal');
    Route::resource('/current-meter', 'CurrentMeterController');
    Route::get('/unload-stock/get-details', 'UnloadStockController@getDetails');
    Route::resource('/unload-stock', 'UnloadStockController');

    Route::get('/get-assigned-pumps/{id}', 'PumpOperatorController@getAssignedPumps');

    Route::get('/pump-operator-actions/get-pumper-assignment/{pump_id}/{pump_operator_id}', 'PumpOperatorAssignmentController@getPumperAssignment');

    Route::get('/pump-operator-actions/get-day-entry-summary', 'PumperDayEntryController@getPumperDayEntrySummary');

    Route::get('/pump-operator-actions/get-closing-shift-summary', 'PumperDayEntryController@getClosingShiftSummary');

    Route::get('/pump-operator-actions/confirm-pumps/{assignment_id}', 'PumpOperatorAssignmentController@confirmAssignment');
    Route::post('/pump-operator-actions/confirm-pumps/{assignment_id}', 'PumpOperatorAssignmentController@postConfirmAssignment');

    Route::post('/bulk-pump-operator-assignment', 'PumpOperatorAssignmentController@storeBulk');
    Route::resource('/pump-operator-assignment', 'PumpOperatorAssignmentController');

    //common controller for document & note
    Route::get('get-document-note-page', 'PumperDocumentAndNoteController@getDocAndNoteIndexPage');
    Route::post('post-document-upload', 'PumperDocumentAndNoteController@postMedia');
    Route::resource('pumper-note-documents', 'PumperDocumentAndNoteController');

    Route::get('/daily-collection/print/{pump_operator_id}', 'DailyCollectionController@print');

    Route::get('/daily-collection/edit-shortage/{id}', 'DailyCollectionController@editShortage');
    Route::put('/daily-collection/edit-shortage/{id}', 'DailyCollectionController@updateShortage');

    Route::get('/daily-collection/get-balance-collection/{pump_operator_id}', 'DailyCollectionController@getBalanceCollection');

    Route::get('/daily-collection/get-daily-shift/{pump_operator_id}', 'DailyCollectionController@getByOperator');

    Route::get('/get-daily-shifts-by-operator', [\Modules\PetroGeneral\Http\Controllers\DailyCardController::class, 'getDailyShiftsByOperator']);

    Route::resource('/daily-collection', 'DailyCollectionController');

    Route::resource('/daily-cards', 'DailyCardController');

    /*
     * IS2243 - Daily Status Report route de-duplication.
     *
     * The Daily Status AJAX/print/PDF endpoints are registered earlier from
     * Routes/daily_status.php with the petrogeneral.daily_status.* route names.
     * Re-registering the same method + URI here replaced those named routes in
     * Laravel's route collection (especially after route cache compilation),
     * causing Blade to fail with: Route [petrogeneral.daily_status.get_pump_sales]
     * not defined. Keep only the legacy /daily-status-report resource URL here;
     * the helper endpoints have one authoritative registration above.
     */
    Route::resource('/daily-status-report', 'DailyStatusReportController');

//   Route::get('/settlement/get-pump-details/{pump_id}', 'SettlementController@getPumpDetails');
    Route::get('/settlement/get-pump-details/{pump_id}/{shift_id?}', 'SettlementController@getPumpDetails');

    Route::get('/settlement/get_balance_stock/{id}', 'SettlementController@getBalanceStock');
    Route::get('/settlement/activity-report', 'SettlementController@getUserActivityReport');
    Route::get('/settlement/get_pumps/{id}', 'SettlementController@getPumps');
    Route::get('/settlement/get_pumps_by_location', 'SettlementController@getPumpsByLocation');

    Route::get('/settlement/get-meter-sales', 'SettlementController@meter_sales');

    Route::get('/settlement/update-meter-sale/{id}', 'SettlementController@editMeterSale');
    Route::post('/settlement/update-meter-sale/{id}', 'SettlementController@updateMeterSale');

    Route::get('/settlement/update-credit-sales', 'SettlementController@updateCreditSales');

    Route::get('/settlement/get_balance_stock_by_id/{id}', 'SettlementController@getBalanceStockById');
    Route::get('/settlement/check_prev_settlement', 'SettlementController@checkPreviousPumpSettlement');
    Route::delete('/settlement/delete-customer-payment/{id}', 'SettlementController@deleteCustomerPayment');
    Route::delete('/settlement/delete-other-income/{id}', 'SettlementController@deleteOtherIncome');
    Route::delete('/settlement/delete-other-sale/{id}', 'SettlementController@deleteOtherSale');
    Route::delete('/settlement/delete-meter-sale/{id}', 'SettlementController@deleteMeterSale');
    Route::post('/settlement/save-customer-payment', 'SettlementController@saveCustomerPayment');
    Route::post('/settlement/save-other-income', 'SettlementController@saveOtherIncome');
    Route::post('/settlement/save-other-sale', 'SettlementController@saveOtherSale');
    Route::post('/settlement/save-meter-sale', 'SettlementController@saveMeterSale');
    Route::post('/settlement/manual-shift-number', 'SettlementController@storeManualShiftNumber');
    Route::get('/settlement/get-pump-details/{pump_id}/{shift_id}', 'SettlementController@getPumpDetailsPerShift');
    // Route::get('/settlement/get-pump-details/{pump_id}/{operator_id}', 'SettlementController@getPumpDetails');
    Route::get('/settlement/print/{id}', 'SettlementController@print');
    Route::get('/settlement/mechanical-meter/{id}', 'SettlementController@mechanicalMeter');
    Route::get('/settlement/get-meter-sale-form/{id}', 'SettlementController@getMeterSaleForm');
    Route::post('/settlement/update-settlement-meter-sale/{id}', 'SettlementController@updateSettlementMeterSale');
    Route::get('/settlement/check-slip-no', 'SettlementController@checkSlipNo');
    Route::get('/settlement/get-payment-tab-totals', 'SettlementController@getPaymentTabTotals');
    Route::resource('/settlement', 'SettlementController');

    // Custom settlement-pd routes MUST come BEFORE the resource route
    Route::get('/pdsettlement-pd/check-prev-settlement', 'SettlementPDController@checkPreviousPumpSettlementPD');
    Route::get('/settlement-pd/get-pump-details/{pump_id}/{shift_id}', 'SettlementPDController@getPumpDetailsPerShift');
    Route::get('/settlement-pd/get-meter-sale-form/{id}', 'SettlementPDController@getMeterSaleForm');
    Route::delete('/settlement-pd/delete-meter-sale/{id}', 'SettlementPDController@deleteMeterSale');
    Route::get('/settlement-pd/print/{id}', 'SettlementPDController@print');
    Route::get('/settlement-pd/update-meter-sale/{id}', 'SettlementPDController@editMeterSale');
    Route::post('/settlement-pd/update-meter-sale/{id}', 'SettlementPDController@updateMeterSale');
     Route::post('/settlement-pd/update-settlement-meter-sale/{id}', 'SettlementPDController@updateSettlementMeterSale');
       Route::post('/settlement-pd/save-meter-sale', 'SettlementPDController@saveMeterSale');
    Route::post('/settlement-pd/save-other-sale', 'SettlementPDController@saveOtherSale');
   Route::get('/settlement-pd/get-payment-tab-totals', 'SettlementPDController@getPaymentTabTotals');
    // Resource route MUST come LAST
    Route::resource('/settlement-pd', 'SettlementPDController');

    Route::delete('/settlement/payment/delete-excess-payment/{id}', 'AddPaymentController@deleteExcessPayment');
    Route::post('/settlement/payment/save-excess-payment', 'AddPaymentController@saveExcessPayment');
    Route::delete('/settlement/payment/delete-shortage-payment/{id}', 'AddPaymentController@deleteShortagePayment');
    Route::post('/settlement/payment/save-shortage-payment', 'AddPaymentController@saveShortagePayment');
    Route::delete('/settlement/payment/delete-expense-payment/{id}', 'AddPaymentController@deleteExpensePayment');
    Route::post('/settlement/payment/save-expense-payment', 'AddPaymentController@saveExpensePayment');
    Route::delete('/settlement/payment/delete-credit-sale-payment/{id}', 'AddPaymentController@deleteCreditSalePayment');
    Route::post('/settlement/payment/save-credit-sale-payment', 'AddPaymentController@saveCreditSalePayment');
    Route::get('/settlement/payment/check-order-number', 'AddPaymentController@check_order_number');
    Route::delete('/settlement/payment/delete-cheque-payment/{id}', 'AddPaymentController@deleteChequePayment');
    Route::post('/settlement/payment/save-cheque-payment', 'AddPaymentController@saveChequePayment');
    Route::delete('/settlement/payment/delete-card-payment/{id}', 'AddPaymentController@deleteCardPayment');
    Route::post('/settlement/payment/save-card-payment', 'AddPaymentController@saveCardPayment');
    Route::delete('/settlement/payment/delete-cash-payment/{id}', 'AddPaymentController@deleteCashPayment');
    Route::post('/settlement/payment/save-pos-payment', 'AddPaymentController@savePosPayment');
    Route::delete('/settlement/payment/delete-pos-payment/{id}', 'AddPaymentController@deletePosPayment');

    Route::delete('/settlement/payment/delete-customer-loans/{id}', 'AddPaymentController@deleteCustomerLoan');

    Route::delete('/settlement/payment/delete-loan-payment/{id}', 'AddPaymentController@deleteLoanPayment');
    Route::delete('/settlement/payment/delete-drawing-payment/{id}', 'AddPaymentController@deleteDrawingPayment');
    Route::delete('/settlement/payment/delete-cash-deposit/{id}', 'AddPaymentController@deleteCashDeposit');
    Route::post('/settlement/payment/save-cash-payment', 'AddPaymentController@saveCashPayment');

    Route::post('/settlement/payment/save-customer-loans', 'AddPaymentController@saveCustomerLoan');

    Route::post('/settlement/payment/save-loan-payment', 'AddPaymentController@saveLoanPayment');
    Route::post('/settlement/payment/save-drawing-payment', 'AddPaymentController@saveDrawingPayment');
    Route::post('/settlement/payment/save-cash-deposit', 'AddPaymentController@saveCashDeposit');
    Route::get('/settlement/payment/get-product-price', 'AddPaymentController@getProductPrice');
    Route::get('/settlement/payment/get-customer-details/{customer_id}', 'AddPaymentController@getCustomerDetails');
    Route::get('/settlement/payment/preview/{id}', 'AddPaymentController@preview');
    Route::get('/settlement/payment/preview/credit-sale-product/{id}', 'AddPaymentController@productPreview');
    Route::get('/settlement/payment', 'AddPaymentController@create');
    Route::resource('/settlement/payment', 'AddPaymentController');
    Route::get('/get-stores-by-id', 'SettlementController@getStoresById');
    Route::get('/get-products-by-store-id', 'SettlementController@getProductsByStoreId');

    Route::get('/get-dip-resetting', 'DipManagementController@getDipResetting');
    Route::get('/get-dip-report', 'DipManagementController@getDipReport');
    Route::get('/get-tank-balance-by-id/{tank_id}', 'DipManagementController@getTankBalanceById');
    Route::get('/get-tank-product/{tank_id}', 'DipManagementController@getTankProduct');
    Route::post('/save-resetting-dip', 'DipManagementController@saveResettingDip');
    Route::get('/add-resetting-dip', 'DipManagementController@addResettingDip');

    /*
     * Inventory adjustment accounts for the Dip Resetting form.
     *
     * The form previously called /stock-adjustments/inventory-adjustment-account.
     * That prefix belongs to the `stock_adjustment` module key, so the request was
     * refused on any business with Stock Adjustment switched off in Manage Side
     * Bar and the account dropdown stayed empty - even though a tank reset needs
     * an account to post its accounting entry.
     *
     * Same data, served from Petro General, so the reset does not depend on
     * another module's sidebar switch. The access_account subscription check is
     * still enforced inside the controller method.
     */
    Route::get('/inventory-adjustment-account', 'DipManagementController@getInventoryAdjustmentAccount');
    Route::post('/save-new-dip-reading', 'DipManagementController@saveNewDip');
    Route::get('/add-new-dip', 'DipManagementController@addNewDip');

    Route::post('/save-dip-chart', 'DipManagementController@saveDipChart');
    Route::get('/add-dip-chart', 'DipManagementController@addDipChart');
    Route::get('/get-dip-chart', 'DipManagementController@getDipChart');

    Route::post('/update-dip-chart/{id}', 'DipManagementController@updateDipChart');
    Route::get('/edit-dip-chart/{id}', 'DipManagementController@editDipChart');

    Route::get('/add-dip-chart-reading/{id}', 'DipManagementController@addDipChartReading');
    Route::post('/add-dip-chart-reading/{id}', 'DipManagementController@saveDipChartReading');

    Route::delete('/delete-dip-chart/{id}', 'DipManagementController@deleteDipChart');

    Route::resource('/dip-management', 'DipManagementController');

    Route::get('/meter-resetting/get-pump-details', 'MeterResettingController@getPumpDetails');
    Route::resource('/meter-resetting', 'MeterResettingController');

    Route::get('issue-customer-bill/get-customer-reference/{id}', 'IssueCustomerBillController@getCustomerReference');
    Route::get('issue-customer-bill/get-product-row', 'IssueCustomerBillController@getProductRow');
    Route::get('issue-customer-bill/get-product-price/{id}', 'IssueCustomerBillController@getProductPrice');
    Route::get('issue-customer-bill/print/{id}', 'IssueCustomerBillController@print');
    Route::get('issue-customer-bill/setting/{pumpId}', 'IssueCustomerBillController@getIssueCustomerBillSetting');

    Route::get('issue-customer-bill-vat/print/{id}', 'IssueCustomerBillWithVATController@print');

    Route::resource('issue-customer-bill', 'IssueCustomerBillController');

    Route::get('get-prefixes/{id}', 'IssueCustomerBillWithVATController@getPrefixes');

    Route::resource('issue-customer-bill_VAT', 'IssueCustomerBillWithVATController');

    Route::get('daily-voucher/print/{id}', 'DailyVoucherController@print');
    Route::get('daily-voucher/get-product-row', 'DailyVoucherController@getProductRow');
    Route::resource('daily-voucher', 'DailyVoucherController');

    Route::resource('issue-customer-bill-setting', 'IssueCustomerBillSettingController');
    Route::resource('pump-operator-mapping', 'PumpOperatorMappingController');

    Route::get('get-products-by-pump', 'PumpOperatorMappingController@getProductsByPumpId');
});
Route::group(['middleware' => ['web', 'auth', 'language', 'SetSessionData', 'DayEnd', \Modules\PetroGeneral\Http\Middleware\InitializePetroGeneralTenantContext::class, \Modules\PetroGeneral\Http\Middleware\EnsurePetroGeneralModuleEnabled::class, \Modules\PetroGeneral\Http\Middleware\RenderPetroGeneralStatusMessage::class], 'namespace' => 'Modules\PetroGeneral\Http\Controllers'], function () {
    Route::get('/vehicles', 'VehicleController@vehicles_list');
    Route::get('/vehicle/edit/{id}', 'VehicleController@edit')->name('vehicle.editVehicle');
    Route::post('/vehicle/update/{id}', 'VehicleController@update')->name('vehicle.updateVehicle');
    Route::get('/vehicle', 'VehicleController@index');
});

Route::group(['middleware' => ['web', 'auth', 'language', 'SetSessionData', 'DayEnd', \Modules\PetroGeneral\Http\Middleware\InitializePetroGeneralTenantContext::class, \Modules\PetroGeneral\Http\Middleware\EnsurePetroGeneralModuleEnabled::class, \Modules\PetroGeneral\Http\Middleware\RenderPetroGeneralStatusMessage::class], 'namespace' => 'Modules\PetroGeneral\Http\Controllers'], function () {
    Route::get('get-settings', 'DailyCollectionController@settings')->name('petrogeneral.getSettings');
    Route::get('daily-cash-status', 'DailyCollectionController@getDailyCashStatus')->name('daily.cash.status');
    Route::get('daily-cash-status-data', 'DailyCollectionController@getDailyCashStatusData')->name('daily.cash.status.data');

    Route::post('daily_shift_status.close', 'DailyShiftController@shiftcloseStatus')->name('daily_shift_status.close');

    Route::get('daily-cash-status-data-by-date', 'DailyCollectionController@getByDate')->name('daily.cash.status.data.by.date');
    Route::post('save-settings', 'DailyCollectionController@saveSettings')
        ->name('daily_collection.save_settings');
    
    Route::get( 'pump-operator-mapping/last/{pump_id}','PumpOperatorMappingController@getLastMapping')->name('pump_operator_mapping.last');

});

<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use Modules\PumperDashboard\Http\Middleware\PumperAutoLogoff;

// PUMPER_DASHBOARD_DOUBLE_NAMESPACE_FIX_V4: provider supplies controller namespace.
Route::group(['middleware' => ['web', 'auth', 'language', 'SetSessionData', 'DayEnd', 'tenant.context', PumperAutoLogoff::class], 'prefix' => 'pumper-dashboard'], function () {

    Route::get('/pump-operators/get-settings', 'PumpOperatorController@dashboard_settings');
    Route::post('/pump-operators/get-settings', 'PumpOperatorController@store_settings');

    Route::get('/pump-operators/update-passcode', 'PumpOperatorController@update_passcode');
    Route::post('/pump-operators/update-passcode', 'PumpOperatorController@store_passcode');

    Route::get('/pump-operators/get-pumpter-excess-shortage-payments', 'PumpOperatorController@getPumperExcessShortagePayments');
    Route::post('/pump-operators/save-import', 'PumpOperatorController@saveImport');
    Route::get('/pump-operators/import', 'PumpOperatorController@importPumps');
    Route::get('/pump-operators/ledger', 'PumpOperatorController@getLedger');
    Route::get('/pump-operators/list-commission/{id}', 'PumpOperatorController@listCommission');
    Route::resource('/recover-shortage', 'RecoverShortageController')->names('pumperdashboard.recover-shortage');
    Route::resource('/excess-comission', 'ExcessComissionController')->names('pumperdashboard.excess-comission');
    Route::get('/pump-operators/toggle-active/{id}', 'PumpOperatorController@toggleActivate');
    Route::get('/pump-operators/get-dashboard-data', 'PumpOperatorController@getDashboardData');

    Route::get('/pump-operators/setting_dash', 'PumpOperatorController@setting_dash');
    Route::get('/pump-operators/dashboard', 'PumpOperatorController@dashboard');
    Route::get('/pump-operators/my-auto-dashboard', 'PumpOperatorController@myAutoDashboard');
    Route::post('/pump-operators/my-auto-subscriptions/start-checkout', 'PumpOperatorController@startMyAutoSubscriptionCheckout')
        ->name('pumper-dashboard.my-auto-subscriptions.startCheckout');
    Route::post('/pump-operators/my-auto-subscriptions/{payment_id}', 'PumpOperatorController@updateMyAutoSubscription')
        ->name('pumper-dashboard.my-auto-subscriptions.update');
    Route::get('/pump-operators/check-passcode', 'PumpOperatorController@checPasscode');
    Route::get('/pump-operators/check-username', 'PumpOperatorController@checUsername');

    Route::get('/pump-operators/set-main-system-session', 'PumpOperatorController@setMainSystemSession');
    Route::get('/pump-operators/unblock-pumper-login-attempt/{id}', 'PumpOperatorController@unblockPumperLoginAttempt')->name('pumperdashboard.unblockPumperLoginAttempt');
    Route::get('/pump-operators/unblock-pumper-login-attempts', 'PumpOperatorController@blockedPumperLoginAttempt')->name('pumperdashboard.blockedPumperLoginAttempt');
    Route::get('/pump-operators/login-attempt-history', 'PumpOperatorController@pumperLoginAttemptHistory')->name('pumperdashboard.pumperLoginAttemptHistory');

    Route::resource('/pump-operators/shift-summary', 'ShiftSummaryController')->names('pumperdashboard.shift-summary');
    Route::get('/pump-operators/pumper-day-entries/add-settlement-no/{id}', 'PumperDayEntryController@getAddSettlementNo');
    Route::post('/pump-operators/pumper-day-entries/add-settlement-no/{id}', 'PumperDayEntryController@postAddSettlementNo');
    Route::get('/pump-operators/pumper-day-entries/view-settlement-no/{id}', 'PumperDayEntryController@viewAddSettlementNo');
    Route::get('/pump-operators/pumper-day-entries/get-daily-collection', 'PumperDayEntryController@getDailyCollection');
    Route::get('/pump-operators/pumper-day-entries/summary', 'PumperDayEntryController@getPumperDayEntrySummary')
        ->name('pumperdashboard.day-entries.summary');
    Route::resource('/pump-operators/pumper-day-entries', 'PumperDayEntryController')->names('pumperdashboard.pumper-day-entries');

    Route::get('/pump-operators/payment/{id}/edit', 'PumpOperatorPaymentController@edit')->name('pumper-dashboard.pump-operators.payments.edit');
    Route::delete('/pump-operator/delete-other-sale/{sale_id}', 'PumpOperatorPaymentController@deleteOtherSaleItem');
    Route::post('/pump-operator/update-other-sale-quantity/{sale_id}', 'PumpOperatorPaymentController@updateOtherSaleItem')
        ->whereNumber('sale_id')
        ->name('pumperdashboard.other-sales.update-quantity');

    Route::post('/pump-operator-pmts/save-credit', 'PumpOperatorPaymentController@saveCredit');
    Route::get('/pump-operator-pmts/print-credit-sale/{id}', 'PumpOperatorPaymentController@printCreditSale');
    Route::post('/pump-operator-pmts/save-cheque', 'PumpOperatorPaymentController@saveChequePayment');
    Route::post('/pump-operator-pmts/save-other-sale', 'PumpOperatorPaymentController@saveOtherSale');
    Route::post('/pump-operator-pmts/save-other-sale-items', 'PumpOperatorPaymentController@saveOtherSaleItems');
    Route::get('/pump-operator-pmts/print-other-sale-preview', 'PumpOperatorPaymentController@printOtherSalePreview')
        ->name('pumperdashboard.other-sale-print-preview');
    Route::post('/pump-operator-pmts/save-cash-denom', 'PumpOperatorPaymentController@saveCashDenom');
    Route::post('/pump-operator-pmts/save-card-pmt', 'PumpOperatorPaymentController@saveCardPayment');
    Route::post('/pump-operator-pmts/save-meter-sale', 'PumpOperatorPaymentController@saveMeterSale');
    Route::get('/pump-operator-pmts/get-other-sale', 'PumpOperatorPaymentController@getOtherSale');

    Route::get('/pump-operator/get-payment-summary-dashboard', 'PumpOperatorPaymentController@summarypaymnetdashboard');
    Route::get('/pump-operator/get-payment-summary-dashboard-optimized', 'PumpOperatorPaymentController@summarypaymnetdashboardOptimized');


    // PD-043: explicit Payment page open route for Pumper Dashboard tile.
    // This avoids any resource/action URL ambiguity and always opens the create page.
    Route::get('/pump-operator-payments/open-page', 'PumpOperatorPaymentController@create')->name('pumper-dashboard.pump-operator-payments.open-page');
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
    Route::get('/pump-operator-payments/{id}/edit', 'PumpOperatorPaymentController@edit')->name('pumperdashboard.pump-operator-payments.edit');
    Route::resource('/pump-operator-payments', 'PumpOperatorPaymentController', ['except' => ['edit']])->names('pumperdashboard.pump-operator-payments');

    // Close Pump uses a module-owned, collision-free URL. Petro/PetroGeneral
    // historically registered the legacy get-colsing-meter path as well, so
    // that path can be dispatched to the wrong controller depending on module
    // boot order. New pages must use these unique routes.
    Route::post(
        '/pump-operator-actions/close-pump/pump/{pump_id}/assignment/{route_assignment_id}',
        'PumpOperatorActionsController@postClosingMeter'
    )->whereNumber('pump_id')
        ->whereNumber('route_assignment_id')
        ->name('pumperdashboard.close-pump.submit');
    Route::get(
        '/pump-operator-actions/close-pump/pump/{pump_id}/assignment/{route_assignment_id}',
        'PumpOperatorActionsController@getClosingMeter'
    )->whereNumber('pump_id')
        ->whereNumber('route_assignment_id')
        ->name('pumperdashboard.close-pump.show');

    // Legacy routes remain temporarily for old bookmarks and already-open
    // browser pages. They are not generated by Pumper Dashboard anymore.
    Route::post(
        '/pump-operator-actions/get-colsing-meter/{pump_id}/assignment/{route_assignment_id}',
        'PumpOperatorActionsController@postClosingMeter'
    )->whereNumber('pump_id')
        ->whereNumber('route_assignment_id')
        ->name('pumperdashboard.close-pump.store');
    // Backward compatibility for already compiled/open older forms.
    Route::post('/pump-operator-actions/get-colsing-meter/{pump_id}', 'PumpOperatorActionsController@postClosingMeter');

    // IS1885: some deployed/published Close Pump pages use the correctly
    // spelled legacy URL (get-closing-meter). Keep both spellings routed to
    // this module so those already-open pages cannot be dispatched elsewhere.
    Route::post(
        '/pump-operator-actions/get-closing-meter/{pump_id}/assignment/{route_assignment_id}',
        'PumpOperatorActionsController@postClosingMeter'
    )->whereNumber('pump_id')->whereNumber('route_assignment_id');
    Route::get(
        '/pump-operator-actions/get-closing-meter/{pump_id}/assignment/{route_assignment_id}',
        'PumpOperatorActionsController@getClosingMeter'
    )->whereNumber('pump_id')->whereNumber('route_assignment_id');
    Route::post('/pump-operator-actions/get-closing-meter/{pump_id}', 'PumpOperatorActionsController@postClosingMeter')
        ->whereNumber('pump_id');
    Route::get('/pump-operator-actions/get-closing-meter/{pump_id}', 'PumpOperatorActionsController@getClosingMeter')
        ->whereNumber('pump_id');
    Route::get(
        '/pump-operator-actions/get-colsing-meter/{pump_id}/assignment/{route_assignment_id}',
        'PumpOperatorActionsController@getClosingMeter'
    )->whereNumber('pump_id')
        ->whereNumber('route_assignment_id')
        ->name('pumperdashboard.close-pump.form');
    // Backward compatibility for old bookmarks; new dashboard cards always
    // carry the assignment in the route above.
    Route::get('/pump-operator-actions/get-colsing-meter/{pump_id}', 'PumpOperatorActionsController@getClosingMeter');
    Route::get('/pump-operator-actions/get-colsing-meter-modal', 'PumpOperatorActionsController@getClosingMeterModal');
    Route::get('/pump-operator-actions/closed-pumps-statement/{shift_id}', 'PumpOperatorActionsController@printClosedPumpsStatement')
        ->whereNumber('shift_id')
        ->name('pumperdashboard.closed-pumps-statement');
    Route::get('/pump-operator-actions/get-receive-pump', 'PumpOperatorActionsController@getReceivePump');

    Route::get('/pump-operator-actions/get-pumper-assignment/{pump_id}/{pump_operator_id}', 'PumpOperatorAssignmentController@getPumperAssignment');
    Route::get('/pump-operator-actions/get-day-entry-summary', 'PumperDayEntryController@getPumperDayEntrySummary');
    Route::get('/pump-operator-actions/get-closing-shift-summary', 'PumperDayEntryController@getClosingShiftSummary')->name('pumperdashboard.pump-operator-actions.get-closing-shift-summary');
    Route::get('/pump-operator-actions/confirm-pumps/{assignment_id}', 'PumpOperatorAssignmentController@confirmAssignment');
    Route::post('/pump-operator-actions/confirm-pumps/{assignment_id}', 'PumpOperatorAssignmentController@postConfirmAssignment');

    Route::post('/bulk-pump-operator-assignment', 'PumpOperatorAssignmentController@storeBulk');
    Route::resource('/pump-operator-assignment', 'PumpOperatorAssignmentController')->names('pumperdashboard.pump-operator-assignment');

    Route::get('get-document-note-page', 'PumperDocumentAndNoteController@getDocAndNoteIndexPage');
    Route::post('post-document-upload', 'PumperDocumentAndNoteController@postMedia');
    Route::resource('pumper-note-documents', 'PumperDocumentAndNoteController')->names('pumperdashboard.pumper-note-documents');

    Route::get('/closing-shift/close-shift/{pump_operator_id}', 'ClosingShiftController@closeShift');

    /*
     | MA-008: Close Shift Summary print.
     |
     | Sits inside this same route group, so it inherits the auth, tenant and
     | PumperAutoLogoff middleware - a pumper printing their summary keeps the
     | same session rules as every other Pumper Dashboard page.
     */
    Route::get('/closing-shift/summary-print/{shift_id}', 'CloseShiftSummaryPrintController@print')
        ->name('pumperdashboard.close-shift.summary.print');
    Route::resource('/closing-shift', 'ClosingShiftController')->names('pumperdashboard.closing-shift');
    Route::get('/current-meter/get-modal', 'CurrentMeterController@getModal');
    Route::resource('/current-meter', 'CurrentMeterController')->names('pumperdashboard.current-meter');
    Route::get('/unload-stock/get-details', 'UnloadStockController@getDetails');
    Route::resource('/unload-stock', 'UnloadStockController')->names('pumperdashboard.unload-stock');

    Route::get('/settlement-pd/get-meter-sale-form/{id}', 'SettlementSupportController@getMeterSaleForm');
    Route::delete('/settlement-pd/delete-meter-sale/{id}', 'SettlementSupportController@deleteMeterSale');
    Route::delete('/settlement/delete-other-sale/{id}', 'SettlementSupportController@deleteOtherSale');
    Route::get('/settlement/get_balance_stock_by_id/{id}', 'SettlementSupportController@getBalanceStockById');
    Route::get('/settlement/payment/get-product-price', 'SettlementSupportController@getProductPrice');
    Route::get('/settlement/payment/get-customer-details/{customer_id}', 'SettlementSupportController@getCustomerDetails');
    Route::get('/get-products-by-store-id', 'SettlementSupportController@getProductsByStoreId');

    Route::resource('/pump-operators', 'PumpOperatorController')->names('pumperdashboard.pump-operators');
    
});

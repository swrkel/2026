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
use Modules\PetroPD\Http\Controllers\PetroPDController;
use Modules\PetroPD\Http\Controllers\PetroPDSettlementController;
use Modules\PetroPD\Http\Controllers\PDOperatorController;
use Modules\PetroPD\Http\Controllers\PDPumpOperatorPaymentController;
use Modules\PetroPD\Http\Controllers\PDShiftSummaryController;
use Modules\PetroPD\Http\Controllers\PDPumperDayEntryController;
use Modules\PetroPD\Http\Controllers\PDClosingShiftController;
use Modules\PetroPD\Http\Controllers\PDCurrentMeterController;
use Modules\PetroPD\Http\Controllers\PDUnloadStockController;
use Modules\PetroPD\Http\Controllers\PDRecoverShortageController;
use Modules\PetroPD\Http\Controllers\PDPumpReceiveController;
use Modules\PetroPD\Http\Controllers\PDPumpOperatorAssignmentController;
use Modules\PetroPD\Http\Controllers\ListAssignedOperatorsController;
use Modules\PetroPD\Http\Controllers\PDExcessComissionController;
use Modules\PetroPD\Http\Controllers\PetroPdNotificationTemplateController;
use Modules\PetroPD\Http\Controllers\PDDayEndSettlementController;
use Modules\PetroPD\Http\Controllers\AddPaymentController;
use Modules\PetroPD\Http\Controllers\PDFuelTankController;
use Modules\PetroPD\Http\Controllers\AdjustedAmountsReportController;
use Modules\PetroPD\Http\Controllers\PaymentReconciliationReportController;
use Modules\PetroPD\Http\Controllers\PumpOperatorController;
use Modules\PetroPD\Http\Controllers\CloseShiftSummaryController;
use Modules\PetroPD\Http\Controllers\PDPumpOperatorActionsController;
use Modules\PetroPD\Http\Middleware\EnsurePetroPDAccess;
use Modules\PetroPD\Http\Middleware\PumperAutoLogoff;

Route::group([
    'middleware' => ['web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'tenant.context', EnsurePetroPDAccess::class, PumperAutoLogoff::class],
    'prefix' => 'petropd'
], function () {


    /*
    |--------------------------------------------------------------------------
    | PetroPD Exclusive Day End Settlement Routes
    |--------------------------------------------------------------------------
    | Required by the Day End Settlement tab. These routes keep Add/Edit/List
    | actions inside PetroPD and prevent Symfony Action not defined exceptions.
    */
    Route::get('/day-end-settlements', [PDDayEndSettlementController::class, 'index'])
        ->name('petropd.day-end-settlements.index');

    Route::get('/day-end-settlements/create', [PDDayEndSettlementController::class, 'create'])
        ->name('petropd.day-end-settlements.create');

    Route::post('/day-end-settlements', [PDDayEndSettlementController::class, 'store'])
        ->name('petropd.day-end-settlements.store');

    Route::get('/day-end-settlements/{id}/edit', [PDDayEndSettlementController::class, 'edit'])
        ->name('petropd.day-end-settlements.edit');

    Route::put('/day-end-settlements/{id}', [PDDayEndSettlementController::class, 'update'])
        ->name('petropd.day-end-settlements.update');

    Route::get('/day-end-settlement-pumps', [PDDayEndSettlementController::class, 'pendingPumps'])
        ->name('petropd.day-end-settlements.pending-pumps');

    Route::get('/day-end-settlement-pos-totals', [PDDayEndSettlementController::class, 'posTotals'])
        ->name('petropd.day-end-settlements.pos-totals');

    Route::get('/pd-settlement', [PetroPDController::class, 'pdSettlement'])
        ->name('petropd.pd-settlement');


    /*
    |--------------------------------------------------------------------------
    | PetroPD Settlement Payment / Payment to Finalize Routes
    |--------------------------------------------------------------------------
    | These routes keep the PD Settlement payment modal inside PetroPD and avoid
    | Laravel action()/permission failures when opening the Payment tab.
    */
    Route::get('/add-payment/create', [AddPaymentController::class, 'create'])
        ->name('petropd.add-payment.create');

    Route::get('/add-payment/{id}/preview', [AddPaymentController::class, 'preview'])
        ->name('petropd.add-payment.preview');

    Route::get('/add-payment/{id}/product-preview', [AddPaymentController::class, 'productPreview'])
        ->name('petropd.add-payment.product-preview');

    Route::prefix('settlement/payment')->group(function () {
        Route::post('/save-cash-payment', [AddPaymentController::class, 'saveCashPayment'])
            ->name('petropd.settlement.payment.save-cash-payment');
        Route::post('/save-customer-loans', [AddPaymentController::class, 'saveCustomerLoan'])
            ->name('petropd.settlement.payment.save-customer-loans');
        Route::post('/save-loan-payment', [AddPaymentController::class, 'saveLoanPayment'])
            ->name('petropd.settlement.payment.save-loan-payment');
        Route::post('/save-drawing-payment', [AddPaymentController::class, 'saveDrawingPayment'])
            ->name('petropd.settlement.payment.save-drawing-payment');
        Route::post('/save-cash-deposit', [AddPaymentController::class, 'saveCashDeposit'])
            ->name('petropd.settlement.payment.save-cash-deposit');
        Route::post('/save-card-payment', [AddPaymentController::class, 'saveCardPayment'])
            ->name('petropd.settlement.payment.save-card-payment');
        Route::post('/save-cheque-payment', [AddPaymentController::class, 'saveChequePayment'])
            ->name('petropd.settlement.payment.save-cheque-payment');
        Route::post('/save-credit-sale-payment', [AddPaymentController::class, 'saveCreditSalePayment'])
            ->name('petropd.settlement.payment.save-credit-sale-payment');
        Route::post('/save-expense-payment', [AddPaymentController::class, 'saveExpensePayment'])
            ->name('petropd.settlement.payment.save-expense-payment');
        Route::post('/save-shortage-payment', [AddPaymentController::class, 'saveShortagePayment'])
            ->name('petropd.settlement.payment.save-shortage-payment');
        Route::post('/save-excess-payment', [AddPaymentController::class, 'saveExcessPayment'])
            ->name('petropd.settlement.payment.save-excess-payment');
        Route::post('/save-pos-payment', [AddPaymentController::class, 'savePosPayment'])
            ->name('petropd.settlement.payment.save-pos-payment');
        Route::get('/get-product-price', [AddPaymentController::class, 'getProductPrice'])
            ->name('petropd.settlement.payment.get-product-price');
        Route::get('/get-customer-details/{customer_id}', [AddPaymentController::class, 'getCustomerDetails'])
            ->name('petropd.settlement.payment.get-customer-details');
        Route::delete('/delete-cash-payment/{id}', [AddPaymentController::class, 'deleteCashPayment'])
            ->name('petropd.settlement.payment.delete-cash-payment');
        Route::delete('/delete-card-payment/{id}', [AddPaymentController::class, 'deleteCardPayment'])
            ->name('petropd.settlement.payment.delete-card-payment');
        Route::delete('/delete-cheque-payment/{id}', [AddPaymentController::class, 'deleteChequePayment'])
            ->name('petropd.settlement.payment.delete-cheque-payment');
        Route::delete('/delete-credit-sale-payment/{id}', [AddPaymentController::class, 'deleteCreditSalePayment'])
            ->name('petropd.settlement.payment.delete-credit-sale-payment');
        Route::delete('/delete-expense-payment/{id}', [AddPaymentController::class, 'deleteExpensePayment'])
            ->name('petropd.settlement.payment.delete-expense-payment');
        Route::delete('/delete-shortage-payment/{id}', [AddPaymentController::class, 'deleteShortagePayment'])
            ->name('petropd.settlement.payment.delete-shortage-payment');
        Route::delete('/delete-excess-payment/{id}', [AddPaymentController::class, 'deleteExcessPayment'])
            ->name('petropd.settlement.payment.delete-excess-payment');
        Route::delete('/delete-pos-payment/{id}', [AddPaymentController::class, 'deletePosPayment'])
            ->name('petropd.settlement.payment.delete-pos-payment');
        Route::delete('/delete-customer-loans/{id}', [AddPaymentController::class, 'deleteCustomerLoan'])
            ->name('petropd.settlement.payment.delete-customer-loans');
        Route::delete('/delete-loan-payment/{id}', [AddPaymentController::class, 'deleteLoanPayment'])
            ->name('petropd.settlement.payment.delete-loan-payment');
        Route::delete('/delete-drawing-payment/{id}', [AddPaymentController::class, 'deleteDrawingPayment'])
            ->name('petropd.settlement.payment.delete-drawing-payment');
        Route::delete('/delete-cash-deposit/{id}', [AddPaymentController::class, 'deleteCashDeposit'])
            ->name('petropd.settlement.payment.delete-cash-deposit');
    });

    Route::get('/settlement/check-slip-no', [PetroPDSettlementController::class, 'checkSlipNo'])
        ->name('petropd.settlement.check-slip-no');

    Route::get('/pd-operators', [PDOperatorController::class, 'index'])
        ->name('petropd.pd-operators');

    Route::get('/list-assigned-operators', [ListAssignedOperatorsController::class, 'index'])
        ->name('petropd.list-assigned-operators');

    Route::get('/list-assigned-operators/settlement-options', [ListAssignedOperatorsController::class, 'settlementOptions'])
        ->name('petropd.list-assigned-operators.settlement-options');

    Route::get('/pd-operators/blocked-login-attempts', [PumpOperatorController::class, 'blockedPumperLoginAttempt'])
        ->name('petropd.blocked-pumper-login-attempts');

    Route::get('/pd-operators/blocked-login-attempts/{id}/unblock', [PumpOperatorController::class, 'unblockPumperLoginAttempt'])
        ->whereNumber('id')
        ->name('petropd.unblock-pumper-login-attempt');

    Route::get('/pd-operators/login-attempt-history', [PumpOperatorController::class, 'pumperLoginAttemptHistory'])
        ->name('petropd.pumper-login-attempt-history');


    /*
    |--------------------------------------------------------------------------
    | PetroPD SMS Notifications
    |--------------------------------------------------------------------------
    | Own PetroPD notification page. Uses PD template keys and does not call
    | Petro notification pages/controllers.
    */
    Route::get('/sms-notifications', [PetroPdNotificationTemplateController::class, 'index'])
        ->name('petropd.sms-notifications');

    Route::post('/sms-notifications', [PetroPdNotificationTemplateController::class, 'store'])
        ->name('petropd.sms-notifications.store');

    Route::get('/user-activity-report', [PetroPDController::class, 'getUserActivityReport'])
        ->name('petropd.user-activity-report');

    Route::get('/adjusted-amounts-report', [AdjustedAmountsReportController::class, 'index'])
        ->name('petropd.adjusted-amounts-report');


    Route::get('/payment-reconciliation-report', [PaymentReconciliationReportController::class, 'index'])
        ->name('petropd.payment-reconciliation-report');

    Route::get('/payment-reconciliation-report/{id}', [PaymentReconciliationReportController::class, 'show'])
        ->whereNumber('id')
        ->name('petropd.payment-reconciliation-report.show');

    Route::post('/payment-reconciliation-report/{id}/recheck', [PaymentReconciliationReportController::class, 'recheck'])
        ->whereNumber('id')
        ->name('petropd.payment-reconciliation-report.recheck');

    Route::get('/list-pd-settlement', [PetroPDController::class, 'listPdSettlement'])
        ->name('petropd.list-pd-settlement');

    Route::get('/get-operator-shifts', [PDOperatorController::class, 'getOperatorShifts'])
        ->name('petropd.get-operator-shifts');

    Route::get('/get-shift-work-shift', [PDOperatorController::class, 'getShiftWorkShift'])
        ->name('petropd.get-shift-work-shift');

    Route::get('/get-manual-entry-meter-sales', [PetroPDController::class, 'getManualEntryMeterSales'])
        ->name('petropd.get-manual-entry-meter-sales');

    Route::get('/pd-operators/get-settings', [PDOperatorController::class, 'setting_dash'])
        ->name('petropd.get-settings');

    Route::post('/pd-operators/get-settings', [PDOperatorController::class, 'store_settings'])
        ->name('petropd.store-settings');

    Route::get('/pd-operators/dashboard-settings', [PDOperatorController::class, 'dashboard_settings'])
        ->name('petropd.dashboard-settings');


    /*
    |--------------------------------------------------------------------------
    | PetroPD Exclusive Pump Assignment Routes
    |--------------------------------------------------------------------------
    | Daily Pump Status > Add Assign Pumps must not call the old Petro module.
    | These routes load Pump Operators directly from pump_operators table.
    */
    Route::get('/pump-assignments/create', [PDPumpOperatorAssignmentController::class, 'create'])
        ->name('petropd.pump-assignments.create');

    Route::post('/pump-assignments/store-bulk', [PDPumpOperatorAssignmentController::class, 'storeBulk'])
        ->name('petropd.pump-assignments.store-bulk');

    Route::get('/pump-assignments/get-pumper-assignment', [PDPumpOperatorAssignmentController::class, 'getPumperAssignment'])
        ->name('petropd.pump-assignments.get-pumper-assignment');

    Route::get('/pump-assignments/operators-for-select', [PDPumpOperatorAssignmentController::class, 'operatorsForSelect'])
        ->name('petropd.pump-assignments.operators-for-select');

    Route::get('/pump-assignments/{id}/edit', [PDPumpOperatorAssignmentController::class, 'edit'])
        ->whereNumber('id')
        ->name('petropd.pump-assignments.edit');

    Route::put('/pump-assignments/{id}', [PDPumpOperatorAssignmentController::class, 'update'])
        ->whereNumber('id')
        ->name('petropd.pump-assignments.update');

    Route::delete('/pump-assignments/{id}', [PDPumpOperatorAssignmentController::class, 'destroy'])
        ->whereNumber('id')
        ->name('petropd.pump-assignments.destroy');

    Route::get('/pump-operators/day-entry/{id}/edit', [PDPumperDayEntryController::class, 'edit'])
        ->whereNumber('id')
        ->name('petropd.day-entries.edit');

    Route::put('/pump-operators/day-entry/{id}', [PDPumperDayEntryController::class, 'update'])
        ->whereNumber('id')
        ->name('petropd.day-entries.update');

    Route::delete('/pump-operators/day-entry/{id}', [PDPumperDayEntryController::class, 'destroy'])
        ->whereNumber('id')
        ->name('petropd.day-entries.destroy');

    Route::get('/pumper-day-entry/{id}/add-settlement-no', [PDPumperDayEntryController::class, 'getAddSettlementNo'])
        ->whereNumber('id')
        ->name('petropd.day-entries.add-settlement-no');

    Route::post('/pumper-day-entry/{id}/add-settlement-no', [PDPumperDayEntryController::class, 'postAddSettlementNo'])
        ->whereNumber('id')
        ->name('petropd.day-entries.add-settlement-no.store');

    Route::get('/pd-operators/receive-pump', [PDPumpReceiveController::class, 'getReceivePump'])
        ->name('petropd.receive-pump');

    Route::get('/pd-operators/receive-pump/{id}/confirm', [PDPumpReceiveController::class, 'confirmAssignment'])
        ->name('petropd.receive-pump.confirm');

    Route::post('/pd-operators/receive-pump/{id}/confirm', [PDPumpReceiveController::class, 'postConfirmAssignment'])
        ->name('petropd.receive-pump.confirm.store');


    /*
    |--------------------------------------------------------------------------
    | PetroPD Exclusive PD Operator Core Action Routes
    |--------------------------------------------------------------------------
    | These routes are required by the Actions dropdown in the PD Operators
    | table. Without these routes, Edit opens a blank modal / failed page.
    */


    Route::get('/pd-operators/create', [PDOperatorController::class, 'create'])
        ->name('petropd.pd-operators.create');

    Route::post('/pd-operators', [PDOperatorController::class, 'store'])
        ->name('petropd.pd-operators.store');

    Route::get('/pd-operators/import', [PDOperatorController::class, 'importPumps'])
        ->name('petropd.pd-operators.import');

    Route::post('/pd-operators/import', [PDOperatorController::class, 'saveImport'])
        ->name('petropd.pd-operators.save-import');

    Route::delete('/pd-operators/{id}', [PDOperatorController::class, 'destroy'])
        ->name('petropd.pd-operators.destroy');

    Route::get('/pd-operators/check-username', [PDOperatorController::class, 'checUsername'])
        ->name('petropd.pd-operators.check-username');

    Route::get('/pd-operators/check-passcode', [PDOperatorController::class, 'checPasscode'])
        ->name('petropd.pd-operators.check-passcode');

    Route::get('/pd-operators/{id}/edit', [PDOperatorController::class, 'edit'])
        ->name('petropd.pd-operators.edit');

    Route::put('/pd-operators/{id}', [PDOperatorController::class, 'update'])
        ->name('petropd.pd-operators.update');

    Route::get('/pd-operators/{id}/toggle-active', [PDOperatorController::class, 'toggleActivate'])
        ->name('petropd.pd-operators.toggle-active');

    Route::get('/pd-operators/{id}/commission', [PDOperatorController::class, 'listCommission'])
        ->name('petropd.pd-operators.commission');

    Route::get('/pd-operators/update-passcode', [PDOperatorController::class, 'update_passcode'])
        ->name('petropd.pd-operators.update-passcode');

    Route::post('/pd-operators/update-passcode', [PDOperatorController::class, 'store_passcode'])
        ->name('petropd.pd-operators.store-passcode');

    Route::get('/pd-operators/dashboard', [PumpOperatorController::class, 'dashboard'])
        ->name('petropd.pd-operators.dashboard');

    Route::get('/pd-operators/my-auto-dashboard', [PumpOperatorController::class, 'myAutoDashboard'])
        ->name('petropd.pd-operators.my-auto-dashboard');

    Route::get('/pd-operators/get-dashboard-data', [PumpOperatorController::class, 'getDashboardData'])
        ->name('petropd.pd-operators.dashboard-data');

    Route::get('/pd-operators-ledger', [PumpOperatorController::class, 'getLedger'])
        ->name('petropd.pd-operators.ledger');

    Route::get('/pd-operators/{id}', [PDOperatorController::class, 'show'])
        ->name('petropd.pd-operators.show');



    /*
    |--------------------------------------------------------------------------
    | PetroPD Exclusive PD Operator Phase A AJAX Routes
    |--------------------------------------------------------------------------
    | These routes separate the high-use PD Operator tabs from the Petro module
    | while continuing to use the existing shared database tables.
    */

    Route::get('/day-entries', [PDPumperDayEntryController::class, 'index'])
        ->name('petropd.day_entries.index');

    Route::get('/day-entry-filter-options', [PDPumperDayEntryController::class, 'filterOptions'])
        ->name('petropd.day_entries.filter-options');

    Route::get('/day-entry-summary', [PDPumperDayEntryController::class, 'getPumperDayEntrySummary'])
        ->name('petropd.day_entries.summary');

    Route::get('/pump-operators/shift-summary', [PDShiftSummaryController::class, 'index'])
        ->name('petropd.shift-summary.index');

    Route::get('/pump-operator/get-payment-summary-dashboard', [PDPumpOperatorPaymentController::class, 'summarypaymnetdashboard'])
        ->name('petropd.payment-summary.dashboard');


    // PETROPD-PAYMENT-SUMMARY-TOTALS-017: Dedicated totals endpoint used by the footer.
    Route::get('/pump-operator-payments/totals', [PDPumpOperatorPaymentController::class, 'paymentSummaryTotals'])
        ->name('petropd.pump-operator-payments.totals');

    // IS1467/IS1468: AJAX endpoint used by PetroPD Payment Summary datatable.
    // Keeps the page inside PetroPD instead of calling the Petro module controller.
    Route::get('/pump-operator-payments', [PDPumpOperatorPaymentController::class, 'index'])
        ->name('petropd.pump-operator-payments.index');


    // IS1591: PetroPD pumper payment action routes must not fall back to Petro module routes.
    Route::get('/pump-operator-payments/create', [PDPumpOperatorPaymentController::class, 'create'])
        ->name('petropd.pump-operator-payments.create');
    Route::post('/pump-operator-payments', [PDPumpOperatorPaymentController::class, 'store'])
        ->name('petropd.pump-operator-payments.store');
    Route::get('/pump-operator-payments/balance-to-operator/{pump_operator_id}', [PDPumpOperatorPaymentController::class, 'balanceToOperator'])
        ->name('petropd.pump-operator-payments.balance-to-operator');
    Route::get('/pump-operator-pmts/other-sales', [PDPumpOperatorPaymentController::class, 'othersalespage'])
        ->name('petropd.pump-operator-payments.other-sales');

    // S369: AJAX endpoints used by PD Settlement create page.
    // Registering these controller actions prevents UrlGenerator action() errors
    // for PDPumpOperatorPaymentController@otherSalesList and @meterSalesList.
    Route::get('/pump-operator-pmts/other-sales-list', [PDPumpOperatorPaymentController::class, 'otherSalesList'])
        ->name('petropd.pump-operator-pmts.other-sales-list');
    Route::get('/pump-operator-pmts/meter-sales-list', [PDPumpOperatorPaymentController::class, 'meterSalesList'])
        ->name('petropd.pump-operator-pmts.meter-sales-list');
    Route::get('/pump-operator-payments/othersales-list', [PDPumpOperatorPaymentController::class, 'pumpOtherSalesList'])
        ->name('petropd.pump-operator-payments.othersales-list');

    // S369: AJAX endpoint used by PD Settlement create page for tank/product lookup.
    Route::get('/fuel-tanks/get-tank-product/{tank_id?}', [PDFuelTankController::class, 'getTankProduct'])
        ->name('petropd.fuel-tanks.get-tank-product');
    Route::post('/pump-operator-pmts/save-credit', [PDPumpOperatorPaymentController::class, 'saveCredit'])
        ->name('petropd.pump-operator-pmts.save-credit');
    Route::post('/pump-operator-pmts/save-card-pmt', [PDPumpOperatorPaymentController::class, 'saveCardPayment'])
        ->name('petropd.pump-operator-pmts.save-card-payment');
    Route::post('/pump-operator-pmts/save-cheque', [PDPumpOperatorPaymentController::class, 'saveChequePayment'])
        ->name('petropd.pump-operator-pmts.save-cheque-payment');
    Route::post('/pump-operator-pmts/save-meter-sale', [PDPumpOperatorPaymentController::class, 'saveMeterSale'])
        ->name('petropd.pump-operator-pmts.save-meter-sale');
    Route::post('/pump-operator-pmts/save-other-sale', [PDPumpOperatorPaymentController::class, 'saveOtherSale'])
        ->name('petropd.pump-operator-pmts.save-other-sale');
    Route::post('/pump-operator-pmts/save-cash-denom', [PDPumpOperatorPaymentController::class, 'saveCashDenom'])
        ->name('petropd.pump-operator-pmts.save-cash-denom');
    Route::get('/pump-operator-pmts/other-sale-products', [PDPumpOperatorPaymentController::class, 'getProducts'])
        ->name('petropd.pump-operator-pmts.other-sale-products');
    Route::post('/pump-operator-pmts/save-other-sale-items', [PDPumpOperatorPaymentController::class, 'saveOtherSaleItems'])
        ->name('petropd.pump-operator-pmts.save-other-sale-items');
    Route::delete('/pump-operator-pmts/other-sale-items/{id}', [PDPumpOperatorPaymentController::class, 'deleteOtherSaleItem'])
        ->whereNumber('id')
        ->name('petropd.pump-operator-pmts.delete-other-sale-item');
    Route::post('/pump-operator-pmts/other-sale-items/{id}', [PDPumpOperatorPaymentController::class, 'updateOtherSaleItem'])
        ->whereNumber('id')
        ->name('petropd.pump-operator-pmts.update-other-sale-item');
    Route::delete('/pump-operator-pmts/other-sales/{id}', [PDPumpOperatorPaymentController::class, 'deleteOtherSale'])
        ->whereNumber('id')
        ->name('petropd.pump-operator-pmts.delete-other-sale');

    Route::get('/pump-operator-actions/closing-meter/{pump_id}', [PDPumpOperatorActionsController::class, 'getClosingMeter'])
        ->whereNumber('pump_id')
        ->name('petropd.pump-operator-actions.closing-meter');
    Route::post('/pump-operator-actions/closing-meter/{pump_id}', [PDPumpOperatorActionsController::class, 'postClosingMeter'])
        ->whereNumber('pump_id')
        ->name('petropd.pump-operator-actions.closing-meter.store');

    Route::get('/settlement-pd/get_pumps/{id}', [PetroPDSettlementController::class, 'getPumps'])
        ->whereNumber('id')
        ->name('petropd.settlement-pd.get-pumps');
    Route::get('/settlement-pd/get_balance_stock/{id}', [PetroPDSettlementController::class, 'getBalanceStock'])
        ->whereNumber('id')
        ->name('petropd.settlement-pd.get-balance-stock');
    Route::get('/settlement-pd/get_balance_stock_by_id/{id}', [PetroPDSettlementController::class, 'getBalanceStockById'])
        ->whereNumber('id')
        ->name('petropd.settlement-pd.get-balance-stock-by-id');
    Route::get('/get-stores-by-id', [PetroPDSettlementController::class, 'getStoresById'])
        ->name('petropd.get-stores-by-id');
    Route::get('/get-products-by-store-id', [PetroPDSettlementController::class, 'getProductsByStoreId'])
        ->name('petropd.get-products-by-store-id');


    /*
    |--------------------------------------------------------------------------
    | PetroPD Exclusive PD Operator Phase B AJAX Routes
    |--------------------------------------------------------------------------
    | These routes separate Current Meter, Closing Shift, and Unload Stock tabs
    | from the Petro module while continuing to use the existing shared tables.
    */

    Route::get('/pump-operators/current-meter', [PDCurrentMeterController::class, 'index'])
        ->name('petropd.current-meter.index');

    Route::get('/pump-operators/current-meter/create', [PDCurrentMeterController::class, 'create'])
        ->name('petropd.current-meter.create');

    Route::post('/pump-operators/current-meter', [PDCurrentMeterController::class, 'store'])
        ->name('petropd.current-meter.store');

    Route::get('/pump-operators/current-meter/modal', [PDCurrentMeterController::class, 'getModal'])
        ->name('petropd.current-meter.modal');

    Route::get('/pump-operators/closing-shift', [PDClosingShiftController::class, 'index'])
        ->name('petropd.closing-shift.index');

    Route::get('/pump-operators/closing-shift-filter-options', [PDClosingShiftController::class, 'filterOptions'])
        ->name('petropd.closing-shift.filter-options');

    Route::get('/pump-operators/closing-shift-summary', [PDPumperDayEntryController::class, 'getClosingShiftSummary'])
        ->name('petropd.closing-shift.summary');

    Route::get('/pump-operators/closing-shift/{shift_id}/print', [PDClosingShiftController::class, 'printClosedShiftStatement'])
        ->whereNumber('shift_id')
        ->name('petropd.closing-shift.print');

    Route::get('/pump-operators/closing-shift/{id}', [PDClosingShiftController::class, 'show'])
        ->whereNumber('id')
        ->name('petropd.closing-shift.show');

    Route::put('/pump-operators/closing-shift/{id}', [PDClosingShiftController::class, 'update'])
        ->whereNumber('id')
        ->name('petropd.closing-shift.update');

    Route::get('/pump-operators/closing-shift/close/{shift_id}', [PDClosingShiftController::class, 'closeShift'])
        ->whereNumber('shift_id')
        ->name('petropd.closing-shift.close');

    Route::get('/pd-operators/close-shift/summary', [CloseShiftSummaryController::class, 'show'])
        ->name('petropd.pumper-dashboard.close-shift.summary');

    Route::get('/pump-operators/unload-stock', [PDUnloadStockController::class, 'index'])
        ->name('petropd.unload-stock.index');

    Route::get('/pump-operators/unload-stock/create', [PDUnloadStockController::class, 'create'])
        ->name('petropd.unload-stock.create');

    Route::post('/pump-operators/unload-stock', [PDUnloadStockController::class, 'store'])
        ->name('petropd.unload-stock.store');

    Route::get('/pump-operators/unload-stock/details', [PDUnloadStockController::class, 'getDetails'])
        ->name('petropd.unload-stock.details');

    Route::post('/pump-assignments', [PDPumpOperatorAssignmentController::class, 'store'])
        ->name('petropd.pump-assignments.store');





    /*
    |--------------------------------------------------------------------------
    | PetroPD Exclusive PD Operator Phase C AJAX Routes
    |--------------------------------------------------------------------------
    | These routes remove the remaining Petro route calls from the PD Operators
    | page while keeping the same existing database tables.
    */

    Route::get('/pump-operators/excess-shortage-payments', [PDOperatorController::class, 'getPumperExcessShortagePayments'])
        ->name('petropd.pumper-excess-shortage-payments');

    Route::get('/pump-operators/daily-collection', [PDPumperDayEntryController::class, 'getDailyCollection'])
        ->name('petropd.daily-collection');

    Route::get('/pump-operators/meters-with-payments', [PDPumpOperatorPaymentController::class, 'metersWithPayments'])
        ->name('petropd.meters-with-payments');


    // IS1449 - PetroPD payment summary edit/update routes.
    // These keep PD Operator Payment editing inside PetroPD and avoid falling back to Petro module routes.
    Route::get('/pump-operators/payment/{id}/edit', [PDPumpOperatorPaymentController::class, 'edit'])
        ->name('petropd.pump-operators.payment.edit');

    Route::put('/pump-operators/payment/{id}', [PDPumpOperatorPaymentController::class, 'update'])
        ->name('petropd.pump-operators.payment.update');


    Route::get('/pump-operator-pmts/print-credit-sale/{id}', [PDPumpOperatorPaymentController::class, 'printCreditSale'])
        ->name('petropd.pump-operator-pmts.print-credit-sale');

    Route::get('/excess-comission/create', [PDExcessComissionController::class, 'create'])
        ->name('petropd.excess-comission.create');
    Route::post('/excess-comission', [PDExcessComissionController::class, 'store'])
        ->name('petropd.excess-comission.store');
    Route::get('/excess-comission/{id}/edit', [PDExcessComissionController::class, 'edit'])
        ->name('petropd.excess-comission.edit');
    Route::put('/excess-comission/{id}', [PDExcessComissionController::class, 'update'])
        ->name('petropd.excess-comission.update');
    Route::delete('/excess-comission/{id}', [PDExcessComissionController::class, 'destroy'])
        ->name('petropd.excess-comission.destroy');

    Route::get('/recover-shortage/create', [PDRecoverShortageController::class, 'create'])
        ->name('petropd.recover-shortage.create');
    Route::post('/recover-shortage', [PDRecoverShortageController::class, 'store'])
        ->name('petropd.recover-shortage.store');
    Route::get('/recover-shortage/{id}/edit', [PDRecoverShortageController::class, 'edit'])
        ->name('petropd.recover-shortage.edit');
    Route::put('/recover-shortage/{id}', [PDRecoverShortageController::class, 'update'])
        ->name('petropd.recover-shortage.update');
    Route::delete('/recover-shortage/{id}', [PDRecoverShortageController::class, 'destroy'])
        ->name('petropd.recover-shortage.destroy');

    /*
    |--------------------------------------------------------------------------
    | PetroPD Exclusive Settlement Actions
    |--------------------------------------------------------------------------
    | These routes keep PetroPD settlement actions inside Modules/PetroPD.
    | They still use the existing settlement tables, but normal Petro module
    | routes are not changed.
    */

    Route::get('/settlement-pd/create', [PetroPDSettlementController::class, 'create'])
        ->name('petropd.settlement-pd.create');

    Route::post('/settlement-pd', [PetroPDSettlementController::class, 'store'])
        ->name('petropd.settlement-pd.store');

    // Petro PD settlement detail actions must remain inside this module.
    // Register these before /settlement-pd/{id} to avoid dynamic-route capture.
    Route::get('/settlement-pd/get-pump-details/{pump_id}/{shift_id}', [PetroPDSettlementController::class, 'getPumpDetailsPerShift'])
        ->name('petropd.settlement-pd.get-pump-details');
    Route::get('/settlement-pd/get-meter-sale-form/{id}', [PetroPDSettlementController::class, 'getMeterSaleForm'])
        ->name('petropd.settlement-pd.get-meter-sale-form');
    Route::get('/settlement-pd/check-previous-settlement', [PetroPDSettlementController::class, 'checkPreviousPumpSettlementPD'])
        ->name('petropd.settlement-pd.check-previous-settlement');
    Route::get('/settlement-pd/update-meter-sale/{id}', [PetroPDSettlementController::class, 'editMeterSale'])
        ->whereNumber('id')
        ->name('petropd.settlement-pd.edit-meter-sale');
    Route::post('/settlement-pd/update-meter-sale/{id}', [PetroPDSettlementController::class, 'updateMeterSale'])
        ->whereNumber('id')
        ->name('petropd.settlement-pd.update-meter-sale-date');
    Route::post('/settlement-pd/update-settlement-meter-sale/{id}', [PetroPDSettlementController::class, 'updateSettlementMeterSale'])
        ->name('petropd.settlement-pd.update-meter-sale');

    Route::post('/settlement-pd/save-meter-sale', [PetroPDSettlementController::class, 'saveMeterSale'])
        ->name('petropd.settlement-pd.save-meter-sale');
    Route::post('/settlement-pd/save-other-sale', [PetroPDSettlementController::class, 'saveOtherSale'])
        ->name('petropd.settlement-pd.save-other-sale');
    Route::post('/settlement-pd/save-other-income', [PetroPDSettlementController::class, 'saveOtherIncome'])
        ->name('petropd.settlement-pd.save-other-income');
    Route::post('/settlement-pd/save-customer-payment', [PetroPDSettlementController::class, 'saveCustomerPayment'])
        ->name('petropd.settlement-pd.save-customer-payment');

    Route::delete('/settlement-pd/delete-meter-sale/{id}', [PetroPDSettlementController::class, 'deleteMeterSale'])
        ->name('petropd.settlement-pd.delete-meter-sale');
    Route::delete('/settlement-pd/delete-other-sale/{id}', [PetroPDSettlementController::class, 'deleteOtherSale'])
        ->name('petropd.settlement-pd.delete-other-sale');
    Route::delete('/settlement-pd/delete-other-income/{id}', [PetroPDSettlementController::class, 'deleteOtherIncome'])
        ->name('petropd.settlement-pd.delete-other-income');
    Route::delete('/settlement-pd/delete-customer-payment/{id}', [PetroPDSettlementController::class, 'deleteCustomerPayment'])
        ->name('petropd.settlement-pd.delete-customer-payment');

    // S368: Payment tab totals endpoint used by pd_settlement/partials/payment.blade.php.
    // Must be registered before /settlement-pd/{id} so it is not captured as an {id} route.
    Route::get('/settlement-pd/get-payment-tab-totals', [PetroPDSettlementController::class, 'getPaymentTabTotals'])
        ->name('petropd.settlement-pd.get-payment-tab-totals');

    Route::get('/settlement-pd/{id}', [PetroPDSettlementController::class, 'show'])
        ->name('petropd.settlement-pd.show');

    Route::get('/settlement-pd/{id}/edit', [PetroPDSettlementController::class, 'edit'])
        ->name('petropd.settlement-pd.edit');

    Route::put('/settlement-pd/{id}', [PetroPDSettlementController::class, 'update'])
        ->name('petropd.settlement-pd.update');

    Route::patch('/settlement-pd/{id}', [PetroPDSettlementController::class, 'update'])
        ->name('petropd.settlement-pd.patch');

    Route::delete('/settlement-pd/{id}', [PetroPDSettlementController::class, 'destroy'])
        ->name('petropd.settlement-pd.destroy');

    Route::get('/settlement-pd/{id}/print', [PetroPDSettlementController::class, 'print'])
        ->name('petropd.settlement-pd.print');

});

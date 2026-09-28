<?php

use Illuminate\Support\Facades\Route;
use Modules\PetroDirect\Http\Controllers\DailyVoucherController;
use Modules\PetroDirect\Http\Controllers\DailyCardController;
use Modules\PetroDirect\Http\Controllers\DashboardController;
use Modules\PetroDirect\Http\Controllers\FuelTankController;
use Modules\PetroDirect\Http\Controllers\PumpController;
use Modules\PetroDirect\Http\Controllers\OpeningMeterController;
use Modules\PetroDirect\Http\Controllers\CurrentMeterController;
use Modules\PetroDirect\Http\Controllers\MeterResettingController;
use Modules\PetroDirect\Http\Controllers\DipManagementController;
use Modules\PetroDirect\Http\Controllers\TankTransferController;
use Modules\PetroDirect\Http\Controllers\SettlementController;
use Modules\PetroDirect\Http\Controllers\AddPaymentController;
use Modules\PetroDirect\Http\Controllers\DailyShiftController;
use Modules\PetroDirect\Http\Controllers\ShiftSummaryController;
use Modules\PetroDirect\Http\Controllers\DailyCollectionController;
use Modules\PetroDirect\Http\Controllers\ClosingShiftController;
use Modules\PetroDirect\Http\Controllers\PumpOperatorPaymentController;
use Modules\PetroDirect\Http\Controllers\PumpOperatorAssignmentController;
use Modules\PetroDirect\Http\Controllers\PumperManagementController;
use Modules\PetroDirect\Http\Controllers\ReportController;
use Modules\PetroDirect\Http\Controllers\AssetController;

/*
|--------------------------------------------------------------------------
| PetroDirect Routes
|--------------------------------------------------------------------------
| PetroDirect routes point only to Modules\PetroDirect controllers.
| Unload Stock is intentionally excluded.
*/

// Module-owned assets. PetroDirect serves its own JavaScript and templates.
Route::get('/assets/js/{file}', [AssetController::class, 'javascript'])
    ->where('file', '[A-Za-z0-9._-]+')
    ->name('assets.js');
Route::get('/assets/files/{file}', [AssetController::class, 'download'])
    ->where('file', '[A-Za-z0-9._-]+')
    ->name('assets.download');

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

// Tank Management
Route::post('/tank/save-import', [FuelTankController::class, 'saveImport'])->name('tank.save-import');
Route::get('/tank/import', [FuelTankController::class, 'import'])->name('tank.import');
Route::get('/tank-management/get-tank-product', [FuelTankController::class, 'getTankProduct'])->name('tank-management.get-tank-product');
Route::resource('/tank-management', FuelTankController::class)->except(['show']);

// Pumper Management (PetroDirect-owned standalone tabs/actions)
Route::match(['get', 'post'], '/pumper-management/assign-pumps', [PumperManagementController::class, 'assignPumps'])->name('pumper-management.assign-pumps');
Route::match(['get', 'post'], '/pumper-management/pay-excess-commission', [PumperManagementController::class, 'payExcess'])->name('pumper-management.pay-excess');
Route::match(['get', 'post'], '/pumper-management/recover-shortage', [PumperManagementController::class, 'recoverShortage'])->name('pumper-management.recover-shortage');
Route::get('/pumper-management/{id}/toggle-activate', [PumperManagementController::class, 'toggleActivate'])->name('pumper-management.toggle-activate');
Route::resource('/pumper-management', PumperManagementController::class);

// Pump Management
Route::post('/pumps/save-import', [PumpController::class, 'saveImport'])->name('pumps.save-import');
Route::get('/pumps/import', [PumpController::class, 'importPumps'])->name('pumps.import');
Route::get('/pump-management/get-meter-readings', [PumpController::class, 'getMeterReadings'])->name('pump-management.get-meter-readings');
Route::get('/pump-management/get-testing-details', [PumpController::class, 'getTestingDetails'])->name('pump-management.get-testing-details');
Route::resource('/pump-management', PumpController::class)->except(['show']);

// Module-owned meter operations. These remain internal PetroDirect routes;
// the sidebar continues to expose only the five approved top-level pages.
// Keeping the endpoints registered prevents Pump Management tabs/actions from
// falling through to Laravel's 404 page after the legacy Petro module retires.
Route::resource('/opening-meter', OpeningMeterController::class);
Route::get('/current-meter/get-modal', [CurrentMeterController::class, 'getModal'])
    ->name('current-meter.get-modal');
Route::resource('/current-meter', CurrentMeterController::class);
Route::get('/meter-resetting/get-pump-details', [MeterResettingController::class, 'getPumpDetails'])
    ->name('meter-resetting.get-pump-details');
Route::resource('/meter-resetting', MeterResettingController::class);

// Dip Management
Route::get('/get-dip-resetting', [DipManagementController::class, 'getDipResetting'])->name('dip.get-resetting');
Route::get('/get-dip-report', [DipManagementController::class, 'getDipReport'])->name('dip.get-report');
Route::get('/get-tank-balance-by-id/{tank_id}', [DipManagementController::class, 'getTankBalanceById'])->name('dip.get-tank-balance-by-id');
Route::get('/get-tank-product/{tank_id}', [DipManagementController::class, 'getTankProduct'])->name('dip.get-tank-product');
Route::post('/save-resetting-dip', [DipManagementController::class, 'saveResettingDip'])->name('dip.save-resetting');
Route::get('/add-resetting-dip', [DipManagementController::class, 'addResettingDip'])->name('dip.add-resetting');
Route::post('/save-new-dip-reading', [DipManagementController::class, 'saveNewDip'])->name('dip.save-new');
Route::get('/add-new-dip', [DipManagementController::class, 'addNewDip'])->name('dip.add-new');
Route::post('/save-dip-chart', [DipManagementController::class, 'saveDipChart'])->name('dip.save-chart');
Route::get('/add-dip-chart', [DipManagementController::class, 'addDipChart'])->name('dip.add-chart');
Route::get('/get-dip-chart', [DipManagementController::class, 'getDipChart'])->name('dip.get-chart');
Route::post('/update-dip-chart/{id}', [DipManagementController::class, 'updateDipChart'])->name('dip.update-chart');
Route::get('/edit-dip-chart/{id}', [DipManagementController::class, 'editDipChart'])->name('dip.edit-chart');
Route::get('/add-dip-chart-reading/{id}', [DipManagementController::class, 'addDipChartReading'])->name('dip.add-chart-reading');
Route::post('/add-dip-chart-reading/{id}', [DipManagementController::class, 'saveDipChartReading'])->name('dip.save-chart-reading');
Route::delete('/delete-dip-chart/{id}', [DipManagementController::class, 'deleteDipChart'])->name('dip.delete-chart');
Route::resource('/dip-management', DipManagementController::class)->except(['create', 'show']);

// Tank Transfer
Route::resource('/tank-transfer', TankTransferController::class)->only(['index', 'create', 'store']);

// Settlement & Shift Migration - ZIP 285
// IS1478-R2: PetroDirect Direct Settlement routes must stay inside PetroDirect.
// These routes are registered before the resource route to avoid {settlement} catching helper URLs.
Route::post('/settlement/store-manual-shift-number', [SettlementController::class, 'storeManualShiftNumber'])
    ->name('settlement.store-manual-shift-number');
Route::post('/settlement/save-meter-sale', [SettlementController::class, 'saveMeterSale'])
    ->name('settlement.save-meter-sale');
Route::post('/settlement/update-settlement-meter-sale/{id}', [SettlementController::class, 'updateMeterSale'])
    ->name('settlement.update-settlement-meter-sale');
Route::post('/settlement/update-meter-sale/{id}', [SettlementController::class, 'updateMeterSale'])
    ->name('settlement.update-meter-sale');
Route::get('/settlement/get-meter-sale-form/{id}', [SettlementController::class, 'getMeterSaleForm'])
    ->name('settlement.get-meter-sale-form');
Route::match(['get', 'delete'], '/settlement/delete-meter-sale/{id}', [SettlementController::class, 'deleteMeterSale'])
    ->name('settlement.delete-meter-sale');

// IS1482: Direct Settlement tab AJAX routes used by app.js.
// Keep inside PetroDirect so Other Sales/Income/Customer Payment do not call Petro module routes.
Route::get('/settlement/get-pump-details/{pump_id}', [SettlementController::class, 'getPumpDetails'])
    ->name('settlement.get-pump-details');
Route::get('/settlement/get-pump-details/{pump_id}/{shift_id}', [SettlementController::class, 'getPumpDetails'])
    ->name('settlement.get-pump-details.shift');
Route::get('/settlement/get-pump-details-per-shift/{pump_id}/{shift_id}', [SettlementController::class, 'getPumpDetailsPerShift'])
    ->name('settlement.get-pump-details-per-shift');
Route::get('/settlement/get_balance_stock_by_id/{id}', [SettlementController::class, 'getBalanceStockById'])
    ->name('settlement.get-balance-stock-by-id');
Route::get('/settlement/get_balance_stock/{id}', [SettlementController::class, 'getBalanceStock'])
    ->name('settlement.get-balance-stock');
Route::post('/settlement/save-other-sale', [SettlementController::class, 'saveOtherSale'])
    ->name('settlement.save-other-sale');
Route::match(['get', 'delete'], '/settlement/delete-other-sale/{id}', [SettlementController::class, 'deleteOtherSale'])
    ->name('settlement.delete-other-sale');
Route::post('/settlement/save-other-income', [SettlementController::class, 'saveOtherIncome'])
    ->name('settlement.save-other-income');
Route::match(['get', 'delete'], '/settlement/delete-other-income/{id}', [SettlementController::class, 'deleteOtherIncome'])
    ->name('settlement.delete-other-income');
Route::post('/settlement/save-customer-payment', [SettlementController::class, 'saveCustomerPayment'])
    ->name('settlement.save-customer-payment');
Route::match(['get', 'delete'], '/settlement/delete-customer-payment/{id}', [SettlementController::class, 'deleteCustomerPayment'])
    ->name('settlement.delete-customer-payment');

Route::get('/settlement/get-payment-tab-totals', [SettlementController::class, 'getPaymentTabTotals'])
    ->name('settlement.get-payment-tab-totals');
Route::get('/settlement/check-slip-no', [SettlementController::class, 'checkSlipNo'])
    ->name('settlement.check-slip-no');
Route::get('/settlement/get-stores-by-id', [SettlementController::class, 'getStoresById'])
    ->name('settlement.get-stores-by-id');
Route::get('/settlement/get-products-by-store-id', [SettlementController::class, 'getProductsByStoreId'])
    ->name('settlement.get-products-by-store-id');
Route::get('/settlement/{id}/print', [SettlementController::class, 'print'])->name('settlement.print');
Route::get('/settlement/mechanical-meter/{id}', [SettlementController::class, 'mechanicalMeter'])
    ->name('settlement.mechanical-meter');
Route::get('/settlement/get_pumps/{id}', [SettlementController::class, 'getPumps'])
    ->name('settlement.get-pumps');
Route::get('/settlement/get_pumps_by_location', [SettlementController::class, 'getPumpsByLocation'])
    ->name('settlement.get-pumps-by-location');
Route::get('/settlement/check_prev_settlement', [SettlementController::class, 'checkPreviousPumpSettlement'])
    ->name('settlement.check-previous-settlement');

// IS2338: stable human-readable page aliases used by sidebar/page registries.
// Keep the resource URLs below as the canonical operational endpoints; these
// aliases simply make Direct Settlement navigation resilient to either URL style.
Route::get('/direct-settlement', [SettlementController::class, 'create'])
    ->name('direct-settlement');
Route::get('/direct-settlement/create', [SettlementController::class, 'create'])
    ->name('direct-settlement.create');
Route::get('/list-direct-settlements', [SettlementController::class, 'index'])
    ->name('list-direct-settlements');

Route::resource('/settlement', SettlementController::class);

// IS1478-R2: routes used by settlement/partials/add_payment.blade.php hard-coded AJAX calls.
Route::get('/add-payment/{id}/preview', [AddPaymentController::class, 'preview'])
    ->name('add-payment.preview');
Route::post('/add-payment/store-payment', [AddPaymentController::class, 'store'])->name('add-payment.store-payment');
Route::match(['get', 'post'], '/settlement/payment/save-cash-payment', [AddPaymentController::class, 'saveCashPayment'])->name('settlement.payment.save-cash-payment');
Route::match(['get', 'post'], '/settlement/payment/save-customer-loans', [AddPaymentController::class, 'saveCustomerLoan'])->name('settlement.payment.save-customer-loans');
Route::match(['get', 'post'], '/settlement/payment/save-loan-payment', [AddPaymentController::class, 'saveLoanPayment'])->name('settlement.payment.save-loan-payment');
Route::match(['get', 'post'], '/settlement/payment/save-drawing-payment', [AddPaymentController::class, 'saveDrawingPayment'])->name('settlement.payment.save-drawing-payment');
Route::match(['get', 'post'], '/settlement/payment/save-cash-deposit', [AddPaymentController::class, 'saveCashDeposit'])->name('settlement.payment.save-cash-deposit');
Route::match(['get', 'delete'], '/settlement/payment/delete-cash-payment/{id}', [AddPaymentController::class, 'deleteCashPayment'])->name('settlement.payment.delete-cash-payment');
Route::match(['get', 'delete'], '/settlement/payment/delete-customer-loans/{id}', [AddPaymentController::class, 'deleteLoanPayment'])->name('settlement.payment.delete-customer-loans');
Route::match(['get', 'delete'], '/settlement/payment/delete-loan-payment/{id}', [AddPaymentController::class, 'deleteLoanPayment'])->name('settlement.payment.delete-loan-payment');
Route::match(['get', 'delete'], '/settlement/payment/delete-drawing-payment/{id}', [AddPaymentController::class, 'deleteDrawingPayment'])->name('settlement.payment.delete-drawing-payment');
Route::match(['get', 'delete'], '/settlement/payment/delete-cash-deposit/{id}', [AddPaymentController::class, 'deleteCashDeposit'])->name('settlement.payment.delete-cash-deposit');
Route::match(['get', 'post'], '/settlement/payment/save-card-payment', [AddPaymentController::class, 'saveCardPayment'])->name('settlement.payment.save-card-payment');
Route::match(['get', 'delete'], '/settlement/payment/delete-card-payment/{id}', [AddPaymentController::class, 'deleteCardPayment'])->name('settlement.payment.delete-card-payment');
Route::match(['get', 'post'], '/settlement/payment/save-cheque-payment', [AddPaymentController::class, 'saveChequePayment'])->name('settlement.payment.save-cheque-payment');
Route::match(['get', 'delete'], '/settlement/payment/delete-cheque-payment/{id}', [AddPaymentController::class, 'deleteChequePayment'])->name('settlement.payment.delete-cheque-payment');
Route::match(['get', 'post'], '/settlement/payment/save-credit-sale-payment', [AddPaymentController::class, 'saveCreditSalePayment'])->name('settlement.payment.save-credit-sale-payment');
Route::match(['get', 'delete'], '/settlement/payment/delete-credit-sale-payment/{id}', [AddPaymentController::class, 'deleteCreditSalePayment'])->name('settlement.payment.delete-credit-sale-payment');
Route::match(['get', 'post'], '/settlement/payment/get-product-price', [AddPaymentController::class, 'getProductPrice'])->name('settlement.payment.get-product-price');
Route::get('/settlement/payment/get-customer-details/{customer_id}', [AddPaymentController::class, 'getCustomerDetails'])->name('settlement.payment.get-customer-details');
Route::match(['get', 'post'], '/settlement/payment/save-expense-payment', [AddPaymentController::class, 'saveExpensePayment'])->name('settlement.payment.save-expense-payment');
Route::match(['get', 'delete'], '/settlement/payment/delete-expense-payment/{id}', [AddPaymentController::class, 'deleteExpensePayment'])->name('settlement.payment.delete-expense-payment');
Route::match(['get', 'post'], '/settlement/payment/save-shortage-payment', [AddPaymentController::class, 'saveShortagePayment'])->name('settlement.payment.save-shortage-payment');
Route::match(['get', 'delete'], '/settlement/payment/delete-shortage-payment/{id}', [AddPaymentController::class, 'deleteShortagePayment'])->name('settlement.payment.delete-shortage-payment');
Route::match(['get', 'post'], '/settlement/payment/save-excess-payment', [AddPaymentController::class, 'saveExcessPayment'])->name('settlement.payment.save-excess-payment');
Route::match(['get', 'delete'], '/settlement/payment/delete-excess-payment/{id}', [AddPaymentController::class, 'deleteExcessPayment'])->name('settlement.payment.delete-excess-payment');
Route::match(['get', 'post'], '/settlement/payment/save-pos-payment', [AddPaymentController::class, 'savePosPayment'])->name('settlement.payment.save-pos-payment');
Route::match(['get', 'delete'], '/settlement/payment/delete-pos-payment/{id}', [AddPaymentController::class, 'deletePosPayment'])->name('settlement.payment.delete-pos-payment');
Route::get('/add-payment/create', [AddPaymentController::class, 'create'])->name('add-payment.create');
Route::get('/add-payment/{id}/product-preview', [AddPaymentController::class, 'productPreview'])
    ->name('add-payment.product-preview');

// PetroDirect-owned operational endpoints used by internal tabs, modal links
// and AJAX actions. They are deliberately not added to the business sidebar.
Route::match(['get', 'post'], '/daily-shift/open', [DailyShiftController::class, 'OpenShift'])
    ->name('daily-shift.open');
Route::post('/daily-shift/save', [DailyShiftController::class, 'saveShift'])
    ->name('daily-shift.save');
Route::put('/daily-shift/{petroDailyShift}/edit-save', [DailyShiftController::class, 'editShift'])
    ->name('daily-shift.edit-save-shift');
Route::post('/daily-shift/close-status', [DailyShiftController::class, 'shiftcloseStatus'])
    ->name('daily-shift.close-status');
Route::get('/daily-shift/operators', [DailyShiftController::class, 'getOperators'])
    ->name('daily-shift.operators');
Route::get('/daily-shift/open-shifts', [DailyShiftController::class, 'fetchOpenShift'])
    ->name('daily-shift.open-shifts');
Route::resource('/daily-shift', DailyShiftController::class);
Route::resource('/shift-summary', ShiftSummaryController::class);
Route::resource('/closing-shift', ClosingShiftController::class);

// Daily Collection Settings (PetroDirect-owned)
Route::get('/daily-collection/settings-data', [DailyCollectionController::class, 'settings'])->name('getSettings');
Route::post('/save-settings', [DailyCollectionController::class, 'saveSettings'])->name('saveSettings');
Route::get('/daily-collection/summary', [DailyCollectionController::class, 'collectionSummary'])
    ->name('daily-collection.summary');
Route::get('/daily-collection/shortage-excess', [DailyCollectionController::class, 'indexShortageExcess'])
    ->name('daily-collection.shortage-excess');
Route::get('/daily-collection/cheques', [DailyCollectionController::class, 'indexCheque'])
    ->name('daily-collection.cheques');
Route::get('/daily-collection/other', [DailyCollectionController::class, 'indexOther'])
    ->name('daily-collection.other');
Route::get('/daily-collection/cash-status', [DailyCollectionController::class, 'getDailyCashStatus'])
    ->name('daily-collection.cash-status');
Route::get('/daily-collection/cash-status-data', [DailyCollectionController::class, 'getDailyCashStatusData'])
    ->name('daily-collection.cash-status-data');
Route::get('/daily-collection/get-balance-collection/{pump_operator_id}', [DailyCollectionController::class, 'getBalanceCollection'])
    ->name('daily-collection.get-balance-collection');
Route::post('/daily-collection/cash-given', [DailyCollectionController::class, 'storeCashGiven'])
    ->name('daily-collection.cash-given');

Route::get('/daily-card', [DailyCardController::class, 'index'])->name('daily-card.index');
Route::get('/daily-card/create', [DailyCardController::class, 'create'])->name('daily-card.create');
Route::post('/daily-card', [DailyCardController::class, 'store'])->name('daily-card.store');
Route::put('/daily-card/{id}', [DailyCardController::class, 'update'])->name('daily-card.update');
Route::get('/daily-voucher/create', [DailyVoucherController::class, 'create'])->name('daily-voucher.create');
Route::post('/daily-voucher', [DailyVoucherController::class, 'store'])->name('daily-voucher.store');
Route::put('/daily-voucher/{id}', [DailyVoucherController::class, 'update'])->name('daily-voucher.update');

Route::resource('/daily-collection', DailyCollectionController::class)->except(['show']);
Route::put('/daily-collection/{id}/shortage', [DailyCollectionController::class, 'updateShortage'])
    ->name('daily-collection.update-shortage');
// IS1478-R4: Settlement create page uses these AJAX endpoints via route() helpers.
// They must be registered explicitly because Laravel resource routes do not include custom methods.
Route::match(['get', 'post'], '/pump-operator-payments/meter-sales-list', [PumpOperatorPaymentController::class, 'meterSalesList'])
    ->name('pump-operator-payments.meter-sales-list');
Route::match(['get', 'post'], '/pump-operator-payments/other-sales-list', [PumpOperatorPaymentController::class, 'otherSalesList'])
    ->name('pump-operator-payments.other-sales-list');
Route::get('/pump-operators/payment-summary-modal', [PumpOperatorPaymentController::class, 'getPaymentSummaryModal'])
    ->name('pump-operator-payments.summary-modal');
Route::get('/pump-operators/payment-modal', [PumpOperatorPaymentController::class, 'getPaymentModal'])
    ->name('pump-operator-payments.payment-modal');
Route::get('/pump-operators/payment/{id}/edit', [PumpOperatorPaymentController::class, 'edit'])
    ->name('pump-operator-payments.legacy-edit');
Route::put('/pump-operators/payment/{id}', [PumpOperatorPaymentController::class, 'update'])
    ->name('pump-operator-payments.legacy-update');
Route::get('/pump-operators/meters-with-payments', [PumpOperatorPaymentController::class, 'metersWithPayments'])
    ->name('pump-operator-payments.meters-with-payments');
Route::get('/pump-operator-pmts/print-credit-sale/{id}', [PumpOperatorPaymentController::class, 'printCreditSale'])
    ->name('pump-operator-payments.print-credit-sale');
Route::resource('/pump-operator-payments', PumpOperatorPaymentController::class);
Route::resource('/pump-operator-assignment', PumpOperatorAssignmentController::class);

// PetroDirect Reports & Exports - ZIP 286
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
Route::get('/reports/{report}/export', [ReportController::class, 'export'])->name('reports.export');

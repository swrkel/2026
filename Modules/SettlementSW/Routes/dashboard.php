<?php

use Illuminate\Support\Facades\Route;
use Modules\SettlementSW\Http\Controllers\SettlementSwCreditSaleController;
use Modules\SettlementSW\Http\Controllers\SettlementSwCustomerPaymentController;
use Modules\SettlementSW\Http\Controllers\SettlementSwDashboardController;
use Modules\SettlementSW\Http\Controllers\SettlementSwExpenseController;
use Modules\SettlementSW\Http\Controllers\SettlementSwMeterSalesController;
use Modules\SettlementSW\Http\Controllers\SettlementSwOtherIncomeController;
use Modules\SettlementSW\Http\Controllers\SettlementSwOtherSalesController;
use Modules\SettlementSW\Http\Controllers\SettlementSwTempController;

Route::get('/', [SettlementSwDashboardController::class, 'index'])->name('settlement-sw.index');
Route::get('/create', [SettlementSwDashboardController::class, 'create'])->name('settlement-sw.create');
Route::redirect('/add', '/settlement-sw/create')->name('settlement-sw.dashboard.create');
Route::post('/store', [SettlementSwDashboardController::class, 'store'])->name('settlement-sw.store');
Route::get('/edit/{id}', [SettlementSwDashboardController::class, 'edit'])->name('settlement-sw.edit');
Route::get('/show/{id}', [SettlementSwDashboardController::class, 'show'])->name('settlement-sw.show');
Route::get('/print/{id}', [SettlementSwDashboardController::class, 'print'])->name('settlement-sw.print');

Route::post('/save-meter-sale', [SettlementSwMeterSalesController::class, 'saveMeterSale'])->name('settlement-sw.save-meter-sale');
Route::get('/get-pump-details/{pump_id}', [SettlementSwMeterSalesController::class, 'getPumpDetails'])->name('settlement-sw.get-pump-details');
Route::get('/get_pumps/{id}', [SettlementSwMeterSalesController::class, 'getPumps'])->name('settlement-sw.get-pumps');
Route::get('/get_balance_stock_by_id/{id}', [SettlementSwMeterSalesController::class, 'getBalanceStockById'])->name('settlement-sw.get-balance-stock-by-id');
Route::delete('/delete-meter-sale/{id}', [SettlementSwMeterSalesController::class, 'deleteMeterSale'])->name('settlement-sw.delete-meter-sale');

Route::post('/save-other-sale', [SettlementSwOtherSalesController::class, 'saveOtherSale'])->name('settlement-sw.save-other-sale');
Route::delete('/delete-other-sale/{id}', [SettlementSwOtherSalesController::class, 'deleteOtherSale'])->name('settlement-sw.delete-other-sale');

Route::post('/save-other-income', [SettlementSwOtherIncomeController::class, 'saveOtherIncome'])->name('settlement-sw.save-other-income');
Route::post('/save-customer-payment', [SettlementSwCustomerPaymentController::class, 'saveCustomerPayment'])->name('settlement-sw.save-customer-payment');
Route::post('/save-expense-payment', [SettlementSwExpenseController::class, 'saveExpansePayment'])->name('settlement-sw.save-expense-payment');
Route::post('/save-credit-sale-payment', [SettlementSwCreditSaleController::class, 'saveCreditSalePayment'])->name('settlement-sw.save-credit-sale-payment');

Route::put('/{id}', [SettlementSwDashboardController::class, 'update'])->name('settlement-sw.update');
Route::delete('/{id}', [SettlementSwDashboardController::class, 'destroy'])->name('settlement-sw.destroy');
Route::get('/pump-operator/other-sales-list', [SettlementSwDashboardController::class, 'otherSalesList'])->name('settlement-sw.pump-operator.other-sales-list');
Route::get('/pump-operator/meter-sales-list', [SettlementSwDashboardController::class, 'meterSalesList'])->name('settlement-sw.pump-operator.meter-sales-list');
Route::get('/tanks/product/{id}', [SettlementSwDashboardController::class, 'getTankProduct'])->name('settlement-sw.tanks.product');
Route::get('/check-slip-no', [\Modules\SettlementSW\Http\Controllers\RouteClosures\DashboardRouteController::class, 'handle1'])->name('settlement-sw.check-slip-no');
Route::get('/check-prev-settlement', [SettlementSwDashboardController::class, 'checkPrevSettlement'])->name('settlement-sw.check-prev-settlement');

Route::get('/get-meter-sale-form/{id}', [SettlementSwMeterSalesController::class, 'getMeterSaleForm'])->name('settlement-sw.get-meter-sale-form');
Route::put('/update-settlement-meter-sale/{id}', [SettlementSwMeterSalesController::class, 'updateSettlementMeterSale'])->name('settlement-sw.update-settlement-meter-sale');
Route::delete('/delete-other-income/{id}', [SettlementSwOtherIncomeController::class, 'deleteOtherIncome'])->name('settlement-sw.delete-other-income');
Route::delete('/delete-customer-payment/{id}', [SettlementSwCustomerPaymentController::class, 'deleteCustomerPayment'])->name('settlement-sw.delete-customer-payment');

Route::post('/temp/save', [SettlementSwTempController::class, 'save'])->name('settlement-sw.temp.save');

<?php

use Illuminate\Support\Facades\Route;
use Modules\SettlementSW\Http\Controllers\SettlementSwAddPaymentCardController;
use Modules\SettlementSW\Http\Controllers\SettlementSwAddPaymentCashController;
use Modules\SettlementSW\Http\Controllers\SettlementSwAddPaymentChequeController;
use Modules\SettlementSW\Http\Controllers\SettlementSwAddPaymentCreditSaleController;
use Modules\SettlementSW\Http\Controllers\SettlementSwAddPaymentDrawingController;
use Modules\SettlementSW\Http\Controllers\SettlementSwAddPaymentExpenseController;
use Modules\SettlementSW\Http\Controllers\SettlementSwAddPaymentLoanController;
use Modules\SettlementSW\Http\Controllers\SettlementSwAddPaymentPageController;
use Modules\SettlementSW\Http\Controllers\SettlementSwAddPaymentPreviewController;
use Modules\SettlementSW\Http\Controllers\SettlementSwAddPaymentShortageExcessController;

Route::get('/sw-add-payment/create', [SettlementSwAddPaymentPageController::class, 'create'])->name('sw-add-payment.create');

Route::post('/sw-add-payment/cash-payment', [SettlementSwAddPaymentCashController::class, 'saveCashPayment'])->name('settlement-sw.add-payment.cash-payment.save');
Route::delete('/sw-add-payment/cash-payment/{id}', [SettlementSwAddPaymentCashController::class, 'deleteCashPayment'])->name('settlement-sw.add-payment.cash-payment.delete');
Route::post('/sw-add-payment/cash-deposit', [SettlementSwAddPaymentCashController::class, 'saveCashDeposit'])->name('settlement-sw.add-payment.cash-deposit.save');
Route::delete('/sw-add-payment/cash-deposit/{id}', [SettlementSwAddPaymentCashController::class, 'deleteCashDeposit'])->name('settlement-sw.add-payment.cash-deposit.delete');

Route::post('/sw-add-payment/card-payment', [SettlementSwAddPaymentCardController::class, 'saveCardPayment'])->name('settlement-sw.add-payment.card-payment.save');
Route::delete('/sw-add-payment/card-payment/{id}', [SettlementSwAddPaymentCardController::class, 'deleteCardPayment'])->name('settlement-sw.add-payment.card-payment.delete');

Route::post('/sw-add-payment/cheque-payment', [SettlementSwAddPaymentChequeController::class, 'saveChequePayment'])->name('settlement-sw.add-payment.cheque-payment.save');
Route::delete('/sw-add-payment/cheque-payment/{id}', [SettlementSwAddPaymentChequeController::class, 'deleteChequePayment'])->name('settlement-sw.add-payment.cheque-payment.delete');

Route::post('/sw-add-payment/credit-sale-payment', [SettlementSwAddPaymentCreditSaleController::class, 'saveCreditSalePayment'])->name('settlement-sw.add-payment.credit-sale-payment.save');
Route::delete('/sw-add-payment/credit-sale-payment/{id}', [SettlementSwAddPaymentCreditSaleController::class, 'deleteCreditSalePayment'])->name('settlement-sw.add-payment.credit-sale-payment.delete');
Route::get('/sw-add-payment/check-order-number', [SettlementSwAddPaymentCreditSaleController::class, 'check_order_number'])->name('settlement-sw.add-payment.check-order-number');
Route::get('/sw-add-payment/customer-details/{customer_id}', [SettlementSwAddPaymentCreditSaleController::class, 'getCustomerDetails'])->name('settlement-sw.add-payment.customer-details');
Route::get('/sw-add-payment/product-price', [SettlementSwAddPaymentCreditSaleController::class, 'getProductPrice'])->name('settlement-sw.add-payment.product-price');

Route::post('/sw-add-payment/customer-loan', [SettlementSwAddPaymentLoanController::class, 'saveCustomerLoan'])->name('settlement-sw.add-payment.customer-loan.save');
Route::delete('/sw-add-payment/customer-loan/{id}', [SettlementSwAddPaymentLoanController::class, 'deleteCustomerLoan'])->name('settlement-sw.add-payment.customer-loan.delete');
Route::post('/sw-add-payment/loan-payment', [SettlementSwAddPaymentLoanController::class, 'saveLoanPayment'])->name('settlement-sw.add-payment.loan-payment.save');
Route::delete('/sw-add-payment/loan-payment/{id}', [SettlementSwAddPaymentLoanController::class, 'deleteLoanPayment'])->name('settlement-sw.add-payment.loan-payment.delete');

Route::post('/sw-add-payment/drawing-payment', [SettlementSwAddPaymentDrawingController::class, 'saveDrawingPayment'])->name('settlement-sw.add-payment.drawing-payment.save');
Route::delete('/sw-add-payment/drawing-payment/{id}', [SettlementSwAddPaymentDrawingController::class, 'deleteDrawingPayment'])->name('settlement-sw.add-payment.drawing-payment.delete');

Route::post('/sw-add-payment/expense-payment', [SettlementSwAddPaymentExpenseController::class, 'saveExpensePayment'])->name('settlement-sw.add-payment.expense-payment.save');
Route::delete('/sw-add-payment/expense-payment/{id}', [SettlementSwAddPaymentExpenseController::class, 'deleteExpensePayment'])->name('settlement-sw.add-payment.expense-payment.delete');

Route::post('/sw-add-payment/shortage-payment', [SettlementSwAddPaymentShortageExcessController::class, 'saveShortagePayment'])->name('settlement-sw.add-payment.shortage-payment.save');
Route::delete('/sw-add-payment/shortage-payment/{id}', [SettlementSwAddPaymentShortageExcessController::class, 'deleteShortagePayment'])->name('settlement-sw.add-payment.shortage-payment.delete');
Route::post('/sw-add-payment/excess-payment', [SettlementSwAddPaymentShortageExcessController::class, 'saveExcessPayment'])->name('settlement-sw.add-payment.excess-payment.save');
Route::delete('/sw-add-payment/excess-payment/{id}', [SettlementSwAddPaymentShortageExcessController::class, 'deleteExcessPayment'])->name('settlement-sw.add-payment.excess-payment.delete');

Route::get('/sw-add-payment/preview/{id}', [SettlementSwAddPaymentPreviewController::class, 'preview'])->name('settlement-sw.add-payment.preview');
Route::get('/sw-add-payment/product-preview/{id}', [SettlementSwAddPaymentPreviewController::class, 'productPreview'])->name('settlement-sw.add-payment.product-preview');

<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\BeautyPosController;
use Modules\BeautySaloons\Http\Controllers\BeautyBillingController;
use Modules\BeautySaloons\Http\Controllers\BeautyRefundController;
use Modules\BeautySaloons\Http\Controllers\BeautyCashierSettlementController;

Route::group(['middleware' => ['web', 'auth'], 'prefix' => 'beauty-saloons', 'as' => 'beauty-saloons.'], function () {
    Route::get('pos', [BeautyPosController::class, 'index'])->name('pos.index');
    Route::post('pos/calculate', [BeautyPosController::class, 'calculate'])->name('pos.calculate');
    Route::post('pos/checkout', [BeautyPosController::class, 'checkout'])->name('pos.checkout');
    Route::get('billings', [BeautyBillingController::class, 'index'])->name('billings.index');
    Route::get('billings/{id}/receipt', [BeautyBillingController::class, 'receipt'])->name('billings.receipt');
    Route::post('billings/{id}/payments', [BeautyBillingController::class, 'addPayment'])->name('billings.payments.store');
    Route::post('refunds', [BeautyRefundController::class, 'store'])->name('refunds.store');
    Route::get('cashier-settlements', [BeautyCashierSettlementController::class, 'index'])->name('cashier-settlements.index');
    Route::post('cashier-settlements/finalize', [BeautyCashierSettlementController::class, 'finalize'])->name('cashier-settlements.finalize');
});

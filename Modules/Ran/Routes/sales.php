<?php

use Illuminate\Support\Facades\Route;
use Modules\Ran\Http\Controllers\CustomerPaymentController;
use Modules\Ran\Http\Controllers\PurchaseController;
use Modules\Ran\Http\Controllers\SaleController;
use Modules\Ran\Http\Controllers\SaleReturnController;
use Modules\Ran\Http\Controllers\SupplierPaymentController;

Route::prefix('ran')->as('ran.')->middleware(['web', 'auth', 'ran.enabled'])->group(function (): void {
    Route::resource('purchases', PurchaseController::class)->only(['index', 'show'])
        ->middleware('ran.page:ran.purchases.view');
    Route::resource('purchases', PurchaseController::class)->only(['create', 'store'])
        ->middleware('ran.page:ran.purchases.create');
    Route::post('purchases/{purchase}/post', [PurchaseController::class, 'post'])
        ->middleware('ran.page:ran.purchases.post')->name('purchases.post');

    Route::resource('supplier-payments', SupplierPaymentController::class)->only(['index'])
        ->middleware('ran.page:ran.purchases.view');
    Route::resource('supplier-payments', SupplierPaymentController::class)->only(['create', 'store'])
        ->middleware('ran.page:ran.purchases.pay');

    Route::resource('sales', SaleController::class)->only(['index', 'show'])
        ->middleware('ran.page:ran.sales.view');
    Route::resource('sales', SaleController::class)->only(['create', 'store'])
        ->middleware('ran.page:ran.sales.create');
    Route::post('sales/{sale}/post', [SaleController::class, 'post'])
        ->middleware('ran.page:ran.sales.post')->name('sales.post');

    Route::resource('customer-payments', CustomerPaymentController::class)->only(['index'])
        ->middleware('ran.page:ran.sales.view');
    Route::resource('customer-payments', CustomerPaymentController::class)->only(['create', 'store'])
        ->middleware('ran.page:ran.sales.receive_payment');

    Route::resource('returns', SaleReturnController::class)->only(['index'])
        ->middleware('ran.page:ran.sales.view');
    Route::resource('returns', SaleReturnController::class)->only(['create', 'store'])
        ->middleware('ran.page:ran.sales.return');
});

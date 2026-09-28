<?php

use Illuminate\Support\Facades\Route;
use Modules\POS\Http\Controllers\SalesWorkspaceController;
use Modules\POS\Http\Controllers\SalesCartController;
use Modules\POS\Http\Controllers\HeldSaleController;
use Modules\POS\Http\Controllers\QuotationController;

Route::group(['middleware' => ['web', 'auth'], 'prefix' => 'pos-module', 'as' => 'pos.'], function () {
    Route::get('/sales/workspace', [SalesWorkspaceController::class, 'index'])->name('sales.workspace');
    Route::get('/sales/products/search', [SalesWorkspaceController::class, 'products'])->name('sales.products.search');
    Route::get('/sales/customers/search', [SalesWorkspaceController::class, 'customers'])->name('sales.customers.search');
    Route::get('/sales/price-check', [SalesWorkspaceController::class, 'priceCheck'])->name('sales.price_check');
    Route::get('/sales/calculator', [SalesWorkspaceController::class, 'calculator'])->name('sales.calculator');

    Route::get('/sales/cart', [SalesCartController::class, 'show'])->name('sales.cart.show');
    Route::post('/sales/cart/lines', [SalesCartController::class, 'addLine'])->name('sales.cart.add_line');
    Route::put('/sales/cart/lines/{line}', [SalesCartController::class, 'updateLine'])->name('sales.cart.update_line');
    Route::delete('/sales/cart/lines/{line}', [SalesCartController::class, 'removeLine'])->name('sales.cart.remove_line');
    Route::post('/sales/cart/customer', [SalesCartController::class, 'setCustomer'])->name('sales.cart.set_customer');
    Route::delete('/sales/cart', [SalesCartController::class, 'clear'])->name('sales.cart.clear');
    Route::post('/sales/cart/hold', [SalesCartController::class, 'hold'])->name('sales.cart.hold');
    Route::post('/sales/cart/suspend', [SalesCartController::class, 'suspend'])->name('sales.cart.suspend');
    Route::post('/sales/cart/{cart}/resume', [SalesCartController::class, 'resume'])->name('sales.cart.resume');

    Route::get('/sales/held', [HeldSaleController::class, 'held'])->name('sales.held');
    Route::get('/sales/suspended', [HeldSaleController::class, 'suspended'])->name('sales.suspended');
    Route::get('/sales/quotations', [QuotationController::class, 'index'])->name('sales.quotations');
    Route::post('/sales/quotations', [QuotationController::class, 'store'])->name('sales.quotations.store');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\Report\PurchaseReportIndexController;
use Modules\Purchase\Http\Controllers\Report\PurchaseRegisterReportController;
use Modules\Purchase\Http\Controllers\Report\PurchasePaymentReportController;
use Modules\Purchase\Http\Controllers\Report\ProductPurchaseReportController;
use Modules\Purchase\Http\Controllers\Report\PurchaseSellReportController;
use Modules\Purchase\Http\Controllers\Report\StockPurchaseSaleReportController;
use Modules\Purchase\Http\Controllers\Report\SupplierOutstandingReportController;

Route::prefix('reports')->as('reports.')->group(function () {
    Route::get('/', [PurchaseReportIndexController::class, 'index'])->name('index');
    Route::get('/purchase-register', [PurchaseRegisterReportController::class, 'index'])->name('purchase-register');
    Route::get('/purchase-register/data', [PurchaseRegisterReportController::class, 'data'])->name('purchase-register.data');
    Route::get('/purchase-payment', [PurchasePaymentReportController::class, 'index'])->name('purchase-payment');
    Route::get('/purchase-payment/data', [PurchasePaymentReportController::class, 'data'])->name('purchase-payment.data');
    Route::get('/product-purchase', [ProductPurchaseReportController::class, 'index'])->name('product-purchase');
    Route::get('/product-purchase/data', [ProductPurchaseReportController::class, 'data'])->name('product-purchase.data');
    Route::get('/purchase-sell', [PurchaseSellReportController::class, 'index'])->name('purchase-sell');
    Route::get('/purchase-sell/data', [PurchaseSellReportController::class, 'data'])->name('purchase-sell.data');
    Route::get('/stock-purchase-sale', [StockPurchaseSaleReportController::class, 'index'])->name('stock-purchase-sale');
    Route::get('/stock-purchase-sale/data', [StockPurchaseSaleReportController::class, 'data'])->name('stock-purchase-sale.data');
    Route::get('/supplier-outstanding', [SupplierOutstandingReportController::class, 'index'])->name('supplier-outstanding');
    Route::get('/supplier-outstanding/data', [SupplierOutstandingReportController::class, 'data'])->name('supplier-outstanding.data');
});

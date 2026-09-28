<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\PurchaseOrderController;
use Modules\RestaurantNew\Http\Controllers\GoodsReceiptController;
use Modules\RestaurantNew\Http\Controllers\SupplierQuotationController;
use Modules\RestaurantNew\Http\Controllers\ProductionBatchController;
use Modules\RestaurantNew\Http\Controllers\CommissaryTransferController;

Route::prefix('restaurant-new/procurement')->middleware(['web','auth','restaurantnew.tenant'])->name('restaurantnew.procurement.')->group(function () {
    Route::resource('supplier-quotations', SupplierQuotationController::class)->only(['index','store']);
    Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index','store']);
    Route::resource('goods-receipts', GoodsReceiptController::class)->only(['index','store']);
    Route::resource('production-batches', ProductionBatchController::class)->only(['index','store']);
    Route::resource('commissary-transfers', CommissaryTransferController::class)->only(['index','store']);
});

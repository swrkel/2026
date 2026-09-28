<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\Order\PurchaseOrderListController;
use Modules\Purchase\Http\Controllers\Order\PurchaseOrderCreateController;
use Modules\Purchase\Http\Controllers\Order\PurchaseOrderEditController;
use Modules\Purchase\Http\Controllers\Order\PurchaseOrderShowController;

Route::prefix('orders')->as('orders.')->group(function () {
    Route::get('/', [PurchaseOrderListController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseOrderCreateController::class, 'create'])->name('create');
    Route::post('/', [PurchaseOrderCreateController::class, 'store'])->name('store');
    Route::get('/{id}', [PurchaseOrderShowController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [PurchaseOrderEditController::class, 'edit'])->name('edit');
    Route::put('/{id}', [PurchaseOrderEditController::class, 'update'])->name('update');
});

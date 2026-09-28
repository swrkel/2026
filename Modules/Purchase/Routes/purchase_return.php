<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\Return\PurchaseReturnCreateController;
use Modules\Purchase\Http\Controllers\Return\PurchaseReturnDataController;
use Modules\Purchase\Http\Controllers\Return\PurchaseReturnDeleteController;
use Modules\Purchase\Http\Controllers\Return\PurchaseReturnEditController;
use Modules\Purchase\Http\Controllers\Return\PurchaseReturnListController;
use Modules\Purchase\Http\Controllers\Return\PurchaseReturnShowController;

Route::prefix('returns')->as('returns.')->group(function () {
    Route::get('/', [PurchaseReturnListController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseReturnCreateController::class, 'create'])->name('create');
    Route::post('/', [PurchaseReturnCreateController::class, 'store'])->name('store');

    Route::prefix('data')->as('data.')->group(function () {
        Route::get('/purchases', [PurchaseReturnDataController::class, 'purchases'])->name('purchases');
        Route::get('/purchase/{id}', [PurchaseReturnDataController::class, 'purchase'])->whereNumber('id')->name('purchase');
    });

    Route::get('/{id}', [PurchaseReturnShowController::class, 'show'])->whereNumber('id')->name('show');
    Route::get('/{id}/edit', [PurchaseReturnEditController::class, 'edit'])->whereNumber('id')->name('edit');
    Route::put('/{id}', [PurchaseReturnEditController::class, 'update'])->whereNumber('id')->name('update');
    Route::delete('/{id}', [PurchaseReturnDeleteController::class, 'destroy'])->whereNumber('id')->name('destroy');
});

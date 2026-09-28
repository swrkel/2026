<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\Payment\SupplierPaymentListController;
use Modules\Purchase\Http\Controllers\Payment\SupplierPaymentCreateController;
use Modules\Purchase\Http\Controllers\Payment\SupplierPaymentEditController;
use Modules\Purchase\Http\Controllers\Payment\SupplierPaymentDeleteController;

Route::prefix('supplier-payments')->as('supplier-payments.')->group(function () {
    Route::get('/', [SupplierPaymentListController::class, 'index'])->name('index');
    Route::get('/create', [SupplierPaymentCreateController::class, 'create'])->name('create');
    Route::post('/', [SupplierPaymentCreateController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [SupplierPaymentEditController::class, 'edit'])->name('edit');
    Route::put('/{id}', [SupplierPaymentEditController::class, 'update'])->name('update');
    Route::delete('/{id}', [SupplierPaymentDeleteController::class, 'destroy'])->name('destroy');
});

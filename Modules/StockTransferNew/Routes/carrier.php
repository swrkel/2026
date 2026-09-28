<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\CarrierInvoiceController;

Route::prefix('stock-transfer-new/carrier-invoices')
    ->name('stock-transfer-new.carrier-invoices.')
    ->middleware(['web', 'auth'])
    ->group(function () {
        Route::get('/', [CarrierInvoiceController::class, 'index'])->name('index');
        Route::get('/create', [CarrierInvoiceController::class, 'create'])->name('create');
        Route::post('/', [CarrierInvoiceController::class, 'store'])->name('store');
        Route::post('/{carrierInvoice}/approve', [CarrierInvoiceController::class, 'approve'])->name('approve');
        Route::post('/{carrierInvoice}/cancel', [CarrierInvoiceController::class, 'cancel'])->name('cancel');
    });

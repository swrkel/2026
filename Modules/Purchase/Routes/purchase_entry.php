<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\Entry\PurchaseEntryCreateController;
use Modules\Purchase\Http\Controllers\Entry\PurchaseEntryDataController;
use Modules\Purchase\Http\Controllers\Entry\PurchaseEntryDeleteController;
use Modules\Purchase\Http\Controllers\Entry\PurchaseEntryEditController;
use Modules\Purchase\Http\Controllers\Entry\PurchaseEntryListController;
use Modules\Purchase\Http\Controllers\Entry\PurchaseEntryPaymentController;
use Modules\Purchase\Http\Controllers\Entry\PurchaseEntryPrintController;
use Modules\Purchase\Http\Controllers\Entry\PurchaseEntryShowController;

Route::prefix('entries')->as('entries.')->group(function () {
    Route::get('/', [PurchaseEntryListController::class, 'index'])->name('index');

    /*
     * IS2152: exports for the list.
     *
     * GET, so the current filters travel in the query string and an export
     * always matches what the list shows. Placed before the /{id} routes below
     * so "export" is never mistaken for a purchase id.
     */
    Route::get('/export/csv', [\Modules\Purchase\Http\Controllers\Entry\PurchaseEntryExportController::class, 'csv'])->name('export.csv');
    Route::get('/export/excel', [\Modules\Purchase\Http\Controllers\Entry\PurchaseEntryExportController::class, 'excel'])->name('export.excel');
    Route::get('/export/print', [\Modules\Purchase\Http\Controllers\Entry\PurchaseEntryExportController::class, 'print'])->name('export.print');
    Route::get('/create', [PurchaseEntryCreateController::class, 'create'])->name('create');
    Route::post('/', [PurchaseEntryCreateController::class, 'store'])->name('store');

    // All Add Purchase Entry lookups are owned by this standalone module.
    Route::prefix('data')->as('data.')->group(function () {
        Route::get('/suppliers', [PurchaseEntryDataController::class, 'suppliers'])->name('suppliers');
        Route::post('/suppliers', [PurchaseEntryDataController::class, 'storeSupplier'])->name('suppliers.store');
        Route::get('/supplier/{id}', [PurchaseEntryDataController::class, 'supplier'])->whereNumber('id')->name('supplier');
        Route::get('/purchase-orders', [PurchaseEntryDataController::class, 'purchaseOrders'])->name('purchase-orders');
        Route::get('/purchase-orders/{id}', [PurchaseEntryDataController::class, 'purchaseOrder'])->whereNumber('id')->name('purchase-orders.show');
        Route::get('/stores', [PurchaseEntryDataController::class, 'stores'])->name('stores');
        Route::get('/products', [PurchaseEntryDataController::class, 'products'])->name('products');
        Route::post('/products', [PurchaseEntryDataController::class, 'storeProduct'])->name('products.store');
        Route::get('/product/{variationId}', [PurchaseEntryDataController::class, 'product'])->whereNumber('variationId')->name('product');
        Route::get('/unload-tanks', [PurchaseEntryDataController::class, 'unloadTanks'])->name('unload-tanks');
        Route::get('/reference-check', [PurchaseEntryDataController::class, 'referenceCheck'])->name('reference-check');
    });

    // Add another payment to an existing purchase entry that still has a balance due.
    // These routes are intentionally under the entry itself and are declared before
    // the generic /{id} route so there is no route ambiguity.
    Route::get('/{id}/payments/create', [PurchaseEntryPaymentController::class, 'create'])
        ->whereNumber('id')
        ->name('payments.create');
    Route::post('/{id}/payments', [PurchaseEntryPaymentController::class, 'store'])
        ->whereNumber('id')
        ->name('payments.store');

    Route::get('/{id}/print', [PurchaseEntryPrintController::class, 'print'])->whereNumber('id')->name('print');
    Route::get('/{id}', [PurchaseEntryShowController::class, 'show'])->whereNumber('id')->name('show');
    Route::get('/{id}/edit', [PurchaseEntryEditController::class, 'edit'])->whereNumber('id')->name('edit');
    Route::put('/{id}', [PurchaseEntryEditController::class, 'update'])->whereNumber('id')->name('update');
    Route::delete('/{id}', [PurchaseEntryDeleteController::class, 'destroy'])->whereNumber('id')->name('destroy');
});

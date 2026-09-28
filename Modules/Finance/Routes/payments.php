<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])->prefix('finance')->name('finance.')->group(function () {
    /*
     * MA-002: the customer-payment cycle now lives in Finance.
     *
     * Only 'index' was routed, but index's DataTable builds View, Edit,
     * Update and Delete links with action(...), and action() THROWS when the
     * target method has no registered route - so the controller could not move
     * without these.
     *
     * Paths mirror core's so behaviour is unchanged; they are served from
     * /finance/... by Finance's controller.
     */
    Route::any('payments/customers', [\Modules\Finance\Http\Controllers\Payments\CustomerPaymentController::class, 'index'])->name('payments.customers.index');
    Route::get('payments/customers/{id}/view', [\Modules\Finance\Http\Controllers\Payments\CustomerPaymentController::class, 'viewPayment'])->where('id', '[0-9]+')->name('payments.customers.view');
    Route::get('payments/customers/{id}/print', [\Modules\Finance\Http\Controllers\Payments\CustomerPaymentController::class, 'printPayment'])->where('id', '[0-9]+')->name('payments.customers.print');
    Route::get('payments/customers/{id}/edit', [\Modules\Finance\Http\Controllers\Payments\CustomerPaymentController::class, 'edit'])->where('id', '[0-9]+')->name('payments.customers.edit');
    Route::put('payments/customers/{id}', [\Modules\Finance\Http\Controllers\Payments\CustomerPaymentController::class, 'update'])->where('id', '[0-9]+')->name('payments.customers.update');
    Route::delete('payments/customers/{id}', [\Modules\Finance\Http\Controllers\Payments\CustomerPaymentController::class, 'destroy'])->where('id', '[0-9]+')->name('payments.customers.destroy');
    Route::get('payments/customers/{id}/show', [\Modules\Finance\Http\Controllers\Payments\CustomerPaymentController::class, 'show'])->where('id', '[0-9]+')->name('payments.customers.show');
    Route::any('payments/bulk', [\Modules\Finance\Http\Controllers\Payments\CustomerPaymentBulkController::class, 'index'])->name('payments.bulk.index');
});

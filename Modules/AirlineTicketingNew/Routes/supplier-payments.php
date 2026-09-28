<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Settlements\SupplierPaymentController;

Route::get('supplier-payments', [SupplierPaymentController::class, 'index'])
    ->name('supplier-payments.index')
    ->middleware('permission:airline_ticketing_new.supplier_payments.view');

Route::post('supplier-settlements/{settlement}/payments', [SupplierPaymentController::class, 'store'])
    ->name('supplier-payments.store')
    ->middleware('permission:airline_ticketing_new.supplier_payments.create');

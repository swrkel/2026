<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Settlements\SupplierSettlementController;

Route::get('supplier-settlements', [SupplierSettlementController::class, 'index'])
    ->name('supplier-settlements.index')
    ->middleware('permission:airline_ticketing_new.supplier_settlements.view');

Route::post('supplier-settlements', [SupplierSettlementController::class, 'store'])
    ->name('supplier-settlements.store')
    ->middleware('permission:airline_ticketing_new.supplier_settlements.create');

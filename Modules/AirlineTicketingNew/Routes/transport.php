<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Transport\TransportVehicleController;

Route::get('transport/vehicles', [TransportVehicleController::class, 'index'])
    ->name('transport.vehicles.index')
    ->middleware('permission:airline_ticketing_new.transport_vehicles.view');

Route::post('transport/vehicles', [TransportVehicleController::class, 'store'])
    ->name('transport.vehicles.store')
    ->middleware('permission:airline_ticketing_new.transport_vehicles.manage');

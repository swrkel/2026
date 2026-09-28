<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Hotels\HotelController;

Route::get('hotels', [HotelController::class, 'index'])
    ->name('hotels.index')
    ->middleware('permission:airline_ticketing_new.hotels.view');

Route::post('hotels', [HotelController::class, 'store'])
    ->name('hotels.store')
    ->middleware('permission:airline_ticketing_new.hotels.manage');

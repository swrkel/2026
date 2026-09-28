<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Tours\TourPackageController;

Route::get('tours/packages', [TourPackageController::class, 'index'])
    ->name('tours.packages.index')
    ->middleware('permission:airline_ticketing_new.tour_packages.view');

Route::post('tours/packages', [TourPackageController::class, 'store'])
    ->name('tours.packages.store')
    ->middleware('permission:airline_ticketing_new.tour_packages.manage');

<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Visa\VisaApplicationController;

Route::get('visa/applications', [VisaApplicationController::class, 'index'])
    ->name('visa.applications.index')
    ->middleware('permission:airline_ticketing_new.visa_applications.view');

Route::post('visa/applications', [VisaApplicationController::class, 'store'])
    ->name('visa.applications.store')
    ->middleware('permission:airline_ticketing_new.visa_applications.create');

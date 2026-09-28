<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Mobile\MobileDashboardController;

Route::get('mobile/dashboard', [MobileDashboardController::class, 'index'])
    ->name('mobile.dashboard')
    ->middleware('permission:airline_ticketing_new.mobile.view');

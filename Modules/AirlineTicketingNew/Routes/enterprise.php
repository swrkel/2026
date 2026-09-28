<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Dashboard\ManagementDashboardController;
Route::get('management-dashboard',[ManagementDashboardController::class,'index'])
    ->name('management-dashboard')
    ->middleware('permission:airline_ticketing_new.management_dashboard.view');

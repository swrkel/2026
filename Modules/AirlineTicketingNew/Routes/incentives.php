<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Incentives\StaffIncentiveController;

Route::get('staff-incentives', [StaffIncentiveController::class, 'index'])
    ->name('staff-incentives.index')
    ->middleware('permission:airline_ticketing_new.staff_incentives.view');

Route::post('tickets/{ticket}/staff-incentive', [StaffIncentiveController::class, 'store'])
    ->name('staff-incentives.store')
    ->middleware('permission:airline_ticketing_new.staff_incentives.create');

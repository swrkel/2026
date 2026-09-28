<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Admin\HealthController;

Route::get('admin/health', [HealthController::class, 'index'])
    ->name('admin.health')
    ->middleware('permission:airline_ticketing_new.admin.health');

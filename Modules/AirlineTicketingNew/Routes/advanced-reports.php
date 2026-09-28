<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Reports\AirlineSalesReportController;
use Modules\AirlineTicketingNew\Http\Controllers\Reports\OutstandingReportController;

Route::get('reports/airline-sales', [AirlineSalesReportController::class, 'index'])
    ->name('reports.airline-sales')
    ->middleware('permission:airline_ticketing_new.reports.airline_sales');

Route::get('reports/outstanding', [OutstandingReportController::class, 'index'])
    ->name('reports.outstanding')
    ->middleware('permission:airline_ticketing_new.reports.outstanding');

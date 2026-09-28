<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Reporting\BiDashboardController;
use Modules\AirlineTicketingNew\Http\Controllers\Reporting\ExecutiveDashboardController;
use Modules\AirlineTicketingNew\Http\Controllers\Reporting\ReportCentreController;

Route::get('reporting/executive-dashboard', [ExecutiveDashboardController::class, 'index'])
    ->name('reporting.executive-dashboard')
    ->middleware('permission:airline_ticketing_new.reporting.executive');

Route::get('reporting/centre', [ReportCentreController::class, 'index'])
    ->name('reporting.centre')
    ->middleware('permission:airline_ticketing_new.reporting.centre');

Route::get('reporting/bi-dashboard', [BiDashboardController::class, 'index'])
    ->name('reporting.bi-dashboard')
    ->middleware('permission:airline_ticketing_new.reporting.bi');

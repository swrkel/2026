<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Reports\ProfitabilityReportController;
use Modules\AirlineTicketingNew\Http\Controllers\Reports\TicketSalesReportController;

Route::get('reports/ticket-sales', [TicketSalesReportController::class, 'index'])
    ->name('reports.ticket-sales')
    ->middleware('permission:airline_ticketing_new.reports.ticket_sales');

Route::get('reports/ticket-sales/csv', [TicketSalesReportController::class, 'csv'])
    ->name('reports.ticket-sales.csv')
    ->middleware('permission:airline_ticketing_new.reports.ticket_sales');

Route::get('reports/profitability', [ProfitabilityReportController::class, 'index'])
    ->name('reports.profitability')
    ->middleware('permission:airline_ticketing_new.reports.profitability');

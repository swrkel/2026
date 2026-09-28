<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Masters\AirlineController;
use Modules\AirlineTicketingNew\Http\Controllers\Masters\AirportController;
use Modules\AirlineTicketingNew\Http\Controllers\Masters\AircraftTypeController;
use Modules\AirlineTicketingNew\Http\Controllers\Masters\TravelClassController;
use Modules\AirlineTicketingNew\Http\Controllers\Masters\RouteController;
use Modules\AirlineTicketingNew\Http\Controllers\Masters\SupplierController;
use Modules\AirlineTicketingNew\Http\Controllers\Masters\AgentController;
use Modules\AirlineTicketingNew\Http\Controllers\Masters\CurrencyController;
use Modules\AirlineTicketingNew\Http\Controllers\Masters\TaxRuleController;
use Modules\AirlineTicketingNew\Http\Controllers\Masters\CommissionRuleController;

Route::prefix('masters')->as('masters.')->group(function (): void {
    Route::resource('airlines', AirlineController::class)->except(['show'])->parameters(['airlines' => 'record'])->middleware('permission:airline_ticketing_new.airlines.manage');
Route::resource('airports', AirportController::class)->except(['show'])->parameters(['airports' => 'record'])->middleware('permission:airline_ticketing_new.airports.manage');
Route::resource('aircraft-types', AircraftTypeController::class)->except(['show'])->parameters(['aircraft-types' => 'record'])->middleware('permission:airline_ticketing_new.aircraft-types.manage');
Route::resource('travel-classes', TravelClassController::class)->except(['show'])->parameters(['travel-classes' => 'record'])->middleware('permission:airline_ticketing_new.travel-classes.manage');
Route::resource('routes', RouteController::class)->except(['show'])->parameters(['routes' => 'record'])->middleware('permission:airline_ticketing_new.routes.manage');
Route::resource('suppliers', SupplierController::class)->except(['show'])->parameters(['suppliers' => 'record'])->middleware('permission:airline_ticketing_new.suppliers.manage');
Route::resource('agents', AgentController::class)->except(['show'])->parameters(['agents' => 'record'])->middleware('permission:airline_ticketing_new.agents.manage');
Route::resource('currencies', CurrencyController::class)->except(['show'])->parameters(['currencies' => 'record'])->middleware('permission:airline_ticketing_new.currencies.manage');
Route::resource('tax-rules', TaxRuleController::class)->except(['show'])->parameters(['tax-rules' => 'record'])->middleware('permission:airline_ticketing_new.tax-rules.manage');
Route::resource('commission-rules', CommissionRuleController::class)->except(['show'])->parameters(['commission-rules' => 'record'])->middleware('permission:airline_ticketing_new.commission-rules.manage');
});

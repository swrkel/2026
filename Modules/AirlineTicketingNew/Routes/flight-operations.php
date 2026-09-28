<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\FlightOperations\FlightScheduleController;
Route::get('flight-operations/schedules',[FlightScheduleController::class,'index'])->name('flight-operations.schedules.index')->middleware('permission:airline_ticketing_new.flight_schedules.view');
Route::post('flight-operations/schedules',[FlightScheduleController::class,'store'])->name('flight-operations.schedules.store')->middleware('permission:airline_ticketing_new.flight_schedules.manage');

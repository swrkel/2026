<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Petro General - Pump Management Routes
|--------------------------------------------------------------------------
| PG020 keeps the existing working PumpController business logic intact and
| introduces small wrapper controllers for each pump action.
*/

Route::get('/pump-management', 'Pump\\PumpIndexController@index')
    ->name('petrogeneral.pump_management.index');
Route::get('/pump-management/create', 'Pump\\PumpCreateController@create')
    ->name('petrogeneral.pump_management.create');
Route::post('/pump-management', 'Pump\\PumpStoreController@store')
    ->name('petrogeneral.pump_management.store');
Route::get('/pump-management/{id}', 'Pump\\PumpShowController@show')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.pump_management.show');
Route::get('/pump-management/{id}/edit', 'Pump\\PumpEditController@edit')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.pump_management.edit');
Route::put('/pump-management/{id}', 'Pump\\PumpUpdateController@update')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.pump_management.update');
Route::patch('/pump-management/{id}', 'Pump\\PumpUpdateController@update')
    ->where('id', '[0-9]+');
Route::delete('/pump-management/{id}', 'Pump\\PumpDeleteController@destroy')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.pump_management.destroy');

Route::get('/pumps/import', 'Pump\\PumpImportController@create')
    ->name('petrogeneral.pump_management.import');
Route::post('/pumps/save-import', 'Pump\\PumpImportController@store')
    ->name('petrogeneral.pump_management.import.save');
Route::get('/pump-management/get-meter-readings', 'Pump\\PumpMeterReadingController@index')
    ->name('petrogeneral.pump_management.meter_readings');
Route::get('/pump-management/get-testing-details', 'Pump\\PumpTestingDetailController@index')
    ->name('petrogeneral.pump_management.testing_details');

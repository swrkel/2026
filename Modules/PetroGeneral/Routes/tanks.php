<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Petro General - Tank Management Routes
|--------------------------------------------------------------------------
| PG020 keeps the existing working FuelTankController business logic intact
| and introduces small wrapper controllers for each tank action.
*/

Route::get('/tank-management', 'Tank\\TankIndexController@index')
    ->name('petrogeneral.tank_management.index');
Route::get('/tank-management/create', 'Tank\\TankCreateController@create')
    ->name('petrogeneral.tank_management.create');
Route::post('/tank-management', 'Tank\\TankStoreController@store')
    ->name('petrogeneral.tank_management.store');
Route::get('/tank-management/{id}', 'Tank\\TankShowController@show')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.tank_management.show');
Route::get('/tank-management/{id}/edit', 'Tank\\TankEditController@edit')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.tank_management.edit');
Route::put('/tank-management/{id}', 'Tank\\TankUpdateController@update')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.tank_management.update');
Route::patch('/tank-management/{id}', 'Tank\\TankUpdateController@update')
    ->where('id', '[0-9]+');
Route::delete('/tank-management/{id}', 'Tank\\TankDeleteController@destroy')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.tank_management.destroy');

Route::get('/tank/import', 'Tank\\TankImportController@create')
    ->name('petrogeneral.tank_management.import');
Route::post('/tank/save-import', 'Tank\\TankImportController@store')
    ->name('petrogeneral.tank_management.import.save');
Route::get('/tank-management/get-tank-product', 'Tank\\TankProductController@getTankProduct')
    ->name('petrogeneral.tank_management.get_tank_product');

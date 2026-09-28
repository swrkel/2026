<?php

use Illuminate\Support\Facades\Route;

Route::get('/business/date-default-settings', 'ModuleDateSettingController@index')
    ->name('business.module-date-defaults.index');
Route::post('/business/date-default-settings', 'ModuleDateSettingController@update')
    ->name('business.module-date-defaults.update');

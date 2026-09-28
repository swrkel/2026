<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Petro General Settings
|--------------------------------------------------------------------------
|
| Petro Settings belongs to Petro General.  Keep this route independent
| from the legacy /petro prefix so the legacy Petro module can be retired.
|
*/

Route::get('/settings', 'CustomerBillVatPrefixController@index')
    ->name('petrogeneral.settings.index');

Route::post('/settings', 'CustomerBillVatPrefixController@store')
    ->name('petrogeneral.settings.store');

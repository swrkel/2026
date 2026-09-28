<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;


Route::middleware('tenant.context')->group(function () {
  Route::resource('stock-reports', 'StockReportsController')->names('stockreports.legacy');
});
Route::group(['middleware' => ['web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone','tenant.context'], 'prefix' => 'stockreports'], function () {
   
    Route::resource('stock-reports', 'StockReportsController');
    Route::get('/transaction/{id}/show', 'StockReportsController@showTransaction')->name('stockreports.show-transaction');
    
});

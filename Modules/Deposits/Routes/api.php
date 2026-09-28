<?php

use Illuminate\Support\Facades\Route;

Route::prefix('deposits')->group(function () {
    Route::get('health', 'DashboardController@health')->name('api.deposits.health');
});

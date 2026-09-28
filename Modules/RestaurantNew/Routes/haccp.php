<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\HaccpController;

Route::prefix('restaurant-new/haccp')->middleware(['web','auth','restaurantnew.access'])->group(function () {
    Route::get('/', [HaccpController::class, 'index'])->name('restaurantnew.haccp.index');
    Route::get('/temperature-logs', [HaccpController::class, 'temperatureLogs'])->name('restaurantnew.haccp.temperature_logs');
    Route::post('/temperature-logs', [HaccpController::class, 'storeTemperature'])->name('restaurantnew.haccp.temperature_logs.store');
    Route::get('/corrective-actions', [HaccpController::class, 'correctiveActions'])->name('restaurantnew.haccp.corrective_actions');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\Analytics\RestaurantAnalyticsController;

Route::prefix('restaurant-new/analytics')->as('restaurantnew.analytics.')->middleware(['web', 'auth', 'restaurantnew.access'])->group(function () {
    Route::get('/', [RestaurantAnalyticsController::class, 'index'])->name('index')->middleware('can:restaurantnew.analytics.view');
    Route::post('/forecast', [RestaurantAnalyticsController::class, 'forecast'])->name('forecast')->middleware('can:restaurantnew.analytics.forecast');
});

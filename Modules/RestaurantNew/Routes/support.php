<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\Support\RestaurantNewReadinessController;

Route::middleware(['web', 'auth'])->prefix('restaurant-new/support')->name('restaurantnew.support.')->group(function () {
    Route::get('/readiness', [RestaurantNewReadinessController::class, 'index'])->name('readiness');
});

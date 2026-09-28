<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\Finalization\ReadinessController;

Route::middleware(['web', 'auth'])->prefix('restaurant-new/finalization')->name('restaurantnew.finalization.')->group(function () {
    Route::get('readiness', [ReadinessController::class, 'index'])->name('readiness');
});

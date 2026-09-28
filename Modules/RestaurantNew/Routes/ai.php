<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\Ai\RestaurantAiOperationsController;

Route::prefix('restaurant-new/ai')->as('restaurantnew.ai.')->middleware(['web', 'auth', 'restaurantnew.access'])->group(function () {
    Route::get('/', [RestaurantAiOperationsController::class, 'index'])->name('index')->middleware('can:restaurantnew.ai.view');
    Route::post('/forecast', [RestaurantAiOperationsController::class, 'forecast'])->name('forecast')->middleware('can:restaurantnew.ai.forecast');
    Route::post('/scan-menu-profit', [RestaurantAiOperationsController::class, 'scanMenuProfit'])->name('scan-menu-profit')->middleware('can:restaurantnew.ai.recommendations');
});

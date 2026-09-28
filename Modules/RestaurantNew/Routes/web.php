<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\PromotionController;
use Modules\RestaurantNew\Http\Controllers\ComboMealController;
use Modules\RestaurantNew\Http\Controllers\HappyHourController;
use Modules\RestaurantNew\Http\Controllers\BuffetPackageController;
use Modules\RestaurantNew\Http\Controllers\BanquetEventController;
use Modules\RestaurantNew\Http\Controllers\CateringController;

Route::middleware(['web', 'auth'])->prefix('restaurant-new/admin')->name('restaurantnew.admin.')->group(function () {
    Route::resource('promotions', PromotionController::class)->only(['index','store']);
    Route::resource('combo-meals', ComboMealController::class)->only(['index','store']);
    Route::resource('happy-hours', HappyHourController::class)->only(['index','store']);
    Route::resource('buffet-packages', BuffetPackageController::class)->only(['index','store']);
    Route::resource('banquet-events', BanquetEventController::class)->only(['index','store']);
    Route::resource('catering-orders', CateringController::class)->only(['index','store']);
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\Api\RestaurantNewLookupController;

Route::group([
    'prefix' => config('restaurantnew.route_prefix', 'restaurant-new'),
    'as' => 'api.restaurant-new.',
    'middleware' => ['auth:sanctum'],
], function () {
    Route::get('/lookups/tables', [RestaurantNewLookupController::class, 'tables'])->name('lookups.tables');
    Route::get('/lookups/menu-items', [RestaurantNewLookupController::class, 'menuItems'])->name('lookups.menu-items');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\RestaurantCrmController;

Route::group(['middleware' => ['web', 'auth', 'restaurantnew.enabled'], 'prefix' => 'restaurant-new/crm', 'as' => 'restaurantnew.crm.'], function () {
    Route::get('/', [RestaurantCrmController::class, 'dashboard'])->name('dashboard');
    Route::get('/customers', [RestaurantCrmController::class, 'customers'])->name('customers');
    Route::get('/customers/{id}', [RestaurantCrmController::class, 'showCustomer'])->name('customers.show');
    Route::get('/feedback', [RestaurantCrmController::class, 'feedback'])->name('feedback');
    Route::get('/campaigns', [RestaurantCrmController::class, 'campaigns'])->name('campaigns');
});

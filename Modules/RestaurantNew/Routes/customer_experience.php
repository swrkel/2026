<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\CustomerExperienceController;

Route::group(['prefix' => 'restaurant-new/customer-experience', 'middleware' => ['web', 'auth']], function () {
    Route::get('/requests', [CustomerExperienceController::class, 'requests'])->name('restaurantnew.customer_experience.requests');
    Route::post('/requests', [CustomerExperienceController::class, 'storeRequest'])->name('restaurantnew.customer_experience.requests.store');
    Route::post('/requests/{id}/status', [CustomerExperienceController::class, 'updateRequestStatus'])->name('restaurantnew.customer_experience.requests.status');
});

Route::group(['prefix' => 'restaurant-new/public', 'middleware' => ['web']], function () {
    Route::get('/track/{token}', [CustomerExperienceController::class, 'publicTracking'])->name('restaurantnew.public.track');
});

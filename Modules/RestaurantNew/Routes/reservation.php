<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\Reservation\RestaurantReservationController;

Route::group(['prefix' => 'restaurant-new/reservations', 'middleware' => ['web', 'auth', 'restaurantnew.permission']], function () {
    Route::get('/dashboard', [RestaurantReservationController::class, 'dashboard'])->name('restaurantnew.reservations.dashboard');
    Route::get('/floor-plans', [RestaurantReservationController::class, 'floorPlans'])->name('restaurantnew.reservations.floor_plans');
    Route::get('/list', [RestaurantReservationController::class, 'reservations'])->name('restaurantnew.reservations.index');
    Route::get('/waitlist', [RestaurantReservationController::class, 'waitlist'])->name('restaurantnew.reservations.waitlist');
    Route::post('/store', [RestaurantReservationController::class, 'storeReservation'])->name('restaurantnew.reservations.store');
    Route::post('/{reservation}/check-in', [RestaurantReservationController::class, 'checkIn'])->name('restaurantnew.reservations.check_in');
});

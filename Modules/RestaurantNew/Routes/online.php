<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\OnlineOrderingController;

Route::group(['prefix' => 'restaurant-new/online', 'as' => 'restaurantnew.online.'], function () {
    Route::get('/', [OnlineOrderingController::class, 'portal'])->name('portal');
    Route::get('/menu', [OnlineOrderingController::class, 'menu'])->name('menu');
    Route::get('/checkout', [OnlineOrderingController::class, 'checkout'])->name('checkout');
    Route::post('/orders', [OnlineOrderingController::class, 'store'])->name('orders.store');
    Route::get('/track/{orderNo}', [OnlineOrderingController::class, 'track'])->name('track');
});

Route::group(['middleware' => ['web','auth'], 'prefix' => 'restaurant-new/online-admin', 'as' => 'restaurantnew.online-admin.'], function () {
    Route::get('/kitchen-queue', [OnlineOrderingController::class, 'kitchenQueue'])->name('kitchen-queue');
    Route::post('/orders/{order}/status', [OnlineOrderingController::class, 'changeStatus'])->name('orders.status');
});

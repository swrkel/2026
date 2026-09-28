<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\CommandCenterController;

Route::group(['prefix' => 'restaurant-new/command-center', 'middleware' => ['web', 'auth']], function () {
    Route::get('/', [CommandCenterController::class, 'restaurant'])->name('restaurantnew.command.center');
    Route::get('/restaurant', [CommandCenterController::class, 'restaurant'])->name('restaurantnew.command.restaurant');
    Route::get('/kitchen', [CommandCenterController::class, 'kitchen'])->name('restaurantnew.command.kitchen');
    Route::get('/cashier', [CommandCenterController::class, 'cashier'])->name('restaurantnew.command.cashier');
    Route::get('/waiter', [CommandCenterController::class, 'waiter'])->name('restaurantnew.command.waiter');
    Route::get('/manager', [CommandCenterController::class, 'manager'])->name('restaurantnew.command.manager');
    Route::get('/executive', [CommandCenterController::class, 'executive'])->name('restaurantnew.command.executive');
    Route::get('/data/{screen}', [CommandCenterController::class, 'data'])->name('restaurantnew.command.data');
});

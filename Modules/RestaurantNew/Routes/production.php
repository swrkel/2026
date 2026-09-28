<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\Production\RestaurantProductionController;

Route::middleware(['web', 'auth', 'restaurantnew.access'])
    ->prefix('restaurant-new/production')
    ->name('restaurantnew.production.')
    ->group(function () {
        Route::get('/', [RestaurantProductionController::class, 'dashboard'])->name('dashboard');
        Route::get('/plans', [RestaurantProductionController::class, 'plans'])->name('plans.index');
        Route::post('/plans', [RestaurantProductionController::class, 'storePlan'])->name('plans.store');
        Route::post('/plans/{plan}/approve', [RestaurantProductionController::class, 'approvePlan'])->name('plans.approve');
        Route::get('/batches', [RestaurantProductionController::class, 'batches'])->name('batches.index');
        Route::post('/batches', [RestaurantProductionController::class, 'storeBatch'])->name('batches.store');
        Route::post('/batches/{batch}/complete', [RestaurantProductionController::class, 'completeBatch'])->name('batches.complete');
    });

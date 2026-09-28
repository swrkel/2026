<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\EnterpriseKdsController;

Route::middleware(['web', 'auth', 'restaurantnew.scope'])->prefix('restaurant-new/kds-enterprise')->name('restaurantnew.kds.enterprise.')->group(function () {
    Route::get('/', [EnterpriseKdsController::class, 'board'])->name('board');
    Route::get('/queue-json', [EnterpriseKdsController::class, 'queueJson'])->name('queue_json');
    Route::post('/push-item', [EnterpriseKdsController::class, 'pushItem'])->name('push_item');
    Route::post('/queue/{id}/status', [EnterpriseKdsController::class, 'changeStatus'])->name('change_status');
    Route::post('/queue/{id}/chef', [EnterpriseKdsController::class, 'reassignChef'])->name('reassign_chef');
    Route::get('/screens', [EnterpriseKdsController::class, 'screens'])->name('screens');
    Route::post('/screens', [EnterpriseKdsController::class, 'storeScreen'])->name('screens.store');
});

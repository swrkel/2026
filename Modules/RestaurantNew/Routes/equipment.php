<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\Equipment\EquipmentController;

Route::middleware(['web', 'auth', 'restaurantnew.enabled', 'restaurantnew.permission'])->prefix('restaurant-new/equipment')->name('restaurantnew.equipment.')->group(function () {
    Route::get('/', [EquipmentController::class, 'dashboard'])->name('dashboard');
    Route::get('/assets', [EquipmentController::class, 'assets'])->name('assets.index');
    Route::post('/assets', [EquipmentController::class, 'storeAsset'])->name('assets.store');
    Route::get('/work-orders', [EquipmentController::class, 'workOrders'])->name('work_orders.index');
    Route::post('/work-orders', [EquipmentController::class, 'storeWorkOrder'])->name('work_orders.store');
    Route::post('/work-orders/{id}/close', [EquipmentController::class, 'closeWorkOrder'])->name('work_orders.close');
    Route::get('/spare-parts', [EquipmentController::class, 'spareParts'])->name('spare_parts.index');
    Route::get('/schedules', [EquipmentController::class, 'schedules'])->name('schedules.index');
    Route::get('/alerts', [EquipmentController::class, 'alerts'])->name('alerts.index');
    Route::get('/reports', [EquipmentController::class, 'reports'])->name('reports.index');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\DockScheduleController;
use Modules\StockTransferNew\Http\Controllers\CapacityPlanController;

Route::middleware(['web','auth'])->prefix('stock-transfer-new')->name('stock-transfer-new.')->group(function () {
    Route::get('dock-schedules', [DockScheduleController::class, 'index'])->name('dock-schedules.index');
    Route::post('dock-schedules', [DockScheduleController::class, 'store'])->name('dock-schedules.store');
    Route::post('dock-schedules/{id}/close', [DockScheduleController::class, 'close'])->name('dock-schedules.close');
    Route::get('capacity-planning', [CapacityPlanController::class, 'index'])->name('capacity.index');
    Route::get('capacity-planning/export', [CapacityPlanController::class, 'export'])->name('capacity.export');
});

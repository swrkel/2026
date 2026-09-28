<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\Admin\MaintenanceController;

Route::prefix('stock-transfer-new/admin/maintenance')
    ->middleware(['web', 'auth'])
    ->name('stocktransfernew.admin.maintenance.')
    ->group(function () {
        Route::get('/', [MaintenanceController::class, 'index'])->name('index');
        Route::post('/', [MaintenanceController::class, 'store'])->name('store');
        Route::get('/calendar', [MaintenanceController::class, 'calendar'])->name('calendar');
        Route::post('/{id}/status', [MaintenanceController::class, 'updateStatus'])->name('status');
    });

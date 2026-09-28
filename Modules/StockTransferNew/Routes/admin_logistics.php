<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\Admin\TransferLogisticsExecutionController;

Route::middleware(['web', 'auth'])->prefix('stock-transfer-new/logistics')->name('stock-transfer-new.logistics.')->group(function () {
    Route::get('/', [TransferLogisticsExecutionController::class, 'index'])->name('index')->middleware('permission:stock_transfer_new.logistics.view');
    Route::post('/loads', [TransferLogisticsExecutionController::class, 'storeLoad'])->name('loads.store')->middleware('permission:stock_transfer_new.logistics.manage_loads');
    Route::post('/loads/{loadId}/dispatch', [TransferLogisticsExecutionController::class, 'dispatchLoad'])->name('loads.dispatch')->middleware('permission:stock_transfer_new.logistics.manage_loads');
    Route::post('/loads/{loadId}/receive', [TransferLogisticsExecutionController::class, 'receive'])->name('loads.receive')->middleware('permission:stock_transfer_new.logistics.manage_loads');
    Route::post('/consolidate', [TransferLogisticsExecutionController::class, 'consolidate'])->name('consolidate')->middleware('permission:stock_transfer_new.logistics.consolidate');
    Route::get('/export', [TransferLogisticsExecutionController::class, 'export'])->name('export')->middleware('permission:stock_transfer_new.logistics.export');
});

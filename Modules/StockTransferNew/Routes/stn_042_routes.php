<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\OperationsControlCenterController;

Route::prefix('stock-transfer-new/operations-control')->name('stocktransfernew.operations.')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [OperationsControlCenterController::class, 'index'])->name('index');
    Route::get('/live-board', [OperationsControlCenterController::class, 'liveBoard'])->name('live_board');
    Route::get('/exceptions', [OperationsControlCenterController::class, 'exceptions'])->name('exceptions');
    Route::post('/{transferId}/escalate', [OperationsControlCenterController::class, 'escalate'])->name('escalate');
    Route::get('/workload', [OperationsControlCenterController::class, 'workload'])->name('workload');
    Route::get('/calendar', [OperationsControlCenterController::class, 'calendar'])->name('calendar');
    Route::get('/export-csv', [OperationsControlCenterController::class, 'exportCsv'])->name('export_csv');
});

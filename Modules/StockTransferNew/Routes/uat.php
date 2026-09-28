<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\Uat\UatController;
use Modules\StockTransferNew\Http\Controllers\Uat\SignoffController;
use Modules\StockTransferNew\Http\Controllers\Uat\DataSnapshotController;

Route::middleware(['web', 'auth'])->prefix('stock-transfer-new/uat')->name('stocktransfernew.uat.')->group(function () {
    Route::get('/', [UatController::class, 'index'])->name('index');
    Route::get('/print', [UatController::class, 'print'])->name('print');
    Route::get('/signoff', [SignoffController::class, 'index'])->name('signoff.index');
    Route::post('/signoff', [SignoffController::class, 'store'])->name('signoff.store');
    Route::get('/snapshot', [DataSnapshotController::class, 'index'])->name('snapshot');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\ProductionReleaseController;

Route::middleware(['web', 'auth'])->prefix('stock-transfer-new')->name('stock-transfer-new.')->group(function () {
    Route::get('production-console', [ProductionReleaseController::class, 'index'])->name('production-console.index');
    Route::post('production-console/run', [ProductionReleaseController::class, 'run'])->name('production-console.run');
    Route::post('production-console/signoff', [ProductionReleaseController::class, 'signOff'])->name('production-console.signoff');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\ProductionValidationController;

Route::middleware(['web', 'auth'])->prefix('stock-transfer-new')->name('stocktransfernew.')->group(function () {
    Route::get('production-validation', [ProductionValidationController::class, 'index'])->name('production-validation.index');
    Route::post('production-validation/run', [ProductionValidationController::class, 'run'])->name('production-validation.run');
    Route::post('production-validation/issues/{issueId}/resolve', [ProductionValidationController::class, 'resolve'])->name('production-validation.resolve');
    Route::get('production-validation/export', [ProductionValidationController::class, 'export'])->name('production-validation.export');
});

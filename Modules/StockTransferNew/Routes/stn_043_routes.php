<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\PredictivePlanningController;

Route::middleware(['web', 'auth'])->prefix('stock-transfer-new')->name('stocktransfernew.')->group(function () {
    Route::get('predictive-planning', [PredictivePlanningController::class, 'index'])->name('predictive-planning.index');
    Route::post('predictive-planning/generate', [PredictivePlanningController::class, 'generate'])->name('predictive-planning.generate');
    Route::get('predictive-planning/{planId}', [PredictivePlanningController::class, 'show'])->name('predictive-planning.show');
    Route::post('predictive-planning/line/{lineId}/approve', [PredictivePlanningController::class, 'approveLine'])->name('predictive-planning.approve-line');
    Route::post('predictive-planning/line/{lineId}/reject', [PredictivePlanningController::class, 'rejectLine'])->name('predictive-planning.reject-line');
    Route::get('workload-balance', [PredictivePlanningController::class, 'workloadBalance'])->name('predictive-planning.workload-balance');
    Route::get('predictive-planning-export/csv', [PredictivePlanningController::class, 'exportCsv'])->name('predictive-planning.export');
});

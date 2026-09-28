<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\TransferForecastController;

Route::middleware(['web', 'auth'])->prefix('stock-transfer-new')->name('stock-transfer-new.')->group(function () {
    Route::get('forecasting', [TransferForecastController::class, 'index'])->name('forecasting.index');
    Route::post('forecasting/generate', [TransferForecastController::class, 'generate'])->name('forecasting.generate');
    Route::get('forecasting/export', [TransferForecastController::class, 'export'])->name('forecasting.export');
    Route::post('forecasting/suggestions/{suggestion}/approve', [TransferForecastController::class, 'approveSuggestion'])->name('forecasting.suggestions.approve');
    Route::post('forecasting/suggestions/{suggestion}/close', [TransferForecastController::class, 'closeSuggestion'])->name('forecasting.suggestions.close');
});

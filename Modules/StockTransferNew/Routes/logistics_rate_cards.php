<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\LogisticsRateCardController;

Route::prefix('stock-transfer-new/logistics-rate-cards')
    ->middleware(['web', 'auth'])
    ->name('stock-transfer-new.logistics-rate-cards.')
    ->group(function () {
        Route::get('/', [LogisticsRateCardController::class, 'index'])->name('index');
        Route::post('/', [LogisticsRateCardController::class, 'store'])->name('store');
        Route::post('/variance', [LogisticsRateCardController::class, 'variance'])->name('variance');
        Route::post('/{rateCard}/deactivate', [LogisticsRateCardController::class, 'deactivate'])->name('deactivate');
    });

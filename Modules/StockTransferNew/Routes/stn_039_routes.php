<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\ReplenishmentVersionController;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])
    ->prefix('stock-transfer-new/replenishment-versions')
    ->name('stocktransfernew.replenishment_versions.')
    ->group(function () {
        Route::get('/', [ReplenishmentVersionController::class, 'index'])->name('index');
        Route::post('/', [ReplenishmentVersionController::class, 'store'])->name('store');
        Route::post('/{id}/approve', [ReplenishmentVersionController::class, 'approve'])->name('approve');
        Route::post('/{id}/convert', [ReplenishmentVersionController::class, 'convert'])->name('convert');
        Route::post('/{id}/cancel', [ReplenishmentVersionController::class, 'cancel'])->name('cancel');
    });

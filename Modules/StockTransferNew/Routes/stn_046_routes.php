<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\ProductionHardeningController;

Route::middleware(['web', 'auth'])
    ->prefix('stock-transfer-new')
    ->as('stock-transfer-new.')
    ->group(function () {
        Route::get('production-hardening', [ProductionHardeningController::class, 'index'])
            ->name('production-hardening.index')
            ->middleware('permission:stock_transfer_new.production_hardening.view');

        Route::post('production-hardening/run', [ProductionHardeningController::class, 'run'])
            ->name('production-hardening.run')
            ->middleware('permission:stock_transfer_new.production_hardening.run');

        Route::get('production-hardening/export', [ProductionHardeningController::class, 'export'])
            ->name('production-hardening.export')
            ->middleware('permission:stock_transfer_new.production_hardening.export');
    });

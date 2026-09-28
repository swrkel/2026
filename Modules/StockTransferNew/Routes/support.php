<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\Support\DiagnosticsController;
use Modules\StockTransferNew\Http\Controllers\Support\RepairController;

Route::middleware(['web', 'auth'])
    ->prefix('stock-transfer-new/support')
    ->as('stock-transfer-new.support.')
    ->group(function () {
        Route::get('/diagnostics', [DiagnosticsController::class, 'index'])->name('diagnostics');
        Route::get('/tenant-scope', [DiagnosticsController::class, 'tenantScope'])->name('tenant-scope');
        Route::get('/permissions', [DiagnosticsController::class, 'permissions'])->name('permissions');
        Route::get('/routes-assets', [DiagnosticsController::class, 'routesAssets'])->name('routes-assets');
        Route::get('/repair', [RepairController::class, 'index'])->name('repair');
        Route::post('/repair/run', [RepairController::class, 'run'])->name('repair.run');
    });

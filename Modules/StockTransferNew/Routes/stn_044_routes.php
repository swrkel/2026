<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\EnterpriseAnalyticsController;

Route::middleware(['web', 'auth'])
    ->prefix('stock-transfer-new/enterprise-analytics')
    ->name('stocktransfernew.enterprise-analytics.')
    ->group(function () {
        Route::get('/', [EnterpriseAnalyticsController::class, 'index'])->name('index');
        Route::post('/detect-bottlenecks', [EnterpriseAnalyticsController::class, 'bottlenecks'])->name('bottlenecks');
        Route::post('/snapshot', [EnterpriseAnalyticsController::class, 'snapshot'])->name('snapshot');
        Route::get('/export', [EnterpriseAnalyticsController::class, 'export'])->name('export');
    });

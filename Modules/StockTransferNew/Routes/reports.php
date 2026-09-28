<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\ReportController;

Route::prefix('stock-transfer-new/reports')
    ->as('stock-transfer-new.reports.')
    ->group(function () {
        Route::get('/transfer-register', [ReportController::class, 'register'])
            ->name('transfer-register');

        // Backward-compatible alias retained for older links.
        Route::get('/register', [ReportController::class, 'register'])
            ->name('register');

        Route::get('/stock-movement', [ReportController::class, 'movement'])
            ->name('movement');

        Route::get('/balances', [ReportController::class, 'balances'])
            ->name('balances');

        Route::get('/stock-in-transit', [ReportController::class, 'inTransit'])
            ->name('in-transit');

        Route::get('/variance', [ReportController::class, 'variance'])
            ->name('variance');

        Route::get('/aging', [ReportController::class, 'aging'])
            ->name('aging');

        Route::get('/export/{type}', [ReportController::class, 'export'])
            ->name('export');
    });

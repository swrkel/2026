<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\TesterSupport\TestCaseController;
use Modules\StockTransferNew\Http\Controllers\TesterSupport\TroubleshootingController;
use Modules\StockTransferNew\Http\Controllers\TesterSupport\CleanupController;

Route::middleware(['web', 'auth'])
    ->prefix('stock-transfer-new/tester-support')
    ->as('stock-transfer-new.tester-support.')
    ->group(function () {
        Route::get('/test-cases', [TestCaseController::class, 'index'])->name('test-cases');
        Route::post('/test-cases/status', [TestCaseController::class, 'status'])->name('test-cases.status');
        Route::get('/troubleshooting', [TroubleshootingController::class, 'index'])->name('troubleshooting');
        Route::get('/cleanup-preview', [CleanupController::class, 'preview'])->name('cleanup-preview');
        Route::post('/cleanup-log', [CleanupController::class, 'storeLog'])->name('cleanup-log');
    });

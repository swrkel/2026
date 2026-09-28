<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\Admin\ArchiveController;

Route::middleware(['web', 'auth'])
    ->prefix('stock-transfer-new/archive')
    ->name('stocktransfernew.archive.')
    ->group(function () {
        Route::get('/', [ArchiveController::class, 'index'])->name('index');
        Route::post('/preview', [ArchiveController::class, 'preview'])->name('preview');
        Route::get('/{id}', [ArchiveController::class, 'show'])->name('show');
        Route::post('/{id}/execute', [ArchiveController::class, 'execute'])->name('execute');
        Route::post('/restore-request', [ArchiveController::class, 'restoreRequest'])->name('restore_request');
    });

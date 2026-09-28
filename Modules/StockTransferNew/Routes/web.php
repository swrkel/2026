<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\ApprovalController;
use Modules\StockTransferNew\Http\Controllers\BalanceController;
use Modules\StockTransferNew\Http\Controllers\CommandCenterController;
use Modules\StockTransferNew\Http\Controllers\DashboardController;
use Modules\StockTransferNew\Http\Controllers\DispatchController;
use Modules\StockTransferNew\Http\Controllers\ReadinessController;
use Modules\StockTransferNew\Http\Controllers\ReceiveController;
use Modules\StockTransferNew\Http\Controllers\SettingsController;
use Modules\StockTransferNew\Http\Controllers\TransferController;

Route::prefix('stock-transfer-new')
    ->as('stock-transfer-new.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/command-center', [CommandCenterController::class, 'index'])
            ->name('command-center.index');

        Route::get('/transfers/data', [TransferController::class, 'data'])
            ->name('transfers.data');

        Route::post('/transfers/{transfer}/submit', [TransferController::class, 'submit'])
            ->name('transfers.submit');

        Route::post('/transfers/{transfer}/cancel', [TransferController::class, 'cancel'])
            ->name('transfers.cancel');

        Route::get('/transfers/{transfer}/dispatch-note', [TransferController::class, 'dispatchNote'])
            ->name('transfers.dispatch-note');

        Route::get('/transfers/{transfer}/receive-note', [TransferController::class, 'receiveNote'])
            ->name('transfers.receive-note');

        Route::resource('transfers', TransferController::class);

        Route::get('/approvals', [ApprovalController::class, 'index'])
            ->name('approvals.index');

        Route::post('/approvals/{transfer}/approve', [ApprovalController::class, 'approve'])
            ->name('approvals.approve');

        Route::post('/approvals/{transfer}/reject', [ApprovalController::class, 'reject'])
            ->name('approvals.reject');

        Route::get('/dispatch', [DispatchController::class, 'index'])
            ->name('dispatch.index');

        Route::post('/dispatch/{transfer}', [DispatchController::class, 'dispatch'])
            ->name('dispatch.store');

        Route::get('/receive', [ReceiveController::class, 'index'])
            ->name('receive.index');

        Route::post('/receive/{transfer}', [ReceiveController::class, 'receive'])
            ->name('receive.store');

        Route::get('/balances', [BalanceController::class, 'index'])
            ->name('balances.index');

        Route::get('/settings', [SettingsController::class, 'index'])
            ->name('settings.index');

        Route::post('/settings', [SettingsController::class, 'store'])
            ->name('settings.store');

        Route::get('/readiness', [ReadinessController::class, 'index'])
            ->name('readiness.index');

        Route::get('/readiness/sql-checklist', [ReadinessController::class, 'sqlChecklist'])
            ->name('readiness.sql-checklist');

        Route::get('/readiness/export-checklist', [ReadinessController::class, 'exportChecklist'])
            ->name('readiness.export-checklist');
    });

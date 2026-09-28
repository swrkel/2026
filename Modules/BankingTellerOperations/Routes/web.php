<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingTellerOperations\Http\Controllers\DashboardController;
use Modules\BankingTellerOperations\Http\Controllers\CashCounterController;
use Modules\BankingTellerOperations\Http\Controllers\DrawerController;
use Modules\BankingTellerOperations\Http\Controllers\TellerSlipController;
use Modules\BankingTellerOperations\Http\Controllers\VaultRequestController;
use Modules\BankingTellerOperations\Http\Controllers\SupervisorApprovalController;
use Modules\BankingTellerOperations\Http\Controllers\EndOfDayController;
use Modules\BankingTellerOperations\Http\Controllers\ReportController;

Route::middleware(['web', 'auth'])->prefix('banking/teller')->name('banking.teller.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('cash-counters', CashCounterController::class);
    Route::resource('drawers', DrawerController::class);
    Route::post('drawers/{drawer}/cash-in', [DrawerController::class, 'cashIn'])->name('drawers.cash-in');
    Route::post('drawers/{drawer}/cash-out', [DrawerController::class, 'cashOut'])->name('drawers.cash-out');
    Route::resource('slips', TellerSlipController::class);
    Route::post('slips/{slip}/approve', [TellerSlipController::class, 'approve'])->name('slips.approve');
    Route::post('slips/{slip}/reverse', [TellerSlipController::class, 'reverse'])->name('slips.reverse');
    Route::resource('vault-requests', VaultRequestController::class);
    Route::get('supervisor/approvals', [SupervisorApprovalController::class, 'index'])->name('supervisor.approvals');
    Route::post('supervisor/approvals/{approval}/approve', [SupervisorApprovalController::class, 'approve'])->name('supervisor.approve');
    Route::post('supervisor/approvals/{approval}/reject', [SupervisorApprovalController::class, 'reject'])->name('supervisor.reject');
    Route::get('end-of-day', [EndOfDayController::class, 'index'])->name('eod.index');
    Route::post('end-of-day/close', [EndOfDayController::class, 'close'])->name('eod.close');
    Route::get('reports/{report?}', [ReportController::class, 'index'])->name('reports.index');
});

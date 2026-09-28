<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\Admin\TransferClaimController;

Route::middleware(['web', 'auth'])->prefix('stock-transfer-new')->name('stock-transfer-new.')->group(function () {
    Route::get('claims', [TransferClaimController::class, 'index'])->name('claims.index')->middleware('permission:stock_transfer_new.claims.view');
    Route::get('claims/create', [TransferClaimController::class, 'create'])->name('claims.create')->middleware('permission:stock_transfer_new.claims.create');
    Route::post('claims', [TransferClaimController::class, 'store'])->name('claims.store')->middleware('permission:stock_transfer_new.claims.create');
    Route::get('claims/{id}', [TransferClaimController::class, 'show'])->name('claims.show')->middleware('permission:stock_transfer_new.claims.view');
    Route::post('claims/{id}/submit', [TransferClaimController::class, 'submit'])->name('claims.submit')->middleware('permission:stock_transfer_new.claims.create');
    Route::post('claims/{id}/approve', [TransferClaimController::class, 'approve'])->name('claims.approve')->middleware('permission:stock_transfer_new.claims.approve');
    Route::post('claims/{id}/close', [TransferClaimController::class, 'close'])->name('claims.close')->middleware('permission:stock_transfer_new.claims.close');
    Route::post('claims/{id}/cancel', [TransferClaimController::class, 'cancel'])->name('claims.cancel')->middleware('permission:stock_transfer_new.claims.cancel');
});

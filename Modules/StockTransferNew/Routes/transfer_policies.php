<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\TransferPolicyController;

Route::middleware(['web', 'auth'])->prefix('stock-transfer-new')->name('stocktransfernew.')->group(function () {
    Route::get('transfer-policies', [TransferPolicyController::class, 'index'])->name('transfer-policies.index');
    Route::post('transfer-policies', [TransferPolicyController::class, 'store'])->name('transfer-policies.store');
    Route::get('transfer-policies/cost-center-summary', [TransferPolicyController::class, 'costCenterSummary'])->name('transfer-policies.cost-center-summary');
});

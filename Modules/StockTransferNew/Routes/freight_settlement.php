<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\FreightSettlementController;

Route::group(['middleware' => ['web', 'auth'], 'prefix' => 'stock-transfer-new', 'as' => 'stock-transfer-new.'], function () {
    Route::get('freight-settlement', [FreightSettlementController::class, 'index'])->name('freight-settlement.index');
    Route::post('freight-settlement/{invoice}/reconcile', [FreightSettlementController::class, 'reconcile'])->name('freight-settlement.reconcile');
    Route::get('freight-settlement-export', [FreightSettlementController::class, 'export'])->name('freight-settlement.export');
});

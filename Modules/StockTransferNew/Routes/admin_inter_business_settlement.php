<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\Admin\InterBusinessSettlementController;

Route::middleware(['web', 'auth'])
    ->prefix('stock-transfer-new/admin')
    ->as('stock-transfer-new.')
    ->group(function () {
        Route::get('inter-business-settlements', [InterBusinessSettlementController::class, 'index'])->name('inter-business-settlement.index');
        Route::get('inter-business-settlements/create', [InterBusinessSettlementController::class, 'create'])->name('inter-business-settlement.create');
        Route::post('inter-business-settlements', [InterBusinessSettlementController::class, 'store'])->name('inter-business-settlement.store');
        Route::get('inter-business-settlements/export', [InterBusinessSettlementController::class, 'export'])->name('inter-business-settlement.export');
        Route::get('inter-business-settlements/{id}', [InterBusinessSettlementController::class, 'show'])->name('inter-business-settlement.show');
        Route::post('inter-business-settlements/{id}/approve', [InterBusinessSettlementController::class, 'approve'])->name('inter-business-settlement.approve');
        Route::post('inter-business-settlements/{id}/cancel', [InterBusinessSettlementController::class, 'cancel'])->name('inter-business-settlement.cancel');
    });

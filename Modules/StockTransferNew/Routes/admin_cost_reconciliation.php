<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\Admin\CostReconciliationController;

Route::middleware(['web', 'auth'])
    ->prefix('stock-transfer-new/admin')
    ->as('stock-transfer-new.')
    ->group(function () {
        Route::get('cost-reconciliation', [CostReconciliationController::class, 'index'])->name('cost-reconciliation.index');
        Route::get('cost-reconciliation/export', [CostReconciliationController::class, 'export'])->name('cost-reconciliation.export');
    });

<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\DeploymentAssistantController;

Route::prefix('stock-transfer-new/deployment')->name('stock-transfer-new.deployment.')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [DeploymentAssistantController::class, 'index'])->name('index');
    Route::get('/sql-tracker', [DeploymentAssistantController::class, 'sqlTracker'])->name('sql-tracker');
    Route::post('/sql-executed', [DeploymentAssistantController::class, 'markSqlExecuted'])->name('sql-executed');
    Route::get('/rollback', [DeploymentAssistantController::class, 'rollbackPlan'])->name('rollback');
    Route::get('/export', [DeploymentAssistantController::class, 'exportChecks'])->name('export');
});

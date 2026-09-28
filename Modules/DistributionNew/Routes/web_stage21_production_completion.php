<?php

use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\ProductionCompletion\WorkflowValidationController;
use Modules\DistributionNew\Http\Controllers\ProductionCompletion\StockReconciliationController;
use Modules\DistributionNew\Http\Controllers\ProductionCompletion\VisitPlanController;
use Modules\DistributionNew\Http\Controllers\ProductionCompletion\ManagementDashboardController;

Route::middleware(['web', 'auth'])->prefix('distribution-new/production-completion')->name('distribution-new.production-completion.')->group(function () {
    Route::get('workflow-validation', [WorkflowValidationController::class, 'index'])->name('workflow-validation.index');
    Route::post('workflow-validation', [WorkflowValidationController::class, 'store'])->name('workflow-validation.store');
    Route::get('stock-reconciliation', [StockReconciliationController::class, 'index'])->name('stock-reconciliation.index');
    Route::post('stock-reconciliation', [StockReconciliationController::class, 'store'])->name('stock-reconciliation.store');
    Route::get('visit-plans', [VisitPlanController::class, 'index'])->name('visit-plans.index');
    Route::post('visit-plans', [VisitPlanController::class, 'store'])->name('visit-plans.store');
    Route::get('management-dashboard', [ManagementDashboardController::class, 'index'])->name('management-dashboard.index');
});

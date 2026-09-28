<?php

use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\OperationalPolish\ProfitabilityController;
use Modules\DistributionNew\Http\Controllers\OperationalPolish\CollectionControlController;
use Modules\DistributionNew\Http\Controllers\OperationalPolish\ReconciliationExceptionController;
use Modules\DistributionNew\Http\Controllers\OperationalPolish\DeploymentVerificationController;

Route::middleware(['web', 'auth'])->prefix('distribution-new/operational-polish')->name('distribution-new.operational-polish.')->group(function () {
    Route::get('/profitability', [ProfitabilityController::class, 'index'])->name('profitability.index');
    Route::get('/collection-controls', [CollectionControlController::class, 'index'])->name('collection-controls.index');
    Route::get('/reconciliation-exceptions', [ReconciliationExceptionController::class, 'index'])->name('reconciliation-exceptions.index');
    Route::get('/deployment-verification', [DeploymentVerificationController::class, 'index'])->name('deployment-verification.index');
});

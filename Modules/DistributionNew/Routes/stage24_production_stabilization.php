<?php

use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\ProductionStabilizationController;

Route::group(['middleware' => ['web', 'auth'], 'prefix' => 'distribution-new', 'as' => 'distributionnew.'], function () {
    Route::get('production-stabilization', [ProductionStabilizationController::class, 'index'])->name('production-stabilization.index');
    Route::get('production-stabilization/exceptions', [ProductionStabilizationController::class, 'exceptions'])->name('production-stabilization.exceptions');
    Route::post('production-stabilization/exceptions/{id}/resolve', [ProductionStabilizationController::class, 'resolveException'])->name('production-stabilization.exceptions.resolve');
    Route::get('production-stabilization/audits', [ProductionStabilizationController::class, 'audits'])->name('production-stabilization.audits');
});

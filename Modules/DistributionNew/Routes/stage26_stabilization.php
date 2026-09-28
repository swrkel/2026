<?php

use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\DisnewStabilizationController;

Route::middleware(['web', 'auth'])->prefix('distribution-new')->name('distribution-new.')->group(function () {
    Route::get('stabilization', [DisnewStabilizationController::class, 'index'])->name('stabilization.index');
    Route::get('stabilization/checklist', [DisnewStabilizationController::class, 'checklist'])->name('stabilization.checklist');
    Route::post('stabilization/run-checks', [DisnewStabilizationController::class, 'runChecks'])->name('stabilization.run-checks');
});

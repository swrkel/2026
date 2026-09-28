<?php

use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\FinalReadiness\FinalReadinessController;

Route::middleware(['web','auth'])->prefix('distribution-new/final-readiness')->name('distribution-new.final-readiness.')->group(function () {
    Route::get('/', [FinalReadinessController::class, 'index'])->name('index');
    Route::post('/run', [FinalReadinessController::class, 'run'])->name('run');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\Audit\DisnewAuditHealthController;
use Modules\DistributionNew\Http\Controllers\Install\DisnewInstallationController;

Route::middleware(['web', 'auth'])->prefix('distribution-new')->name('distributionnew.')->group(function () {
    Route::get('audit-health', [DisnewAuditHealthController::class, 'index'])->name('audit.index');
    Route::post('audit-health/run', [DisnewAuditHealthController::class, 'run'])->name('audit.run');

    Route::get('installation-checklist', [DisnewInstallationController::class, 'index'])->name('install.index');
    Route::post('installation-checklist/{id}/complete', [DisnewInstallationController::class, 'complete'])->name('install.complete');
});

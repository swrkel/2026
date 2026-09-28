<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Certification\MyHealthProductionCertificationController;

Route::prefix('my-health/production-certification')->name('myhealth.certification.')->group(function () {
    Route::get('/', [MyHealthProductionCertificationController::class, 'dashboard'])->name('dashboard');
    Route::get('/standalone', [MyHealthProductionCertificationController::class, 'standalone'])->name('standalone');
    Route::get('/security', [MyHealthProductionCertificationController::class, 'security'])->name('security');
    Route::get('/performance', [MyHealthProductionCertificationController::class, 'performance'])->name('performance');
    Route::get('/workflow', [MyHealthProductionCertificationController::class, 'workflow'])->name('workflow');
    Route::get('/release-notes', [MyHealthProductionCertificationController::class, 'releaseNotes'])->name('release_notes');
});

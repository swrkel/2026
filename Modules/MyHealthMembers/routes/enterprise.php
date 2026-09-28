<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Enterprise\MyHealthEnterpriseReleaseController;

Route::prefix('my-health/enterprise')->name('myhealth.enterprise.')->group(function () {
    Route::get('/', [MyHealthEnterpriseReleaseController::class, 'index'])->name('index');
    Route::get('/end-to-end-checklist', [MyHealthEnterpriseReleaseController::class, 'endToEnd'])->name('e2e');
    Route::get('/security-review', [MyHealthEnterpriseReleaseController::class, 'security'])->name('security');
    Route::get('/performance-review', [MyHealthEnterpriseReleaseController::class, 'performance'])->name('performance');
    Route::get('/release-notes', [MyHealthEnterpriseReleaseController::class, 'releaseNotes'])->name('release_notes');
});

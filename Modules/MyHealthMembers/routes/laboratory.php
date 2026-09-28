<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Laboratory\MyHealthLabProcessingController;
use Modules\MyHealthMembers\Http\Controllers\Laboratory\MyHealthLabSampleController;
use Modules\MyHealthMembers\Http\Controllers\Laboratory\MyHealthLabTestCatalogueController;
use Modules\MyHealthMembers\Http\Controllers\Laboratory\MyHealthLaboratoryDashboardController;

Route::prefix('my-health/laboratory')->name('myhealth.laboratory.')->group(function () {
    Route::get('/', [MyHealthLaboratoryDashboardController::class, 'index'])->name('dashboard');
    Route::resource('catalogue', MyHealthLabTestCatalogueController::class)->only(['index', 'create', 'store']);
    Route::resource('samples', MyHealthLabSampleController::class)->only(['index', 'create', 'store']);
    Route::post('samples/{sample}/stage', [MyHealthLabSampleController::class, 'stage'])->name('samples.stage');
    Route::resource('processing', MyHealthLabProcessingController::class)->only(['index', 'create', 'store']);
});

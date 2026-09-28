<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Radiology\MyHealthRadiologyDashboardController;
use Modules\MyHealthMembers\Http\Controllers\Radiology\MyHealthRadiologyReportController;
use Modules\MyHealthMembers\Http\Controllers\Radiology\MyHealthRadiologyRequestController;

Route::prefix('my-health/radiology')->name('myhealth.radiology.')->group(function () {
    Route::get('/', [MyHealthRadiologyDashboardController::class, 'index'])->name('dashboard');
    Route::resource('requests', MyHealthRadiologyRequestController::class)->only(['index', 'create', 'store']);
    Route::post('requests/{radiologyRequest}/stage', [MyHealthRadiologyRequestController::class, 'stage'])->name('requests.stage');
    Route::resource('reports', MyHealthRadiologyReportController::class)->only(['index', 'create', 'store']);
});

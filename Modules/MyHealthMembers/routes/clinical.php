<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Clinical\MyHealthClinicalDecisionController;

Route::prefix('myhealth')->as('myhealth.')->group(function () {
    Route::get('/members/{member}/clinical-decision-support', [MyHealthClinicalDecisionController::class, 'index'])->name('clinical.index');
    Route::post('/members/{member}/allergies', [MyHealthClinicalDecisionController::class, 'storeAllergy'])->name('clinical.allergies.store');
    Route::post('/members/{member}/chronic-conditions', [MyHealthClinicalDecisionController::class, 'storeCondition'])->name('clinical.conditions.store');
    Route::post('/clinical-alerts/{alert}/acknowledge', [MyHealthClinicalDecisionController::class, 'acknowledge'])->name('clinical.alerts.acknowledge');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Nursing\MyHealthCarePlanController;
use Modules\MyHealthMembers\Http\Controllers\Nursing\MyHealthHandoverController;
use Modules\MyHealthMembers\Http\Controllers\Nursing\MyHealthMedicationAdministrationController;
use Modules\MyHealthMembers\Http\Controllers\Nursing\MyHealthNursingDashboardController;
use Modules\MyHealthMembers\Http\Controllers\Nursing\MyHealthNursingNoteController;
use Modules\MyHealthMembers\Http\Controllers\Nursing\MyHealthNursingStationController;
use Modules\MyHealthMembers\Http\Controllers\Nursing\MyHealthVitalSignController;

Route::prefix('my-health/nursing')->name('myhealth.nursing.')->group(function () {
    Route::get('/', [MyHealthNursingDashboardController::class, 'index'])->name('dashboard');
    Route::get('/station', [MyHealthNursingStationController::class, 'index'])->name('station.index');

    Route::resource('vitals', MyHealthVitalSignController::class)->only(['index', 'create', 'store']);
    Route::resource('notes', MyHealthNursingNoteController::class)->only(['index', 'create', 'store']);
    Route::resource('medications', MyHealthMedicationAdministrationController::class)->only(['index', 'create', 'store']);
    Route::resource('care-plans', MyHealthCarePlanController::class)->names('care_plans')->only(['index', 'create', 'store']);
    Route::resource('handovers', MyHealthHandoverController::class)->only(['index', 'create', 'store']);
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Vaccination\MyHealthImmunizationScheduleController;
use Modules\MyHealthMembers\Http\Controllers\Vaccination\MyHealthVaccinationDashboardController;
use Modules\MyHealthMembers\Http\Controllers\Vaccination\MyHealthVaccinationRecordController;
use Modules\MyHealthMembers\Http\Controllers\Vaccination\MyHealthVaccinationReportController;
use Modules\MyHealthMembers\Http\Controllers\Vaccination\MyHealthVaccineController;

Route::prefix('my-health/vaccination')->name('myhealth.vaccination.')->group(function () {
    Route::get('/', [MyHealthVaccinationDashboardController::class, 'index'])->name('dashboard');
    Route::resource('vaccines', MyHealthVaccineController::class)->only(['index','create','store']);
    Route::resource('records', MyHealthVaccinationRecordController::class)->only(['index','create','store']);
    Route::get('records/{record}/certificate', [MyHealthVaccinationRecordController::class, 'certificate'])->name('records.certificate');
    Route::resource('schedules', MyHealthImmunizationScheduleController::class)->only(['index','create','store']);
    Route::get('reports', [MyHealthVaccinationReportController::class, 'index'])->name('reports.index');
});

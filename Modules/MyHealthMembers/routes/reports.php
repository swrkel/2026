<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Reports\MyHealthReportController;

Route::prefix('myhealth/reports')->as('myhealth.reports.')->group(function () {
    Route::get('/', [MyHealthReportController::class, 'index'])->name('index');
    Route::get('/patient-history', [MyHealthReportController::class, 'patientHistory'])->name('patient_history');
    Route::get('/prescriptions', [MyHealthReportController::class, 'prescriptions'])->name('prescriptions');
    Route::get('/labs', [MyHealthReportController::class, 'labs'])->name('labs');
    Route::get('/medicine-dispenses', [MyHealthReportController::class, 'dispenses'])->name('dispenses');
    Route::get('/insurance-claims', [MyHealthReportController::class, 'claims'])->name('claims');
    Route::get('/telemedicine', [MyHealthReportController::class, 'telemedicine'])->name('telemedicine');
    Route::get('/revenue', [MyHealthReportController::class, 'revenue'])->name('revenue');
    Route::get('/doctor-performance', [MyHealthReportController::class, 'doctorPerformance'])->name('doctor_performance');
    Route::get('/export/{report}', [MyHealthReportController::class, 'export'])->name('export');
});

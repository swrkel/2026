<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Doctor\MyHealthDoctorController;
use Modules\MyHealthMembers\Http\Controllers\Doctor\MyHealthConsultationController;
use Modules\MyHealthMembers\Http\Controllers\Doctor\MyHealthDoctorPortalController;
use Modules\MyHealthMembers\Http\Controllers\Doctor\MyHealthDoctorRecordController;

Route::prefix('myhealth')->as('myhealth.')->group(function () {
    Route::get('/doctor-portal', [MyHealthDoctorPortalController::class, 'dashboard'])->name('doctor.portal.dashboard');
    Route::get('/doctor-portal/members/{member}/consult', [MyHealthDoctorPortalController::class, 'createConsultation'])->name('doctor.portal.consultation.create');
    Route::post('/doctor-portal/members/{member}/consult', [MyHealthDoctorPortalController::class, 'storeConsultation'])->name('doctor.portal.consultation.store');
    Route::post('/doctor-portal/consultations/{consultation}/complete', [MyHealthDoctorPortalController::class, 'complete'])->name('doctor.portal.consultation.complete');

    Route::get('/doctors', [MyHealthDoctorController::class, 'index'])->name('doctors.index');
    Route::get('/doctors/create', [MyHealthDoctorController::class, 'create'])->name('doctors.create');
    Route::post('/doctors', [MyHealthDoctorController::class, 'store'])->name('doctors.store');

    Route::get('/consultations/{member}', [MyHealthConsultationController::class, 'index'])->name('consultations.index');
    Route::get('/consultations/{member}/create', [MyHealthConsultationController::class, 'create'])->name('consultations.create');
    Route::post('/consultations/{member}', [MyHealthConsultationController::class, 'store'])->name('consultations.store');

    Route::get('/doctor/{member}/records', [MyHealthDoctorRecordController::class, 'index'])->name('doctor.records.index');
    Route::post('/doctor/{member}/history', [MyHealthDoctorRecordController::class, 'saveHistory'])->name('doctor.history.save');
    Route::post('/doctor/{member}/diagnosis', [MyHealthDoctorRecordController::class, 'storeDiagnosis'])->name('doctor.diagnosis.store');
    Route::post('/doctor/{member}/prescription', [MyHealthDoctorRecordController::class, 'storePrescription'])->name('doctor.prescription.store');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Api\MyHealthAuthApiController;
use Modules\MyHealthMembers\Http\Controllers\Api\MyHealthInsuranceApiController;
use Modules\MyHealthMembers\Http\Controllers\Api\MyHealthPharmacyApiController;
use Modules\MyHealthMembers\Http\Controllers\Api\MyHealthProfileApiController;
use Modules\MyHealthMembers\Http\Controllers\Api\MyHealthRecordsApiController;

Route::prefix('api/myhealth')->as('api.myhealth.')->group(function () {
    Route::post('/member/login', [MyHealthAuthApiController::class, 'login'])->name('member.login');

    Route::middleware(['myhealth.mobile'])->group(function () {
        Route::post('/member/logout', [MyHealthAuthApiController::class, 'logout'])->name('member.logout');
        Route::get('/member/profile', [MyHealthProfileApiController::class, 'profile'])->name('member.profile');
        Route::put('/member/profile', [MyHealthProfileApiController::class, 'updateProfile'])->name('member.profile.update');
        Route::get('/member/history', [MyHealthProfileApiController::class, 'medicalHistory'])->name('member.history');
        Route::get('/member/diagnoses', [MyHealthRecordsApiController::class, 'diagnoses'])->name('member.diagnoses');
        Route::get('/member/documents', [MyHealthRecordsApiController::class, 'documents'])->name('member.documents');
        Route::get('/member/labs', [MyHealthRecordsApiController::class, 'labs'])->name('member.labs');
        Route::get('/member/prescriptions', [MyHealthRecordsApiController::class, 'prescriptions'])->name('member.prescriptions');
        Route::get('/member/pharmacy/dispenses', [MyHealthPharmacyApiController::class, 'dispenses'])->name('member.pharmacy.dispenses');
        Route::get('/member/pharmacy/dispenses/{dispense}', [MyHealthPharmacyApiController::class, 'dispenseItems'])->name('member.pharmacy.dispenses.show');
        Route::get('/member/insurance/policies', [MyHealthInsuranceApiController::class, 'policies'])->name('member.insurance.policies');
        Route::get('/member/insurance/claims', [MyHealthInsuranceApiController::class, 'claims'])->name('member.insurance.claims');
        Route::get('/member/insurance/claims/{claim}', [MyHealthInsuranceApiController::class, 'claimItems'])->name('member.insurance.claims.show');
        Route::get('/member/qr', [MyHealthProfileApiController::class, 'qr'])->name('member.qr');
    });
});

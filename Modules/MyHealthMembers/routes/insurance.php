<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Insurance\MyHealthInsuranceClaimController;
use Modules\MyHealthMembers\Http\Controllers\Insurance\MyHealthInsuranceCompanyController;
use Modules\MyHealthMembers\Http\Controllers\Insurance\MyHealthInsuranceController;
use Modules\MyHealthMembers\Http\Controllers\Insurance\MyHealthInsurancePolicyController;

Route::prefix('myhealth/insurance')->as('myhealth.insurance.')->group(function () {
    Route::get('/', [MyHealthInsuranceController::class, 'dashboard'])->name('dashboard');

    Route::get('/companies', [MyHealthInsuranceCompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/create', [MyHealthInsuranceCompanyController::class, 'create'])->name('companies.create');
    Route::post('/companies', [MyHealthInsuranceCompanyController::class, 'store'])->name('companies.store');

    Route::get('/policies', [MyHealthInsurancePolicyController::class, 'index'])->name('policies.index');
    Route::get('/policies/create', [MyHealthInsurancePolicyController::class, 'create'])->name('policies.create');
    Route::post('/policies', [MyHealthInsurancePolicyController::class, 'store'])->name('policies.store');

    Route::get('/claims', [MyHealthInsuranceClaimController::class, 'index'])->name('claims.index');
    Route::get('/claims/create', [MyHealthInsuranceClaimController::class, 'create'])->name('claims.create');
    Route::post('/claims', [MyHealthInsuranceClaimController::class, 'store'])->name('claims.store');
    Route::get('/claims/{claim}', [MyHealthInsuranceClaimController::class, 'show'])->name('claims.show');
});

<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrRecruitmentTalentController;

Route::prefix('hr-manager/recruitment-talent')->middleware(['web','auth'])->name('hrmanager.recruitment_talent.')->group(function(){
    Route::get('/', [HrRecruitmentTalentController::class, 'index'])->name('index');
    Route::post('/candidates', [HrRecruitmentTalentController::class, 'storeCandidate'])->name('candidates.store');
    Route::post('/applications', [HrRecruitmentTalentController::class, 'storeApplication'])->name('applications.store');
});

Route::prefix('hr/recruitment-talent')->middleware(['web','auth'])->name('hr.recruitment_talent.')->group(function(){
    Route::get('/', [HrRecruitmentTalentController::class, 'index'])->name('dashboard');
    Route::post('/candidates', [HrRecruitmentTalentController::class, 'storeCandidate'])->name('candidates.store');
    Route::post('/applications', [HrRecruitmentTalentController::class, 'storeApplication'])->name('applications.store');
});

<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrRecruitmentController;

Route::prefix('hr-manager/recruitment')->middleware(['web','auth'])->name('hrmanager.recruitment.')->group(function(){
    Route::get('/', [HrRecruitmentController::class, 'index'])->name('index');
    Route::post('/candidates', [HrRecruitmentController::class, 'storeCandidate'])->name('candidates.store');
});

Route::prefix('hr/recruitment')->middleware(['web','auth'])->name('hr.recruitment.')->group(function(){
    Route::get('/', [HrRecruitmentController::class, 'index'])->name('dashboard');
    Route::post('/candidates', [HrRecruitmentController::class, 'storeCandidate'])->name('candidates.store');
});

<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrCompetencyController;

Route::prefix('hr-manager/competencies')->middleware(['web','auth'])->name('hrmanager.competencies.')->group(function(){
    Route::get('/', [HrCompetencyController::class, 'index'])->name('index');
    Route::post('/competencies', [HrCompetencyController::class, 'storeCompetency'])->name('competencies.store');
    Route::post('/assessments', [HrCompetencyController::class, 'storeAssessment'])->name('assessments.store');
});

Route::prefix('hr/competencies')->middleware(['web','auth'])->name('hr.competencies.')->group(function(){
    Route::get('/', [HrCompetencyController::class, 'index'])->name('dashboard');
    Route::post('/competencies', [HrCompetencyController::class, 'storeCompetency'])->name('competencies.store');
    Route::post('/assessments', [HrCompetencyController::class, 'storeAssessment'])->name('assessments.store');
});

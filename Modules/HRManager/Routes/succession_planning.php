<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrSuccessionPlanningController;

Route::prefix('hr-manager/succession-planning')->middleware(['web','auth'])->name('hrmanager.succession_planning.')->group(function(){
    Route::get('/', [HrSuccessionPlanningController::class, 'index'])->name('index');
    Route::post('/critical-roles', [HrSuccessionPlanningController::class, 'storeRole'])->name('roles.store');
    Route::post('/candidates', [HrSuccessionPlanningController::class, 'nominateCandidate'])->name('candidates.store');
});

Route::prefix('hr/succession-planning')->middleware(['web','auth'])->name('hr.succession_planning.')->group(function(){
    Route::get('/', [HrSuccessionPlanningController::class, 'index'])->name('dashboard');
    Route::post('/critical-roles', [HrSuccessionPlanningController::class, 'storeRole'])->name('roles.store');
    Route::post('/candidates', [HrSuccessionPlanningController::class, 'nominateCandidate'])->name('candidates.store');
});

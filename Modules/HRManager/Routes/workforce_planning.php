<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrWorkforcePlanningController;

Route::prefix('hr-manager/workforce-planning')->middleware(['web','auth'])->name('hrmanager.workforce_planning.')->group(function(){
    Route::get('/', [HrWorkforcePlanningController::class, 'index'])->name('index');
    Route::post('/plans', [HrWorkforcePlanningController::class, 'storePlan'])->name('plans.store');
});

Route::prefix('hr/workforce-planning')->middleware(['web','auth'])->name('hr.workforce_planning.')->group(function(){
    Route::get('/', [HrWorkforcePlanningController::class, 'index'])->name('dashboard');
    Route::post('/plans', [HrWorkforcePlanningController::class, 'storePlan'])->name('plans.store');
});

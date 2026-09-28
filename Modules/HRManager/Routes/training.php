<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrTrainingController;
Route::prefix('hr-manager/training')->middleware(['web','auth'])->name('hrmanager.training.')->group(function(){
    Route::get('/', [HrTrainingController::class, 'index'])->name('index');
    Route::post('/enroll', [HrTrainingController::class, 'enroll'])->name('enroll');
});
Route::prefix('hr/training')->middleware(['web','auth'])->name('hr.training.')->group(function(){
    Route::get('/', [HrTrainingController::class, 'index'])->name('dashboard');
    Route::post('/enroll', [HrTrainingController::class, 'enroll'])->name('enroll');
});

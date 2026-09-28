<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrPayrollEngineController;

Route::prefix('hr-manager/payroll-engine')->middleware(['web','auth'])->name('hrmanager.payroll_engine.')->group(function(){
    Route::get('/', [HrPayrollEngineController::class, 'index'])->name('index');
    Route::post('/runs', [HrPayrollEngineController::class, 'storeRun'])->name('runs.store');
    Route::post('/runs/{id}/calculate', [HrPayrollEngineController::class, 'calculateRun'])->name('runs.calculate');
});

Route::prefix('hr/payroll-engine')->middleware(['web','auth'])->name('hr.payroll_engine.')->group(function(){
    Route::get('/', [HrPayrollEngineController::class, 'index'])->name('dashboard');
    Route::post('/runs', [HrPayrollEngineController::class, 'storeRun'])->name('runs.store');
    Route::post('/runs/{id}/calculate', [HrPayrollEngineController::class, 'calculateRun'])->name('runs.calculate');
});

<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrPayrollCentralController;
Route::prefix('hr-manager/payroll')->middleware(['web','auth'])->name('hrmanager.payroll.')->group(function(){
 Route::get('/',[HrPayrollCentralController::class,'index'])->name('index');
 Route::post('/runs',[HrPayrollCentralController::class,'storeRun'])->name('runs.store');
 Route::post('/runs/{id}/process',[HrPayrollCentralController::class,'processRun'])->name('runs.process');
});
Route::prefix('hr/payroll')->middleware(['web','auth'])->name('hr.payroll.')->group(function(){
 Route::get('/',[HrPayrollCentralController::class,'index'])->name('dashboard');
 Route::post('/runs',[HrPayrollCentralController::class,'storeRun'])->name('runs.store');
 Route::post('/runs/{id}/process',[HrPayrollCentralController::class,'processRun'])->name('runs.process');
});

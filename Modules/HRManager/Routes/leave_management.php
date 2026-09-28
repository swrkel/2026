<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrEnterpriseLeaveController;
Route::prefix('hr-manager/leave-management')->middleware(['web','auth'])->name('hrmanager.leave_management.')->group(function(){
 Route::get('/',[HrEnterpriseLeaveController::class,'index'])->name('index');
 Route::post('/applications',[HrEnterpriseLeaveController::class,'storeApplication'])->name('applications.store');
 Route::post('/applications/{id}/approve',[HrEnterpriseLeaveController::class,'approve'])->name('applications.approve');
 Route::post('/applications/{id}/reject',[HrEnterpriseLeaveController::class,'reject'])->name('applications.reject');
});
Route::prefix('hr/leave-management')->middleware(['web','auth'])->name('hr.leave_management.')->group(function(){
 Route::get('/',[HrEnterpriseLeaveController::class,'index'])->name('dashboard');
 Route::post('/applications',[HrEnterpriseLeaveController::class,'storeApplication'])->name('applications.store');
 Route::post('/applications/{id}/approve',[HrEnterpriseLeaveController::class,'approve'])->name('applications.approve');
 Route::post('/applications/{id}/reject',[HrEnterpriseLeaveController::class,'reject'])->name('applications.reject');
});

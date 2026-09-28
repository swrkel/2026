<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HRFaceAttendanceController;
Route::prefix('hr-manager/face-attendance')->middleware(['web','auth'])->name('hrmanager.face.')->group(function(){
 Route::get('/',[HRFaceAttendanceController::class,'index'])->name('index');
 Route::post('/enroll',[HRFaceAttendanceController::class,'enroll'])->name('enroll');
 Route::post('/devices',[HRFaceAttendanceController::class,'storeDevice'])->name('devices.store');
 Route::post('/simulate-log',[HRFaceAttendanceController::class,'simulateLog'])->name('simulate_log');
});
Route::prefix('hr/face')->middleware(['web','auth'])->name('hr.face.')->group(function(){
 Route::get('/',[HRFaceAttendanceController::class,'index'])->name('dashboard');
 Route::post('/enroll',[HRFaceAttendanceController::class,'enroll'])->name('enroll');
 Route::post('/devices',[HRFaceAttendanceController::class,'storeDevice'])->name('devices.store');
 Route::post('/simulate-log',[HRFaceAttendanceController::class,'simulateLog'])->name('simulate_log');
});

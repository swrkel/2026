<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrManagerController;
Route::middleware(['web','auth'])->group(function () {
    Route::get('/hr-manager/attendance', [HrManagerController::class, 'attendance'])->name('hrmanager.attendance.index');
    Route::get('/hr/attendance', [HrManagerController::class, 'attendance'])->name('hr.attendance.index');
});

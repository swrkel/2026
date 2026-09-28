<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrManagerController;
Route::middleware(['web','auth'])->group(function () {
    Route::get('/hr-manager/employee-records', [HrManagerController::class, 'employeeRecords'])->name('hrmanager.employee_records.index');
    Route::get('/hr/employee-records', [HrManagerController::class, 'employeeRecords'])->name('hr.employee_records.dashboard');
});

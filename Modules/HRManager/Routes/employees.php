<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrManagerController;
Route::middleware(['web','auth'])->group(function () {
    Route::get('/hr-manager/employees', [HrManagerController::class, 'employees'])->name('hrmanager.employees.index');
    Route::get('/hr/employees', [HrManagerController::class, 'employees'])->name('hr.employees.index');
});

<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrManagerController;
Route::middleware(['web','auth'])->group(function () {
    Route::get('/hr-manager/reports', [HrManagerController::class, 'reports'])->name('hrmanager.reports.index');
    Route::get('/hr/reports', [HrManagerController::class, 'reports'])->name('hr.reports.index');
});

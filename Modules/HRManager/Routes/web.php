<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrManagerController;
Route::middleware(['web','auth'])->group(function () {
    Route::get('/hr-manager', [HrManagerController::class, 'dashboard'])->name('hrmanager.dashboard.root');
    Route::get('/hr-manager/dashboard', [HrManagerController::class, 'dashboard'])->name('hrmanager.dashboard');
    Route::get('/hr', [HrManagerController::class, 'dashboard'])->name('hr.index');
    Route::get('/hr/dashboard', [HrManagerController::class, 'dashboard'])->name('hr.dashboard');
});

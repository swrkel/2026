<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrManagerController;
Route::middleware(['web','auth'])->group(function () {
    Route::get('/hr-manager/setup', [HrManagerController::class, 'setup'])->name('hrmanager.setup');
    Route::get('/hr/setup', [HrManagerController::class, 'setup'])->name('hr.setup.index');
});

<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrOrganizationController;

Route::prefix('hr-manager/organization')->middleware(['web','auth'])->name('hrmanager.organization.')->group(function(){
    Route::get('/', [HrOrganizationController::class, 'index'])->name('index');
    Route::post('/units', [HrOrganizationController::class, 'storeUnit'])->name('units.store');
    Route::post('/positions', [HrOrganizationController::class, 'storePosition'])->name('positions.store');
    Route::post('/assignments', [HrOrganizationController::class, 'storeAssignment'])->name('assignments.store');
});

Route::prefix('hr/organization')->middleware(['web','auth'])->name('hr.organization.')->group(function(){
    Route::get('/', [HrOrganizationController::class, 'index'])->name('dashboard');
    Route::post('/units', [HrOrganizationController::class, 'storeUnit'])->name('units.store');
    Route::post('/positions', [HrOrganizationController::class, 'storePosition'])->name('positions.store');
    Route::post('/assignments', [HrOrganizationController::class, 'storeAssignment'])->name('assignments.store');
});

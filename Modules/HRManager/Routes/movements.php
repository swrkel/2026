<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrEmployeeMovementController;

Route::prefix('hr-manager/movements')->middleware(['web','auth'])->name('hrmanager.movements.')->group(function(){
    Route::get('/', [HrEmployeeMovementController::class, 'index'])->name('index');
    Route::post('/employee-movements', [HrEmployeeMovementController::class, 'storeMovement'])->name('employee_movements.store');
});

Route::prefix('hr/movements')->middleware(['web','auth'])->name('hr.movements.')->group(function(){
    Route::get('/', [HrEmployeeMovementController::class, 'index'])->name('dashboard');
    Route::post('/employee-movements', [HrEmployeeMovementController::class, 'storeMovement'])->name('employee_movements.store');
});

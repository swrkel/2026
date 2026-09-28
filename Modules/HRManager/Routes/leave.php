<?php

use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrLeaveCentralController;

Route::prefix('hr-manager/leave')->middleware(['web', 'auth'])->name('hrmanager.leave.')->group(function () {
    Route::get('/', [HrLeaveCentralController::class, 'index'])->name('index');
    Route::post('/', [HrLeaveCentralController::class, 'store'])->name('store');
    Route::post('/{id}/approve', [HrLeaveCentralController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [HrLeaveCentralController::class, 'reject'])->name('reject');
});

Route::prefix('hr/leave')->middleware(['web', 'auth'])->name('hr.leave.')->group(function () {
    Route::get('/', [HrLeaveCentralController::class, 'index'])->name('dashboard');
    Route::post('/', [HrLeaveCentralController::class, 'store'])->name('store');
    Route::post('/{id}/approve', [HrLeaveCentralController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [HrLeaveCentralController::class, 'reject'])->name('reject');
});

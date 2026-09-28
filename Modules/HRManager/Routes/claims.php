<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrClaimController;
Route::prefix('hr-manager/claims')->middleware(['web','auth'])->name('hrmanager.claims.')->group(function(){
    Route::get('/', [HrClaimController::class, 'index'])->name('index');
    Route::post('/claims', [HrClaimController::class, 'storeClaim'])->name('claims.store');
});
Route::prefix('hr/claims')->middleware(['web','auth'])->name('hr.claims.')->group(function(){
    Route::get('/', [HrClaimController::class, 'index'])->name('dashboard');
    Route::post('/claims', [HrClaimController::class, 'storeClaim'])->name('claims.store');
});

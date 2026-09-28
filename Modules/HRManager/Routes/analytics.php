<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrAnalyticsController;

Route::prefix('hr-manager/analytics')->middleware(['web','auth'])->name('hrmanager.analytics.')->group(function(){
    Route::get('/', [HrAnalyticsController::class, 'index'])->name('index');
    Route::post('/snapshots', [HrAnalyticsController::class, 'createSnapshot'])->name('snapshots.store');
});

Route::prefix('hr/analytics')->middleware(['web','auth'])->name('hr.analytics.')->group(function(){
    Route::get('/', [HrAnalyticsController::class, 'index'])->name('dashboard');
    Route::post('/snapshots', [HrAnalyticsController::class, 'createSnapshot'])->name('snapshots.store');
});

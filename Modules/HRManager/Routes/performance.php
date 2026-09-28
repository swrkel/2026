<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrPerformanceController;

Route::prefix('hr-manager/performance')->middleware(['web','auth'])->name('hrmanager.performance.')->group(function(){
    Route::get('/', [HrPerformanceController::class, 'index'])->name('index');
    Route::post('/goals', [HrPerformanceController::class, 'storeGoal'])->name('goals.store');
    Route::post('/reviews', [HrPerformanceController::class, 'storeReview'])->name('reviews.store');
});

Route::prefix('hr/performance')->middleware(['web','auth'])->name('hr.performance.')->group(function(){
    Route::get('/', [HrPerformanceController::class, 'index'])->name('dashboard');
    Route::post('/goals', [HrPerformanceController::class, 'storeGoal'])->name('goals.store');
    Route::post('/reviews', [HrPerformanceController::class, 'storeReview'])->name('reviews.store');
});

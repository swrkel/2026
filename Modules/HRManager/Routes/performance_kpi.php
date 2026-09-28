<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrPerformanceKpiController;

Route::prefix('hr-manager/performance-kpi')->middleware(['web','auth'])->name('hrmanager.performance_kpi.')->group(function(){
    Route::get('/', [HrPerformanceKpiController::class, 'index'])->name('index');
    Route::post('/assign-kpis', [HrPerformanceKpiController::class, 'assignKpis'])->name('assign_kpis');
    Route::post('/appraisals', [HrPerformanceKpiController::class, 'storeAppraisal'])->name('appraisals.store');
});

Route::prefix('hr/performance-kpi')->middleware(['web','auth'])->name('hr.performance_kpi.')->group(function(){
    Route::get('/', [HrPerformanceKpiController::class, 'index'])->name('dashboard');
    Route::post('/assign-kpis', [HrPerformanceKpiController::class, 'assignKpis'])->name('assign_kpis');
    Route::post('/appraisals', [HrPerformanceKpiController::class, 'storeAppraisal'])->name('appraisals.store');
});

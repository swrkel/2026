<?php
use Illuminate\Support\Facades\Route;
use Modules\Tailoring\Reports\Http\Controllers\TailoringReportsCentreController;
use Modules\Tailoring\Reports\Http\Controllers\TailoringExecutiveDashboardController;
use Modules\Tailoring\Reports\Http\Controllers\TailoringConfigurationCentreController;
use Modules\Tailoring\Reports\Http\Controllers\TailoringPermissionAuditController;
use Modules\Tailoring\Reports\Http\Controllers\TailoringReleaseReadinessController;
Route::middleware(['web','auth'])->prefix('tailoring')->group(function(){
Route::get('reports-centre',[TailoringReportsCentreController::class,'index'])->name('tailoring.reports_centre.index');
Route::get('executive-bi-dashboard',[TailoringExecutiveDashboardController::class,'index'])->name('tailoring.executive_bi_dashboard.index');
Route::get('configuration-centre-v2',[TailoringConfigurationCentreController::class,'index'])->name('tailoring.configuration_centre_v2.index');
Route::get('permission-audit',[TailoringPermissionAuditController::class,'index'])->name('tailoring.permission_audit.index');
Route::get('release-readiness',[TailoringReleaseReadinessController::class,'index'])->name('tailoring.release_readiness.index');
});

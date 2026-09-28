<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\AiClinical\MyHealthAiClinicalAlertController;
use Modules\MyHealthMembers\Http\Controllers\AiClinical\MyHealthAiClinicalDashboardController;
use Modules\MyHealthMembers\Http\Controllers\AiClinical\MyHealthAiClinicalReportController;
use Modules\MyHealthMembers\Http\Controllers\AiClinical\MyHealthAiClinicalTimelineController;

Route::prefix('myhealth/ai-clinical')->as('myhealth.ai_clinical.')->group(function () {
    Route::get('/', [MyHealthAiClinicalDashboardController::class, 'index'])->name('dashboard');
    Route::get('/alerts', [MyHealthAiClinicalAlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/{alert}/acknowledge', [MyHealthAiClinicalAlertController::class, 'acknowledge'])->name('alerts.acknowledge');
    Route::get('/timeline', [MyHealthAiClinicalTimelineController::class, 'index'])->name('timeline.index');
    Route::get('/reports', [MyHealthAiClinicalReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/medication-safety', [MyHealthAiClinicalReportController::class, 'medicationSafety'])->name('reports.medication_safety');
    Route::get('/reports/preventive-care', [MyHealthAiClinicalReportController::class, 'preventiveCare'])->name('reports.preventive_care');
});

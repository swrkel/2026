<?php
use Illuminate\Support\Facades\Route;
use Modules\BankingAML\Http\Controllers\AmlDashboardController;
use Modules\BankingAML\Http\Controllers\KycReviewController;
use Modules\BankingAML\Http\Controllers\ScreeningController;
use Modules\BankingAML\Http\Controllers\AmlCaseController;
use Modules\BankingAML\Http\Controllers\ComplianceAlertController;
use Modules\BankingAML\Http\Controllers\RegulatoryReportController;
use Modules\BankingAML\Http\Controllers\ComplianceAuditController;
use Modules\BankingAML\Http\Controllers\AmlSettingController;

Route::prefix('banking/aml')->name('bankingaml.')->group(function () {
    Route::get('/', [AmlDashboardController::class, 'index'])->name('dashboard');
    Route::get('/kyc-reviews', [KycReviewController::class, 'index'])->name('kyc_reviews');
    Route::get('/screening', [ScreeningController::class, 'index'])->name('screening');
    Route::get('/cases', [AmlCaseController::class, 'index'])->name('cases');
    Route::get('/alerts', [ComplianceAlertController::class, 'index'])->name('alerts');
    Route::get('/regulatory-reports', [RegulatoryReportController::class, 'index'])->name('regulatory_reports');
    Route::get('/audit', [ComplianceAuditController::class, 'index'])->name('audit');
    Route::get('/settings', [AmlSettingController::class, 'index'])->name('settings');
});

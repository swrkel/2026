<?php
use Illuminate\Support\Facades\Route;
use Modules\BankingMicrofinance\Http\Controllers\Compliance\KycProfileController;
use Modules\BankingMicrofinance\Http\Controllers\Compliance\KycDocumentController;
use Modules\BankingMicrofinance\Http\Controllers\Compliance\ComplianceAlertController;
use Modules\BankingMicrofinance\Http\Controllers\Risk\RiskAssessmentController;
use Modules\BankingMicrofinance\Http\Controllers\Risk\CollateralController;
use Modules\BankingMicrofinance\Http\Controllers\Recovery\NplCaseController;
use Modules\BankingMicrofinance\Http\Controllers\Recovery\NplActionController;
use Modules\BankingMicrofinance\Http\Controllers\Settings\ApprovalMatrixController;
use Modules\BankingMicrofinance\Http\Controllers\Reports\ComplianceReportController;
use Modules\BankingMicrofinance\Http\Controllers\Reports\RiskReportController;
use Modules\BankingMicrofinance\Http\Controllers\Reports\NplReportController;
Route::middleware(['web','auth'])->prefix('banking/microfinance')->name('bkg.mfi.')->group(function(){
    Route::resource('kyc-profiles', KycProfileController::class)->only(['index','create','store']);
    Route::resource('kyc-documents', KycDocumentController::class)->only(['index','create','store']);
    Route::resource('compliance-alerts', ComplianceAlertController::class)->only(['index','create','store']);
    Route::resource('risk-assessments', RiskAssessmentController::class)->only(['index','create','store']);
    Route::resource('collaterals', CollateralController::class)->only(['index','create','store']);
    Route::resource('npl-cases', NplCaseController::class)->only(['index','create','store']);
    Route::resource('npl-actions', NplActionController::class)->only(['index','create','store']);
    Route::resource('approval-matrix', ApprovalMatrixController::class)->only(['index','create','store']);
    Route::get('reports/compliance', [ComplianceReportController::class,'index'])->name('reports.compliance');
    Route::get('reports/risk', [RiskReportController::class,'index'])->name('reports.risk');
    Route::get('reports/npl', [NplReportController::class,'index'])->name('reports.npl');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingMicrofinance\Http\Controllers\Accounting\RepaymentAllocationController;
use Modules\BankingMicrofinance\Http\Controllers\Accounting\WriteOffController;
use Modules\BankingMicrofinance\Http\Controllers\Accounting\ProvisioningController;
use Modules\BankingMicrofinance\Http\Controllers\Accounting\OfficerTargetController;
use Modules\BankingMicrofinance\Http\Controllers\Reports\PortfolioQualityReportController;
use Modules\BankingMicrofinance\Http\Controllers\Reports\MicrofinanceAuditReportController;

Route::middleware(['web','auth'])->prefix('banking/microfinance')->name('banking.microfinance.')->group(function () {
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('repayment-allocation', [RepaymentAllocationController::class, 'index'])->name('repayment-allocation.index');
        Route::post('repayment-allocation/preview', [RepaymentAllocationController::class, 'preview'])->name('repayment-allocation.preview');
        Route::post('repayment-allocation/apply', [RepaymentAllocationController::class, 'apply'])->name('repayment-allocation.apply');
        Route::resource('write-offs', WriteOffController::class)->except(['show']);
        Route::post('write-offs/{writeOff}/approve', [WriteOffController::class, 'approve'])->name('write-offs.approve');
        Route::post('write-offs/{writeOff}/recoveries', [WriteOffController::class, 'recovery'])->name('write-offs.recoveries.store');
        Route::resource('provisioning', ProvisioningController::class)->except(['show']);
        Route::post('provisioning/generate', [ProvisioningController::class, 'generate'])->name('provisioning.generate');
        Route::resource('officer-targets', OfficerTargetController::class)->except(['show']);
    });
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('portfolio-quality', [PortfolioQualityReportController::class, 'index'])->name('portfolio-quality.index');
        Route::get('audit-exceptions', [MicrofinanceAuditReportController::class, 'index'])->name('audit-exceptions.index');
    });
});

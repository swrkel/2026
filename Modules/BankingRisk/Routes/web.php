<?php
use Illuminate\Support\Facades\Route;
use Modules\BankingRisk\Http\Controllers\RiskDashboardController;
use Modules\BankingRisk\Http\Controllers\CreditRiskController;
use Modules\BankingRisk\Http\Controllers\LiquidityRiskController;
use Modules\BankingRisk\Http\Controllers\OperationalRiskController;
use Modules\BankingRisk\Http\Controllers\MarketRiskController;
use Modules\BankingRisk\Http\Controllers\EarlyWarningController;
use Modules\BankingRisk\Http\Controllers\StressTestController;
use Modules\BankingRisk\Http\Controllers\RiskReportController;
use Modules\BankingRisk\Http\Controllers\RiskSettingController;

Route::prefix('banking/risk')->name('bankingrisk.')->group(function () {
    Route::get('/', [RiskDashboardController::class, 'index'])->name('dashboard');
    Route::get('/credit-risk', [CreditRiskController::class, 'index'])->name('credit_risk');
    Route::get('/liquidity-risk', [LiquidityRiskController::class, 'index'])->name('liquidity_risk');
    Route::get('/operational-risk', [OperationalRiskController::class, 'index'])->name('operational_risk');
    Route::get('/market-risk', [MarketRiskController::class, 'index'])->name('market_risk');
    Route::get('/early-warning', [EarlyWarningController::class, 'index'])->name('early_warning');
    Route::get('/stress-tests', [StressTestController::class, 'index'])->name('stress_tests');
    Route::get('/reports', [RiskReportController::class, 'index'])->name('reports');
    Route::get('/settings', [RiskSettingController::class, 'index'])->name('settings');
});

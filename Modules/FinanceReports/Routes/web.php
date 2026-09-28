<?php

use Illuminate\Support\Facades\Route;
use Modules\FinanceReports\Http\Controllers\FinanceReportsController;

Route::get('/', [FinanceReportsController::class, 'dashboard'])->name('dashboard');
Route::get('/executive-bi-dashboard-new', [FinanceReportsController::class, 'executiveBiDashboard'])->name('executive-bi-dashboard-new');
Route::get('/report-engine-status', [FinanceReportsController::class, 'reportEngineStatus'])->name('report-engine-status');
Route::get('/trial-balance-new', [FinanceReportsController::class, 'trialBalance'])->name('trial-balance-new');
Route::get('/balance-sheet-new', [FinanceReportsController::class, 'balanceSheet'])->name('balance-sheet-new');
Route::get('/profit-loss-new', [FinanceReportsController::class, 'profitLoss'])->name('profit-loss-new');
Route::get('/income-statement-new', [FinanceReportsController::class, 'incomeStatement'])->name('income-statement-new');
Route::get('/account-ledger-new', [FinanceReportsController::class, 'accountLedger'])->name('account-ledger-new');


/*
|--------------------------------------------------------------------------
| Backward-compatible Finance Reports route names
|--------------------------------------------------------------------------
|
| Older menu/tab registries used route names without the "-new" suffix.
| Keep these aliases mapped to their correct controller actions so no menu
| item can fall back to a different report page.
|
*/
Route::get('/trial-balance', [FinanceReportsController::class, 'trialBalance'])->name('trial-balance');
Route::get('/balance-sheet', [FinanceReportsController::class, 'balanceSheet'])->name('balance-sheet');
Route::get('/profit-loss', [FinanceReportsController::class, 'profitLoss'])->name('profit-loss');
Route::get('/income-statement', [FinanceReportsController::class, 'incomeStatement'])->name('income-statement');
Route::get('/account-ledger', [FinanceReportsController::class, 'accountLedger'])->name('account-ledger');
Route::get('/general-ledger', [FinanceReportsController::class, 'generalLedger'])->name('general-ledger');
Route::get('/cash-book', [FinanceReportsController::class, 'cashBook'])->name('cash-book');
Route::get('/bank-book', [FinanceReportsController::class, 'bankBook'])->name('bank-book');
Route::get('/financial-intelligence', [FinanceReportsController::class, 'financialIntelligence'])->name('financial-intelligence');
Route::get('/executive-dashboard', [FinanceReportsController::class, 'executiveBiDashboard'])->name('executive-dashboard');
Route::get('/cfo-dashboard', [FinanceReportsController::class, 'cfoDashboard'])->name('cfo-dashboard');

Route::get('/general-ledger-new', [FinanceReportsController::class, 'generalLedger'])->name('general-ledger-new');
Route::get('/cash-book-new', [FinanceReportsController::class, 'cashBook'])->name('cash-book-new');
Route::get('/bank-book-new', [FinanceReportsController::class, 'bankBook'])->name('bank-book-new');
Route::get('/journal-register-new', [FinanceReportsController::class, 'journalRegister'])->name('journal-register-new');
Route::get('/day-book-new', [FinanceReportsController::class, 'dayBook'])->name('day-book-new');


Route::get('/financial-dashboard-new', [FinanceReportsController::class, 'financialDashboard'])->name('financial-dashboard-new');
Route::get('/budget-vs-actual-new', [FinanceReportsController::class, 'budgetVsActual'])->name('budget-vs-actual-new');
Route::get('/revenue-analysis-new', [FinanceReportsController::class, 'revenueAnalysis'])->name('revenue-analysis-new');
Route::get('/expense-analysis-new', [FinanceReportsController::class, 'expenseAnalysis'])->name('expense-analysis-new');
Route::get('/branch-performance-new', [FinanceReportsController::class, 'branchPerformance'])->name('branch-performance-new');
Route::get('/financial-ratios-new', [FinanceReportsController::class, 'financialRatios'])->name('financial-ratios-new');
Route::get('/comparative-report-new', [FinanceReportsController::class, 'comparativeReport'])->name('comparative-report-new');


Route::get('/customer-outstanding-new', [FinanceReportsController::class, 'customerOutstanding'])->name('customer-outstanding-new');
Route::get('/supplier-outstanding-new', [FinanceReportsController::class, 'supplierOutstanding'])->name('supplier-outstanding-new');
Route::get('/customer-aging-new', [FinanceReportsController::class, 'customerAging'])->name('customer-aging-new');
Route::get('/supplier-aging-new', [FinanceReportsController::class, 'supplierAging'])->name('supplier-aging-new');
Route::get('/collection-analysis-new', [FinanceReportsController::class, 'collectionAnalysis'])->name('collection-analysis-new');
Route::get('/payment-analysis-new', [FinanceReportsController::class, 'paymentAnalysis'])->name('payment-analysis-new');
Route::get('/receivable-summary-new', [FinanceReportsController::class, 'receivableSummary'])->name('receivable-summary-new');
Route::get('/payable-summary-new', [FinanceReportsController::class, 'payableSummary'])->name('payable-summary-new');
Route::get('/customer-statement-new', [FinanceReportsController::class, 'customerStatement'])->name('customer-statement-new');
Route::get('/supplier-statement-new', [FinanceReportsController::class, 'supplierStatement'])->name('supplier-statement-new');


// FR005 - Cash, Banking & Treasury Reports
Route::get('/cash-flow-statement-new', [FinanceReportsController::class, 'cashFlowStatement'])->name('cash-flow-statement-new');
Route::get('/cash-position-report-new', [FinanceReportsController::class, 'cashPositionReport'])->name('cash-position-report-new');
Route::get('/bank-position-report-new', [FinanceReportsController::class, 'bankPositionReport'])->name('bank-position-report-new');
Route::get('/bank-reconciliation-new', [FinanceReportsController::class, 'bankReconciliation'])->name('bank-reconciliation-new');
Route::get('/cheque-register-new', [FinanceReportsController::class, 'chequeRegister'])->name('cheque-register-new');
Route::get('/post-dated-cheque-register-new', [FinanceReportsController::class, 'postDatedChequeRegister'])->name('post-dated-cheque-register-new');
Route::get('/cash-movement-analysis-new', [FinanceReportsController::class, 'cashMovementAnalysis'])->name('cash-movement-analysis-new');


// FR006 - Audit, Compliance & Financial Controls Reports
Route::get('/audit-trail-new', [FinanceReportsController::class, 'auditTrail'])->name('audit-trail-new');
Route::get('/transaction-history-new', [FinanceReportsController::class, 'transactionHistory'])->name('transaction-history-new');
Route::get('/user-financial-activity-new', [FinanceReportsController::class, 'userFinancialActivity'])->name('user-financial-activity-new');
Route::get('/deleted-transactions-new', [FinanceReportsController::class, 'deletedTransactions'])->name('deleted-transactions-new');
Route::get('/edited-transactions-new', [FinanceReportsController::class, 'editedTransactions'])->name('edited-transactions-new');
Route::get('/voucher-approval-history-new', [FinanceReportsController::class, 'voucherApprovalHistory'])->name('voucher-approval-history-new');
Route::get('/exception-report-new', [FinanceReportsController::class, 'exceptionReport'])->name('exception-report-new');
Route::get('/financial-log-viewer-new', [FinanceReportsController::class, 'financialLogViewer'])->name('financial-log-viewer-new');


// FR007 - Fixed Assets & Capital Assets Reports
Route::get('/fixed-asset-dashboard-new', [FinanceReportsController::class, 'fixedAssetDashboard'])->name('fixed-asset-dashboard-new');
Route::get('/fixed-asset-register-new', [FinanceReportsController::class, 'fixedAssetRegister'])->name('fixed-asset-register-new');
Route::get('/depreciation-register-new', [FinanceReportsController::class, 'depreciationRegister'])->name('depreciation-register-new');
Route::get('/asset-movement-register-new', [FinanceReportsController::class, 'assetMovementRegister'])->name('asset-movement-register-new');
Route::get('/asset-transfer-report-new', [FinanceReportsController::class, 'assetTransferReport'])->name('asset-transfer-report-new');
Route::get('/asset-disposal-register-new', [FinanceReportsController::class, 'assetDisposalRegister'])->name('asset-disposal-register-new');
Route::get('/asset-category-summary-new', [FinanceReportsController::class, 'assetCategorySummary'])->name('asset-category-summary-new');
Route::get('/asset-valuation-report-new', [FinanceReportsController::class, 'assetValuationReport'])->name('asset-valuation-report-new');

// Enterprise RC-1 - Forecasting, Consolidation & Performance Framework
Route::get('/enterprise-center', [FinanceReportsController::class, 'enterpriseCenter'])->name('enterprise-center');
Route::get('/cash-flow-forecast-new', [FinanceReportsController::class, 'cashFlowForecast'])->name('cash-flow-forecast-new');
Route::get('/revenue-forecast-new', [FinanceReportsController::class, 'revenueForecast'])->name('revenue-forecast-new');
Route::get('/expense-forecast-new', [FinanceReportsController::class, 'expenseForecast'])->name('expense-forecast-new');
Route::get('/profit-forecast-new', [FinanceReportsController::class, 'profitForecast'])->name('profit-forecast-new');
Route::get('/consolidation-center-new', [FinanceReportsController::class, 'consolidationCenter'])->name('consolidation-center-new');
Route::get('/performance-center-new', [FinanceReportsController::class, 'performanceCenter'])->name('performance-center-new');

// Enterprise RC-2 - Final production readiness centers
Route::get('/standalone-audit-new', [FinanceReportsController::class, 'standaloneAudit'])->name('standalone-audit-new');
Route::get('/export-center-new', [FinanceReportsController::class, 'exportCenter'])->name('export-center-new');
Route::get('/print-layout-center-new', [FinanceReportsController::class, 'printLayoutCenter'])->name('print-layout-center-new');
Route::get('/drilldown-center-new', [FinanceReportsController::class, 'drilldownCenter'])->name('drilldown-center-new');

// Enterprise v1.0 Final - scheduler, packs, KPI and production readiness
Route::get('/financial-pack-new', [FinanceReportsController::class, 'financialPack'])->name('financial-pack-new');
Route::get('/report-scheduler-new', [FinanceReportsController::class, 'reportScheduler'])->name('report-scheduler-new');
Route::get('/executive-kpi-center-new', [FinanceReportsController::class, 'executiveKpiCenter'])->name('executive-kpi-center-new');
Route::get('/calculation-verification-new', [FinanceReportsController::class, 'calculationVerification'])->name('calculation-verification-new');
Route::get('/production-readiness-new', [FinanceReportsController::class, 'productionReadiness'])->name('production-readiness-new');

// Enterprise v2.0 - Financial Intelligence Platform
Route::get('/financial-intelligence-new', [FinanceReportsController::class, 'financialIntelligence'])->name('financial-intelligence-new');
Route::get('/cfo-dashboard-new', [FinanceReportsController::class, 'cfoDashboard'])->name('cfo-dashboard-new');
Route::get('/financial-health-score-new', [FinanceReportsController::class, 'financialHealthScore'])->name('financial-health-score-new');
Route::get('/scenario-analysis-new', [FinanceReportsController::class, 'scenarioAnalysis'])->name('scenario-analysis-new');
Route::get('/board-pack-new', [FinanceReportsController::class, 'boardPack'])->name('board-pack-new');


// Enterprise v3.0 - Enterprise Integration Platform
Route::get('/enterprise-data-hub-new', [FinanceReportsController::class, 'enterpriseDataHub'])->name('enterprise-data-hub-new');
Route::get('/cross-module-financial-intelligence-new', [FinanceReportsController::class, 'crossModuleFinancialIntelligence'])->name('cross-module-financial-intelligence-new');
Route::get('/enterprise-dashboard-builder-new', [FinanceReportsController::class, 'enterpriseDashboardBuilder'])->name('enterprise-dashboard-builder-new');
Route::get('/enterprise-report-builder-new', [FinanceReportsController::class, 'enterpriseReportBuilder'])->name('enterprise-report-builder-new');
Route::get('/financial-workspace-new', [FinanceReportsController::class, 'financialWorkspace'])->name('financial-workspace-new');
Route::get('/enterprise-report-scheduler-new', [FinanceReportsController::class, 'enterpriseReportScheduler'])->name('enterprise-report-scheduler-new');
Route::get('/enterprise-platform-audit-new', [FinanceReportsController::class, 'enterprisePlatformAudit'])->name('enterprise-platform-audit-new');

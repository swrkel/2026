<?php

use Illuminate\Support\Facades\Route;
use Modules\ExpensesNew\Http\Controllers\Analytics\AnalyticsController as SnapshotAnalyticsController;
use Modules\ExpensesNew\Http\Controllers\Analytics\ExpenseIntelligenceController;
use Modules\ExpensesNew\Http\Controllers\AnalyticsController;
use Modules\ExpensesNew\Http\Controllers\ApprovalController;
use Modules\ExpensesNew\Http\Controllers\Budget\BudgetApprovalController;
use Modules\ExpensesNew\Http\Controllers\Budget\BudgetController as BudgetCentreController;
use Modules\ExpensesNew\Http\Controllers\Budget\BudgetForecastController;
use Modules\ExpensesNew\Http\Controllers\Budget\BudgetVarianceController;
use Modules\ExpensesNew\Http\Controllers\BudgetControlController;
use Modules\ExpensesNew\Http\Controllers\BudgetController;
use Modules\ExpensesNew\Http\Controllers\CategoryController;
use Modules\ExpensesNew\Http\Controllers\CommandCenter\ExpenseApprovalWorkbenchController;
use Modules\ExpensesNew\Http\Controllers\CommandCenter\ExpenseCommandCenterController;
use Modules\ExpensesNew\Http\Controllers\CommandCenter\ExpenseOperationsBoardController;
use Modules\ExpensesNew\Http\Controllers\CostCenterController;
use Modules\ExpensesNew\Http\Controllers\Costing\ActivityBasedCostingController;
use Modules\ExpensesNew\Http\Controllers\Costing\AllocationRuleController;
use Modules\ExpensesNew\Http\Controllers\Costing\AllocationRunController;
use Modules\ExpensesNew\Http\Controllers\Costing\CostEngineController;
use Modules\ExpensesNew\Http\Controllers\Costing\KpiController as CostingKpiController;
use Modules\ExpensesNew\Http\Controllers\Costing\OverheadRecoveryController;
use Modules\ExpensesNew\Http\Controllers\Costing\ProfitabilityController;
use Modules\ExpensesNew\Http\Controllers\Costing\SharedExpenseController;
use Modules\ExpensesNew\Http\Controllers\DashboardController;
use Modules\ExpensesNew\Http\Controllers\DepartmentController;
use Modules\ExpensesNew\Http\Controllers\ExpenseAccountController;
use Modules\ExpensesNew\Http\Controllers\ExpenseController;
use Modules\ExpensesNew\Http\Controllers\ExpenseReportController;
use Modules\ExpensesNew\Http\Controllers\IntegrationController;
use Modules\ExpensesNew\Http\Controllers\Intelligence\AuditCentreController;
use Modules\ExpensesNew\Http\Controllers\Intelligence\ClosingCentreController;
use Modules\ExpensesNew\Http\Controllers\Intelligence\FinancialIntelligenceController;
use Modules\ExpensesNew\Http\Controllers\Intelligence\KpiDashboardController;
use Modules\ExpensesNew\Http\Controllers\NotificationController;
use Modules\ExpensesNew\Http\Controllers\PayeeController;
use Modules\ExpensesNew\Http\Controllers\PolicyController;
use Modules\ExpensesNew\Http\Controllers\ProjectController;
use Modules\ExpensesNew\Http\Controllers\RecurringExpenseController;
use Modules\ExpensesNew\Http\Controllers\ReportController;
use Modules\ExpensesNew\Http\Controllers\Reports\ExpenseReportCenterController;
use Modules\ExpensesNew\Http\Controllers\Reports\ExpenseReportRunController;
use Modules\ExpensesNew\Http\Controllers\SettingsController;
use Modules\ExpensesNew\Http\Controllers\TaxController;
use Modules\ExpensesNew\Http\Controllers\VendorAnalyticsController;

/*
|--------------------------------------------------------------------------
| Expenses New - canonical module routes
|--------------------------------------------------------------------------
|
| The /expenses-new prefix and the ERP web/auth/tenant middleware are
| applied once by Providers/RouteServiceProvider.php.
|
*/

// Dashboard and compatibility landing URLs.
Route::get('/', [DashboardController::class, 'index'])->name('expensesnew.dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('expensesnew.dashboard.index');

// Expenses.
Route::get('/expenses/data', [ExpenseController::class, 'data'])->name('expensesnew.expenses.data');
Route::delete('/expenses/attachments/{id}', [ExpenseController::class, 'destroyAttachment'])
    ->whereNumber('id')->name('expensesnew.expenses.attachments.destroy');
Route::get('/expenses/{id}/print', [ExpenseController::class, 'print'])
    ->whereNumber('id')->name('expensesnew.expenses.print');
Route::get('/expenses/{id}/view', [ExpenseController::class, 'show'])
    ->whereNumber('id')->name('expensesnew.expenses.show');
Route::get('/expenses', [ExpenseController::class, 'index'])->name('expensesnew.expenses.index');
Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expensesnew.expenses.create');
Route::post('/expenses', [ExpenseController::class, 'store'])->name('expensesnew.expenses.store');
Route::get('/expenses/{id}/edit', [ExpenseController::class, 'edit'])
    ->whereNumber('id')->name('expensesnew.expenses.edit');
Route::put('/expenses/{id}', [ExpenseController::class, 'update'])
    ->whereNumber('id')->name('expensesnew.expenses.update');
Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy'])
    ->whereNumber('id')->name('expensesnew.expenses.destroy');

// Categories.
/*
 | MA-002 - inline quick-add expense category, for OTHER modules.
 |
 | 26 screens across Fleet, Property, SettlementSW, AutoRepairServices,
 | EVCharging and the four Petro modules currently call
 |     action('ExpenseCategoryController@create', ['quick_add' => true])
 | which resolves to CORE's controller. These three routes let them stop doing
 | that without any change to their own markup or JS: the GET returns bare
 | modal markup and the POST returns the same JSON keys core returns.
 |
 | NOTE: QuickCategoryController reads and writes `expense_categories` - the
 | table those 26 screens actually read - NOT `expnew_categories`. See the long
 | note in that controller; it is deliberate and it does not pre-empt the
 | decision about which table owns expense categories.
 */
Route::get('/quick-category/create', [\Modules\ExpensesNew\Http\Controllers\QuickCategoryController::class, 'create'])->name('expensesnew.quick_category.create');
Route::post('/quick-category', [\Modules\ExpensesNew\Http\Controllers\QuickCategoryController::class, 'store'])->name('expensesnew.quick_category.store');
Route::get('/quick-category/options', [\Modules\ExpensesNew\Http\Controllers\QuickCategoryController::class, 'options'])->name('expensesnew.quick_category.options');

Route::get('/categories/options', [CategoryController::class, 'options'])->name('expensesnew.categories.options');
Route::get('/categories/data', [CategoryController::class, 'data'])->name('expensesnew.categories.data');
// MA-002 (S-621 #2): which fields a category needs on the Add Expenses form,
// plus the options for each - one request instead of one per dropdown.
Route::get('/categories/{id}/requirements', [CategoryController::class, 'requirements'])
    ->whereNumber('id')->name('expensesnew.categories.requirements');
// MA-002 (S-620 #8): enable / disable a category, the safe alternative to
// deleting one that is already used by expenses.
Route::post('/categories/{id}/toggle-active', [CategoryController::class, 'toggleActive'])->name('expensesnew.categories.toggle');
Route::get('/categories', [CategoryController::class, 'index'])->name('expensesnew.categories.index');
Route::get('/categories/create', [CategoryController::class, 'create'])->name('expensesnew.categories.create');
Route::post('/categories', [CategoryController::class, 'store'])->name('expensesnew.categories.store');
Route::get('/categories/{id}/view', [CategoryController::class, 'show'])
    ->whereNumber('id')->name('expensesnew.categories.show');
Route::get('/categories/{id}/edit', [CategoryController::class, 'edit'])
    ->whereNumber('id')->name('expensesnew.categories.edit');
Route::put('/categories/{id}', [CategoryController::class, 'update'])
    ->whereNumber('id')->name('expensesnew.categories.update');
Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])
    ->whereNumber('id')->name('expensesnew.categories.destroy');

// Payees.
Route::get('/payees/data', [PayeeController::class, 'data'])->name('expensesnew.payees.data');
Route::post('/payees/sync-cheque-payees', [PayeeController::class, 'syncChequePayees'])
    ->name('expensesnew.payees.sync_cheque');
Route::get('/payees', [PayeeController::class, 'index'])->name('expensesnew.payees.index');
Route::get('/payees/create', [PayeeController::class, 'create'])->name('expensesnew.payees.create');
Route::post('/payees', [PayeeController::class, 'store'])->name('expensesnew.payees.store');
Route::get('/payees/{id}/edit', [PayeeController::class, 'edit'])
    ->whereNumber('id')->name('expensesnew.payees.edit');
Route::put('/payees/{id}', [PayeeController::class, 'update'])
    ->whereNumber('id')->name('expensesnew.payees.update');
Route::delete('/payees/{id}', [PayeeController::class, 'destroy'])
    ->whereNumber('id')->name('expensesnew.payees.destroy');

// Expense accounts.
Route::get('/accounts/data', [ExpenseAccountController::class, 'data'])->name('expensesnew.accounts.data');
Route::post('/accounts/sync', [ExpenseAccountController::class, 'sync'])->name('expensesnew.accounts.sync');
Route::get('/accounts', [ExpenseAccountController::class, 'index'])->name('expensesnew.accounts.index');
Route::get('/accounts/create', [ExpenseAccountController::class, 'create'])->name('expensesnew.accounts.create');
Route::post('/accounts', [ExpenseAccountController::class, 'store'])->name('expensesnew.accounts.store');
Route::get('/accounts/{id}/edit', [ExpenseAccountController::class, 'edit'])
    ->whereNumber('id')->name('expensesnew.accounts.edit');
Route::put('/accounts/{id}', [ExpenseAccountController::class, 'update'])
    ->whereNumber('id')->name('expensesnew.accounts.update');
Route::delete('/accounts/{id}', [ExpenseAccountController::class, 'destroy'])
    ->whereNumber('id')->name('expensesnew.accounts.destroy');

// Settings and category codes.
Route::get('/settings/category-codes/data', [SettingsController::class, 'categoryCodeData'])
    ->name('expensesnew.settings.category_codes.data');
Route::post('/settings/category-codes', [SettingsController::class, 'storeCategoryCode'])
    ->name('expensesnew.settings.category_codes.store');
Route::delete('/settings/category-codes/{id}', [SettingsController::class, 'destroyCategoryCode'])
    ->whereNumber('id')->name('expensesnew.settings.category_codes.destroy');
// IS1991 (#1): Prefix List edit and delete. Declared before the bare /settings
// routes for the same reason the category-code routes are - a more specific
// path must not be swallowed by a looser one registered earlier.
Route::put('/settings/prefixes/{id}', [SettingsController::class, 'updatePrefix'])
    ->whereNumber('id')->name('expensesnew.settings.prefixes.update');
Route::delete('/settings/prefixes/{id}', [SettingsController::class, 'destroyPrefix'])
    ->whereNumber('id')->name('expensesnew.settings.prefixes.destroy');
Route::get('/settings', [SettingsController::class, 'index'])->name('expensesnew.settings.index');
Route::post('/settings', [SettingsController::class, 'save'])->name('expensesnew.settings.save');

// Core reports. Fixed routes must appear before the {code} routes.
Route::get('/reports/expense-summary/data', [ReportController::class, 'expenseSummaryData'])
    ->name('expensesnew.reports.expense_summary.data');
Route::get('/reports/expense-summary', [ReportController::class, 'expenseSummary'])
    ->name('expensesnew.reports.expense_summary');
Route::get('/reports', [ExpenseReportController::class, 'index'])->name('expensesnew.reports.index');
Route::get('/reports/{code}/data', [ExpenseReportController::class, 'data'])
    ->name('expensesnew.reports.data');
Route::get('/reports/{code}/export/{format}', [ExpenseReportController::class, 'export'])
    ->name('expensesnew.reports.export');
Route::get('/reports/{code}', [ExpenseReportController::class, 'show'])
    ->name('expensesnew.reports.show');

// Approval workflow.
Route::get('/approvals', [ApprovalController::class, 'index'])->name('expensesnew.approvals.index');
Route::post('/approvals/{id}/approve', [ApprovalController::class, 'approve'])
    ->whereNumber('id')->name('expensesnew.approvals.approve');
Route::post('/approvals/{id}/reject', [ApprovalController::class, 'reject'])
    ->whereNumber('id')->name('expensesnew.approvals.reject');

// Business masters.
Route::get('/departments', [DepartmentController::class, 'index'])->name('expensesnew.departments.index');
Route::post('/departments', [DepartmentController::class, 'store'])->name('expensesnew.departments.store');
Route::delete('/departments/{id}', [DepartmentController::class, 'destroy'])
    ->whereNumber('id')->name('expensesnew.departments.destroy');
Route::get('/cost-centers', [CostCenterController::class, 'index'])->name('expensesnew.cost-centers.index');
Route::post('/cost-centers', [CostCenterController::class, 'store'])->name('expensesnew.cost-centers.store');
Route::delete('/cost-centers/{id}', [CostCenterController::class, 'destroy'])
    ->whereNumber('id')->name('expensesnew.cost-centers.destroy');
Route::get('/projects', [ProjectController::class, 'index'])->name('expensesnew.projects.index');
Route::post('/projects', [ProjectController::class, 'store'])->name('expensesnew.projects.store');
Route::delete('/projects/{id}', [ProjectController::class, 'destroy'])
    ->whereNumber('id')->name('expensesnew.projects.destroy');

// Integrations and notification templates.
Route::get('/integration', [IntegrationController::class, 'index'])->name('expensesnew.integration.index');
Route::get('/notifications', [NotificationController::class, 'index'])->name('expensesnew.notifications.index');

// Budget, policy, tax, recurring and analysis pages (EXPNEW_005).
Route::get('/budgets', [BudgetController::class, 'index'])->name('expenses-new.budgets.index');
Route::get('/budgets/create', [BudgetController::class, 'create'])->name('expenses-new.budgets.create');
Route::post('/budgets', [BudgetController::class, 'store'])->name('expenses-new.budgets.store');
Route::get('/budgets/{id}/edit', [BudgetController::class, 'edit'])
    ->whereNumber('id')->name('expenses-new.budgets.edit');
Route::put('/budgets/{id}', [BudgetController::class, 'update'])
    ->whereNumber('id')->name('expenses-new.budgets.update');

Route::get('/policies', [PolicyController::class, 'index'])->name('expenses-new.policies.index');
Route::get('/policies/create', [PolicyController::class, 'create'])->name('expenses-new.policies.create');
Route::post('/policies', [PolicyController::class, 'store'])->name('expenses-new.policies.store');
Route::get('/policies/{id}/edit', [PolicyController::class, 'edit'])
    ->whereNumber('id')->name('expenses-new.policies.edit');
Route::put('/policies/{id}', [PolicyController::class, 'update'])
    ->whereNumber('id')->name('expenses-new.policies.update');

Route::get('/taxes', [TaxController::class, 'index'])->name('expenses-new.taxes.index');
Route::get('/taxes/create', [TaxController::class, 'create'])->name('expenses-new.taxes.create');
Route::post('/taxes', [TaxController::class, 'store'])->name('expenses-new.taxes.store');
Route::get('/taxes/{id}/edit', [TaxController::class, 'edit'])
    ->whereNumber('id')->name('expenses-new.taxes.edit');
Route::put('/taxes/{id}', [TaxController::class, 'update'])
    ->whereNumber('id')->name('expenses-new.taxes.update');

Route::get('/recurring', [RecurringExpenseController::class, 'index'])->name('expenses-new.recurring.index');
Route::get('/recurring/create', [RecurringExpenseController::class, 'create'])->name('expenses-new.recurring.create');
Route::post('/recurring', [RecurringExpenseController::class, 'store'])->name('expenses-new.recurring.store');
Route::get('/recurring/{id}/edit', [RecurringExpenseController::class, 'edit'])
    ->whereNumber('id')->name('expenses-new.recurring.edit');
Route::put('/recurring/{id}', [RecurringExpenseController::class, 'update'])
    ->whereNumber('id')->name('expenses-new.recurring.update');

Route::get('/budget-control', [BudgetControlController::class, 'index'])->name('expenses-new.budget-control.index');
Route::get('/budget-control/create', [BudgetControlController::class, 'create'])->name('expenses-new.budget-control.create');
Route::post('/budget-control', [BudgetControlController::class, 'store'])->name('expenses-new.budget-control.store');
Route::get('/budget-control/{id}/edit', [BudgetControlController::class, 'edit'])
    ->whereNumber('id')->name('expenses-new.budget-control.edit');
Route::put('/budget-control/{id}', [BudgetControlController::class, 'update'])
    ->whereNumber('id')->name('expenses-new.budget-control.update');

Route::get('/analytics', [AnalyticsController::class, 'index'])->name('expenses-new.analytics.index');
Route::get('/analytics/create', [AnalyticsController::class, 'create'])->name('expenses-new.analytics.create');
Route::post('/analytics', [AnalyticsController::class, 'store'])->name('expenses-new.analytics.store');
Route::get('/analytics/{id}/edit', [AnalyticsController::class, 'edit'])
    ->whereNumber('id')->name('expenses-new.analytics.edit');
Route::put('/analytics/{id}', [AnalyticsController::class, 'update'])
    ->whereNumber('id')->name('expenses-new.analytics.update');
Route::get('/analytics/data', [SnapshotAnalyticsController::class, 'data'])->name('expenses-new.analytics.data');
Route::get('/analytics/overview', [SnapshotAnalyticsController::class, 'index'])->name('expenses-new.analytics');

Route::get('/vendor-analytics', [VendorAnalyticsController::class, 'index'])->name('expenses-new.vendor-analytics.index');
Route::get('/vendor-analytics/create', [VendorAnalyticsController::class, 'create'])->name('expenses-new.vendor-analytics.create');
Route::post('/vendor-analytics', [VendorAnalyticsController::class, 'store'])->name('expenses-new.vendor-analytics.store');
Route::get('/vendor-analytics/{id}/edit', [VendorAnalyticsController::class, 'edit'])
    ->whereNumber('id')->name('expenses-new.vendor-analytics.edit');
Route::put('/vendor-analytics/{id}', [VendorAnalyticsController::class, 'update'])
    ->whereNumber('id')->name('expenses-new.vendor-analytics.update');

// Command centre, intelligence and reporting centre (EXPNEW_006+).
Route::get('/command-center', [ExpenseCommandCenterController::class, 'index'])
    ->name('expenses-new.command-center');
Route::get('/command-center/data', [ExpenseCommandCenterController::class, 'widgets'])
    ->name('expenses-new.command-center.data');
Route::get('/command-center/widgets', [ExpenseCommandCenterController::class, 'widgets'])
    ->name('expensesnew.command-center.widgets');
Route::get('/approval-workbench', [ExpenseApprovalWorkbenchController::class, 'index'])
    ->name('expensesnew.approval-workbench.index');
Route::post('/approval-workbench/action', [ExpenseApprovalWorkbenchController::class, 'action'])
    ->name('expensesnew.approval-workbench.action');
Route::get('/operations-board', [ExpenseOperationsBoardController::class, 'index'])
    ->name('expensesnew.operations-board.index');
Route::get('/operations-board/feed', [ExpenseOperationsBoardController::class, 'feed'])
    ->name('expensesnew.operations-board.feed');

Route::get('/intelligence', [ExpenseIntelligenceController::class, 'index'])->name('expenses-new.intelligence');
Route::get('/intelligence/data', [ExpenseIntelligenceController::class, 'data'])->name('expenses-new.intelligence.data');
Route::get('/financial-intelligence', [FinancialIntelligenceController::class, 'index'])
    ->name('expensesnew.intelligence.financial');
Route::get('/audit-centre', [AuditCentreController::class, 'index'])->name('expensesnew.intelligence.audit');
Route::get('/closing-centre', [ClosingCentreController::class, 'index'])->name('expensesnew.intelligence.closing');
Route::get('/kpi-dashboard', [KpiDashboardController::class, 'index'])->name('expensesnew.intelligence.kpi');

Route::get('/reporting-centre', [ExpenseReportCenterController::class, 'index'])->name('expenses-new.reports');
Route::get('/reporting-centre/data', [ExpenseReportCenterController::class, 'data'])->name('expenses-new.reports.data');
Route::get('/reporting-centre/run/{code?}', [ExpenseReportRunController::class, 'index'])
    ->name('expenses-new.reports.run');
Route::get('/reporting-centre/run/{code?}/data', [ExpenseReportRunController::class, 'data'])
    ->name('expenses-new.reports.run.data');

// Budget centre snapshots.
Route::get('/budget-centre', [BudgetCentreController::class, 'index'])->name('expensesnew.budget-centre.index');
Route::get('/budget-centre/approvals', [BudgetApprovalController::class, 'index'])
    ->name('expensesnew.budget-centre.approvals');
Route::get('/budget-centre/forecast', [BudgetForecastController::class, 'index'])
    ->name('expensesnew.budget-centre.forecast');
Route::get('/budget-centre/variance', [BudgetVarianceController::class, 'index'])
    ->name('expensesnew.budget-centre.variance');

// Costing pages.
Route::get('/costing/engine', [CostEngineController::class, 'index'])->name('expensesnew.costing.engine.index');
Route::get('/costing/engine/create', [CostEngineController::class, 'create'])->name('expensesnew.costing.engine.create');
Route::post('/costing/engine', [CostEngineController::class, 'store'])->name('expensesnew.costing.engine.store');
Route::get('/costing/allocation-rules', [AllocationRuleController::class, 'index'])->name('expensesnew.costing.rules.index');
Route::get('/costing/allocation-rules/create', [AllocationRuleController::class, 'create'])->name('expensesnew.costing.rules.create');
Route::post('/costing/allocation-rules', [AllocationRuleController::class, 'store'])->name('expensesnew.costing.rules.store');
Route::get('/costing/allocation-runs', [AllocationRunController::class, 'index'])->name('expensesnew.costing.runs.index');
Route::get('/costing/allocation-runs/create', [AllocationRunController::class, 'create'])->name('expensesnew.costing.runs.create');
Route::post('/costing/allocation-runs', [AllocationRunController::class, 'store'])->name('expensesnew.costing.runs.store');
Route::get('/costing/activity-based', [ActivityBasedCostingController::class, 'index'])->name('expensesnew.costing.abc.index');
Route::get('/costing/activity-based/create', [ActivityBasedCostingController::class, 'create'])->name('expensesnew.costing.abc.create');
Route::post('/costing/activity-based', [ActivityBasedCostingController::class, 'store'])->name('expensesnew.costing.abc.store');
Route::get('/costing/shared-expenses', [SharedExpenseController::class, 'index'])->name('expensesnew.costing.shared.index');
Route::get('/costing/shared-expenses/create', [SharedExpenseController::class, 'create'])->name('expensesnew.costing.shared.create');
Route::post('/costing/shared-expenses', [SharedExpenseController::class, 'store'])->name('expensesnew.costing.shared.store');
Route::get('/costing/overhead-recovery', [OverheadRecoveryController::class, 'index'])->name('expensesnew.costing.overhead.index');
Route::get('/costing/overhead-recovery/create', [OverheadRecoveryController::class, 'create'])->name('expensesnew.costing.overhead.create');
Route::post('/costing/overhead-recovery', [OverheadRecoveryController::class, 'store'])->name('expensesnew.costing.overhead.store');
Route::get('/costing/profitability', [ProfitabilityController::class, 'index'])->name('expensesnew.costing.profitability.index');
Route::get('/costing/profitability/create', [ProfitabilityController::class, 'create'])->name('expensesnew.costing.profitability.create');
Route::post('/costing/profitability', [ProfitabilityController::class, 'store'])->name('expensesnew.costing.profitability.store');
Route::get('/costing/kpis', [CostingKpiController::class, 'index'])->name('expensesnew.costing.kpi.index');
Route::get('/costing/kpis/create', [CostingKpiController::class, 'create'])->name('expensesnew.costing.kpi.create');
Route::post('/costing/kpis', [CostingKpiController::class, 'store'])->name('expensesnew.costing.kpi.store');

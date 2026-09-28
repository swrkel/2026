<?php

use Illuminate\Support\Facades\Route;
use Modules\LeadsNew\Http\Controllers\Admin\LeadsNewAdministrationController;
use Modules\LeadsNew\Http\Controllers\Calendar\LeadsNewCalendarController as LeadsNewCalendarCentreController;
use Modules\LeadsNew\Http\Controllers\LeadsNewActivityController;
use Modules\LeadsNew\Http\Controllers\LeadsNewAdvancedSearchController;
use Modules\LeadsNew\Http\Controllers\LeadsNewAnalyticsController;
use Modules\LeadsNew\Http\Controllers\LeadsNewBulkActionController;
use Modules\LeadsNew\Http\Controllers\LeadsNewCalendarController;
use Modules\LeadsNew\Http\Controllers\LeadsNewCampaignController;
use Modules\LeadsNew\Http\Controllers\LeadsNewConversionController;
use Modules\LeadsNew\Http\Controllers\LeadsNewCustomer360Controller;
use Modules\LeadsNew\Http\Controllers\LeadsNewCustomerJourneyController;
use Modules\LeadsNew\Http\Controllers\LeadsNewDashboardController;
use Modules\LeadsNew\Http\Controllers\LeadsNewDocumentController;
use Modules\LeadsNew\Http\Controllers\LeadsNewExecutiveDashboardController;
use Modules\LeadsNew\Http\Controllers\LeadsNewFollowupController;
use Modules\LeadsNew\Http\Controllers\LeadsNewHealthController;
use Modules\LeadsNew\Http\Controllers\LeadsNewImportExportController;
use Modules\LeadsNew\Http\Controllers\LeadsNewLeadController;
use Modules\LeadsNew\Http\Controllers\LeadsNewNotificationCenterController;
use Modules\LeadsNew\Http\Controllers\LeadsNewOpportunityController;
use Modules\LeadsNew\Http\Controllers\LeadsNewQuoteController;
use Modules\LeadsNew\Http\Controllers\LeadsNewReleaseController;
use Modules\LeadsNew\Http\Controllers\LeadsNewTemplateController;
use Modules\LeadsNew\Http\Controllers\LeadsNewTerritoryController;
use Modules\LeadsNew\Http\Controllers\LeadsNewUiController;
use Modules\LeadsNew\Http\Controllers\LeadsNewWorkflowController;
use Modules\LeadsNew\Http\Controllers\LeadsNewWorkflowEngineController;
use Modules\LeadsNew\Http\Controllers\Opportunity\LeadsNewOpportunityCentreController;
use Modules\LeadsNew\Http\Controllers\Reports\LeadsNewReportController;
use Modules\LeadsNew\Http\Controllers\Settings\LeadsNewSettingsController;

/*
|--------------------------------------------------------------------------
| Leads-New Web Routes
|--------------------------------------------------------------------------
|
| This file is loaded by Modules\LeadsNew\Providers\RouteServiceProvider.
| The provider applies the ERP tenant middleware stack, so these routes run
| against the active tenant database and not the central database.
|
*/

// Defensive namespace registration for servers where the module loader caches
// providers aggressively. The service provider also registers these; this is
// harmless and prevents "No hint path defined for [leadsnew]" during cache drift.
if (function_exists('module_path')) {
    app('view')->addNamespace('leadsnew', module_path('LeadsNew', 'Resources/views'));
    app('view')->addNamespace('leads_new', module_path('LeadsNew', 'Resources/views'));
    app('translator')->addNamespace('leadsnew', module_path('LeadsNew', 'Resources/lang'));
    app('translator')->addNamespace('leads_new', module_path('LeadsNew', 'Resources/lang'));
}

Route::prefix('leads-new')->as('leads-new.')->group(function () {
    Route::get('/route-ok', [\Modules\LeadsNew\Http\Controllers\RouteClosures\WebRouteController::class, 'handle1'])->name('route-ok');

    Route::get('/health-check', [LeadsNewHealthController::class, 'index'])->name('health-check');
    Route::get('/', [LeadsNewDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [LeadsNewDashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/executive-dashboard', [LeadsNewExecutiveDashboardController::class, 'index'])->name('executive-dashboard');
    Route::get('/executive-dashboard/data', [LeadsNewExecutiveDashboardController::class, 'data'])->name('executive-dashboard.data');

    // Leads CRUD is declared explicitly (instead of Route::resource) because some
    // tenant route caches in this ERP did not keep resource route names reliably.
    Route::get('leads', [LeadsNewLeadController::class, 'index'])->name('leads.index');
    Route::get('leads/create', [LeadsNewLeadController::class, 'create'])->name('leads.create');
    Route::post('leads', [LeadsNewLeadController::class, 'store'])->name('leads.store');
    Route::get('leads/{lead}', [LeadsNewLeadController::class, 'show'])->name('leads.show');
    Route::get('leads/{lead}/edit', [LeadsNewLeadController::class, 'edit'])->name('leads.edit');
    Route::put('leads/{lead}', [LeadsNewLeadController::class, 'update'])->name('leads.update');
    Route::patch('leads/{lead}', [LeadsNewLeadController::class, 'update'])->name('leads.patch');
    Route::delete('leads/{lead}', [LeadsNewLeadController::class, 'destroy'])->name('leads.destroy');
    Route::post('leads/{lead}/duplicate', [LeadsNewLeadController::class, 'duplicate'])->name('leads.duplicate');
    Route::post('leads/{lead}/archive', [LeadsNewLeadController::class, 'archive'])->name('leads.archive');
    Route::post('leads/{lead}/restore', [LeadsNewLeadController::class, 'restore'])->name('leads.restore');
    Route::post('leads/{lead}/convert', [LeadsNewConversionController::class, 'store'])->name('leads.convert');

    Route::resource('opportunities', LeadsNewOpportunityController::class)->only(['index', 'store', 'update']);
    Route::get('opportunity-centre', [LeadsNewOpportunityCentreController::class, 'index'])->name('opportunity-centre.index');

    Route::resource('activities', LeadsNewActivityController::class)->only(['index', 'store']);
    Route::resource('documents', LeadsNewDocumentController::class)->only(['index', 'store', 'destroy']);
    Route::resource('campaigns', LeadsNewCampaignController::class)->only(['index', 'store', 'update']);
    Route::resource('territories', LeadsNewTerritoryController::class)->only(['index', 'store', 'update']);
    Route::resource('templates', LeadsNewTemplateController::class)->only(['index', 'store']);
    Route::resource('quotes', LeadsNewQuoteController::class)->only(['index', 'store']);

    Route::get('followups', [LeadsNewFollowupController::class, 'index'])->name('followups.index');
    Route::resource('workflows', LeadsNewWorkflowController::class)->only(['index', 'store', 'update']);
    Route::get('workflow', [LeadsNewWorkflowController::class, 'index'])->name('workflow.index');
    Route::get('workflow/kanban', [LeadsNewWorkflowEngineController::class, 'kanban'])->name('workflow.kanban');
    Route::post('workflow/move', [LeadsNewWorkflowEngineController::class, 'move'])->name('workflow.move');

    Route::get('calendar', [LeadsNewCalendarController::class, 'index'])->name('calendar.index');
    Route::get('calendar/feed', [LeadsNewCalendarController::class, 'feed'])->name('calendar.feed');
    Route::get('calendar-centre', [LeadsNewCalendarCentreController::class, 'index'])->name('calendar-centre.index');

    Route::get('customer-360/{leadId}', [LeadsNewCustomer360Controller::class, 'show'])->name('customer360.show');
    Route::get('customer-journey/{id}', [LeadsNewCustomerJourneyController::class, 'show'])->name('customer-journey.show');

    Route::get('search', [LeadsNewAdvancedSearchController::class, 'index'])->name('search.index');
    Route::post('search', [LeadsNewAdvancedSearchController::class, 'results'])->name('search.results');
    Route::get('advanced-search', [LeadsNewAdvancedSearchController::class, 'index'])->name('advanced-search.index');
    Route::get('advanced-search/results', [LeadsNewAdvancedSearchController::class, 'results'])->name('advanced-search.results');

    Route::get('import', [LeadsNewImportExportController::class, 'import'])->name('import');
    Route::get('import-export', [LeadsNewImportExportController::class, 'import'])->name('import-export.index');
    Route::get('export/csv', [LeadsNewImportExportController::class, 'exportCsv'])->name('export.csv');

    Route::post('bulk/status', [LeadsNewBulkActionController::class, 'updateStatus'])->name('bulk.status');
    Route::post('bulk/assign', [LeadsNewBulkActionController::class, 'assign'])->name('bulk.assign');
    Route::post('bulk/action', [LeadsNewBulkActionController::class, 'handle'])->name('bulk.action');

    Route::get('notifications', [LeadsNewNotificationCenterController::class, 'index'])->name('notifications.index');
    Route::get('administration-centre', [LeadsNewAdministrationController::class, 'index'])->name('admin.index');
    Route::get('api-docs', [\Modules\LeadsNew\Http\Controllers\RouteClosures\WebRouteController::class, 'handle2'])->name('api.docs');
    Route::get('ui-standards', [LeadsNewUiController::class, 'index'])->name('ui.standards');
    Route::get('release/checklist', [LeadsNewReleaseController::class, 'checklist'])->name('release.checklist');

    Route::get('reports', [LeadsNewReportController::class, 'index'])->name('reports.index');
    Route::get('reports/lead-register', [LeadsNewReportController::class, 'leadRegister'])->name('reports.lead-register');
    Route::get('reports/conversion', [LeadsNewReportController::class, 'conversion'])->name('reports.conversion');
    Route::get('reports/pipeline', [LeadsNewReportController::class, 'pipeline'])->name('reports.pipeline');
    Route::get('reports/followups', [LeadsNewReportController::class, 'followups'])->name('reports.followups');
    Route::get('reports/executive', [LeadsNewAnalyticsController::class, 'executive'])->name('reports.executive');
    Route::get('reports/source', [LeadsNewAnalyticsController::class, 'source'])->name('reports.source');
    Route::get('reports/ageing', [\Modules\LeadsNew\Http\Controllers\RouteClosures\WebRouteController::class, 'handle3'])->name('reports.ageing');
    Route::get('reports/team-performance', [\Modules\LeadsNew\Http\Controllers\RouteClosures\WebRouteController::class, 'handle4'])->name('reports.team_performance');

    Route::get('settings', [LeadsNewSettingsController::class, 'index'])->name('settings.index');
    Route::post('settings/numbering', [LeadsNewSettingsController::class, 'saveNumbering'])->name('settings.numbering.save');
    Route::post('settings/defaults', [LeadsNewSettingsController::class, 'saveDefaults'])->name('settings.defaults.save');

    // Compatibility aliases for earlier sidebar links and route names.
    Route::get('add-leads', [LeadsNewLeadController::class, 'create'])->name('add-leads');
    Route::get('list-leads', [LeadsNewLeadController::class, 'index'])->name('list-leads');
});

// Older sidebar code used the leadsnew.* route name prefix. Keep these aliases
// temporarily so cached or published sidebar views cannot break the ERP.
Route::prefix('leads-new')->as('leadsnew.')->group(function () {
    Route::get('route-ok', [\Modules\LeadsNew\Http\Controllers\RouteClosures\WebRouteController::class, 'handle5'])->name('route-ok');
    Route::get('health-check', [LeadsNewHealthController::class, 'index'])->name('health-check');
    Route::get('add-leads', [LeadsNewLeadController::class, 'create'])->name('add-leads');
    Route::get('/', [LeadsNewDashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard', [LeadsNewDashboardController::class, 'index'])->name('dashboard.index');

    // Compatibility aliases for older cached menu/views that used leadsnew.* names.
    Route::get('leads', [LeadsNewLeadController::class, 'index'])->name('leads.index');
    Route::get('leads/create', [LeadsNewLeadController::class, 'create'])->name('leads.create');
    Route::post('leads', [LeadsNewLeadController::class, 'store'])->name('leads.store');
    Route::get('leads/{lead}', [LeadsNewLeadController::class, 'show'])->name('leads.show');
    Route::get('leads/{lead}/edit', [LeadsNewLeadController::class, 'edit'])->name('leads.edit');
    Route::put('leads/{lead}', [LeadsNewLeadController::class, 'update'])->name('leads.update');
    Route::delete('leads/{lead}', [LeadsNewLeadController::class, 'destroy'])->name('leads.destroy');

    Route::get('calendar', [LeadsNewCalendarController::class, 'index'])->name('calendar.index');
    Route::get('reports', [LeadsNewReportController::class, 'index'])->name('reports.index');
    Route::get('settings', [LeadsNewSettingsController::class, 'index'])->name('settings.index');
    Route::get('import', [LeadsNewImportExportController::class, 'import'])->name('import');
});

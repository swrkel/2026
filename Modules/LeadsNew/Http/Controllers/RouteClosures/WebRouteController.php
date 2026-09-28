<?php

namespace Modules\LeadsNew\Http\Controllers\RouteClosures;

use App\Http\Controllers\Controller;
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

/**
 * MA-002 - route closures moved out of Modules/LeadsNew/Routes/web.php.
 *
 * WHY: Laravel cannot run `php artisan route:cache` while ANY route is defined
 * with a closure. This installation has 8,613 routes across 349 files, and
 * without the cache every one is parsed and compiled on EVERY request,
 * including the login page. That is the multi-second delay.
 *
 * Only 35 closures across 14 files were blocking it.
 *
 * The method bodies are BYTE-IDENTICAL to the closures they replace. Nothing
 * was rewritten - the code simply lives in a class so the route can be cached.
 */
class WebRouteController
{
    public function handle1()
    {
        return 'LEADS_NEW_TENANT_ROUTE_OK';
    }

    public function handle2()
    { return view('leadsnew::api_docs.index'); }

    public function handle3()
    { return view('leadsnew::reports.ageing'); }

    public function handle4()
    { return view('leadsnew::reports.team_performance'); }

    public function handle5()
    { return 'LEADS_NEW_TENANT_ROUTE_OK'; }
}

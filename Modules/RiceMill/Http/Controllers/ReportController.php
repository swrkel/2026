<?php

namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\RiceMill\Reports\DispatchReport;
use Modules\RiceMill\Reports\FinishedStockReport;
use Modules\RiceMill\Reports\PaddyPurchaseReport;
use Modules\RiceMill\Reports\PaddyReceivingReport;
use Modules\RiceMill\Reports\PaddyStockReport;
use Modules\RiceMill\Reports\ProductionReport;
use Modules\RiceMill\Reports\ProfitabilityReport;
use Modules\RiceMill\Services\ExternalMasterDataService;
use Modules\RiceMill\Services\LocationAccessService;
use Modules\RiceMill\Services\TenantContext;

class ReportController extends BaseController
{
    public function __construct(
        TenantContext $context,
        private ExternalMasterDataService $masters,
        private LocationAccessService $locationAccess
    ) {
        parent::__construct($context);
    }

    /**
     * One Rice Mill Reports workspace. Only the active report is queried so the
     * page stays light as data grows. Every report request runs after the module
     * tenant/business context guard and optional Location / Store filters are
     * validated against that same operational boundary.
     */
    public function index(Request $request): View
    {
        $tabs = $this->tabs();
        $activeTab = (string) $request->input('tab', 'purchases');

        if (! array_key_exists($activeTab, $tabs)) {
            $activeTab = 'purchases';
        }

        $businessId = $this->bid();
        $selection = $this->validateFilterSelection($request, $businessId);
        [$reportKind, $reportData] = $this->reportPayload($activeTab, $request, $businessId);

        if ($request->boolean('partial')) {
            // Partial tab requests render only report data. Do not reload the
            // complete Location/Store masters on every tab click.
            return view(
                $reportKind === 'profitability'
                    ? 'RiceMill::reports.partials.profitability'
                    : 'RiceMill::reports.partials.table',
                array_merge($reportData, $selection, ['activeTab' => $activeTab])
            );
        }

        $filterData = array_merge($selection, [
            'reportLocations' => $this->locationAccess->businessOptions($businessId),
            'reportStores' => $this->masters->businessStores($businessId),
        ]);
        $reportData = array_merge($reportData, $filterData);

        return view('RiceMill::reports.index', [
            'tabs' => $tabs,
            'activeTab' => $activeTab,
            'reportKind' => $reportKind,
            'reportData' => $reportData,
            'reportLocations' => $filterData['reportLocations'],
            'reportStores' => $filterData['reportStores'],
            'selectedLocationId' => $filterData['selectedLocationId'],
            'selectedStoreId' => $filterData['selectedStoreId'],
        ]);
    }

    public function purchases(Request $request): RedirectResponse
    {
        return $this->toTab($request, 'purchases');
    }

    public function receipts(Request $request): RedirectResponse
    {
        return $this->toTab($request, 'receipts');
    }

    public function paddyStock(Request $request): RedirectResponse
    {
        return $this->toTab($request, 'paddy-stock');
    }

    public function production(Request $request): RedirectResponse
    {
        return $this->toTab($request, 'production');
    }

    public function finishedStock(Request $request): RedirectResponse
    {
        return $this->toTab($request, 'finished-stock');
    }

    public function dispatch(Request $request): RedirectResponse
    {
        return $this->toTab($request, 'dispatch');
    }

    public function profitability(Request $request): RedirectResponse
    {
        return $this->toTab($request, 'profitability');
    }

    private function tabs(): array
    {
        return [
            'purchases' => 'Purchase Order Report',
            'receipts' => 'Paddy Receiving Report',
            'paddy-stock' => 'Paddy Stock Report',
            'production' => 'Production / Milling Report',
            'finished-stock' => 'Finished Rice Stock Report',
            'dispatch' => 'Sales / Dispatch Report',
            'profitability' => 'Rice Mill Profitability',
        ];
    }

    private function reportPayload(string $tab, Request $request, int $businessId): array
    {
        $reportMap = [
            'purchases' => [PaddyPurchaseReport::class, 'table'],
            'receipts' => [PaddyReceivingReport::class, 'table'],
            'paddy-stock' => [PaddyStockReport::class, 'table'],
            'production' => [ProductionReport::class, 'table'],
            'finished-stock' => [FinishedStockReport::class, 'table'],
            'dispatch' => [DispatchReport::class, 'table'],
            'profitability' => [ProfitabilityReport::class, 'profitability'],
        ];

        [$class, $kind] = $reportMap[$tab];
        $report = app($class);

        return [$kind, $report->data($businessId, $request)];
    }

    private function validateFilterSelection(Request $request, int $businessId): array
    {
        // Reports are administrative / management views. The explicit "All"
        // selection means consolidated data for the active Tenant UID + Business UID.
        $selectedLocationId = $this->reportFilterId($request, 'location_id');
        $selectedStoreId = $this->reportFilterId($request, 'store_id');

        if ($selectedLocationId) {
            $this->locationAccess->assertBusinessLocation($selectedLocationId, $businessId, true);
        }
        if ($selectedStoreId) {
            $this->masters->assertBusinessStore($selectedStoreId, $businessId, $selectedLocationId);
        }

        return [
            'selectedLocationId' => $selectedLocationId ?: 'all',
            'selectedStoreId' => $selectedStoreId ?: 'all',
        ];
    }

    /**
     * Report filter convention:
     *   all / blank / missing => consolidated active Tenant + Business
     *   positive integer      => one Location / Store
     */
    private function reportFilterId(Request $request, string $key): ?int
    {
        $value = trim((string) $request->input($key, 'all'));

        if ($value === '' || strtolower($value) === 'all') {
            return null;
        }

        abort_unless(ctype_digit($value) && (int) $value > 0, 422, 'Invalid report filter.');

        return (int) $value;
    }

    private function toTab(Request $request, string $tab): RedirectResponse
    {
        $query = $request->query();
        unset($query['partial']);
        $query['tab'] = $tab;

        return redirect()->route('rice-mill.reports.index', $query);
    }
}

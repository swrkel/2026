<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Illuminate\Http\Request;
use Modules\PetroPDNew\Services\PdnewBusinessFeatureService;
use Modules\PetroPDNew\Services\PdnewMasterDataService;
use Modules\PetroPDNew\Services\Reports\PdnewReportRegistry;
use Modules\PetroPDNew\Services\Reports\PdnewReportService;

class ReportController extends PdnewController
{
    public function index(
        Request $request,
        PdnewReportRegistry $registry,
        PdnewReportService $service,
        PdnewMasterDataService $masterData,
        PdnewBusinessFeatureService $features,
        ?string $report = null
    ) {
        $businessId = $this->context->businessId();
        $reports = $features->visibleReports($registry->all(), $businessId);
        abort_if($reports === [], 403, 'All Petro PD-New report tabs are disabled for this business.');

        $key = $report ?: (string) $request->input('report', array_key_first($reports));
        abort_unless(isset($reports[$key]), 403, 'This Petro PD-New report tab is disabled for this business.');
        $features->authorizeReport($key, $businessId);

        $definition = $registry->get($key);
        abort_unless($request->user()->can($definition->permission()), 403);

        $filters = $this->filters($request);
        $data = $service->paginate(
            $key,
            $businessId,
            $filters,
            (int) $request->input('per_page', 50)
        );

        return view('petropdnew::reports.index', array_merge($data, [
            'reportKey' => $key,
            'reports' => $reports,
            'filters' => $filters,
            'locations' => $masterData->locations($businessId),
        ]));
    }

    public function print(
        Request $request,
        string $report,
        PdnewReportRegistry $registry,
        PdnewReportService $service,
        PdnewBusinessFeatureService $features
    ) {
        $businessId = $this->context->businessId();
        $features->authorizeReport($report, $businessId);
        $definition = $registry->get($report);
        abort_unless($request->user()->can($definition->permission()), 403);

        return view('petropdnew::reports.print', $service->printable(
            $report,
            $businessId,
            $this->filters($request)
        ));
    }

    public function export(
        Request $request,
        string $report,
        PdnewReportRegistry $registry,
        PdnewReportService $service,
        PdnewBusinessFeatureService $features,
        ?string $format = null
    ) {
        $businessId = $this->context->businessId();
        $features->authorizeReport($report, $businessId);
        $definition = $registry->get($report);
        abort_unless($request->user()->can($definition->permission()), 403);

        return $service->export(
            $format ?: 'csv',
            $report,
            $businessId,
            $this->filters($request)
        );
    }

    private function filters(Request $request): array
    {
        $filters = $request->only(['date_from', 'date_to', 'location_id', 'status', 'only_variance']);
        $requestedLocationId = (int) ($filters['location_id'] ?? 0);
        $activeLocationId = $this->context->locationId();

        if ($requestedLocationId > 0) {
            $this->context->authorizeLocation($requestedLocationId);
            $filters['location_id'] = $requestedLocationId;
        } elseif ($activeLocationId) {
            $filters['location_id'] = $activeLocationId;
        } else {
            unset($filters['location_id']);
        }

        return $filters;
    }
}

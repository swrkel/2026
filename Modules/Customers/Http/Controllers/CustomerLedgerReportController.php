<?php

namespace Modules\Customers\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Customers\Exports\CustomerLedgerExport;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerReportService;

class CustomerLedgerReportController extends CustomerReportBaseController
{
    protected $reportService;

    public function __construct(CustomerReportService $reportService, CustomerPermissionService $permissionService)
    {
        parent::__construct($permissionService);
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $this->authorizeReport();

        // IS1815: generic start_date/end_date query parameters are also read by
        // a core report partial included by the global layout. On this page that
        // caused the unrelated profit/loss partial to call format_date() and
        // throw HTTP 500. Convert old bookmarked URLs once, then use Customers-
        // owned parameter names only.
        if ($request->hasAny(['start_date', 'end_date'])) {
            $query = $request->except(['start_date', 'end_date']);
            if ($request->filled('start_date')) {
                $query['ledger_start_date'] = $request->input('start_date');
            }
            if ($request->filled('end_date')) {
                $query['ledger_end_date'] = $request->input('end_date');
            }

            return redirect()->route('customers.reports.ledger', $query);
        }

        $businessId = $this->businessId();
        $customerId = $request->filled('customer_id') ? (int) $request->input('customer_id') : null;
        $dateFilter = $this->resolveDateFilter($request);

        $data = $this->reportService->ledgerData(
            $businessId,
            $customerId,
            $dateFilter['start_date'],
            $dateFilter['end_date']
        );

        $data['dateFilter'] = $dateFilter;
        $data['selectedCustomerId'] = $customerId;

        return view('customers::reports.customer-ledger', $data);
    }

    public function export(Request $request, CustomerLedgerExport $export)
    {
        $this->authorizeExport();

        $customerId = $request->filled('customer_id') ? (int) $request->input('customer_id') : null;
        $dateFilter = $this->resolveDateFilter($request);
        $data = $this->reportService->ledgerData(
            $this->businessId(),
            $customerId,
            $dateFilter['start_date'],
            $dateFilter['end_date']
        );

        return $export->downloadLedger('customer-ledger-' . date('Y-m-d') . '.csv', $data['rows'] ?? []);
    }

    private function resolveDateFilter(Request $request): array
    {
        $preset = strtolower(trim((string) $request->query('date_preset', 'current_month')));
        $today = Carbon::today();
        $start = $today->copy()->startOfMonth();
        $end = $today->copy()->endOfMonth();

        switch ($preset) {
            case 'today':
                $start = $today->copy();
                $end = $today->copy();
                break;

            case 'yesterday':
                $start = $today->copy()->subDay();
                $end = $today->copy()->subDay();
                break;

            case 'last_7_days':
                $start = $today->copy()->subDays(6)->startOfDay();
                $end = $today->copy()->endOfDay();
                break;

            case 'last_30_days':
                $start = $today->copy()->subDays(29)->startOfDay();
                $end = $today->copy()->endOfDay();
                break;

            case 'last_month':
                $start = $today->copy()->subMonthNoOverflow()->startOfMonth();
                $end = $today->copy()->subMonthNoOverflow()->endOfMonth();
                break;

            case 'this_year':
                $start = $today->copy()->startOfYear();
                $end = $today->copy()->endOfYear();
                break;

            case 'last_year':
                $start = $today->copy()->subYear()->startOfYear();
                $end = $today->copy()->subYear()->endOfYear();
                break;

            case 'this_fy':
            case 'last_fy':
                $fyStartMonth = max(1, min(12, (int) $request->session()->get('business.fy_start_month', 1)));
                $fyStart = Carbon::create($today->year, $fyStartMonth, 1)->startOfDay();
                if ($today->lt($fyStart)) {
                    $fyStart->subYear();
                }
                if ($preset === 'last_fy') {
                    $fyStart->subYear();
                }
                $start = $fyStart->copy();
                $end = $fyStart->copy()->addYear()->subDay()->endOfDay();
                break;

            case 'custom':
                try {
                    $start = Carbon::parse((string) $request->query('ledger_start_date'))->startOfDay();
                    $end = Carbon::parse((string) $request->query('ledger_end_date'))->endOfDay();
                    if ($end->lt($start)) {
                        [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                    }
                } catch (\Throwable $exception) {
                    $preset = 'current_month';
                    $start = $today->copy()->startOfMonth();
                    $end = $today->copy()->endOfMonth();
                }
                break;

            case 'current_month':
            default:
                $preset = 'current_month';
                $start = $today->copy()->startOfMonth();
                $end = $today->copy()->endOfMonth();
                break;
        }

        return [
            'preset' => $preset,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ];
    }
}

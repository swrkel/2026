<?php

namespace Modules\Customers\Http\Controllers;

use Carbon\Carbon;
use Modules\Customers\Services\CustomerLedgerService;

class CustomerLedgerController extends CustomerActionBaseController
{
    public function ledger(CustomerLedgerService $ledgerService, $id)
    {
        $this->permissionService->authorize('view');
        $customer = $this->getCustomer($id);

        // IS1815: remove legacy generic date parameters before rendering the
        // global application layout. A core report partial also reads those
        // names and caused an unrelated format_date() HTTP 500 error.
        if (request()->hasAny(['start_date', 'end_date'])) {
            $query = request()->except(['start_date', 'end_date']);
            if (request()->filled('start_date')) {
                $query['ledger_start_date'] = request()->input('start_date');
            }
            if (request()->filled('end_date')) {
                $query['ledger_end_date'] = request()->input('end_date');
            }

            return redirect()->route('customers.ledger', array_merge(['id' => $customer->id], $query));
        }

        $businessId = (int) request()->session()->get('user.business_id');
        $dateFilter = $this->resolveDateFilter();

        $rows = $ledgerService->ledgerRows(
            $businessId,
            (int) $customer->id,
            5000,
            false,
            $customer,
            $dateFilter['start_date'],
            $dateFilter['end_date']
        );

        // Attach the invoices selected in Customers / Bulk Payment to the
        // aggregate ledger payment row in one query. The view renders a robust
        // inline Bills detail area without adding per-row database calls.
        $rows = $ledgerService->withBulkPaymentBillDetails($rows, $businessId);
        $summary = $ledgerService->reportLedgerSummary($rows);

        return view('customers::ledger.index', compact('customer', 'rows', 'summary', 'dateFilter'));
    }

    public function balance(CustomerLedgerService $ledgerService, $id)
    {
        $this->permissionService->authorize('view');
        $customer = $this->getCustomer($id);
        $businessId = (int) request()->session()->get('user.business_id');
        $rows = $ledgerService->ledgerRows($businessId, (int) $customer->id, 500, false, $customer);
        return view('customers::balance.index', compact('customer', 'rows'));
    }

    private function resolveDateFilter(): array
    {
        $preset = strtolower(trim((string) request()->query('date_preset', 'current_month')));
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
                $fyStartMonth = max(1, min(12, (int) request()->session()->get('business.fy_start_month', 1)));
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
                    $start = Carbon::parse((string) request()->query('ledger_start_date'))->startOfDay();
                    $end = Carbon::parse((string) request()->query('ledger_end_date'))->endOfDay();
                    if ($end->lt($start)) {
                        [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                    }
                } catch (\Throwable $e) {
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

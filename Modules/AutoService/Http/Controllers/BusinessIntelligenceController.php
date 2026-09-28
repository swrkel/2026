<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BusinessIntelligenceController extends AutoServiceBaseController
{
    public function index(Request $request)
    {
        $businessId = $this->businessId();
        $locationId = $this->locationId();
        $from = $request->get('from') ?: date('Y-m-01');
        $to = $request->get('to') ?: date('Y-m-d');
        $groupBy = $request->get('group_by') ?: 'service_type';
        $search = trim((string) $request->get('search'));

        $invoiceBase = DB::table('auto_service_invoices as i')
            ->leftJoin('auto_service_jobs as j', 'j.id', '=', 'i.job_id')
            ->leftJoin('auto_service_vehicles as v', 'v.id', '=', 'j.vehicle_id')
            ->leftJoin('contacts as c', 'c.id', '=', 'i.contact_id')
            ->when($businessId, fn($q) => $q->where('i.business_id', $businessId))
            ->when($locationId, fn($q) => $q->where(function ($qq) use ($locationId) {
                $qq->whereNull('i.location_id')->orWhere('i.location_id', $locationId);
            }))
            ->whereBetween('i.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('i.invoice_no', 'like', "%{$search}%")
                       ->orWhere('j.job_no', 'like', "%{$search}%")
                       ->orWhere('v.registration_no', 'like', "%{$search}%")
                       ->orWhere('c.name', 'like', "%{$search}%")
                       ->orWhere('c.mobile', 'like', "%{$search}%");
                });
            });

        $summary = [
            'invoices' => (clone $invoiceBase)->count(),
            'gross_revenue' => (float) (clone $invoiceBase)->sum('i.total_amount'),
            'discounts' => (float) (clone $invoiceBase)->sum('i.discount_amount'),
            'tax' => (float) (clone $invoiceBase)->sum('i.tax_amount'),
            'paid' => (float) (clone $invoiceBase)->sum('i.paid_amount'),
            'outstanding' => (float) (clone $invoiceBase)->sum(DB::raw('COALESCE(i.total_amount,0) - COALESCE(i.paid_amount,0)')),
        ];

        $parts = $this->partsProfit($from, $to, $search);
        $labour = $this->labourProfit($from, $to, $search);
        $technicians = $this->technicianRevenue($from, $to);
        $customers = $this->customerRevenue($from, $to, $search);
        $vehicles = $this->vehicleRevenue($from, $to, $search);
        $monthlyTrend = $this->monthlyTrend($from, $to);
        $warrantyCost = $this->warrantyCost($from, $to);
        $retention = $this->customerRetention($from, $to);
        $jobProfitability = $this->jobProfitability($from, $to, $search);

        return view('autoservice::business_intelligence.index', compact(
            'from', 'to', 'groupBy', 'search', 'summary', 'parts', 'labour', 'technicians', 'customers', 'vehicles', 'monthlyTrend', 'warrantyCost', 'retention', 'jobProfitability'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $from = $request->get('from') ?: date('Y-m-01');
        $to = $request->get('to') ?: date('Y-m-d');
        $search = trim((string) $request->get('search'));
        $rows = $this->jobProfitability($from, $to, $search, 5000);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Job No', 'Invoice No', 'Date', 'Customer', 'Vehicle', 'Parts Revenue', 'Labour Revenue', 'Discount', 'Tax', 'Total Revenue', 'Estimated Cost', 'Estimated Profit']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->job_no, $row->invoice_no, $row->invoice_date, $row->customer_name, $row->registration_no,
                    $row->parts_revenue, $row->labour_revenue, $row->discount_amount, $row->tax_amount, $row->total_amount,
                    $row->estimated_cost, $row->estimated_profit,
                ]);
            }
            fclose($out);
        }, 'autoservice_business_intelligence_' . date('Ymd_His') . '.csv');
    }

    protected function partsProfit(string $from, string $to, string $search)
    {
        if (!Schema::hasTable('auto_service_job_lines')) {
            return collect();
        }

        return DB::table('auto_service_job_lines as l')
            ->leftJoin('auto_service_jobs as j', 'j.id', '=', 'l.job_id')
            ->leftJoin('auto_service_vehicles as v', 'v.id', '=', 'j.vehicle_id')
            ->select(
                DB::raw('COALESCE(l.item_name, l.description, "Part / Accessory") as item_name'),
                DB::raw('SUM(COALESCE(l.quantity,0)) as qty'),
                DB::raw('SUM(COALESCE(l.line_total, COALESCE(l.quantity,0) * COALESCE(l.unit_price,0))) as revenue'),
                DB::raw('SUM(COALESCE(l.discount_amount,0)) as discount'),
                DB::raw('SUM(COALESCE(l.tax_amount,0)) as tax'),
                DB::raw('SUM(COALESCE(l.cost_amount, COALESCE(l.quantity,0) * COALESCE(l.unit_cost,0), 0)) as estimated_cost')
            )
            ->when($this->businessId(), fn($q) => $q->where('j.business_id', $this->businessId()))
            ->when($this->locationId(), fn($q) => $q->where(function ($qq) { $qq->whereNull('j.location_id')->orWhere('j.location_id', $this->locationId()); }))
            ->whereBetween('l.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereIn(DB::raw('LOWER(COALESCE(l.line_type, l.type, "part"))'), ['part','parts','accessory','accessories','item','product'])
            ->when($search, fn($q) => $q->where(function ($qq) use ($search) { $qq->where('l.item_name', 'like', "%{$search}%")->orWhere('v.registration_no', 'like', "%{$search}%"); }))
            ->groupBy(DB::raw('COALESCE(l.item_name, l.description, "Part / Accessory")'))
            ->orderByDesc('revenue')
            ->limit(20)
            ->get();
    }

    protected function labourProfit(string $from, string $to, string $search)
    {
        if (!Schema::hasTable('auto_service_job_lines')) {
            return collect();
        }

        return DB::table('auto_service_job_lines as l')
            ->leftJoin('auto_service_jobs as j', 'j.id', '=', 'l.job_id')
            ->select(
                DB::raw('COALESCE(l.item_name, l.description, "Labour") as labour_name'),
                DB::raw('SUM(COALESCE(l.quantity,1)) as qty'),
                DB::raw('SUM(COALESCE(l.line_total, COALESCE(l.quantity,1) * COALESCE(l.unit_price,0))) as revenue'),
                DB::raw('SUM(COALESCE(l.cost_amount, COALESCE(l.quantity,1) * COALESCE(l.unit_cost,0), 0)) as estimated_cost')
            )
            ->when($this->businessId(), fn($q) => $q->where('j.business_id', $this->businessId()))
            ->when($this->locationId(), fn($q) => $q->where(function ($qq) { $qq->whereNull('j.location_id')->orWhere('j.location_id', $this->locationId()); }))
            ->whereBetween('l.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereIn(DB::raw('LOWER(COALESCE(l.line_type, l.type, "labour"))'), ['labour','labor','service','job'])
            ->when($search, fn($q) => $q->where('l.item_name', 'like', "%{$search}%"))
            ->groupBy(DB::raw('COALESCE(l.item_name, l.description, "Labour")'))
            ->orderByDesc('revenue')
            ->limit(20)
            ->get();
    }

    protected function technicianRevenue(string $from, string $to)
    {
        if (!Schema::hasTable('auto_service_job_mechanics')) {
            return collect();
        }

        return DB::table('auto_service_job_mechanics as jm')
            ->leftJoin('auto_service_mechanics as m', 'm.id', '=', 'jm.mechanic_id')
            ->leftJoin('auto_service_jobs as j', 'j.id', '=', 'jm.job_id')
            ->leftJoin('auto_service_invoices as i', 'i.job_id', '=', 'j.id')
            ->select('m.name', DB::raw('COUNT(DISTINCT j.id) as jobs'), DB::raw('SUM(COALESCE(i.total_amount,0)) as revenue'))
            ->when($this->businessId(), fn($q) => $q->where('j.business_id', $this->businessId()))
            ->whereBetween('j.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy('m.name')
            ->orderByDesc('revenue')
            ->limit(15)
            ->get();
    }

    protected function customerRevenue(string $from, string $to, string $search)
    {
        return DB::table('auto_service_invoices as i')
            ->leftJoin('contacts as c', 'c.id', '=', 'i.contact_id')
            ->select('c.name', 'c.mobile', DB::raw('COUNT(i.id) as invoices'), DB::raw('SUM(COALESCE(i.total_amount,0)) as revenue'), DB::raw('SUM(COALESCE(i.total_amount,0)-COALESCE(i.paid_amount,0)) as outstanding'))
            ->when($this->businessId(), fn($q) => $q->where('i.business_id', $this->businessId()))
            ->whereBetween('i.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($search, fn($q) => $q->where(function ($qq) use ($search) { $qq->where('c.name', 'like', "%{$search}%")->orWhere('c.mobile', 'like', "%{$search}%"); }))
            ->groupBy('c.name', 'c.mobile')
            ->orderByDesc('revenue')
            ->limit(15)
            ->get();
    }

    protected function vehicleRevenue(string $from, string $to, string $search)
    {
        return DB::table('auto_service_invoices as i')
            ->leftJoin('auto_service_jobs as j', 'j.id', '=', 'i.job_id')
            ->leftJoin('auto_service_vehicles as v', 'v.id', '=', 'j.vehicle_id')
            ->select('v.registration_no', 'v.make', 'v.model', DB::raw('COUNT(i.id) as invoices'), DB::raw('SUM(COALESCE(i.total_amount,0)) as revenue'))
            ->when($this->businessId(), fn($q) => $q->where('i.business_id', $this->businessId()))
            ->whereBetween('i.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($search, fn($q) => $q->where('v.registration_no', 'like', "%{$search}%"))
            ->groupBy('v.registration_no', 'v.make', 'v.model')
            ->orderByDesc('revenue')
            ->limit(15)
            ->get();
    }

    protected function monthlyTrend(string $from, string $to)
    {
        return DB::table('auto_service_invoices')
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('COUNT(id) as invoices'), DB::raw('SUM(COALESCE(total_amount,0)) as revenue'))
            ->when($this->businessId(), fn($q) => $q->where('business_id', $this->businessId()))
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
            ->orderBy('month')
            ->get();
    }

    protected function warrantyCost(string $from, string $to): float
    {
        if (!Schema::hasTable('auto_service_warranty_claims')) {
            return 0.0;
        }
        return (float) DB::table('auto_service_warranty_claims')
            ->when($this->businessId(), fn($q) => $q->where('business_id', $this->businessId()))
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->sum(DB::raw('COALESCE(estimated_cost,0) + COALESCE(actual_cost,0)'));
    }

    protected function customerRetention(string $from, string $to): array
    {
        $totalCustomers = DB::table('auto_service_jobs')
            ->when($this->businessId(), fn($q) => $q->where('business_id', $this->businessId()))
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->distinct('contact_id')->count('contact_id');

        $repeatCustomers = DB::table('auto_service_jobs')
            ->select('contact_id')
            ->when($this->businessId(), fn($q) => $q->where('business_id', $this->businessId()))
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereNotNull('contact_id')
            ->groupBy('contact_id')
            ->havingRaw('COUNT(id) > 1')
            ->get()->count();

        return [
            'total_customers' => $totalCustomers,
            'repeat_customers' => $repeatCustomers,
            'retention_percent' => $totalCustomers ? round(($repeatCustomers / $totalCustomers) * 100, 2) : 0,
        ];
    }

    protected function jobProfitability(string $from, string $to, string $search, int $limit = 50)
    {
        return DB::table('auto_service_invoices as i')
            ->leftJoin('auto_service_jobs as j', 'j.id', '=', 'i.job_id')
            ->leftJoin('auto_service_vehicles as v', 'v.id', '=', 'j.vehicle_id')
            ->leftJoin('contacts as c', 'c.id', '=', 'i.contact_id')
            ->leftJoin(DB::raw('(SELECT job_id, SUM(CASE WHEN LOWER(COALESCE(line_type,type,"part")) IN ("part","parts","accessory","accessories","item","product") THEN COALESCE(line_total, quantity * unit_price,0) ELSE 0 END) parts_revenue, SUM(CASE WHEN LOWER(COALESCE(line_type,type,"labour")) IN ("labour","labor","service","job") THEN COALESCE(line_total, quantity * unit_price,0) ELSE 0 END) labour_revenue, SUM(COALESCE(cost_amount, COALESCE(quantity,0) * COALESCE(unit_cost,0),0)) estimated_cost FROM auto_service_job_lines GROUP BY job_id) as lp'), 'lp.job_id', '=', 'j.id')
            ->select('j.job_no', 'i.invoice_no', DB::raw('date(i.created_at) as invoice_date'), 'c.name as customer_name', 'v.registration_no', 'i.discount_amount', 'i.tax_amount', 'i.total_amount', DB::raw('COALESCE(lp.parts_revenue,0) as parts_revenue'), DB::raw('COALESCE(lp.labour_revenue,0) as labour_revenue'), DB::raw('COALESCE(lp.estimated_cost,0) as estimated_cost'), DB::raw('COALESCE(i.total_amount,0) - COALESCE(lp.estimated_cost,0) as estimated_profit'))
            ->when($this->businessId(), fn($q) => $q->where('i.business_id', $this->businessId()))
            ->whereBetween('i.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($search, fn($q) => $q->where(function ($qq) use ($search) { $qq->where('j.job_no', 'like', "%{$search}%")->orWhere('i.invoice_no', 'like', "%{$search}%")->orWhere('c.name', 'like', "%{$search}%")->orWhere('v.registration_no', 'like', "%{$search}%"); }))
            ->orderByDesc('i.created_at')
            ->limit($limit)
            ->get();
    }
}

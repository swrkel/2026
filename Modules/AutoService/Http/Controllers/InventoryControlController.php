<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryControlController extends AutoServiceBaseController
{
    public function index(Request $request)
    {
        $businessId = $this->businessId();
        $locationId = $this->locationId();
        $from = $request->get('from') ?: date('Y-m-01');
        $to = $request->get('to') ?: date('Y-m-d');
        $search = trim((string) $request->get('search'));
        $stockFilter = $request->get('stock_filter');

        $usageBase = $this->usageQuery($request, $businessId, $locationId, $from, $to, $search);

        $summary = [
            'parts_used' => (clone $usageBase)->sum('jl.quantity'),
            'parts_sales' => (clone $usageBase)->sum(DB::raw('jl.total_amount')),
            'parts_discount' => (clone $usageBase)->sum(DB::raw('coalesce(jl.discount_amount,0)')),
            'jobs_with_parts' => (clone $usageBase)->distinct('jl.job_id')->count('jl.job_id'),
            'low_stock_items' => DB::table('auto_service_parts_stock')
                ->when($businessId, fn($q) => $q->where('business_id', $businessId))
                ->when($locationId, fn($q) => $q->where(function ($qq) use ($locationId) { $qq->whereNull('location_id')->orWhere('location_id', $locationId); }))
                ->whereRaw('coalesce(qty_available,0) <= coalesce(reorder_level,0)')
                ->count(),
        ];

        $usage = (clone $usageBase)
            ->select('jl.*', 'j.job_no', 'j.status as job_status', 'v.registration_no', 'c.name as customer_name', 'c.mobile as customer_mobile', 's.sku', 's.qty_available', 's.reorder_level', 's.last_purchase_price', 's.preferred_supplier_name')
            ->orderByDesc('jl.created_at')
            ->paginate(25)
            ->appends($request->query());

        $stock = DB::table('auto_service_parts_stock as s')
            ->when($businessId, fn($q) => $q->where('s.business_id', $businessId))
            ->when($locationId, fn($q) => $q->where(function ($qq) use ($locationId) { $qq->whereNull('s.location_id')->orWhere('s.location_id', $locationId); }))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('s.part_name', 'like', "%{$search}%")
                       ->orWhere('s.sku', 'like', "%{$search}%")
                       ->orWhere('s.preferred_supplier_name', 'like', "%{$search}%");
                });
            })
            ->when($stockFilter === 'low', fn($q) => $q->whereRaw('coalesce(s.qty_available,0) <= coalesce(s.reorder_level,0)'))
            ->when($stockFilter === 'out', fn($q) => $q->whereRaw('coalesce(s.qty_available,0) <= 0'))
            ->orderByRaw('case when coalesce(s.qty_available,0) <= coalesce(s.reorder_level,0) then 0 else 1 end')
            ->orderBy('s.part_name')
            ->limit(100)
            ->get();

        $profitability = (clone $this->usageQuery($request, $businessId, $locationId, $from, $to, $search))
            ->select('jl.item_name', DB::raw('sum(jl.quantity) as qty'), DB::raw('sum(jl.total_amount) as sales'), DB::raw('sum(coalesce(jl.cost_amount,0) * jl.quantity) as cost'), DB::raw('sum(jl.total_amount - (coalesce(jl.cost_amount,0) * jl.quantity)) as gross_profit'))
            ->groupBy('jl.item_name')
            ->orderByDesc('sales')
            ->limit(20)
            ->get();

        return view('autoservice::inventory_control.index', compact('summary','usage','stock','profitability','from','to','search','stockFilter'));
    }

    public function exportUsage(Request $request): StreamedResponse
    {
        $businessId = $this->businessId();
        $locationId = $this->locationId();
        $from = $request->get('from') ?: date('Y-m-01');
        $to = $request->get('to') ?: date('Y-m-d');
        $search = trim((string) $request->get('search'));

        $rows = $this->usageQuery($request, $businessId, $locationId, $from, $to, $search)
            ->select('jl.created_at','j.job_no','v.registration_no','c.name as customer_name','jl.item_name','jl.quantity','jl.unit_price','jl.discount_amount','jl.tax_amount','jl.total_amount','jl.cost_amount')
            ->orderByDesc('jl.created_at')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date','Job No','Vehicle','Customer','Part/Accessory','Qty','Unit Price','Discount','Tax','Total','Cost','Gross Profit']);
            foreach ($rows as $r) {
                $costTotal = (float)($r->cost_amount ?? 0) * (float)($r->quantity ?? 0);
                fputcsv($out, [
                    optional($r->created_at)->format('Y-m-d H:i:s') ?: (string)$r->created_at,
                    $r->job_no,
                    $r->registration_no,
                    $r->customer_name,
                    $r->item_name,
                    number_format((float)$r->quantity, 3, '.', ''),
                    number_format((float)$r->unit_price, 4, '.', ''),
                    number_format((float)($r->discount_amount ?? 0), 4, '.', ''),
                    number_format((float)($r->tax_amount ?? 0), 4, '.', ''),
                    number_format((float)$r->total_amount, 4, '.', ''),
                    number_format($costTotal, 4, '.', ''),
                    number_format((float)$r->total_amount - $costTotal, 4, '.', ''),
                ]);
            }
            fclose($out);
        }, 'auto_service_parts_usage_'.date('Ymd_His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function saveStock(Request $request)
    {
        $data = $request->validate([
            'part_name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'qty_available' => 'required|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'last_purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'preferred_supplier_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::table('auto_service_parts_stock')->insert([
            'business_id' => $this->businessId(),
            'location_id' => $this->locationId(),
            'part_name' => $data['part_name'],
            'sku' => $data['sku'] ?? null,
            'qty_available' => $data['qty_available'],
            'reorder_level' => $data['reorder_level'] ?? 0,
            'last_purchase_price' => $data['last_purchase_price'] ?? 0,
            'selling_price' => $data['selling_price'] ?? 0,
            'preferred_supplier_name' => $data['preferred_supplier_name'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Part stock record saved.');
    }

    public function createReorder(Request $request)
    {
        $data = $request->validate([
            'part_stock_id' => 'required|integer',
            'required_qty' => 'required|numeric|min:0.001',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'note' => 'nullable|string',
        ]);

        $part = DB::table('auto_service_parts_stock')
            ->where('id', $data['part_stock_id'])
            ->when($this->businessId(), fn($q) => $q->where('business_id', $this->businessId()))
            ->first();

        if (!$part) {
            return back()->withErrors(['part_stock_id' => 'Part stock record not found for this business.']);
        }

        DB::table('auto_service_reorder_requests')->insert([
            'business_id' => $part->business_id,
            'location_id' => $part->location_id ?? $this->locationId(),
            'part_stock_id' => $part->id,
            'part_name' => $part->part_name,
            'sku' => $part->sku,
            'current_qty' => $part->qty_available,
            'reorder_level' => $part->reorder_level,
            'required_qty' => $data['required_qty'],
            'preferred_supplier_name' => $part->preferred_supplier_name,
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'pending',
            'note' => $data['note'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Reorder request created.');
    }

    protected function usageQuery(Request $request, $businessId, $locationId, $from, $to, $search)
    {
        return DB::table('auto_service_job_lines as jl')
            ->leftJoin('auto_service_jobs as j', 'j.id', '=', 'jl.job_id')
            ->leftJoin('auto_service_vehicles as v', 'v.id', '=', 'j.vehicle_id')
            ->leftJoin('contacts as c', 'c.id', '=', 'j.contact_id')
            ->leftJoin('auto_service_parts_stock as s', function ($join) {
                $join->on('s.product_id', '=', 'jl.product_id')->orOn('s.part_name', '=', 'jl.item_name');
            })
            ->whereIn('jl.line_type', ['part','parts','accessory','accessories','material'])
            ->when($businessId, fn($q) => $q->where('j.business_id', $businessId))
            ->when($locationId, fn($q) => $q->where(function ($qq) use ($locationId) { $qq->whereNull('j.location_id')->orWhere('j.location_id', $locationId); }))
            ->when($from, fn($q) => $q->whereDate('jl.created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('jl.created_at', '<=', $to))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('jl.item_name', 'like', "%{$search}%")
                       ->orWhere('j.job_no', 'like', "%{$search}%")
                       ->orWhere('v.registration_no', 'like', "%{$search}%")
                       ->orWhere('c.name', 'like', "%{$search}%")
                       ->orWhere('c.mobile', 'like', "%{$search}%")
                       ->orWhere('s.sku', 'like', "%{$search}%");
                });
            });
    }
}

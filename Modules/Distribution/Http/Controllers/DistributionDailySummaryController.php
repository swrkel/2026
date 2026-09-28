<?php

namespace Modules\Distribution\Http\Controllers;

use Modules\Distribution\Entities\Core\Category;
use Modules\Distribution\Http\Controllers\Base\DistributionBaseController as Controller;
use Modules\Distribution\Entities\Core\Product;
use Modules\Distribution\Entities\Core\SalesAgent;
use Modules\Distribution\Entities\Core\User;
use Modules\Distribution\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Distribution\Entities\DistributionLoading;
use Modules\Distribution\Entities\DistributionDailySummary;
use Modules\Distribution\Entities\DistributionDailySummaryLine;
use Modules\Distribution\Entities\DistributionVehicleMeters;
use Modules\Distribution\Entities\DistributionVehicles;

class DistributionDailySummaryController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        $summaries = DistributionDailySummary::where('business_id', $business_id)
            ->with(['salesRep', 'route', 'vehicle', 'productCategory', 'lines'])
            ->orderBy('date', 'desc')
            ->paginate(25);

        $hasFreeIssues = false;
        $uniqueUnits   = collect();

        foreach ($summaries as $summary) {
            if ($summary->lines) {
                foreach ($summary->lines as $line) {
                    if (!empty($line->free_issues_json)) {
                        $freeIssues = is_string($line->free_issues_json)
                            ? json_decode($line->free_issues_json, true)
                            : $line->free_issues_json;
                        if (!empty($freeIssues)) {
                            $hasFreeIssues = true;
                            foreach ($freeIssues as $issue) {
                                if (!empty($issue['unit_name'])) {
                                    $uniqueUnits->push($issue['unit_name']);
                                }
                            }
                        }
                    }
                }
            }
        }

        $uniqueUnits = $uniqueUnits->unique()->values();

        return view('distribution::daily_summary_sheet.index', compact('summaries', 'hasFreeIssues', 'uniqueUnits'));
    }

    public function create(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $salesReps = SalesAgent::where('business_id', $business_id)
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name', 'asc')
            ->pluck('name', 'id')
            ->toArray();

        Log::info('Sales Reps: ', $salesReps);

        $routes     = DB::table('distribution_routes')->where('business_id', $business_id)->pluck('name', 'id');
        $vehicles   = DB::table('distribution_vehicles')->where('business_id', $business_id)->pluck('vehicle_no', 'id');
        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->distinct()
            ->pluck('name', 'id');

        $draftId = $request->get('draft_id') ?: $request->session()->get('dss_restore_id');
        if ($draftId) {
            $existingSheet = DistributionDailySummary::where('business_id', $business_id)
                ->where('id', $draftId)
                ->first();
            $sheet_number = $existingSheet ? $existingSheet->sheet_number : $this->getNextSheetNumber($business_id);
        } else {
            $sheet_number = $this->getNextSheetNumber($business_id);
        }

        $business         = \Modules\Distribution\Entities\Core\Business::where('id', $business_id)->first();
        $show_date_picker = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'distribution_show_date_picker');
        $auto_date_time   = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'distribution_auto_date_time');

        return view('distribution::daily_summary_sheet.create', compact(
            'salesReps',
            'routes',
            'vehicles',
            'categories',
            'sheet_number',
            'show_date_picker',
            'auto_date_time',
            'business'
        ));
    }

    private function generateSheetNumber($business_id)
    {
        return DB::transaction(function () use ($business_id) {
            $setting = DB::table('distribution_prefix_settings')
                ->where('business_id', $business_id)
                ->where('numbering_type', 'daily_summary_sheet')
                ->lockForUpdate()
                ->first();

            if ($setting) {
                $current = (int) ($setting->current_no ?? $setting->starting_no ?? 1);
                $prefix  = $setting->prefix ?? '';
                $number  = $prefix . $current;
                DB::table('distribution_prefix_settings')
                    ->where('id', $setting->id)
                    ->update(['current_no' => $current + 1]);
                return $number;
            }

            $lastId = DB::table('distribution_daily_summaries')
                ->where('business_id', $business_id)
                ->max('id');
            $next = ($lastId ? $lastId + 1 : 1);
            return 'LD-' . str_pad($next, 6, '0', STR_PAD_LEFT);
        });
    }

    private function getNextSheetNumber($business_id)
    {
        $setting = DB::table('distribution_prefix_settings')
            ->where('business_id', $business_id)
            ->where('numbering_type', 'daily_summary_sheet')
            ->first();

        if ($setting) {
            $current = (int) ($setting->current_no ?? $setting->starting_no ?? 1);
            $prefix  = $setting->prefix ?? '';
            return $prefix . $current;
        }

        $lastId = DB::table('distribution_daily_summaries')
            ->where('business_id', $business_id)
            ->max('id');
        $next = ($lastId ? $lastId + 1 : 1);
        return 'LD-' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Encode a value for storage in a JSON database column.
     * Arrays -> json_encode(). Strings -> stored as-is if valid JSON.
     * Empty arrays/nulls -> null.
     */
    private function encodeJsonColumn($value): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        if (is_array($value) || is_object($value)) {
            if (empty((array) $value)) {
                return null;
            }
            return json_encode($value);
        }
        if (is_string($value)) {
            // Validate it is actually JSON
            json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Reject empty JSON structures
                if ($value === '[]' || $value === '{}' || $value === 'null') {
                    return null;
                }
                return $value;
            }
        }
        return null;
    }

public function fetchBills(Request $request)
{
    try {
        $business_id = $request->session()->get('user.business_id');

        if (!$business_id) {
            return response()->json(['error' => 'Business ID not found in session'], 400);
        }

        $dateRaw             = $request->date;
        $sales_rep_id        = $request->sales_rep_id;
        $vehicle_id          = $request->vehicle_id;
        $route_id            = $request->route_id;
        $product_category_id = $request->product_category_id;

        $date = null;
        if ($dateRaw) {
            try {
                $datePart = trim(explode(' ', $dateRaw)[0]);
                $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePart)
                    ? $datePart
                    : \Carbon\Carbon::parse($dateRaw)->format('Y-m-d');
            } catch (\Exception $e) {
                $date = null;
            }
        }

        if ($sales_rep_id && !is_numeric($sales_rep_id)) {
            return response()->json(['error' => 'Invalid sales representative ID'], 400);
        }
        if ($vehicle_id && !is_numeric($vehicle_id)) {
            return response()->json(['error' => 'Invalid vehicle ID'], 400);
        }
        if ($route_id && !is_numeric($route_id)) {
            return response()->json(['error' => 'Invalid route ID'], 400);
        }
        if ($product_category_id && !is_numeric($product_category_id)) {
            return response()->json(['error' => 'Invalid product category ID'], 400);
        }
        if ($date && !strtotime($date)) {
            return response()->json(['error' => 'Invalid date format'], 400);
        }

        $invoices = DB::table('distribution_invoices as i')
            ->leftJoin('contacts as c', 'c.id', '=', 'i.customer_id')
            ->select('i.id', 'i.invoice_no', 'i.customer_id', 'i.grand_total', 'i.total', 'i.discount', 'c.name as customer_name')
            ->where('i.business_id', $business_id)
            ->when($date, fn($q) => $q->whereDate('i.date', $date))
            ->when($sales_rep_id, fn($q) => $q->where('i.sales_rep_id', $sales_rep_id))
            ->when($vehicle_id, fn($q) => $q->where('i.vehicle_id', $vehicle_id))
            ->when($route_id, fn($q) => $q->where('i.route_id', $route_id))
            ->when($product_category_id, fn($q) => $q->where('i.category_id', $product_category_id))
            ->orderBy('i.date', 'desc')
            ->limit(100)
            ->get();

        $invoiceIds = $invoices->pluck('id')->toArray();

        // ── Fetch REGULAR (non-free) lines only ──────────────────────────────
        $regularLinesData = [];
        if (!empty($invoiceIds)) {
            $regularLinesData = DB::table('distribution_invoice_lines as l')
                ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
                ->whereIn('l.invoice_id', $invoiceIds)
                ->where('l.is_free', 0)
                ->where('l.is_free_auto', 0)
                ->where('l.is_free_bottles', 0)
                ->select(
                    'l.invoice_id',
                    'l.product_id',
                    'l.qty',
                    'p.name as product_name'
                )
                ->get()
                ->groupBy('invoice_id');
        }

        $out = [];
        foreach ($invoices as $inv) {
            // Regular products only (no free lines)
            $products = [];
            if (isset($regularLinesData[$inv->id])) {
                foreach ($regularLinesData[$inv->id] as $prod) {
                    $products[] = [
                        'product_id'   => $prod->product_id,
                        'product_name' => $prod->product_name ?? 'Product',
                        'qty'          => $prod->qty,
                    ];
                }
            }

            $grossSale = ($inv->total > 0) ? $inv->total : ($inv->grand_total ?? 0);

            $out[] = [
                'id'            => $inv->id,
                'invoice_no'    => $inv->invoice_no,
                'text'          => $inv->invoice_no . ' - ' . ($inv->grand_total ?? '0.00'),
                'customer_id'   => $inv->customer_id,
                'customer_name' => $inv->customer_name ?? 'Unknown Customer',
                'grand_total'   => $inv->grand_total,
                'total'         => $inv->total,
                'gross_sale'    => $grossSale,
                'discount'      => $inv->discount ?? 0,
                'net_sale'      => $inv->grand_total ?? 0,
                'products'      => $products, // regular lines only — free lines handled separately via /free-issues
            ];
        }

        return response()->json($out);

    } catch (\Exception $e) {
        Log::error('fetchBills error: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to fetch bills'], 500);
    }
}

    public function fetchVehicles(Request $request)
    {
        $business_id  = $request->session()->get('user.business_id');
        $sales_rep_id = $request->sales_rep_id;

        if (!$sales_rep_id) {
            return response()->json([]);
        }

        $vehicles = DB::table('distribution_vehicles as v')
            ->where('v.business_id', $business_id)
            ->select('v.id', 'v.vehicle_no')
            ->orderBy('v.vehicle_no')
            ->get();

        return response()->json($vehicles->map(fn($v) => ['id' => $v->id, 'vehicle_no' => $v->vehicle_no]));
    }

    public function fetchBillDetails($bill_id)
    {
        $inv = DB::table('distribution_invoices as i')->where('i.id', $bill_id)->first();
        if (!$inv) {
            return response()->json(['error' => 'Bill not found'], 404);
        }

        $productsData = DB::table('distribution_invoice_lines as l')
            ->join('products as p', 'p.id', '=', 'l.product_id')
            ->where('l.invoice_id', $bill_id)
            ->select('p.id as product_id', 'p.name as product_name', 'l.qty')
            ->get();

        return response()->json([
            'invoice_id' => $inv->id,
            'invoice_no' => $inv->invoice_no,
            'gross_sale' => (float) ($inv->total ?? $inv->grand_total ?? 0),
            'discount'   => (float) ($inv->discount ?? 0),
            'net_sale'   => (float) ($inv->grand_total ?? 0),
            'products'   => $productsData->map(fn($p) => [
                'product_id'   => $p->product_id,
                'product_name' => $p->product_name,
                'qty'          => $p->qty,
            ])->toArray(),
        ]);
    }

    /**
     * Returns free-issue lines for a given invoice.
     * Returns 'total_qty' from DB aggregate.
     * JS stores it as 'qty' in dataset.freeIssues.
     * Print blade handles both via ($fi['qty'] ?? $fi['total_qty'] ?? 0).
     */
    public function getInvoiceFreeIssues(Request $request)
    {
        $invoice_id = (int) $request->input('invoice_id');
        if (!$invoice_id) {
            return response()->json([]);
        }

        $lines = DB::table('distribution_invoice_lines as l')
            ->leftJoin('products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'l.unit_id')
            ->where('l.invoice_id', $invoice_id)
            ->where(function ($q) {
                $q->where('l.is_free', 1)
                    ->orWhere('l.is_free_auto', 1)
                    ->orWhere('l.is_free_bottles', 1);
            })
            ->select(
                'l.product_id',
                DB::raw('COALESCE(p.name, "Unknown Product") as product_name'),
                'l.unit_id',
                DB::raw('COALESCE(u.short_name, u.actual_name, "") as unit_name'),
                DB::raw('SUM(l.qty) as total_qty')
            )
            ->groupBy('l.product_id', 'l.unit_id')
            ->get();

        return response()->json($lines);
    }

    public function autosave(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $data        = $request->only([
                'sheet_number',
                'sales_rep_id',
                'agent_name',
                'date',
                'route_id',
                'vehicle_id',
                'product_category_id',
                'distance_km',
                'page_no',
                'total_pages',
                'product_list',
                'lines',
                'status',
                'previous_page_gt',
                'total_this_page',
                'grand_total',
                'cash_deposited',
                'cheque_deposited',
                'credit_bills_bf',
                'cheques_in_hand',
                'credit_bills_in_hand',
                'cash_in_hand',
                'calls_visited',
                'productive_calls',
                'calls_visited_bf',
                'productive_calls_bf',
                'loading_sheet_no',
                'loading_sheets',
                'stock_status',
            ]);

            $data['product_list']   = $this->decodeJsonField($data['product_list'] ?? null);
            $data['stock_status']   = $this->decodeJsonField($data['stock_status'] ?? null);
            $data['loading_sheets'] = $this->decodeJsonField($data['loading_sheets'] ?? null);

            if (!($data['loading_sheet_no'] ?? null) && is_array($data['loading_sheets']) && count($data['loading_sheets'])) {
                $data['loading_sheet_no'] = $data['loading_sheets'][0]['loading_no'] ?? null;
            }

            $data['status'] = $data['status'] ?? 'draft';

            if ($request->id) {
                $sheet = DistributionDailySummary::where('business_id', $business_id)->find($request->id);
                if (!$sheet) {
                    return response()->json(['error' => 'Not found'], 404);
                }
                $sheet->update($data);
            } else {
                $data['business_id'] = $business_id;
                $data['created_by']  = auth()->id() ?? null;
                $sheet               = DistributionDailySummary::create($data);
            }

            $linesPayload = $request->lines ?? [];
            if (is_string($linesPayload)) {
                $decoded = json_decode($linesPayload, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $linesPayload = $decoded;
                }
            }

            if ($linesPayload && is_array($linesPayload)) {
                DistributionDailySummaryLine::where('daily_summary_id', $sheet->id)->delete();
                $i = 1;
                foreach ($linesPayload as $row) {
                    DistributionDailySummaryLine::create([
                        'daily_summary_id' => $sheet->id,
                        'page_no'          => $row['page_no'] ?? 1,
                        'bill_id'          => $row['bill_id'] ?? null,
                        'customer_id'      => $row['customer_id'] ?? null,
                        'gross_sale'       => $row['gross_sale'] ?? 0,
                        'discount'         => $row['discount'] ?? 0,
                        'net_sale'         => $row['net_sale'] ?? 0,
                        'products_json'    => $this->encodeJsonColumn($row['products'] ?? null),
                        'free_issues_json' => $this->encodeJsonColumn($row['free_issues'] ?? null),
                        'row_number'       => $i++,
                    ]);
                }
            }

            return response()->json(['success' => true, 'id' => $sheet->id]);
        } catch (\Exception $e) {
            Log::error('Daily Summary autosave error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        Log::info('DSS store() called', [
            'sheet_number'        => $request->sheet_number,
            'sales_rep_id'        => $request->sales_rep_id,
            'date'                => $request->date,
            'product_category_id' => $request->product_category_id,
            'id'                  => $request->id,
            'lines_raw'           => substr($request->lines ?? 'NULL', 0, 500),
            'total_this_page'     => $request->total_this_page,
            'grand_total'         => $request->grand_total,
        ]);

        $request->validate([
            'sheet_number'        => 'required',
            'date'                => 'required|date',
            'sales_rep_id'        => 'required',
            'product_category_id' => 'required|exists:categories,id',
        ]);

        if ($request->filled('product_category_id') && $request->filled('lines')) {
            $category_id = $request->product_category_id;
            $lines       = $request->lines;
            if (is_string($lines)) {
                $lines = json_decode($lines, true);
            }
            if (is_array($lines) && !empty($lines)) {
                $bill_ids = array_filter(array_column($lines, 'bill_id'));
                if (!empty($bill_ids)) {
                    $invoicesInCategory = DB::table('distribution_invoices')
                        ->whereIn('id', $bill_ids)
                        ->where('category_id', $category_id)
                        ->count();
                    if ($invoicesInCategory != count($bill_ids)) {
                        return back()->withErrors([
                            'product_category_id' => 'All selected bills must belong to the selected product category.',
                        ])->withInput();
                    }
                }
            }
        }

        DB::beginTransaction();
        try {
            $productList    = $this->decodeJsonField($request->product_list);
            $stockStatus    = $this->decodeJsonField($request->stock_status);
            $loadingSheets  = $this->decodeJsonField($request->loading_sheets);
            $firstLoadingNo = (is_array($loadingSheets) && isset($loadingSheets[0]['loading_no']))
                ? $loadingSheets[0]['loading_no']
                : null;

            $payload = [
                'business_id'          => $business_id,
                'sheet_number'         => $request->id ? $request->sheet_number : $this->generateSheetNumber($business_id),
                'sales_rep_id'         => $request->sales_rep_id,
                'agent_name'           => $request->agent_name ?? null,
                'date'                 => $request->date,
                'route_id'             => $request->route_id ?? null,
                'vehicle_id'           => $request->vehicle_id ?? null,
                'product_category_id'  => $request->product_category_id ?? null,
                'distance_km'          => $request->distance_km ?? 0,
                'page_no'              => $request->page_no ?? 1,
                'total_pages'          => $request->total_pages ?? 1,
                'product_list'         => $productList,
                'total_this_page'      => $request->total_this_page ?? 0,
                'previous_page_gt'     => $request->previous_page_gt ?? 0,
                'grand_total'          => $request->grand_total ?? 0,
                'cash_deposited'       => $request->cash_deposited ?? 0,
                'cheque_deposited'     => $request->cheque_deposited ?? 0,
                'credit_bills_bf'      => $request->credit_bills_bf ?? 0,
                'cheques_in_hand'      => $request->cheques_in_hand ?? 0,
                'credit_bills_in_hand' => $request->credit_bills_in_hand ?? 0,
                'cash_in_hand'         => $request->cash_in_hand ?? 0,
                'calls_visited'        => $request->calls_visited ?? 0,
                'productive_calls'     => $request->productive_calls ?? 0,
                'calls_visited_bf'     => $request->calls_visited_bf ?? 0,
                'productive_calls_bf'  => $request->productive_calls_bf ?? 0,
                'loading_sheet_no'     => $request->loading_sheet_no ?? $firstLoadingNo,
                'loading_sheets'       => $loadingSheets,
                'stock_status'         => $stockStatus,
                'status'               => 'finalized',
                'created_by'           => auth()->id() ?? null,
            ];

            if ($request->id) {
                $sheet = DistributionDailySummary::where('business_id', $business_id)->find($request->id);
                if ($sheet) {
                    $sheet->update($payload);
                } else {
                    $sheet = DistributionDailySummary::create($payload);
                }
            } else {
                $sheet = DistributionDailySummary::create($payload);
            }

            $linesPayload = $request->lines;
            if (is_string($linesPayload)) {
                $decoded = json_decode($linesPayload, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $linesPayload = $decoded;
                } else {
                    Log::error('DSS store: JSON decode error: ' . json_last_error_msg());
                    $linesPayload = [];
                }
            }

            Log::info('DSS store parsed lines count: ' . count($linesPayload ?? []));
            if (!empty($linesPayload)) {
                Log::info('DSS store first line: ', (array) ($linesPayload[0] ?? []));
            }

            if ($linesPayload && is_array($linesPayload)) {
                DistributionDailySummaryLine::where('daily_summary_id', $sheet->id)->delete();
                $rowNumber = 1;
                foreach ($linesPayload as $row) {
                    DistributionDailySummaryLine::create([
                        'daily_summary_id' => $sheet->id,
                        'page_no'          => $row['page_no'] ?? 1,
                        'bill_id'          => $row['bill_id'] ?? null,
                        'customer_id'      => $row['customer_id'] ?? null,
                        'gross_sale'       => $row['gross_sale'] ?? 0,
                        'discount'         => $row['discount'] ?? 0,
                        'net_sale'         => $row['net_sale'] ?? 0,
                        'products_json'    => $this->encodeJsonColumn($row['products'] ?? null),
                        'free_issues_json' => $this->encodeJsonColumn($row['free_issues'] ?? null),
                        'row_number'       => $rowNumber++,
                    ]);
                }
            }

            if ($request->vehicle_id) {
                try {
                    $vehicle = DistributionVehicles::where('business_id', $business_id)->find($request->vehicle_id);
                    DistributionVehicleMeters::create([
                        'business_id'            => $business_id,
                        'daily_summary_id'       => $sheet->id,
                        'vehicle_id'             => $request->vehicle_id,
                        'date'                   => $request->date,
                        'daily_summary_sheet_no' => $sheet->sheet_number,
                        'starting_meter'         => $vehicle->starting_meter ?? 0,
                        'closing_meter'          => $request->closing_meter ?? 0,
                        'sales_rep_id'           => $sheet->sales_rep_id,
                        'route_id'               => $sheet->route_id,
                        'added_by'               => auth()->id(),
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Vehicle meter creation failed (non-critical): ' . $e->getMessage());
                }
            }

            DB::commit();
            Log::info('DSS store() SUCCESS, sheet id: ' . $sheet->id);
            $request->session()->forget('dss_restore_id');

            return redirect()->route('distribution.daily_summary.create', ['saved' => '1'])
                ->with('status', ['success' => true, 'msg' => 'Daily Summary saved']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Daily Summary store error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function restoreDraft($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $sheet       = DistributionDailySummary::with('lines')
            ->where('business_id', $business_id)
            ->findOrFail($id);

        $lineBillIds = $sheet->lines->pluck('bill_id')->filter()->unique();
        $invoiceInfo = collect();

        if ($lineBillIds->count()) {
            $invoiceInfo = DB::table('distribution_invoices as i')
                ->leftJoin('contacts as c', 'c.id', '=', 'i.customer_id')
                ->whereIn('i.id', $lineBillIds)
                ->select('i.id', 'i.invoice_no', 'c.name as customer_name', 'i.customer_name as stored_customer_name')
                ->get()
                ->keyBy('id');
        }

        $sheet->lines->transform(function ($line) use ($invoiceInfo) {
            $inv               = $invoiceInfo->get($line->bill_id);
            $line->invoice_no  = $inv->invoice_no ?? null;
            $line->customer_name = $inv->stored_customer_name ?? $inv->customer_name ?? null;
            return $line;
        });

        return response()->json($sheet);
    }

    public function stockStatus(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $dateRaw     = $request->date ?: null;

        $date = null;
        if ($dateRaw) {
            try {
                $datePart = trim(explode(' ', $dateRaw)[0]);
                $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePart)
                    ? $datePart
                    : \Carbon\Carbon::parse($dateRaw)->format('Y-m-d');
            } catch (\Exception $e) {
                $date = null;
            }
        }

        $vehicle_id       = $request->vehicle_id ?: null;
        $route_id         = $request->route_id ?: null;
        $sales_rep_id     = $request->sales_rep_id ?: null;
        $product_category = $request->product_category_id ?: null;

        $productIds = $request->product_ids ?? [];
        if (is_string($productIds)) {
            $decoded = json_decode($productIds, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $productIds = $decoded;
            }
        }

        if (!$productIds || !is_array($productIds)) {
            return response()->json(['stock_status' => [], 'loading_sheets' => [], 'loading_sheet_no' => null]);
        }

        $products            = DB::table('products')->whereIn('id', $productIds)->pluck('name', 'id');
        $currentStoreStock   = DB::table('variation_store_details')
            ->whereIn('product_id', $productIds)
            ->select('product_id', DB::raw('SUM(qty_available) as qty'))
            ->groupBy('product_id')->pluck('qty', 'product_id');

        $currentVehicleStock = collect();
        if ($vehicle_id) {
            $currentVehicleStock = DB::table('distribution_vehicle_stocks')
                ->where('business_id', $business_id)->where('vehicle_id', $vehicle_id)
                ->whereIn('product_id', $productIds)
                ->select('product_id', DB::raw('SUM(qty) as qty'))
                ->groupBy('product_id')->pluck('qty', 'product_id');
        }

        $loadedOnDate = DB::table('distribution_loading_lines as ll')
            ->join('distribution_loadings as l', 'll.loading_id', '=', 'l.id')
            ->where('l.business_id', $business_id)
            ->when($date, fn($q) => $q->whereDate('l.date_time', $date))
            ->when($vehicle_id, fn($q) => $q->where('l.vehicle_id', $vehicle_id))
            ->whereIn('ll.product_id', $productIds)
            ->select('ll.product_id', DB::raw('SUM(ll.issued_qty) as qty'))
            ->groupBy('ll.product_id')->pluck('qty', 'product_id');

        $soldOnDate = DB::table('distribution_invoice_lines as il')
            ->join('distribution_invoices as i', 'i.id', '=', 'il.invoice_id')
            ->where('i.business_id', $business_id)
            ->when($date, fn($q) => $q->whereDate('i.date', $date))
            ->when($vehicle_id, fn($q) => $q->where('i.vehicle_id', $vehicle_id))
            ->when($route_id, fn($q) => $q->where('i.route_id', $route_id))
            ->when($sales_rep_id, fn($q) => $q->where('i.sales_rep_id', $sales_rep_id))
            ->when($product_category, fn($q) => $q->where('i.category_id', $product_category))
            ->whereIn('il.product_id', $productIds)
            ->select('il.product_id', DB::raw('SUM(il.qty) as qty'))
            ->groupBy('il.product_id')->pluck('qty', 'product_id');

        $loadedBeforeDate = $soldBeforeDate = $loadedAfterDate = $soldAfterDate = collect();

        if ($date) {
            $loadedBeforeDate = DB::table('distribution_loading_lines as ll')
                ->join('distribution_loadings as l', 'll.loading_id', '=', 'l.id')
                ->where('l.business_id', $business_id)->whereDate('l.date_time', '<', $date)
                ->when($vehicle_id, fn($q) => $q->where('l.vehicle_id', $vehicle_id))
                ->whereIn('ll.product_id', $productIds)
                ->select('ll.product_id', DB::raw('SUM(ll.issued_qty) as qty'))
                ->groupBy('ll.product_id')->pluck('qty', 'product_id');

            $soldBeforeDate = DB::table('distribution_invoice_lines as il')
                ->join('distribution_invoices as i', 'i.id', '=', 'il.invoice_id')
                ->where('i.business_id', $business_id)->whereDate('i.date', '<', $date)
                ->when($vehicle_id, fn($q) => $q->where('i.vehicle_id', $vehicle_id))
                ->when($route_id, fn($q) => $q->where('i.route_id', $route_id))
                ->when($sales_rep_id, fn($q) => $q->where('i.sales_rep_id', $sales_rep_id))
                ->whereIn('il.product_id', $productIds)
                ->select('il.product_id', DB::raw('SUM(il.qty) as qty'))
                ->groupBy('il.product_id')->pluck('qty', 'product_id');

            $loadedAfterDate = DB::table('distribution_loading_lines as ll')
                ->join('distribution_loadings as l', 'll.loading_id', '=', 'l.id')
                ->where('l.business_id', $business_id)->whereDate('l.date_time', '>', $date)
                ->when($vehicle_id, fn($q) => $q->where('l.vehicle_id', $vehicle_id))
                ->whereIn('ll.product_id', $productIds)
                ->select('ll.product_id', DB::raw('SUM(ll.issued_qty) as qty'))
                ->groupBy('ll.product_id')->pluck('qty', 'product_id');

            $soldAfterDate = DB::table('distribution_invoice_lines as il')
                ->join('distribution_invoices as i', 'i.id', '=', 'il.invoice_id')
                ->where('i.business_id', $business_id)->whereDate('i.date', '>', $date)
                ->when($vehicle_id, fn($q) => $q->where('i.vehicle_id', $vehicle_id))
                ->whereIn('il.product_id', $productIds)
                ->select('il.product_id', DB::raw('SUM(il.qty) as qty'))
                ->groupBy('il.product_id')->pluck('qty', 'product_id');
        }

        $stockStatus = [];
        foreach ($productIds as $pid) {
            $currStore   = (float) ($currentStoreStock[$pid]  ?? 0);
            $currVehicle = (float) ($currentVehicleStock[$pid] ?? 0);
            $loadBefore  = (float) ($loadedBeforeDate[$pid]   ?? 0);
            $soldBefore  = (float) ($soldBeforeDate[$pid]     ?? 0);
            $loadAfter   = (float) ($loadedAfterDate[$pid]    ?? 0);
            $soldAfter   = (float) ($soldAfterDate[$pid]      ?? 0);
            $loadedQty   = (float) ($loadedOnDate[$pid]       ?? 0);
            $soldQty     = (float) ($soldOnDate[$pid]         ?? 0);

            $openingStore = $currStore + $loadAfter;
            $openingVehicle = $date
                ? (($loadBefore > 0 || $soldBefore > 0)
                    ? $loadBefore - $soldBefore
                    : $currVehicle + $soldQty + $soldAfter - $loadedQty - $loadAfter)
                : $currVehicle + $soldAfter - $loadAfter;

            $stockStatus[] = [
                'product_id'          => $pid,
                'product_name'        => $products[$pid] ?? 'Product ' . $pid,
                'store_opening_qty'   => $openingStore,
                'vehicle_opening_qty' => $openingVehicle,
                'loaded_qty'          => $loadedQty,
                'sold_qty'            => $soldQty,
                'balance_qty'         => $openingVehicle + $loadedQty - $soldQty,
            ];
        }

        $loadingSheets = DistributionLoading::where('business_id', $business_id)
            ->when($date, fn($q) => $q->whereDate('date_time', $date))
            ->when($vehicle_id, fn($q) => $q->where('vehicle_id', $vehicle_id))
            ->when($product_category, fn($q) => $q->where('product_category_id', $product_category))
            ->orderBy('date_time', 'desc')
            ->get(['id', 'loading_no', 'date_time', 'vehicle_id'])
            ->map(fn($row) => [
                'id'         => $row->id,
                'loading_no' => $row->loading_no,
                'date_time'  => $row->date_time,
                'view_url'   => route('distribution.loadings.show', $row->id),
                'print_url'  => route('distribution.loadings.print', $row->id),
            ]);

        return response()->json([
            'stock_status'     => $stockStatus,
            'loading_sheets'   => $loadingSheets,
            'loading_sheet_no' => $loadingSheets->pluck('loading_no')->implode(', '),
        ]);
    }

    public function loadingInfo(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $dateRaw     = $request->date;
        $date        = null;

        if ($dateRaw) {
            try {
                $datePart = trim(explode(' ', $dateRaw)[0]);
                $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePart)
                    ? $datePart
                    : \Carbon\Carbon::parse($dateRaw)->format('Y-m-d');
            } catch (\Exception $e) {
                $date = null;
            }
        }

        $vehicle_id       = $request->vehicle_id;
        $product_category = $request->product_category_id;

        $loadingSheets = DistributionLoading::where('business_id', $business_id)
            ->when($date, fn($q) => $q->whereDate('date_time', $date))
            ->when($vehicle_id, fn($q) => $q->where('vehicle_id', $vehicle_id))
            ->when($product_category, fn($q) => $q->where('product_category_id', $product_category))
            ->orderBy('date_time', 'desc')
            ->get(['id', 'loading_no', 'date_time', 'vehicle_id'])
            ->map(fn($row) => [
                'id'         => $row->id,
                'loading_no' => $row->loading_no,
                'date_time'  => $row->date_time,
                'view_url'   => route('distribution.loadings.show', $row->id),
                'print_url'  => route('distribution.loadings.print', $row->id),
            ]);

        return response()->json([
            'loading_sheets'   => $loadingSheets,
            'loading_sheet_no' => $loadingSheets->pluck('loading_no')->implode(', '),
        ]);
    }

    /**
     * Print / preview.
     * Enriches lines with invoice_no and customer_name only.
     * All free-issue column logic is handled by the print blade.
     */
    public function print($id)
    {
        $sheet        = DistributionDailySummary::with(['lines', 'salesRep', 'route', 'vehicle', 'productCategory'])
            ->findOrFail($id);
        $sheet->lines = $sheet->lines->sortBy('row_number');

        $billIds     = $sheet->lines->pluck('bill_id')->filter()->unique();
        $invoiceInfo = collect();

        if ($billIds->count()) {
            $invoiceInfo = DB::table('distribution_invoices as i')
                ->leftJoin('contacts as c', 'c.id', '=', 'i.customer_id')
                ->whereIn('i.id', $billIds)
                ->select('i.id', 'i.invoice_no', 'c.name as customer_name', 'i.customer_name as stored_customer_name')
                ->get()
                ->keyBy('id');
        }

        $sheet->lines->transform(function ($line) use ($invoiceInfo) {
            $inv               = $invoiceInfo->get($line->bill_id);
            $line->invoice_no  = $inv->invoice_no ?? $line->bill_id;
            $line->customer_name = $inv->stored_customer_name ?? $inv->customer_name ?? '';
            return $line;
        });

        // Fallback: derive product_list from stock_status if missing
        if (!$sheet->product_list && $sheet->stock_status) {
            $sheet->product_list = collect($sheet->stock_status)->map(fn($row) => [
                'id'   => $row['product_id'] ?? null,
                'name' => $row['product_name'] ?? null,
            ])->toArray();
        }

        return view('distribution::daily_summary_sheet.print', compact('sheet'));
    }

    protected function decodeJsonField($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return $value;
    }


    /**
     * Returns free issue column definitions for a given category and date.
     * Queries distribution_free_issues settings, groups by unit of the free products.
     */

public function getFreeIssueColumns(Request $request)
{
    $business_id = $request->session()->get('user.business_id');
    $category_id = $request->category_id;
    $date        = $request->date;

    if (!$category_id) {
        return response()->json([]);
    }

    // First, get all free issues for this category
    $query = DB::table('distribution_free_issues as fi')
        ->where('fi.business_id', $business_id)
        ->where('fi.product_category', $category_id)
        ->where('fi.status', 1)
        ->select(
            'fi.id',
            'fi.product_name',
            'fi.free_products',
            'fi.product_category'
        );

    if ($date) {
        $query->where(function ($q) use ($date) {
            $q->whereNull('fi.date_since')->orWhere('fi.date_since', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('fi.date_till')->orWhere('fi.date_till', '>=', $date);
        });
    }

    $freeIssues = $query->get();

    if ($freeIssues->isEmpty()) {
        return response()->json([]);
    }

    // Collect all product IDs from free_products
    $allProductIds = [];
    foreach ($freeIssues as $fi) {
        $freeProducts = json_decode($fi->free_products, true);
        if (is_array($freeProducts)) {
            $allProductIds = array_merge($allProductIds, $freeProducts);
        }
    }
    $allProductIds = array_unique(array_filter($allProductIds));

    // Get product details with their units
    $products = [];
    if (!empty($allProductIds)) {
        $products = DB::table('products')
            ->whereIn('id', $allProductIds)
            ->select('id', 'name', 'unit_id')
            ->get()
            ->keyBy('id');
    }

    // Get unit names
    $unitIds = $products->pluck('unit_id')->filter()->unique()->values()->toArray();
    $units = [];
    if (!empty($unitIds)) {
        $units = DB::table('units')
            ->whereIn('id', $unitIds)
            ->select('id', 'short_name', 'actual_name')
            ->get()
            ->keyBy('id');
    }

    // Build columns - one per unique product + unit combination
    $columns = [];
    $seenKeys = [];

    foreach ($freeIssues as $fi) {
        $freeProducts = json_decode($fi->free_products, true);
        if (!is_array($freeProducts)) continue;

        foreach ($freeProducts as $productId) {
            $product = $products[$productId] ?? null;
            if (!$product) continue;

            $unitId = $product->unit_id ?? 0;
            $unit = $units[$unitId] ?? null;
            $unitName = $unit ? ($unit->short_name ?? $unit->actual_name ?? '') : '';
            
            $key = $productId . '_' . $unitId;
            
            if (!in_array($key, $seenKeys)) {
                $seenKeys[] = $key;
                $columns[] = [
                    'key'          => $key,
                    'product_id'   => $productId,
                    'unit_id'      => $unitId,
                    'unit_name'    => $unitName,
                    'product_name' => $product->name,
                    'label'        => $product->name . ($unitName ? ' (' . $unitName . ')' : ''),
                    'category_id'  => $fi->product_category,
                ];
            }
        }
    }

    return response()->json($columns);
}



}

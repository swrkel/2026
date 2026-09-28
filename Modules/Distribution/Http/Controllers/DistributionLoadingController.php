<?php
namespace Modules\Distribution\Http\Controllers;

use App\Services\Documents\GlobalPdfService;
use Modules\Distribution\Entities\Core\Business;
use Modules\Distribution\Entities\Core\BusinessLocation;
use Modules\Distribution\Entities\Core\Category;
use Modules\Distribution\Entities\Core\Product;
use Modules\Distribution\Entities\Core\SalesAgent;
use Modules\Distribution\Entities\Core\System;
use Modules\Distribution\Entities\Core\User;
use Modules\Distribution\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Distribution\Entities\DistributionLoading;
use Modules\Distribution\Entities\DistributionLoadingLine;
use Modules\Distribution\Entities\DistributionNumberingPrefix;
use Modules\Distribution\Entities\DistributionVehicles;

class DistributionLoadingController extends Controller
{
    /**
     * @var ModuleUtil
     */
    protected $moduleUtil;

    /**
     * Constructor
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        // AJAX response for DataTables
        if ($request->ajax()) {
            $query = DistributionLoading::select([
                'id',
                'business_id',
                'date_time',
                'loading_no',
                'sales_rep_id',
                'vehicle_id',
                'product_category_id',
                'total_sale_price',
                'created_by',
            ])
                ->with([
                    'salesRep:id,name',
                    'vehicle:id,vehicle_no',
                    'category:id,name',
                    'creator:id,first_name',
                ])
                ->where('business_id', $business_id);

            // Filters
            if ($request->filled('sales_rep_id')) {
                $query->where('sales_rep_id', $request->sales_rep_id);
            }
            if ($request->filled('vehicle_id')) {
                $query->where('vehicle_id', $request->vehicle_id);
            }
            if ($request->filled('product_category_id')) {
                $query->where('product_category_id', $request->product_category_id);
            }
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $start = \Carbon\Carbon::parse($request->start_date)->startOfDay();
                $end   = \Carbon\Carbon::parse($request->end_date)->endOfDay();
                $query->whereBetween('date_time', [$start, $end]);
            }

            return datatables()->of($query)
                ->addColumn('sales_rep', fn($row) => $row->salesRep->name ?? '')
                ->addColumn('vehicle', fn($row) => $row->vehicle->vehicle_no ?? '')
                ->addColumn('category', fn($row) => $row->category->name ?? 'All')
                ->editColumn('total_sale_price', function ($row) {
                    return number_format($row->total_sale_price, 2);
                })
                ->addColumn('creator', fn($row) => $row->creator->first_name ?? '')
                ->addColumn('action', function ($row) {

                    $print_url = action('\Modules\Distribution\Http\Controllers\DistributionLoadingController@print', [$row->id]);
                    $edit_url  = action('\Modules\Distribution\Http\Controllers\DistributionLoadingController@edit', [$row->id]);
                    $view_url  = action('\Modules\Distribution\Http\Controllers\DistributionLoadingController@show', [$row->id]);

                    return '
                        <button class="btn btn-primary btn-xs print_btn"
                            data-href="' . $print_url . '">
                            <i class="fa fa-print" aria-hidden="true"></i> Print
                        </button>

                        <button data-href="' . $view_url . '"
                            data-container=".view_modal"
                            class="btn btn-info btn-xs btn-modal">
                            <i class="fa fa-eye" aria-hidden="true"></i> View
                        </button>

                        <button data-href="' . $edit_url . '"
                            data-container=".edit_modal"
                            class="btn btn-success btn-xs btn-modal">
                            <i class="fa fa-pencil" aria-hidden="true"></i> Edit
                        </button>
                    ';
                })

                ->rawColumns(['action'])
                ->make(true);
        }

        // Filters for the Blade form
        $salesRepQuery = SalesAgent::forBusiness($business_id);
        if (! Schema::hasColumn('distribution_sales_agents', 'deleted_at')) {
            $salesRepQuery = $salesRepQuery->withoutGlobalScopes();
        }
        $salesReps = ['' => __('lang_v1.all')] + $salesRepQuery
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
        $vehicles   = ['' => __('lang_v1.all')] + DistributionVehicles::where('business_id', $business_id)->pluck('vehicle_no', 'id')->toArray();
        $categories = ['' => __('lang_v1.all')] + Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->whereNull('category_type')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        // Check Distribution permissions
        $show_date_picker = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'distribution_show_date_picker');
        $auto_date_time   = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'distribution_auto_date_time');

        return view('distribution::loadings.index', compact('salesReps', 'vehicles', 'categories', 'show_date_picker', 'auto_date_time'));
    }

    public function create(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        // Only show Sales Agents from Sales Agent module
        // Check if deleted_at column exists to handle soft deletes properly
        $query = SalesAgent::forBusiness($business_id);

        // If deleted_at column doesn't exist, bypass soft delete scope
        if (! Schema::hasColumn('distribution_sales_agents', 'deleted_at')) {
            $query = $query->withoutGlobalScopes();
        }

        // Get all sales agents with names (show only Sales Agents names)
        $salesReps = ['' => '-- Select --'] + $query
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        // Show vehicle numbers instead of vehicle type
        $vehiclesArray = DistributionVehicles::where('business_id', $business_id)
            ->orderBy('vehicle_no')
            ->pluck('vehicle_no', 'id')
            ->toArray();
        $vehicles = ['' => '-- Select --'] + $vehiclesArray;

        // Get unique parent categories for this business
        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->whereNull('category_type')
            ->orderBy('name')
            ->distinct()
            ->pluck('name', 'id')
            ->toArray();

        Log::info('Sales Reps count: ' . count($salesReps));
        Log::info('Vehicles count: ' . count($vehicles));
        Log::info('Categories count: ' . count($categories));

        // generate loading_no using distribution_prefix_settings (numbering_type = loading_sheet)
        $loading_no = $this->generateLoadingNumber($business_id);

        // Check Distribution permissions
        $show_date_picker = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'distribution_show_date_picker');
        $auto_date_time   = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'distribution_auto_date_time');

        return view('distribution::loadings.create', compact('salesReps', 'vehicles', 'categories', 'loading_no', 'show_date_picker', 'auto_date_time'));
    }

    public function store(Request $request)
    {
        Log::info('Store request sent: ', $request->all());
        $business_id = $request->session()->get('user.business_id');

        $request->validate([
            'date'                => 'required|date',
            'sales_rep_id'        => 'required',
            'product_id'          => 'required|array|min:1',
            'product_category_id' => 'nullable',
        ]);

        // Validate single product category - all products must belong to the same category
        // Skip validation if "all" is selected
        if ($request->filled('product_category_id') && $request->product_category_id !== 'all') {
            $category_id = $request->product_category_id;
            $product_ids = $request->product_id;

            $productsInCategory = DB::table('products')
                ->whereIn('id', $product_ids)
                ->where('category_id', $category_id)
                ->count();

            if ($productsInCategory != count($product_ids)) {
                return back()->withErrors([
                    'product_category_id' => 'All products must belong to the selected product category.',
                ])->withInput();
            }
        }

        DB::beginTransaction();

        $loading_no = $request->loading_no ?: $this->generateLoadingNumber($business_id);

        $loading = DistributionLoading::create([
            'business_id'             => $business_id,
            'loading_no'              => $loading_no,
            'date_time'               => $request->date,
            'sales_rep_id'            => $request->sales_rep_id,
            'vehicle_id'              => $request->vehicle_id,
            'product_category_id'     => ($request->product_category_id === 'all' || empty($request->product_category_id)) ? null : $request->product_category_id,
            'product_sub_category_id' => ($request->product_sub_category_id === 'all' || empty($request->product_sub_category_id)) ? null : $request->product_sub_category_id,
            'total_sale_price'        => 0,
            'created_by'              => $request->user()->id,
            'status'                  => 'confirmed',
        ]);

        $total = 0;
        foreach ($request->product_id as $i => $product_id) {
            $requested = floatval($request->requested_qty[$i] ?? 0);
            $issued    = floatval($request->issued_qty[$i] ?? 0);
            $price     = floatval($request->unit_sale_price[$i] ?? 0);
            $lineTotal = $issued * $price;

            DistributionLoadingLine::create([
                'loading_id'    => $loading->id,
                'product_id'    => $product_id,
                'unit_id'       => $request->unit_ids[$i] ?? null,
                'requested_qty' => $requested,
                'issued_qty'    => $issued,
                'sale_price'    => $price,
                'line_total'    => $lineTotal,
            ]);

            $total += $lineTotal;
        }

        $loading->update(['total_sale_price' => $total]);

        // Update current_no in prefix settings for loading sheets
        $prefixSetting = DistributionNumberingPrefix::where('business_id', $business_id)
            ->where('numbering_type', 'loading_sheet')
            ->orderBy('id', 'desc')
            ->first();

        if ($prefixSetting) {
            $current = $prefixSetting->current_no ?? $prefixSetting->starting_no ?? 1;
            // Try to extract numeric part from loading_no if possible
            if (preg_match('/\d+$/', $loading->loading_no, $matches)) {
                $numeric = (int) $matches[0];
                if ($numeric > $current) {
                    $current = $numeric;
                }
            }
            $prefixSetting->current_no = $current + 1;
            $prefixSetting->save();
        }

        DB::commit();

        $output = [
            'success' => true,
            'msg'     => __('Products loaded successfully.'),
        ];

        // return redirect()->back()
        //     ->with('status', ['success' => true, 'msg' => 'Products loaded successfully.']);
        return redirect()->back()->with(['status' => $output]);
    }

    public function show($id)
    {
        $loading = DistributionLoading::with([
            'lines.product',
            'salesRep',
            'vehicle',
            'category',
            'subcategory',
        ])->findOrFail($id);

        // 🔹 Attach Available & Vehicle stock for each line
        foreach ($loading->lines as $line) {

            // Available stock (system stock)
            $available = DB::table('variation_location_details')
                ->where('product_id', $line->product_id)
                ->sum('qty_available');

            // Vehicle balance stock
            $vehicleStock = DB::table('distribution_vehicle_stocks')
                ->where('vehicle_id', $loading->vehicle_id)
                ->where('product_id', $line->product_id)
                ->value('qty');

            $line->available_qty       = $available ?? 0;
            $line->vehicle_balance_qty = $vehicleStock ?? 0;
        }

        return view('distribution::loadings.partials.show', compact('loading'));
    }

    public function edit($id)
    {
        $loading     = DistributionLoading::with(['lines.product'])->findOrFail($id);
        $business_id = request()->session()->get('user.business_id');

        $salesRepQuery = SalesAgent::forBusiness($business_id);
        if (! Schema::hasColumn('distribution_sales_agents', 'deleted_at')) {
            $salesRepQuery = $salesRepQuery->withoutGlobalScopes();
        }
        $salesReps = ['' => '-- Select --'] + $salesRepQuery
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $vehicles = ['' => '-- Select --'] + DistributionVehicles::where('business_id', $business_id)
            ->orderBy('vehicle_no')
            ->pluck('vehicle_no', 'id')
            ->toArray();

        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->whereNull('category_type')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $subcategories = Category::where('business_id', $business_id)
            ->where('parent_id', '!=', 0)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        foreach ($loading->lines as $line) {

            // Available stock (system stock)
            $available = DB::table('variation_location_details')
                ->where('product_id', $line->product_id)
                ->sum('qty_available');

            // Vehicle balance stock
            $vehicleStock = DB::table('distribution_vehicle_stocks')
                ->where('vehicle_id', $loading->vehicle_id)
                ->where('product_id', $line->product_id)
                ->value('qty');

            $line->available_qty       = $available ?? 0;
            $line->vehicle_balance_qty = $vehicleStock ?? 0;
        }
        // Check Distribution permissions
        $show_date_picker = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'distribution_show_date_picker');
        $auto_date_time   = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'distribution_auto_date_time');

        return view('distribution::loadings.partials.edit', compact(
            'loading', 'salesReps', 'vehicles', 'categories', 'subcategories', 'show_date_picker', 'auto_date_time'
        ));
    }

    public function update(Request $request, $id)
    {
        $loading = DistributionLoading::findOrFail($id);

        $request->validate([
            'date_time'           => 'required|date',
            'sales_rep_id'        => 'required',
            'product_ids'         => 'required|array|min:1',
            'product_category_id' => 'nullable',
        ]);

        // Validate single product category - all products must belong to the same category
        // Skip validation if "all" is selected
        if ($request->filled('product_category_id') && $request->product_category_id !== 'all') {
            $category_id = $request->product_category_id;
            $product_ids = $request->product_ids;

            $productsInCategory = DB::table('products')
                ->whereIn('id', $product_ids)
                ->where('category_id', $category_id)
                ->count();

            if ($productsInCategory != count($product_ids)) {
                return back()->withErrors([
                    'product_category_id' => 'All products must belong to the selected product category.',
                ])->withInput();
            }
        }

        DB::beginTransaction();

        $loading->update([
            'date_time'               => $request->date_time,
            'sales_rep_id'            => $request->sales_rep_id,
            'vehicle_id'              => $request->vehicle_id,
            'product_category_id'     => ($request->product_category_id === 'all' || empty($request->product_category_id)) ? null : $request->product_category_id,
            'product_sub_category_id' => ($request->product_sub_category_id === 'all' || empty($request->product_sub_category_id)) ? null : $request->product_sub_category_id,
        ]);

        // remove old lines and insert new
        $loading->lines()->delete();

        $total = 0;
        foreach ($request->product_ids as $i => $product_id) {
            $requested = floatval($request->requested_qty[$i] ?? 0);
            $issued    = floatval($request->issued_qty[$i] ?? 0);
            $price     = floatval($request->sale_price[$i] ?? 0);
            $lineTotal = $issued * $price;

            DistributionLoadingLine::create([
                'loading_id'    => $loading->id,
                'product_id'    => $product_id,
                'unit_id'       => $request->unit_ids[$i] ?? null,
                'requested_qty' => $requested,
                'issued_qty'    => $issued,
                'sale_price'    => $price,
                'line_total'    => $lineTotal,
            ]);

            $total += $lineTotal;
        }

        $loading->update(['total_sale_price' => $total]);

        DB::commit();

        $output = ['success' => true, 'msg' => 'Loading updated'];

        if ($request->ajax()) {
            return $output;
        }

        return redirect()->route('distribution.loadings.index')->with('status', $output);
    }

    public function destroy($id)
    {
        $loading = DistributionLoading::findOrFail($id);
        $loading->delete();
        return back()->with('status', ['success' => true, 'msg' => 'Loading deleted']);
    }

    public function getSubcategories(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $category_id = $request->category_id;

        // If 'all' or no category selected, return ALL subcategories for this business
        // so that the Sub Category dropdown can still be fully searchable.
        $query = Category::where('business_id', $business_id)
            ->where('parent_id', '!=', 0);

        if ($category_id && $category_id !== 'all') {
            // Filter subcategories by the selected parent category
            $query->where('parent_id', $category_id);
        }

        return $query->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Returns products for category/subcategory + available qty from products table.
     * Response: [{id,name,available_qty,min_sell_price}]
     */
    public function getProducts(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $category_id     = $request->input('category_id');
        $sub_category_id = $request->input('sub_category_id');
        $vehicle_id      = $request->input('vehicle_id');

        // Normalize values - convert empty strings to null, then cast valid IDs to integers
        $category_id     = ($category_id === '' || $category_id === 'all' || $category_id === null) ? null : (int) $category_id;
        $sub_category_id = ($sub_category_id === '' || $sub_category_id === 'all' || $sub_category_id === null) ? null : (int) $sub_category_id;
        $vehicle_id      = ($vehicle_id === '' || $vehicle_id === null) ? null : (int) $vehicle_id;

        $query = DB::table('products')
            ->join('variations', 'products.id', '=', 'variations.product_id')
            ->leftJoin('variation_store_details', 'variations.id', '=', 'variation_store_details.variation_id')
            ->leftJoin('distribution_vehicle_stocks', function ($join) use ($vehicle_id) {
                $join->on('variations.id', '=', 'distribution_vehicle_stocks.variation_id')
                    ->where('distribution_vehicle_stocks.vehicle_id', '=', $vehicle_id);
            })
            ->where('products.business_id', $business_id);

        // Priority: If subcategory is selected, filter by subcategory only
        if ($sub_category_id !== null) {
            $query->where('products.sub_category_id', '=', $sub_category_id);
        }
        // If no subcategory selected but category is selected, filter by category
        elseif ($category_id !== null) {
            $query->where('products.category_id', '=', $category_id);
        }

        $products = $query
            ->select(
                'products.id as product_id',
                'products.name',
                'products.sub_category_id',
                'variations.id as variation_id',
                DB::raw('COALESCE(variation_store_details.qty_available, 0) AS available_qty'),
                DB::raw('COALESCE(distribution_vehicle_stocks.qty, 0) AS vehicle_balance_qty'),
                DB::raw('COALESCE(variations.sell_price_inc_tax, variations.default_sell_price) AS sale_price')
            )
            ->distinct()
            ->orderBy('products.name', 'asc')
            ->get();

        return response()->json($products);
    }

    // helper to generate loading number (implement according to your numbering settings)
    protected function generateLoadingNumber($business_id)
    {
        $setting = DistributionNumberingPrefix::where('business_id', $business_id)
            ->where('numbering_type', 'loading_sheet')
            ->orderBy('id', 'desc')
            ->first();

        if ($setting) {
            $next   = $setting->current_no ?? $setting->starting_no ?? 1; // start from starting_no
            $prefix = $setting->prefix ?? '';
            $padded_next = str_pad($next, 4, '0', STR_PAD_LEFT);

            return $prefix . $padded_next;
        }

        // Fallback: if no prefix settings
        $last = DistributionLoading::where('business_id', $business_id)
            ->orderBy('id', 'desc')
            ->first();

        $next = 1;

        if ($last && $last->loading_no) {
            if (preg_match('/\d+$/', $last->loading_no, $matches)) {
                $next = ((int) $matches[0]) + 1;
            }
        }

        $padded_next = str_pad($next, 4, '0', STR_PAD_LEFT);
        return 'LD-' . $padded_next;
    }

    /**
     * Vehicle stock qty for a product (distribution_vehicle_stocks)
     */
    public function getVehicleStock(Request $request)
    {
        $vehicleId = $request->vehicle_id;
        $productId = $request->product_id;

        $stock = DB::table('distribution_vehicle_stocks')
            ->where('vehicle_id', $vehicleId)
            ->where('product_id', $productId)
            ->select('qty')
            ->first();

        return response()->json(['vehicle_balance_qty' => $stock ? (float) $stock->qty : 0]);
    }

    public function print($id)
    {
        $loading = DistributionLoading::with([
            'lines.product',
            'salesRep',
            'vehicle',
            'category',
            'subcategory',
        ])->findOrFail($id);

        $business_id       = request()->session()->get('user.business_id');
        $business_location = BusinessLocation::where('business_id', $business_id)->first();

        // 🔹 Add stock details for each line
        foreach ($loading->lines as $line) {

            // Available qty from product stock table
            $available = DB::table('variation_location_details')
                ->where('product_id', $line->product_id)
                ->sum('qty_available');

            // Vehicle balance qty
            $vehicleStock = DB::table('distribution_vehicle_stocks')
                ->where('vehicle_id', $loading->vehicle_id)
                ->where('product_id', $line->product_id)
                ->value('qty');

            $line->available_qty       = $available ?? 0;
            $line->vehicle_balance_qty = $vehicleStock ?? 0;
        }

        $business_details = Business::find($business_id);

        $currency_precision = ! empty($business_details) &&
        ! empty($business_details->currency_precision)
            ? $business_details->currency_precision
            : config('constants.currency_precision', 2);

        $quantity_precision = ! empty($business_details) &&
        ! empty($business_details->quantity_precision)
            ? $business_details->quantity_precision
            : config('constants.quantity_precision', 2);

        $report_footer = System::getProperty('admin_reports_footer');

        $html = view(
            'distribution::loadings.print',
            compact(
                'loading',
                'business_location',
                'currency_precision',
                'quantity_precision',
                'report_footer'
            )
        )->render();

        return app(GlobalPdfService::class)->stream(
            $html,
            'LoadingSheet_' . $loading->loading_no . '.pdf',
            ['format' => 'A4', 'orientation' => 'P'],
            [
                'page_title' => 'Distribution Loading Sheet',
                'business_location' => optional($business_location)->name ?: 'All Locations',
                'date_range' => $loading->loading_date ?? $loading->created_at ?? 'All Dates',
            ]
        );
    }

}

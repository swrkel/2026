<?php

namespace Modules\MPCS\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\MPCS\Entities\FormF18Detail;
use Modules\MPCS\Entities\FormF18Header;
use Modules\MPCS\Entities\FormF18PrefixNumber;
use Yajra\DataTables\Facades\DataTables;

class F18FormController extends Controller
{
    /**
     * Show F 18 main page (Form + Prefix & Numbers tab).
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $business_id = request()->session()->get('business.id') ?? request()->session()->get('user.business_id');

        $business = Business::findOrFail($business_id);
        $qty_precision = (int) ($business->quantity_precision ?? 2);

        $business_locations = BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)
            ->pluck('name', 'id');

        // Logged-in user's permitted locations
        $permitted_locations = auth()->user()->permitted_locations();
        $default_location_id = null;

        if ($permitted_locations !== 'all') {
            $business_locations = $business_locations->only($permitted_locations);
        }

        if ($business_locations->count() === 1) {
            $default_location_id = $business_locations->keys()->first();
        }

        $today = now()->toDateString();

        $transferred_locations_list = $business_locations->toArray();

        $activeSettings = FormF18PrefixNumber::where('business_id', $business_id)
            ->whereDate('opening_date', '<=', $today)
            ->orderByDesc('opening_date')
            ->orderByDesc('id')
            ->get();

        // Build "Transferred to" dropdown: split each prefix record's text by comma into individual options.
        // Key = prefix_id * 1000 + location_index (synthetic ID), value = individual location text.
        $f18_to_locations = [];
        foreach ($activeSettings as $setting) {
            $raw = is_array($setting->transferred_locations)
                ? ($setting->transferred_locations[0] ?? '')
                : ($setting->transferred_locations ?? '');
            $parts = array_values(array_filter(array_map('trim', explode(',', $raw))));
            foreach ($parts as $i => $loc) {
                $f18_to_locations[$setting->id * 1000 + $i] = $loc;
            }
        }

        /*
         * IS2013: never leave the "Transferred to" dropdown empty.
         *
         * These options come only from the comma-separated list on the F18
         * prefix settings. When no setting exists for the date (or its list is
         * blank) the select rendered with NO options at all, so the form posted
         * to_location_id = 0 - a value that can never decode back to a name.
         * That is the root of all three reported blanks: the list column, the
         * print preview and the view screen.
         *
         * Falling back to the business's own locations keeps the field usable,
         * and resolveToLocationText() reads these ids back correctly because it
         * also checks business_locations.
         */
        if ($f18_to_locations === []) {
            foreach ($business_locations as $loc_id => $loc_name) {
                $f18_to_locations[(int) $loc_id] = $loc_name;
            }
        }

        $to_keys = array_keys($f18_to_locations);
        $auto_to = count($to_keys) === 1 ? (int) $to_keys[0] : null;

        // Categories and products for filters
        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');

        $sub_categories = Category::where('business_id', $business_id)
            ->where('parent_id', '!=', 0)
            ->pluck('name', 'id');

        $products = Product::where('business_id', $business_id)
            ->pluck('name', 'id');

        $prefix_numbers = FormF18PrefixNumber::where('business_id', $business_id)
            ->orderByDesc('opening_date')
            ->orderByDesc('id')
            ->get();

        $F18_form_no = null;

        if ($auto_to && $activeSettings->isNotEmpty()) {
            $prefix_id_auto = $auto_to >= 1000 ? intdiv($auto_to, 1000) : $auto_to;
            $matchingSetting = $activeSettings->firstWhere('id', $prefix_id_auto) ?? $activeSettings->first();

            $existingCount = FormF18Header::where('business_id', $business_id)
                ->whereRaw('(CASE WHEN to_location_id < 1000 THEN to_location_id ELSE FLOOR(to_location_id / 1000) END) = ?', [$prefix_id_auto])
                ->whereDate('form_date', '>=', $matchingSetting->opening_date)
                ->count();

            $nextNumber = $matchingSetting->starting_number + $existingCount;
            $F18_form_no = $matchingSetting->prefix
                ? ($matchingSetting->prefix . ' ' . $nextNumber)
                : (string) $nextNumber;
        }

        $f18_numbers = FormF18Header::where('business_id', $business_id)
            ->pluck('form_no', 'form_no');

        $f18_users = \App\User::whereIn('id', function($q) use ($business_id) {
                $q->select('created_by')->from('form_f18_headers')->where('business_id', $business_id);
            })->select('id', \DB::raw('CONCAT(COALESCE(first_name, ""), " ", COALESCE(last_name, "")) as full_name'))
            ->pluck('full_name', 'id');

        return view('mpcs::forms.F18.index', compact(
            'business',
            'business_locations',
            'transferred_locations_list',
            'f18_to_locations',
            'auto_to',
            'default_location_id',
            'categories',
            'sub_categories',
            'products',
            'prefix_numbers',
            'F18_form_no',
            'f18_numbers',
            'f18_users',
            'qty_precision'
        ));
    }

    /**
     * Store a new Prefix & Numbers row for F 18.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function storePrefixNumbers(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');

        $validated = $request->validate([
            'opening_date'           => ['required', 'date'],
            'prefix'                 => ['nullable', 'string', 'max:50'],
            'starting_number'        => ['required', 'integer', 'min:1'],
            'transferred_locations'  => ['nullable', 'string', 'max:500'],
        ]);

        $record = FormF18PrefixNumber::create([
            'business_id'           => $business_id,
            'opening_date'          => $validated['opening_date'],
            'prefix'                => $validated['prefix'] ?? null,
            'starting_number'       => $validated['starting_number'],
            'transferred_locations' => isset($validated['transferred_locations']) ? [$validated['transferred_locations']] : [],
            'created_by'            => Auth::id(),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'msg' => __('mpcs::lang.success'),
                'data' => $record,
            ]);
        }

        return redirect()
            ->back()
            ->with('status', [
                'success' => true,
                'msg' => __('mpcs::lang.success'),
            ]);
    }

    /**
     * Return products list for the Add row selector with filters.
     */
    public function getProducts(Request $request)
    {
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');

        $query = Product::where('business_id', $business_id);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('sub_category_ids')) {
            $query->whereIn('sub_category_id', (array)$request->input('sub_category_ids'));
        }

        $products = $query->orderBy('name')
            ->select('id', 'name')
            ->get();

        return response()->json($products);
    }

    /**
     * Fetch snapshot prices for a product at both locations (issued & received).
     * Prices are inclusive of taxes and won't be changed after saving.
     */
    public function getProductPrices(Request $request)
    {
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
        $product_id = (int)$request->input('product_id');

        if (!$product_id) {
            return response()->json(['success' => false, 'msg' => 'Invalid product'], 422);
        }

        // Basic implementation: use default variation prices (inclusive of tax)
        $row = DB::table('variations')
            ->join('products', 'products.id', '=', 'variations.product_id')
            ->where('products.business_id', $business_id)
            ->where('products.id', $product_id)
            ->select(
                'variations.dpp_inc_tax as purchase_inc_tax',
                'variations.sell_price_inc_tax as sale_inc_tax'
            )
            ->first();

        if (!$row) {
            return response()->json(['success' => false, 'msg' => 'Product prices not found'], 404);
        }

        // Same snapshot used for both issued and received sections
        return response()->json([
            'success' => true,
            'issued_purchase_unit_price' => (float)($row->purchase_inc_tax ?? 0),
            'issued_sale_unit_price' => (float)($row->sale_inc_tax ?? 0),
            'received_purchase_unit_price' => (float)($row->purchase_inc_tax ?? 0),
            'received_sale_unit_price' => (float)($row->sale_inc_tax ?? 0),
        ]);
    }

    /**
     * Store a new F18 form (header + details) from the client table.
     */
    public function store(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');

        $validated = $request->validate([
            'form_date' => ['required', 'date'],
            'from_location_id' => ['required', 'integer'],
            'to_location_id' => ['required', 'integer'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.product_id' => ['required', 'integer'],
            'rows.*.qty' => ['required', 'numeric', 'min:0.0001'],
        ]);

        DB::beginTransaction();

        try {
            $date = $validated['form_date'];
            $to_location_id = (int) $validated['to_location_id'];

            $settings = FormF18PrefixNumber::where('business_id', $business_id)
                ->whereDate('opening_date', '<=', $date)
                ->orderByDesc('opening_date')
                ->orderByDesc('id')
                ->get();

            $prefix_id = $to_location_id >= 1000 ? intdiv($to_location_id, 1000) : $to_location_id;
            $matchingSetting = $settings->firstWhere('id', $prefix_id) ?? $settings->first();

            $baseNumber = $matchingSetting?->starting_number ?? 1;
            $prefix = $matchingSetting?->prefix;
            $openingDate = $matchingSetting?->opening_date ?? '2000-01-01';

            $existingCount = FormF18Header::where('business_id', $business_id)
                ->whereRaw('(CASE WHEN to_location_id < 1000 THEN to_location_id ELSE FLOOR(to_location_id / 1000) END) = ?', [$prefix_id])
                ->whereDate('form_date', '>=', $openingDate)
                ->count();

            $nextNumber = $baseNumber + $existingCount;
            $form_no = $prefix ? ($prefix . ' ' . $nextNumber) : (string)$nextNumber;
            $next_form_no = $prefix ? ($prefix . ' ' . ($nextNumber + 1)) : (string)($nextNumber + 1);

            $header = FormF18Header::create([
                'business_id' => $business_id,
                'form_no' => $form_no,
                'from_location_id' => $validated['from_location_id'],
                'to_location_id' => $validated['to_location_id'],
                'form_date' => $validated['form_date'],
                'created_by' => Auth::id(),
            ]);

            $rawRows = $request->input('rows', []);
            foreach ($validated['rows'] as $index => $row) {
                $raw = $rawRows[$index] ?? [];
                $qty = (float)$row['qty'];
                $issued_purchase_unit_price   = (float)($raw['issued_purchase_unit_price'] ?? 0);
                $issued_sale_unit_price       = (float)($raw['issued_sale_unit_price'] ?? 0);
                $received_purchase_unit_price = (float)($raw['received_purchase_unit_price'] ?? 0);
                $received_sale_unit_price     = (float)($raw['received_sale_unit_price'] ?? 0);

                FormF18Detail::create([
                    'header_id' => $header->id,
                    'business_id' => $business_id,
                    'product_id' => $row['product_id'],
                    'from_location_id' => $validated['from_location_id'],
                    'to_location_id' => $validated['to_location_id'],
                    'qty' => $qty,
                    'issued_purchase_unit_price' => $issued_purchase_unit_price,
                    'issued_purchase_total' => $issued_purchase_unit_price * $qty,
                    'issued_sale_unit_price' => $issued_sale_unit_price,
                    'issued_sale_total' => $issued_sale_unit_price * $qty,
                    'received_purchase_unit_price' => $received_purchase_unit_price,
                    'received_purchase_total' => $received_purchase_unit_price * $qty,
                    'received_sale_unit_price' => $received_sale_unit_price,
                    'received_sale_total' => $received_sale_unit_price * $qty,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('mpcs::lang.success'),
                'id' => $header->id,
                'current_form_no' => $form_no,
                'next_form_no' => $next_form_no,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'msg' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Return the next F18 form number for a given Transferred To location.
     */
    public function getFormNo(Request $request): \Illuminate\Http\JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
        $to_location_id = (int) $request->input('to_location_id');
        $form_date = $request->input('form_date', now()->toDateString());

        if (!$to_location_id) {
            return response()->json(['form_no' => null]);
        }

        $settings = FormF18PrefixNumber::where('business_id', $business_id)
            ->whereDate('opening_date', '<=', $form_date)
            ->orderByDesc('opening_date')
            ->orderByDesc('id')
            ->get();

        $prefix_id = $to_location_id >= 1000 ? intdiv($to_location_id, 1000) : $to_location_id;
        $matchingSetting = $settings->firstWhere('id', $prefix_id) ?? $settings->first();

        if (!$matchingSetting) {
            return response()->json(['form_no' => null]);
        }

        $existingCount = FormF18Header::where('business_id', $business_id)
            ->whereRaw('(CASE WHEN to_location_id < 1000 THEN to_location_id ELSE FLOOR(to_location_id / 1000) END) = ?', [$prefix_id])
            ->whereDate('form_date', '>=', $matchingSetting->opening_date)
            ->count();

        $nextNumber = $matchingSetting->starting_number + $existingCount;
        $form_no = $matchingSetting->prefix
            ? ($matchingSetting->prefix . ' ' . $nextNumber)
            : (string) $nextNumber;

        return response()->json(['form_no' => $form_no]);
    }

    /**
     * AJAX DataTable endpoint for the List F18 Forms tab.
     */
    public function listData(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');

        $query = FormF18Header::query()
            ->where('form_f18_headers.business_id', $business_id)
            ->leftJoin('business', 'business.id', '=', 'form_f18_headers.business_id')
            ->leftJoin('business_locations as loc_from', 'loc_from.id', '=', 'form_f18_headers.from_location_id')
            ->leftJoin(DB::raw('form_f18_prefix_numbers as pfx_to'), function ($join) {
                $join->on(DB::raw('pfx_to.id'), '=', DB::raw(
                    'CASE WHEN form_f18_headers.to_location_id < 1000 THEN form_f18_headers.to_location_id ELSE FLOOR(form_f18_headers.to_location_id / 1000) END'
                ));
            })
            ->leftJoin('users as creator', 'creator.id', '=', 'form_f18_headers.created_by')
            ->leftJoin('form_f18_details', 'form_f18_details.header_id', '=', 'form_f18_headers.id')
            ->leftJoin('products', 'products.id', '=', 'form_f18_details.product_id')
            ->select(
                'form_f18_headers.id',
                'form_f18_headers.form_date',
                'form_f18_headers.form_no',
                'form_f18_headers.from_location_id',
                'form_f18_headers.to_location_id',
                'business.name as business_name',
                'loc_from.name as from_location_name',
                'pfx_to.transferred_locations as to_location_name',
                \DB::raw('GROUP_CONCAT(DISTINCT products.name ORDER BY products.name SEPARATOR ", ") as product_names'),
                \DB::raw('CONCAT(COALESCE(creator.first_name, ""), " ", COALESCE(creator.last_name, "")) as created_by_name'),
                \DB::raw('SUM(form_f18_details.issued_purchase_total) as total_issued_purchase'),
                \DB::raw('SUM(form_f18_details.issued_sale_total) as total_issued_sale'),
                \DB::raw('SUM(form_f18_details.received_purchase_total) as total_received_purchase'),
                \DB::raw('SUM(form_f18_details.received_sale_total) as total_received_sale')
            )
            ->groupBy(
                'form_f18_headers.id',
                'form_f18_headers.form_date',
                'form_f18_headers.form_no',
                'form_f18_headers.from_location_id',
                'form_f18_headers.to_location_id',
                'business.name',
                'loc_from.name',
                'pfx_to.transferred_locations',
                'creator.first_name',
                'creator.last_name'
            );

        // Filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('form_f18_headers.form_date', [
                $request->input('start_date'),
                $request->input('end_date'),
            ]);
        }
        if ($request->filled('location_id')) {
            $query->where('form_f18_headers.from_location_id', $request->input('location_id'));
        }
        if ($request->filled('form_no')) {
            $query->where('form_f18_headers.form_no', $request->input('form_no'));
        }
        if ($request->filled('user_id')) {
            $query->where('form_f18_headers.created_by', $request->input('user_id'));
        }

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $viewUrl  = route('F18.show',  $row->id);
                $printUrl = route('F18.print', $row->id);
                return '<a href="' . $viewUrl . '" class="btn btn-xs btn-info" target="_blank"><i class="fa fa-eye"></i> View</a>';
            })
            ->editColumn('form_date', fn($r) => \Carbon\Carbon::parse($r->form_date)->format('Y-m-d'))
            ->editColumn('to_location_name', function ($r) use ($business_id) {
                // IS2013: was a third copy of the decode, ending in a bare '—'.
                // This is why the Received Location column showed a dash.
                return $this->resolveToLocationText($r->to_location_id, $business_id);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * IS2013: resolve the "Transferred to" location text for a stored id.
     *
     * to_location_id is a SYNTHETIC key: prefix_id * 1000 + index_into_csv,
     * where the CSV lives in form_f18_prefix_numbers.transferred_locations.
     * Decoding it was copy-pasted into three places (the datatable column,
     * show() and printForm()), each ending in a bare '—'. That is why the same
     * blank appeared in the list column, the print preview and the view screen:
     * one decode failure surfacing three times.
     *
     * Two things could make it fail. The prefix row may be missing or its CSV
     * may no longer contain that index - a setting edited after forms were
     * saved. Or the id may not be synthetic at all: when no F18 prefix settings
     * exist, the dropdown is EMPTY, and older saved rows can carry a plain
     * business_locations id instead.
     *
     * So the lookup now falls back to business_locations before giving up, and
     * every caller uses this one method.
     */
    private function resolveToLocationText($toLocationId, $businessId = null): string
    {
        $toLocationId = (int) $toLocationId;

        if ($toLocationId <= 0) {
            return '—';
        }

        $prefixId = $toLocationId >= 1000 ? intdiv($toLocationId, 1000) : $toLocationId;
        $locIndex = $toLocationId >= 1000 ? ($toLocationId % 1000) : 0;

        $prefix = FormF18PrefixNumber::find($prefixId);

        if ($prefix) {
            $raw = is_array($prefix->transferred_locations)
                ? ($prefix->transferred_locations[0] ?? '')
                : ($prefix->transferred_locations ?? '');

            $parts = array_values(array_filter(array_map('trim', explode(',', $raw))));

            if (isset($parts[$locIndex]) && $parts[$locIndex] !== '') {
                return $parts[$locIndex];
            }

            // The CSV changed after this form was saved; the first entry is a
            // better answer than a dash, but only when one exists.
            if (isset($parts[0]) && $parts[0] !== '') {
                return $parts[0];
            }
        }

        /*
         * Fallback: treat the value as a real business location id. This covers
         * forms saved while no F18 prefix settings existed, which is the state
         * that produced the reported blanks.
         */
        $location = \App\BusinessLocation::when(
            $businessId,
            fn ($query) => $query->where('business_id', $businessId)
        )->find($toLocationId);

        return $location->name ?? '—';
    }

    /**
     * View a single F18 form.
     */
    public function show($id)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $business_id = request()->session()->get('business.id') ?? request()->session()->get('user.business_id');
        $header = FormF18Header::where('business_id', $business_id)->findOrFail($id);
        $details = FormF18Detail::where('header_id', $id)
            ->with('product')
            ->get();

        $business        = Business::findOrFail($business_id);
        $qty_precision   = (int) ($business->quantity_precision ?? 2);
        $from_location   = \App\BusinessLocation::find($header->from_location_id);
        // IS2013: single shared decode - see resolveToLocationText().
        $to_location_text = $this->resolveToLocationText($header->to_location_id, $business_id);
        $created_by_user = \App\User::find($header->created_by);

        return view('mpcs::forms.F18.show', compact(
            'business', 'header', 'details', 'from_location', 'to_location_text', 'created_by_user', 'qty_precision'
        ));
    }

    /**
     * Print a single F18 form.
     */
    public function printForm($id)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $business_id = request()->session()->get('business.id') ?? request()->session()->get('user.business_id');
        $header = FormF18Header::where('business_id', $business_id)->findOrFail($id);
        $details = FormF18Detail::where('header_id', $id)
            ->with('product')
            ->get();

        $business        = Business::findOrFail($business_id);
        $qty_precision   = (int) ($business->quantity_precision ?? 2);
        $from_location   = \App\BusinessLocation::find($header->from_location_id);
        // IS2013: single shared decode - see resolveToLocationText().
        $to_location_text = $this->resolveToLocationText($header->to_location_id, $business_id);
        $created_by_user = \App\User::find($header->created_by);

        return view('mpcs::forms.F18.print', compact(
            'business', 'header', 'details', 'from_location', 'to_location_text', 'created_by_user', 'qty_precision'
        ));
    }
}


<?php

namespace Modules\MPCS\Http\Controllers;

use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\Store;
use App\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\MPCS\Entities\MpcsFormSetting;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\MPCS\Entities\FormF17Detail;
use Modules\MPCS\Entities\FormF17Header;
use Modules\MPCS\Entities\FormF17HeaderController;
use Modules\MPCS\Entities\FormF22Header;

class F17FormController extends Controller
{

    /**
     * MA-002 PERF: request-scoped cache of F17 detail rows by header.
     *
     * The list has two columns - select_mode and page_no - and EACH ran
     *     self::ma002F17Details($row->id)
     * for the SAME row, fetching the identical collection twice per row.
     *
     * Cached by header id, so both columns share one fetch. The data is
     * still fetched per row - nothing is shared between rows that should not
     * be - it is simply not fetched twice for the same row.
     *
     * This is a read-only listing, so the details cannot change while the
     * table renders.
     */
    private static array $ma002F17DetailCache = [];

    private static function ma002F17Details($headerId)
    {
        if (empty($headerId)) {
            return collect();
        }

        if (! array_key_exists($headerId, self::$ma002F17DetailCache)) {
            self::$ma002F17DetailCache[$headerId] = FormF17Detail::where('header_id', $headerId)->get();
        }

        return self::$ma002F17DetailCache[$headerId];
    }

    /**
     * All Utils instance.
     *
     */
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;
    protected $util;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, Util $util)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->util = $util;
    }


    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        $business_id = request()->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id
            ?? request()->session()->get('business.id');

        abort_if(empty($business_id), 403, 'Business context is missing.');

        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
        $count = FormF17Header::where('business_id', $business_id)->count();
        if (!empty($settings)) {
            $F17_from_no = $settings->F17_form_sn + $count;
        } else {
            $F17_from_no = 1 + $count;
        }

        $stores = Store::where('business_id', $business_id)->pluck('name', 'id');
        $products = Product::where('business_id', $business_id)->pluck('name', 'id');

        // Check if petro module is enabled to show Fuel category
        $enable_petro_module = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_module');

        $categories = Category::forDropdown($business_id, $enable_petro_module);
        $sub_categories = Category::subCategoryforDropdown($business_id, $enable_petro_module);

        $brands = Brands::forDropdown($business_id);

        $units = Unit::forDropdown($business_id);

        // Fetch all business locations without permission filtering
        $business_locations = BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)
            ->orderBy('id', 'asc')
            ->select(DB::raw("IF(location_id IS NULL OR location_id='', name, CONCAT(name, ' (', location_id, ')')) AS name"), 'id')
            ->pluck('name', 'id');
        $default_location_id = $business_locations->keys()->first();
        $default_location_name = $default_location_id !== null
            ? $business_locations->get($default_location_id)
            : '';

        $forms_nos = FormF17Header::where('business_id', $business_id)->pluck('form_no', 'id');

        return view('mpcs::forms.F17.index')->with(compact(
            'stores',
            'products',
            'categories',
            'sub_categories',
            'brands',
            'units',
            'business_locations',
            'F17_from_no',
            'forms_nos',
            'default_location_id',
            'default_location_name'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $selected_date = request()->start_date;
            
            $products = Product::leftjoin('variations', 'products.id', 'variations.product_id')
                ->leftjoin('variation_location_details as vld', 'variations.id', 'vld.variation_id')
                ->leftjoin('categories', 'products.category_id', 'categories.id')
                ->leftJoin('form_f17_details', function($join) use ($business_id, $selected_date) {
                    $join->on('form_f17_details.product_id', '=', 'products.id')
                         ->whereRaw('form_f17_details.id = (
                             SELECT MAX(id) 
                             FROM form_f17_details 
                             WHERE product_id = products.id 
                             AND header_id IN (
                                 SELECT id FROM form_f17_headers 
                                 WHERE business_id = ? 
                                 AND date <= ?
                             )
                         )', [$business_id, $selected_date ?: date('Y-m-d')]);
                })
                ->leftJoin('form_f17_headers', 'form_f17_details.header_id', '=', 'form_f17_headers.id')
                ->where('products.business_id', $business_id)
                ->select(
                    'products.id as p_id',
                    'products.name as product',
                    'products.sku as sku',
                    'variations.id as variation_id',
                    'variations.default_sell_price',
                    'variations.sell_price_inc_tax',
                    'categories.name as category_name',
                    'form_f17_details.new_price as f17_new_price',
                    'form_f17_details.unit_price as f17_original_price',
                    DB::raw('IFNULL(vld.qty_available,0) as current_stock')
                );

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $products->whereIn('vld.location_id', $permitted_locations);
            }

            if (!empty(request()->location_id)) {
                $products->where('vld.location_id', request()->location_id);
            }
            if (!empty(request()->category_id)) {
                $products->where('products.category_id', request()->category_id);
            }
            if (!empty(request()->sub_category_id)) {
                $products->where('products.sub_category_id', request()->sub_category_id);
            }
            if (!empty(request()->brand_id)) {
                $products->where('products.brand_id', request()->brand_id);
            }
            if (!empty(request()->product_id)) {
                $products->where('products.id', request()->product_id);
            }
            if (!empty(request()->unit_id)) {
                $unitId = request()->unit_id;
                $products->where(function ($q) use ($unitId) {
                    $q->where('products.unit_id', $unitId)
                        ->orWhereJsonContains('products.sub_unit_ids', $unitId);
                });
            }


            $business_id = session()->get('user.business_id');
            $business_details = Business::find($business_id);

            return DataTables::of($products)
                ->addIndexColumn()
                ->editColumn('product', function ($row) use ($business_details) {
                    return '<span>' . $row->product . '</span><input type="hidden" value="' . $row->product . '" name="F17[' . $row->p_id . '][product]" id="F17[' . $row->p_id . '][product]">';
                })
                ->editColumn('sku', function ($row) use ($business_details) {
                    return '<span>' . $row->sku . '</span><input type="hidden" value="' . $row->sku . '" name="F17[' . $row->p_id . '][sku]" id="F17[' . $row->p_id . '][sku]">';
                })
                ->editColumn('unit_price', function ($row) use ($business_details) {
                    // dd($row);
                    // Use the new price from F17 form if available, otherwise use original price
                    $unitPriceWithTax = 0;
                    if (!empty($row->f17_new_price) && $row->f17_new_price > 0) {
                        $unitPriceWithTax = $row->f17_new_price;
                    } else {
                        $unitPriceWithTax = $row->sell_price_inc_tax 
                            ?? $row->default_sell_price 
                            ?? 0;
                    }

                    $formatted_price = $this->productUtil->num_f(
                        $unitPriceWithTax,
                        false,
                        $business_details,
                        false
                    );

                    return '<span class="display_currency unit_price text-right" 
                                data-orig-value="' . $unitPriceWithTax . '">'
                            . $formatted_price .
                        '</span>
                        <input type="hidden" value="' . $unitPriceWithTax . '" 
                            name="F17[' . $row->p_id . '][unit_price]">';
                })
                ->editColumn('current_stock', function ($row) use ($business_details) {
                    $current_stock = $row->current_stock;
                    
                    // If it is a fuel item, fetch the stock sum from associated fuel tanks
                    if ($row->category_name == 'Fuel') {
                        $location_id = request()->location_id;
                        if (!empty($location_id)) {
                            $current_stock = $this->transactionUtil->getFuelStockByProductId($row->p_id, $location_id);
                        }
                    }

                    // Show with 3 decimals for Qty
                    $formatted_stock = number_format($current_stock, 3, '.', '');
                    return '<span  class="current_stock" data-orig-value="' . $current_stock . '">' . $formatted_stock . '</span><input type="hidden" value="' . $current_stock . '" name="F17[' . $row->p_id . '][current_stock]" id="F17[' . $row->p_id . '][current_stock]">';
                })
                ->addColumn('select_mode', function ($row) use ($business_details) {
                    $html = '<select name="F17[' . $row->p_id . '][select_mode]" id="F17[' . $row->p_id . '][select_mode]" class="form-control select_mode input_number" placeholder="Please Select">
                        <option value="increase">Increase</option>
                        <option value="decrease">Decrease</option>
                    </select>';
                    return $html;
                })
                ->addColumn('unit_price_difference', function ($row) use ($business_details) {
                    return '<span class="display_currency unit_price_difference" data-orig-value="' . $row->unit_price_difference . '" data-currency_symbol = "false"></span><input type="hidden" name="F17[' . $row->p_id . '][unit_price_difference]" id="F17[' . $row->p_id . '][unit_price_difference]" class="unit_price_difference_value">';
                })
                ->addColumn('new_price', function ($row) {
                    return '<input type="text" style="width: 60px;" name="F17[' . $row->p_id . '][new_price]" id="F17[' . $row->p_id . '][new_price]" class="form-control input_number new_price_value">';
                })
                ->addColumn('price_changed_loss', function ($row) {
                    return '<span class="display_currency price_changed_loss" data-orig-value="" data-currency_symbol = "false"></span><input type="hidden" name="F17[' . $row->p_id . '][price_changed_loss]" id="F17[' . $row->p_id . '][price_changed_loss]" class="price_changed_loss_value">';
                })
                ->addColumn('price_changed_gain', function ($row) {
                    return '<span class="display_currency price_changed_gain" data-orig-value="" data-currency_symbol = "false"></span><input type="hidden" name="F17[' . $row->p_id . '][price_changed_gain]" id="F17[' . $row->p_id . '][price_changed_gain]" class="price_changed_gain_value">';
                })
                ->addColumn('page_no', function ($row) {
                    return '<input type="text" style="width: 60px;" name="F17[' . $row->p_id . '][page_no]" id="F17[' . $row->p_id . '][page_no]" class="form-control input_number page_no">';
                })
                ->addColumn('signature', function ($row) {
                    return '';
                })
                ->removeColumn('id')

                ->rawColumns(['sku', 'unit_price', 'current_stock', 'product', 'select_mode', 'unit_price_difference', 'new_price', 'price_changed_loss', 'price_changed_gain', 'page_no'])
                ->make(true);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $business_id = (int) (
            $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
        );
        $expects_json = $request->ajax() || $request->wantsJson();

        $respondWithError = function ($message, $status = 422) use ($expects_json) {
            if ($expects_json) {
                return response()->json([
                    'success' => 0,
                    'msg' => $message,
                ], $status);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $message);
        };

        if ($business_id <= 0 || ! auth()->check()) {
            return $respondWithError(__('messages.unauthorized_action'), 403);
        }

        /*
         * Accept both submission formats:
         *  1. Native HTML form fields: F17[product_id][...]
         *  2. Legacy Ajax payload: data=F17%5Bproduct_id%5D...
         *
         * Native form submission is the primary path. This keeps Save working even
         * when an unrelated global JavaScript file has a parse/runtime error.
         */
        $rows = $request->input('F17', []);

        if ((empty($rows) || ! is_array($rows)) && is_string($request->input('data'))) {
            $legacy_data = [];
            parse_str($request->input('data'), $legacy_data);
            $rows = $legacy_data['F17'] ?? [];
        }

        $date_input = $request->input('f17_date', $request->input('date'));
        $location_id = (int) $request->input('location_id');

        if (empty($date_input)) {
            return $respondWithError(__('validation.required', [
                'attribute' => __('mpcs::lang.date'),
            ]));
        }

        if ($location_id <= 0) {
            return $respondWithError(__('validation.required', [
                'attribute' => __('purchase.business_location'),
            ]));
        }

        $location_exists = BusinessLocation::where('business_id', $business_id)
            ->where('id', $location_id)
            ->where('is_active', 1)
            ->exists();

        if (! $location_exists) {
            return $respondWithError(__('messages.unauthorized_action'), 403);
        }

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations !== 'all') {
            $permitted_locations = array_map('intval', (array) $permitted_locations);

            if (! in_array($location_id, $permitted_locations, true)) {
                return $respondWithError(__('messages.unauthorized_action'), 403);
            }
        }

        try {
            $form_date = Carbon::parse($date_input)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return $respondWithError(__('validation.date', [
                'attribute' => __('mpcs::lang.date'),
            ]));
        }

        if (empty($rows) || ! is_array($rows)) {
            return $respondWithError(__('mpcs::lang.please_add_products_first'));
        }

        $toDecimal = static function ($value) {
            if ($value === null || $value === '') {
                return null;
            }

            if (is_string($value)) {
                $value = str_replace([',', ' '], '', trim($value));
            }

            return is_numeric($value) ? (float) $value : null;
        };

        /*
         * Save only rows where a new price was actually entered. Blank rows belong
         * to the DataTable display and must not create zero-value F17 detail records.
         */
        $changed_rows = [];

        foreach ($rows as $product_id => $row) {
            if (! is_array($row)) {
                continue;
            }

            $new_price = $toDecimal($row['new_price'] ?? null);

            if ($new_price === null || $new_price <= 0) {
                continue;
            }

            $changed_rows[(int) $product_id] = $row;
        }

        if (empty($changed_rows)) {
            return $respondWithError('Please enter at least one valid new price before saving.');
        }

        $nullableInteger = static function ($value) {
            return ($value === '' || $value === null) ? null : (int) $value;
        };

        DB::beginTransaction();

        try {
            $settings = MpcsFormSetting::where('business_id', $business_id)->first();
            $count = FormF17Header::where('business_id', $business_id)->count();

            $form_no = ! empty($settings)
                ? ((int) $settings->F17_form_sn + $count)
                : (1 + $count);

            $header = FormF17Header::create([
                'business_id' => $business_id,
                'date' => $form_date,
                'form_no' => $form_no,
                'location_id' => $location_id,
                'store_id' => $nullableInteger($request->input('store_id')),
                'category_id' => $nullableInteger($request->input('category_id')),
                'sub_category_id' => $nullableInteger($request->input('sub_category_id')),
                'unit_id' => $nullableInteger($request->input('unit_id')),
                'brand_id' => $nullableInteger($request->input('brand_id')),
                'total_price_change_loss' => 0,
                'total_price_change_gain' => 0,
                'page_no' => $request->input('page_no') ?: null,
                'user' => Auth::id(),
            ]);

            $total_loss = 0.0;
            $total_gain = 0.0;
            $saved_rows = 0;

            foreach ($changed_rows as $product_id => $row) {
                $product = Product::where('business_id', $business_id)
                    ->where('id', $product_id)
                    ->first();

                if (empty($product)) {
                    continue;
                }

                $current_stock = $toDecimal($row['current_stock'] ?? null) ?? 0.0;
                $unit_price = $toDecimal($row['unit_price'] ?? null) ?? 0.0;
                $new_price = $toDecimal($row['new_price'] ?? null) ?? 0.0;
                $difference = $new_price - $unit_price;

                $select_mode = $row['select_mode']
                    ?? ($difference < 0 ? 'decrease' : 'increase');

                if (! in_array($select_mode, ['increase', 'decrease'], true)) {
                    $select_mode = $difference < 0 ? 'decrease' : 'increase';
                }

                $price_changed_gain = $difference > 0
                    ? ($current_stock * $difference)
                    : 0.0;

                $price_changed_loss = $difference < 0
                    ? abs($current_stock * $difference)
                    : 0.0;

                FormF17Detail::create([
                    'header_id' => $header->id,
                    'product_id' => $product->id,
                    'sku' => $product->sku ?? ($row['sku'] ?? ''),
                    'product' => $product->name ?? ($row['product'] ?? ''),
                    'current_stock' => $current_stock,
                    'unit_price' => $unit_price,
                    'select_mode' => $select_mode,
                    'new_price' => $new_price,
                    'unit_price_difference' => $difference,
                    'price_changed_loss' => $price_changed_loss,
                    'price_changed_gain' => $price_changed_gain,
                    'page_no' => $row['page_no'] ?? null,
                ]);

                $total_loss += $price_changed_loss;
                $total_gain += $price_changed_gain;
                $saved_rows++;
            }

            if ($saved_rows === 0) {
                throw new \RuntimeException(
                    'No valid F17 product row was available to save.'
                );
            }

            $header->total_price_change_loss = $total_loss;
            $header->total_price_change_gain = $total_gain;
            $header->save();

            DB::commit();

            $message = __('mpcs::lang.success');

            if ($expects_json) {
                return response()->json([
                    'success' => 1,
                    'msg' => $message,
                    'form_no' => $header->form_no,
                    'saved_rows' => $saved_rows,
                ]);
            }

            return redirect('/mpcs/F17')
                ->with('success', $message);
        } catch (\Throwable $exception) {
            DB::rollBack();

            \Log::error('F17 form save failed', [
                'business_id' => $business_id,
                'user_id' => Auth::id(),
                'date' => $form_date,
                'location_id' => $location_id,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            $message = __('messages.something_went_wrong');

            if (config('app.debug')) {
                $message .= ' (' . $exception->getMessage() . ')';
            }

            return $respondWithError($message, 500);
        }
    }

    /**
     * list the specified resource.
     * @return Response
     */
    public function list()
    {
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $products = FormF17Header::leftjoin('categories', 'form_f17_headers.category_id', 'categories.id')
                ->leftjoin('categories as sub_cat', 'form_f17_headers.sub_category_id', 'sub_cat.id')
                ->leftjoin('business_locations', 'form_f17_headers.location_id', 'business_locations.id')
                ->leftjoin('stores', 'form_f17_headers.store_id', 'stores.id')
                ->leftjoin('units', 'form_f17_headers.unit_id', 'units.id')
                ->leftjoin('brands', 'form_f17_headers.brand_id', 'brands.id')
                ->leftjoin('users', 'form_f17_headers.user', 'users.id')
                ->where('form_f17_headers.business_id', $business_id)
                ->select(
                    'form_f17_headers.*',
                    'categories.name as category',
                    'sub_cat.name as sub_category',
                    'business_locations.name as location',
                    'stores.name as store',
                    'units.actual_name as unit',
                    'brands.name as brands',
                    'users.username'
                );

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $products->whereIn('form_f17_headers.location_id', $permitted_locations);
            }

            if (!empty(request()->location_id)) {
                $products->where('form_f17_headers.location_id', request()->location_id);
            }
            if (!empty(request()->category_id)) {
                $products->where('form_f17_headers.category_id', request()->category_id);
            }
            if (!empty(request()->sub_category_id)) {
                $products->where('form_f17_headers.sub_category_id', request()->sub_category_id);
            }
            if (!empty(request()->brand_id)) {
                $products->where('form_f17_headers.brand_id', request()->brand_id);
            }
            if (!empty(request()->unit_id)) {
                $products->where('form_f17_headers.unit_id', request()->unit_id);
            }
            if (!empty(request()->store_id)) {
                $products->where('form_f17_headers.store_id', request()->store_id);
            }
            if (!empty(request()->from_no)) {
                $products->where('form_f17_headers.id', request()->from_no);
            }

            $start_date = Carbon::parse(request()->start_date)->format('Y-m-d');
            $end_date = Carbon::parse(request()->end_date)->format('Y-m-d');

            if (!empty($start_date) && !empty($end_date)) {
                $products->whereBetween('form_f17_headers.date', [$start_date, $end_date]);
            }


            $business_id = session()->get('user.business_id');
            $business_details = Business::find($business_id);

            return DataTables::of($products)
                ->addColumn('action', function ($row) use ($business_details) {
                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    $viewUrl = action('\Modules\MPCS\Http\Controllers\F17FormController@show', [$row->id]);
                    $html .= '<li><a target="_blank" href="' . $viewUrl . '"><i class="glyphicon glyphicon-eye-open"></i> ' . __("messages.view") . '</a></li>';
                    $html .= '<li><a target="_blank" href="' . $viewUrl . '?print=1"><i class="fa fa-print"></i> ' . __("messages.print") . '</a></li>';

                    $user = auth()->user();
                    if ($user && ($user->can("edit_f17_form") || $user->can("f17_form") || $user->can("superadmin"))) {
                        $html .= '<li><a target="_blank" href="' . action('\Modules\MPCS\Http\Controllers\F17FormController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                    }

                    $html .= '</ul></div>';

                    return $html;
                })
                ->editColumn('store', function ($row) use ($business_details) {
                    return $row->store ?? '-';
                })
                ->editColumn('total_price_change_loss', function ($row) use ($business_details) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->productUtil->num_f($row->total_price_change_loss, false, $business_details, false) . '</span>';
                })
                ->editColumn('total_price_change_gain', function ($row) use ($business_details) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $this->productUtil->num_f($row->total_price_change_gain, false, $business_details, false) . '</span>';
                })
                ->addColumn('select_mode', function ($row) use ($business_details) {
                    // Get the most common select_mode from details
                    $details = self::ma002F17Details($row->id);
                    $modes = $details->pluck('select_mode')->filter()->unique();

                    if ($modes->count() == 1) {
                        return ucfirst($modes->first());
                    } elseif ($modes->count() > 1) {
                        return 'Mixed';
                    }
                    return '-';
                })
                ->addColumn('page_no', function ($row) use ($business_details) {
                    // Get page numbers from details
                    $details = self::ma002F17Details($row->id);
                    $page_nos = $details->pluck('page_no')->filter()->unique();

                    if ($page_nos->count() == 1) {
                        return $page_nos->first();
                    } elseif ($page_nos->count() > 1) {
                        return $page_nos->implode(', ');
                    }
                    return $row->page_no ?? '-';
                })
                ->removeColumn('id')

                ->rawColumns(['action', 'total_price_change_loss', 'total_price_change_gain'])
                ->make(true);
        }
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show($id)
    {
        $business_id = session()->get('user.business_id');
        if (request()->ajax()) {
            $form = FormF17Header::leftjoin('form_f17_details', 'form_f17_headers.id', 'form_f17_details.header_id')
                ->leftjoin('products', 'form_f17_details.product_id', 'products.id')
                ->leftjoin('categories', 'products.category_id', 'categories.id')
                ->where('form_f17_headers.id', $id)
                ->select('form_f17_headers.*', 'form_f17_details.*', 'form_f17_details.id as detial_id', 'categories.name as category_name');

            $business_details = Business::find($business_id);

            return DataTables::of($form)
                ->addIndexColumn()
                ->editColumn('product', function ($row) {
                    return '<span>' . $row->product . '</span>';
                })
                ->editColumn('sku', function ($row) {
                    return '<span>' . $row->sku . '</span>';
                })
                ->editColumn('unit_price', function ($row) use ($business_details) {
                    $display_price = $row->unit_price ?? 0;
                    $formatted_price = $this->productUtil->num_f($display_price, false, $business_details, false);
                    return '<span class="display_currency unit_price" data-orig-value="' . $display_price . '">' . $formatted_price . '</span>';
                })
                ->editColumn('current_stock', function ($row) {
                    $current_stock = $row->current_stock;
                    $formatted_stock = number_format($current_stock, 3, '.', '');
                    return '<span class="current_stock" data-orig-value="' . $current_stock . '">' . $formatted_stock . '</span>';
                })
                ->addColumn('select_mode', function ($row) {
                    return ucfirst($row->select_mode ?? '-');
                })
                ->addColumn('unit_price_difference', function ($row) use ($business_details) {
                    return '<span class="display_currency unit_price_difference" data-orig-value="' . $row->unit_price_difference . '" data-currency_symbol = "false">' . $this->productUtil->num_f($row->unit_price_difference, false, $business_details, false) . '</span>';
                })
                ->addColumn('new_price', function ($row) {
                    return '<span>' . $row->new_price . '</span>';
                })
                ->addColumn('price_changed_loss', function ($row) use ($business_details) {
                    return '<span class="display_currency price_changed_loss" data-orig-value="' . $row->price_changed_loss . '" data-currency_symbol = "false">' . $this->productUtil->num_f($row->price_changed_loss, false, $business_details, false) . '</span>';
                })
                ->addColumn('price_changed_gain', function ($row) use ($business_details) {
                    return '<span class="display_currency price_changed_gain" data-orig-value="' . $row->price_changed_gain . '" data-currency_symbol = "false">' .  $this->productUtil->num_f($row->price_changed_gain, false, $business_details, false) . '</span>';
                })
                ->addColumn('page_no', function ($row) {
                    return '<span>' . ($row->page_no ?? '') . '</span>';
                })
                ->addColumn('signature', function () {
                    return '';
                })
                ->removeColumn('id')
                ->rawColumns(['sku', 'unit_price', 'current_stock', 'product', 'select_mode', 'unit_price_difference', 'new_price', 'price_changed_loss', 'price_changed_gain', 'page_no'])
                ->make(true);
        }

        return view('mpcs::forms.F17.show')->with(compact('id'));
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id)
    {
        // dd('masok');
        $business_id = session()->get('user.business_id');
        if (request()->ajax()) {
            $form = FormF17Header::leftjoin('form_f17_details', 'form_f17_headers.id', 'form_f17_details.header_id')
                ->leftjoin('products', 'form_f17_details.product_id', 'products.id')
                ->leftjoin('categories', 'products.category_id', 'categories.id')
                ->where('form_f17_headers.id', $id)
                ->select('form_f17_headers.*', 'form_f17_details.*', 'form_f17_details.id as detial_id', 'categories.name as category_name');

            $business_details = Business::find($business_id);

            return DataTables::of($form)
                ->addIndexColumn()
                ->editColumn('product', function ($row) use ($business_details) {
                    return '<span>' . $row->product . '</span><input type="hidden" value="' . $row->product . '" name="F17[' . $row->detial_id . '][product]" id="F17[' . $row->detial_id . '][product]">';
                })
                ->editColumn('sku', function ($row) use ($business_details) {
                    return '<span>' . $row->sku . '</span><input type="hidden" value="' . $row->sku . '" name="F17[' . $row->detial_id . '][sku]" id="F17[' . $row->detial_id . '][sku]">';
                })
                ->editColumn('unit_price', function ($row) use ($business_details) {
                    $display_price = $row->unit_price ?? 0;
                    $formatted_price = $this->productUtil->num_f($display_price, false, $business_details, false);
                    return '<span class="display_currency unit_price" data-orig-value="' . $display_price . '">' . $formatted_price . '</span><input type="hidden" value="' . $display_price . '" name="F17[' . $row->detial_id . '][unit_price]" id="F17[' . $row->detial_id . '][unit_price]">';
                })
                ->editColumn('current_stock', function ($row) use ($business_details) {
                    $current_stock = $row->current_stock;

                    // For fuel items, we should probably show the real-time stock if requested, 
                    // but since this is an edit of a saved form, normally it should show what was saved.
                    // However, if the saved value was 0 due to the bug, we might want to update it.
                    // Based on "Need to get auto updated", we'll provide the real-time stock.
                    if ($row->category_name == 'Fuel') {
                        $location_id = $row->location_id; // From form_f17_headers
                        if (!empty($location_id)) {
                            $current_stock = $this->transactionUtil->getFuelStockByProductId($row->product_id, $location_id);
                        }
                    }

                    // Show with 3 decimals for Qty
                    $formatted_stock = number_format($current_stock, 3, '.', '');
                    return '<span  class="current_stock" data-orig-value="' . $current_stock . '">' . $formatted_stock . '</span><input type="hidden" value="' . $current_stock . '" name="F17[' . $row->detial_id . '][current_stock]" id="F17[' . $row->detial_id . '][current_stock]">';
                })
                ->addColumn('select_mode', function ($row) use ($business_details) {
                    $increase = '';
                    $decrease = '';
                    if ($row->select_mode == 'increase') {
                        $increase = 'selected';
                    } else {
                        $decrease = 'selected';
                    }
                    $html = '<select name="F17[' . $row->detial_id . '][select_mode]" id="F17[' . $row->detial_id . '][select_mode]" class="form-control select_mode" placeholder="Please Select">
                        <option ' . $increase . ' value="increase">Increase</option>
                        <option ' . $decrease . ' value="decrease">Decrease</option>
                    </select>';
                    return $html;
                })
                ->addColumn('unit_price_difference', function ($row) use ($business_details) {
                    return '<span class="display_currency unit_price_difference" data-orig-value="' . $row->unit_price_difference . '" data-currency_symbol = "false">' . $this->productUtil->num_f($row->unit_price_difference, false, $business_details, false) . '</span><input type="hidden" name="F17[' . $row->detial_id . '][unit_price_difference]" id="F17[' . $row->detial_id . '][unit_price_difference]" class="unit_price_difference_value">';
                })
                ->addColumn('new_price', function ($row) {
                    return '<input type="text" style="width: 60px;" name="F17[' . $row->detial_id . '][new_price]" id="F17[' . $row->detial_id . '][new_price]" value="' . $row->new_price . '" class="form-control input_number new_price_value">';
                })
                ->addColumn('price_changed_loss', function ($row) use ($business_details) {
                    return '<span class="display_currency price_changed_loss" data-orig-value="' . $row->price_changed_loss . '" data-currency_symbol = "false">' . $this->productUtil->num_f($row->price_changed_loss, false, $business_details, false) . '</span><input type="hidden" name="F17[' . $row->detial_id . '][price_changed_loss]" id="F17[' . $row->detial_id . '][price_changed_loss]" value="' . $row->price_changed_loss . '" class="price_changed_loss_value">';
                })
                ->addColumn('price_changed_gain', function ($row) use ($business_details) {
                    return '<span class="display_currency price_changed_gain" data-orig-value="' . $row->price_changed_gain . '" data-currency_symbol = "false">' .  $this->productUtil->num_f($row->price_changed_gain, false, $business_details, false) . '</span><input type="hidden" name="F17[' . $row->detial_id . '][price_changed_gain]" id="F17[' . $row->detial_id . '][price_changed_gain]" value="' . $row->price_changed_gain . '" class="price_changed_gain_value">';
                })
                ->addColumn('page_no', function ($row) {
                    return '<input value="' . $row->page_no . '" type="text" style="width: 60px;" name="F17[' . $row->detial_id . '][page_no]" id="F17[' . $row->detial_id . '][page_no]" class="form-control input_number page_no">';
                })
                ->addColumn('signature', function ($row) {
                    return '';
                })
                ->removeColumn('id')

                ->rawColumns(['sku', 'unit_price', 'current_stock', 'product', 'select_mode', 'unit_price_difference', 'new_price', 'price_changed_loss', 'price_changed_gain', 'page_no'])
                ->make(true);
        }

        return view('mpcs::forms.F17.edit')->with(compact('id'));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update($id, Request $request)
    {
        $business_id = session()->get('user.business_id');

        try {
            $data = array();
            parse_str($request->data, $data); // converting serielize string to array

            $data_array = $data['F17'];

            $header = FormF17Header::findOrFail($id);

            DB::beginTransaction();
            $total_price_change_loss = 0;
            $total_price_change_gain = 0;
            foreach ($data_array as $key => $item) {
                $array_details = array(
                    'sku' => $item['sku'],
                    'product' => $item['product'],
                    'current_stock' => $item['current_stock'],
                    'unit_price' => $item['unit_price'],
                    'select_mode' => $item['select_mode'],
                    'new_price' => $item['new_price'],
                    'unit_price_difference' => $item['unit_price_difference'],
                    'price_changed_loss' => $item['price_changed_loss'],
                    'price_changed_gain' => $item['price_changed_gain'],
                    'page_no' => $item['page_no'],
                );

                FormF17Detail::where('id', $key)->update($array_details);
                $total_price_change_loss += !empty($item['price_changed_loss']) ? $item['price_changed_loss'] : 0;
                $total_price_change_gain += !empty($item['price_changed_gain']) ? $item['price_changed_gain'] : 0;
            }
            $header->total_price_change_loss = $total_price_change_loss;
            $header->total_price_change_gain = $total_price_change_gain;
            $header->save();
            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('mpcs::lang.success')
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy() {}

    /**
     * Get sub-categories for a category
     * @return Response
     */
    public function getSubCategories(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $category_id = $request->input('category_id');

        // Check if petro module is enabled (cache this check)
        $enable_petro_module = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_module');
        
        // OPTIMIZED: Use caching for better performance
        $cache_key = "subcategories_business_{$business_id}_category_{$category_id}_petro_" . ($enable_petro_module ? '1' : '0');
        
        return \Cache::remember($cache_key, 300, function() use ($business_id, $category_id, $enable_petro_module) {
            // If no category is selected, return ALL sub-categories for the business
            if (empty($category_id)) {
                $query = Category::where('business_id', $business_id)
                    ->where('parent_id', '!=', 0) // Only subcategories
                    ->select(['id', 'name', 'parent_id']);

                // If petro module is not enabled, filter out Fuel sub-categories
                if (empty($enable_petro_module)) {
                    $query->whereHas('parent', function($q) {
                        $q->where('name', '!=', 'Fuel');
                    });
                }

                $all_sub_categories = $query->orderBy('name')->get();

                $results = $all_sub_categories->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                    ];
                })->values();

                return response()->json($results);
            }

            // If a specific category is selected, load only its direct sub-categories
            $sub_categories_query = Category::where('business_id', $business_id)
                ->where('parent_id', $category_id)
                ->select(['id', 'name'])
                ->orderBy('name');

            $sub_categories = $sub_categories_query->get();

            return response()->json($sub_categories);
        });
    }

    /**
     * Get products filtered by category, sub-category, and brand
     * @return Response
     */
    public function getProducts(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $products = Product::where('business_id', $business_id);

        if (!empty($request->category_id)) {
            $products->where('category_id', $request->category_id);
        }

        if (!empty($request->sub_category_id)) {
            $products->where('sub_category_id', $request->sub_category_id);
        }

        if (!empty($request->brand_id)) {
            $products->where('brand_id', $request->brand_id);
        }

        $products = $products->select(['name', 'id'])->get();

        return response()->json($products);
    }

    /**
     * Get brands filtered by category and sub-category
     * @return Response
     */
    public function getBrands(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $brands = Brands::where('business_id', $business_id);

        // Get product IDs that match the category/sub-category filters
        // Only filter brands when BOTH category and sub-category are selected
        if (!empty($request->category_id) && !empty($request->sub_category_id)) {
            $product_query = Product::where('business_id', $business_id)
                ->where('category_id', $request->category_id)
                ->where('sub_category_id', $request->sub_category_id);
            
            $brand_ids = $product_query->pluck('brand_id')->unique()->toArray();
            $brands->whereIn('id', $brand_ids);
        }

        $brands = $brands->select(['name', 'id'])->get();

        return response()->json($brands);
    }

    /**
     * Get product's brand
     * @return Response
     */
    public function getProductBrand(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $product_id = $request->input('product_id');

        $product = Product::where('business_id', $business_id)
            ->where('id', $product_id)
            ->select(['brand_id', 'category_id', 'sub_category_id'])
            ->first();

        return response()->json([
            'brand_id' => $product->brand_id ?? null,
            'category_id' => $product->category_id ?? null,
            'sub_category_id' => $product->sub_category_id ?? null
        ]);
    }

    public function getProductUnits(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $product_id = $request->input('product_id');

        $product = Product::with(['unit.sub_units', 'second_unit'])
            ->where('business_id', $business_id)
            ->find($product_id);

        $units = [];
        $unitIdsAdded = [];

        if ($product && $product->unit_id && $product->unit) {
            $units[] = [
                'id' => $product->unit_id,
                'name' => $product->unit->actual_name ?? $product->unit->short_name ?? 'Unit',
            ];
            $unitIdsAdded[] = $product->unit_id;

            if ($product->unit->sub_units && $product->unit->sub_units->count() > 0) {
                foreach ($product->unit->sub_units as $subUnit) {
                    if (!in_array($subUnit->id, $unitIdsAdded)) {
                        $units[] = [
                            'id' => $subUnit->id,
                            'name' => $subUnit->actual_name ?? $subUnit->short_name ?? 'Unit',
                        ];
                        $unitIdsAdded[] = $subUnit->id;
                    }
                }
            }
        }

        if ($product && $product->sub_unit_ids) {
            $subUnitIds = is_array($product->sub_unit_ids) ? $product->sub_unit_ids : json_decode($product->sub_unit_ids, true);
            if ($subUnitIds && is_array($subUnitIds) && count($subUnitIds) > 0) {
                // Exclude soft-deleted units
                $subUnits = DB::table('units')->whereIn('id', $subUnitIds)->whereNull('deleted_at')->get();
                foreach ($subUnits as $subUnit) {
                    if (!in_array($subUnit->id, $unitIdsAdded)) {
                        $units[] = [
                            'id' => $subUnit->id,
                            'name' => $subUnit->actual_name ?? $subUnit->short_name ?? 'Unit',
                        ];
                        $unitIdsAdded[] = $subUnit->id;
                    }
                }
            }
        }

        if ($product && $product->secondary_unit_id) {
            if (!$product->second_unit) {
                $product->load('second_unit');
            }

            if ($product->second_unit && !in_array($product->secondary_unit_id, $unitIdsAdded)) {
                $units[] = [
                    'id' => $product->secondary_unit_id,
                    'name' => $product->second_unit->actual_name ?? $product->second_unit->short_name ?? 'Unit',
                ];
                $unitIdsAdded[] = $product->secondary_unit_id;
            }
        }

        if (empty($units) && $product && $product->unit_id) {
            // Exclude soft-deleted units
            $mainUnit = DB::table('units')->where('id', $product->unit_id)->whereNull('deleted_at')->first();
            if ($mainUnit) {
                $units[] = [
                    'id' => $mainUnit->id,
                    'name' => $mainUnit->actual_name ?? $mainUnit->short_name ?? 'Unit',
                ];
            }
        }

        return response()->json($units);
    }
}

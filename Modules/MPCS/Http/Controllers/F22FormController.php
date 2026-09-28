<?php

namespace Modules\MPCS\Http\Controllers;

use App\Account;
use App\AccountType;
use App\Business;
use App\TaxRate;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\Transaction;
use App\Variation;
use App\AccountTransaction;
use Modules\MPCS\Entities\Mpcs16aFormSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Modules\MPCS\Entities\FormF22Detail;
use Modules\MPCS\Entities\FormF22Header;
use Modules\MPCS\Entities\FormF22LossGain;
use Modules\MPCS\Entities\MpcsFormSetting;
use Modules\MPCS\Entities\Pump;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\Util;
use App\Utils\ProductUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use App\Utils\BusinessUtil;
use App\VariationLocationDetails;

use App\Store;
use App\StockAdjustmentSetting;


class F22FormController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $moduleUtil;
    protected $transactionUtil;
    protected $commonUtil;
    protected $businessUtil;

    private $barcode_types;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(Util $commonUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Resolve permitted locations from the authenticated user with a safe fallback.
     *
     * @return array|string
     */
    private function getPermittedLocations()
    {
        $user = auth()->user();
        if ($user && method_exists($user, 'permitted_locations')) {
            return $user->permitted_locations();
        }

        return 'all';
    }


    public function F22StockTaking()
    {

        if (!auth()->check()) {
            return Redirect::route('login');
        }
        
        $business_id = request()->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id
            ?? request()->session()->get('business.id');

        abort_if(empty($business_id), 403, 'Business context is missing.');

         $mpcs_authorized_signature_permission =
            $this->moduleUtil->hasThePermissionInSubscription(
                $business_id,
                'authorized_signature'
            );
          $stock_taking_approve_permission = Gate::forUser(auth()->user())->check('stock_taking_approve') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'stock_taking_approve');
          
          // New F22 Authorized Signature Role Permission
          $f22_authorized_signature_permission = Gate::forUser(auth()->user())->check('f22_authorized_signature_form');
          
          $f22_latest_signature = \Modules\MPCS\Entities\F22Signature::where('business_id', $business_id)->orderBy('created_at', 'desc')->first();
     
        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
        $f22_counts  = FormF22Header::where('business_id', $business_id)->count();
        if (!empty($settings)) {
            $F22_from_no = $settings->F22_form_sn +  $f22_counts;
        } else {
            $F22_from_no = 1 +  $f22_counts;
        }
        $business_locations = BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)
            ->orderBy('id', 'asc')
            ->select(
                DB::raw("IF(location_id IS NULL OR location_id = '', name, CONCAT(name, ' (', location_id, ')')) AS display_name"),
                'id'
            )
            ->pluck('display_name', 'id');
        $default_location_id = $business_locations->keys()->first();
        $default_location_name = $default_location_id !== null
            ? $business_locations->get($default_location_id)
            : '';
        $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->pluck('name', 'id');
        $products = Product::where('business_id', $business_id)->pluck('name', 'id');

        $settings = MpcsFormSetting::where('business_id',  $business_id)->select('F22_no_of_product_per_page')->first();

        $last_form = FormF22Header::where('business_id', $business_id)->orderBy('id', 'desc')->first();
        $last_form_no = !empty($last_form) ? $last_form->form_no : '';

        $accountType = AccountType::where('business_id', $business_id)->where('name', 'expenses')->first();
        $accountType_gains = AccountType::where('business_id', $business_id)->whereIn('name', ['Current Assets', 'Current Liabilities'])->first();

        $expenseTypeIds = AccountType::getAccountTypeIdOfType('Expenses', $business_id);
        $incomeTypeIds  = AccountType::getAccountTypeIdOfType('Income', $business_id);

        $expenseAccounts = collect();
        $incomeAccounts  = collect();
        $accounts = collect();
        $accountType_gain = collect();

        if (!empty($expenseTypeIds)) {
            $expenseAccounts = Account::where('business_id', $business_id)
                ->whereIn('account_type_id', $expenseTypeIds)
                ->where('is_closed', 0)
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        if (!empty($incomeTypeIds)) {
            $incomeAccounts = Account::where('business_id', $business_id)
                ->whereIn('account_type_id', $incomeTypeIds)
                ->where('is_closed', 0)
                ->orderBy('name')
                ->pluck('name', 'id');
        }


         if ($accountType_gains) {
            // Fetch all accounts under "expenses"
            $accountType_gain = Account::where('business_id', $business_id)
                ->where('account_type_id', $accountType_gains->id)
                ->where('is_closed', 0)
                ->orderBy('name')
                ->pluck('name', 'id');
        }
        if ($accountType) {
            // Fetch all accounts under "expenses"
            $accounts = Account::where('business_id', $business_id)
                ->where('account_type_id', $accountType->id)
                ->where('is_closed', 0)
                ->orderBy('name')
                ->pluck('name', 'id');
        }
        return view('mpcs::forms.F22.F22_stock_taking')->with(
            compact('F22_from_no', 'business_locations', 'stock_taking_approve_permission','mpcs_authorized_signature_permission', 'f22_authorized_signature_permission', 'f22_latest_signature', 'products', 'settings', 'last_form_no','accounts','accountType_gain', 'sub_categories', 'expenseAccounts',
                 'incomeAccounts', 'default_location_id', 'default_location_name',));
    }

    public function getF22FormList(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $currency_precision = Business::where('id', $business_id)->value('currency_precision');
            $header = FormF22Header::leftjoin('business_locations', 'form_f22_headers.location_id', 'business_locations.id')
                ->leftjoin('users', 'form_f22_headers.created_by', 'users.id')
                ->where('form_f22_headers.business_id', $business_id)
                ->select('form_f22_headers.*', 'business_locations.name as locations_name', 'users.username')
                ->orderBy('form_f22_headers.form_date', 'desc')
                ->orderBy('form_f22_headers.created_at', 'desc');


            $permitted_locations = $this->getPermittedLocations();
            if ($permitted_locations != 'all') {
                $header->whereIn('form_f22_headers.location_id', $permitted_locations);
            }

            /*
             * IS-1933:
             * Only narrow the list when an EXPLICIT list filter is sent.
             *
             * This used to read request()->location_id, which the F22 entry tab's
             * location picker was posting on every list load. That picker defaults
             * to the first business location, so a form saved against any other
             * location disappeared from "List F22 Stock Taking" as soon as the page
             * reloaded after saving - the form was in the database, it was just
             * being filtered out.
             *
             * Same rule as the list_date filter below (IS1454): a list filter must
             * come from a control on the LIST, not from the entry form. The
             * permitted-locations restriction above is a security boundary and
             * still applies unconditionally.
             */
            if (!empty($request->list_location_id)) {
                $header->where('form_f22_headers.location_id', $request->list_location_id);
            }
            /*
             * IS1454:
             * List F22 Stock Taking must show all saved forms.
             * Do not automatically filter the list by the F22 entry date picker
             * (which defaults to today and hides historical saved forms).
             * Only apply the filter when an explicit list date filter is sent.
             */
            if (!empty($request->list_date)) {
                $selected_date = date('Y-m-d', strtotime($request->list_date));
                $header->whereDate('form_f22_headers.form_date', $selected_date);
            }


            return Datatables::of($header)
                ->addIndexColumn()
                ->orderColumn('form_date', 'form_f22_headers.form_date $1')
                ->removeColumn('id')
                ->addColumn('stock_adjustment_no', function ($row) {
                    return $row->pre_field ?? '';
                })
                ->addColumn('total_stock_lose_purchase', function ($row) use ($currency_precision) {
                    $total = FormF22Detail::where('form_no', $row->form_no)
                        ->where('status', 1) // Only count active entries
                        ->selectRaw("SUM(CASE WHEN difference_qty < 0 THEN ABS(difference_qty) * unit_purchase_price ELSE 0 END) as total")
                        ->value('total');
                    return number_format($total ?? 0, $currency_precision);
                })

                ->addColumn('total_stock_lose_sale', function ($row) use ($currency_precision) {
                    $total = FormF22Detail::where('form_no', $row->form_no)
                        ->where('status', 1) // Only count active entries
                        ->selectRaw("SUM(CASE WHEN difference_qty < 0 THEN ABS(difference_qty) * unit_sale_price ELSE 0 END) as total")
                        ->value('total');
                    return number_format($total ?? 0, $currency_precision);
                })
                ->editColumn('form_date', function ($row) {
                    if (!empty($row->form_date)) {
                        return \Carbon\Carbon::parse($row->form_date)->format('Y-m-d');
                    }
                    // Fallback to created_at if form_date is null
                    return \Carbon\Carbon::parse($row->created_at)->format('Y-m-d');
                })
                ->editColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    if (Gate::forUser(auth()->user())->check("superadmin")) {
                        $html .= '<li><a href="' . action('\Modules\MPCS\Http\Controllers\F22FormController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit" aria-hidden="true"></i>' . __("messages.edit") . '</a></li>';
                    }

                    $html .= '<li><a href="#" class="reprint_form" data-href="' . action('\Modules\MPCS\Http\Controllers\F22FormController@printF22FormById', [$row->id]) . '"><i class="fa fa-print" aria-hidden="true"></i>' . __("messages.print") . '</a></li>';
                    $html .= '<li><a href="' . action('\Modules\MPCS\Http\Controllers\F22FormController@view', [$row->id]) . '"><i class="fa fa-eye" aria-hidden="true"></i>' . __("messages.view") . '</a></li>';

                    $html .= '</ul>';
                    return $html;
                })

                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $form = FormF22Header::leftjoin('form_f22_details', 'form_f22_headers.id', 'form_f22_details.header_id')
                ->where('form_f22_headers.id', $id)
                ->select('form_f22_headers.*', 'form_f22_details.*', 'form_f22_details.id as detial_id');
            return DataTables::of($form)
                ->addIndexColumn()

                ->removeColumn('id')
                ->editColumn('current_stock', function ($row) {
                    return '<input type="hidden" value="' . $row->current_stock . '" name="f22[' . $row->detial_id . '][current_stock]" id="f22[' . $row->detial_id . '][current_stock]" ><span class="display_currency current_stock" data-orig-value="' . $row->current_stock . '" data-currency_symbol = "false">' . $row->current_stock . '</span>';
                })
                ->editColumn('unit_purchase_price', function ($row) {
                    return '<span class="display_currency unit_purchase_price" data-orig-value="' . $row->unit_purchase_price . '" data-currency_symbol = "false">' . $row->unit_purchase_price . '</span><input type="hidden" value="' . $row->unit_purchase_price . '"  class="unit_purchase_price" name="f22[' . $row->detial_id . '][unit_purchase_price]" >';
                })
                ->editColumn('total_purchase_price', function ($row) {
                    return '<span class="display_currency total_purchase_price" data-orig-value="' . $row->purchase_price_total . '" data-currency_symbol = "false"></span><input value="' . $row->purchase_price_total . '" type="hidden" class="total_purhcase_value" name="f22[' . $row->detial_id . '][total_purhcase_value]" >';
                })
                ->editColumn('unit_sale_price', function ($row) {
                    return '<span class="display_currency unit_sale_price" data-orig-value="' . $row->unit_sale_price . '" data-currency_symbol = "false">' . $row->unit_sale_price . '</span><input type="hidden" value="' . $row->unit_sale_price . '"   class="unit_sale_price" name="f22[' . $row->detial_id . '][unit_sale_price]" >';
                })
                ->editColumn('total_sale_price', function ($row) {
                    return '<span class="display_currency total_sale_price" data-orig-value="' . $row->sales_price_total . '" data-currency_symbol = "false"></span><input value="' . $row->sales_price_total . '" type="hidden" class="total_sale_value" name="f22[' . $row->detial_id . '][total_sale_value]" >';
                })

                ->addColumn('book_no', function ($row) {
                    return '<input class="form-control input_number book_no" name="f22[' . $row->detial_id . '][book_no]" id="f22[' . $row->detial_id . '][book_no]" style="width: 80px;" name="book_no" value="' . $row->book_no . '" >';
                })
                ->addColumn('stock_count', function ($row) {
                    return '<input class="form-control input_number stock_count"  name="f22[' . $row->detial_id . '][stock_count]" id="f22[' . $row->detial_id . '][stock_count]"  style="width: 80px;" name="stock_count" value="' . $row->stock_count . '" >';
                })
                ->addColumn('qty_difference', function ($row) {
                    return '<input class="form-control input_number qty_difference" name="f22[' . $row->detial_id . '][qty_difference]" id="f22[' . $row->detial_id . '][qty_difference]" style="width: 80px;" name="qty_difference" value="' . $row->difference_qty . '" readonly >';
                })
                ->editColumn('sku', function ($row) {
                    return '<input type="hidden" value="' . $row->product_code . '" name="f22[' . $row->detial_id . '][sku]" id="f22[' . $row->detial_id . '][sku]" > ' . $row->sku;
                })
                ->editColumn('product', function ($row) {
                    return '<input type="hidden" value="' . $row->product . '" name="f22[' . $row->detial_id . '][product]" id="f22[' . $row->detial_id . '][product]" > ' . $row->product;
                })
                ->rawColumns(['total_purchase_price', 'total_sale_price', 'book_no', 'stock_count', 'qty_difference', 'unit_purchase_price', 'unit_sale_price', 'current_stock', 'sku', 'product'])
                ->make(true);
        }

        return view('mpcs::forms.F22.edit')->with(compact('id'));
    }

    public function view($header_id)
    {
        $business_id = request()->session()->get('user.business_id');

        $header = FormF22Header::leftjoin('business_locations', 'form_f22_headers.location_id', 'business_locations.id')
            ->where('form_f22_headers.id', $header_id)->select('business_locations.name as location_name', 'form_f22_headers.*')->first();
        // Get all active details for this header (no location filtering to show all entries)
        $details = FormF22Detail::where('header_id', $header_id)
            ->where('status', 1)
            ->orderBy('id', 'asc')
            ->get();
        $settings = MpcsFormSetting::where('business_id',  $business_id)->select('F22_no_of_product_per_page')->first();

        // Fetch saved pump meters for this form
        $savedPumpMeters = \Modules\MPCS\Entities\FormF22PumpMeter::where('header_id', $header_id)->get();
        
        if ($savedPumpMeters->isNotEmpty()) {
            // Use saved pump meter data
            $pumps = $savedPumpMeters->map(function($meter) {
                return (object)[
                    'id' => $meter->pump_id,
                    'pump_name' => $meter->pump_name,
                    'product_name' => $meter->product_name,
                    'last_meter_reading' => $meter->meter_reading,
                    'closing_meter' => $meter->meter_reading
                ];
            });
        } else {
            // No saved pump data
            $pumps = collect();
        }

        return view('mpcs::forms.F22.partials.view_byID_f22_form')->with(compact('header', 'details', 'settings', 'pumps'));
    }

    public function update(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        try {
            $data = array();
            parse_str($request->data, $data); // converting serielize string to array

            DB::beginTransaction();

            foreach ($data['f22'] as $key => $item) {
                $data_details = array(
                    'product_code' => $item['sku'],
                    'product' => $item['product'],
                    'book_no' => $item['book_no'],
                    'current_stock' => $item['current_stock'],
                    'stock_count' => $item['stock_count'],
                    'unit_purchase_price' => $item['unit_purchase_price'],
                    'unit_sale_price' => $item['unit_sale_price'],
                    'purchase_price_total' => $item['total_purhcase_value'],
                    'sales_price_total' => $item['total_sale_value'],
                    'difference_qty' => $item['qty_difference']
                );

                $details = FormF22Detail::where('id', $key)->update($data_details);
            }
            DB::commit();

            return $this->printF22FormById($id);
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];

            return $output;
        }
    }

    public function getF22Form(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $business_details = Business::find($business_id);

        $qty_precision = (int) $business_details->quantity_precision;
        $product_id = $request->input('product_id');
        $sub_category_id = $request->input('sub_category_id');
        if (request()->ajax()) {
            $currency_precision = Business::where('id', $business_id)->value('currency_precision');
            $permitted_locations = $this->getPermittedLocations();

            // PRODUCTS INFORMATION
            $products = Product::leftJoin('variations', 'products.id', 'variations.product_id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('variation_location_details', function($join) use ($request, $permitted_locations) {
                    $join->on('variations.id', '=', 'variation_location_details.variation_id');
                    if (!empty($request->location_id)) {
                        $join->where('variation_location_details.location_id', $request->location_id);
                    } else if ($permitted_locations != 'all') {
                        $join->whereIn('variation_location_details.location_id', $permitted_locations);
                    }
                })
                ->where('products.business_id', $business_id);

            // Apply filters
            if (!empty($request->product_id)) {
                $products->where('products.id', $request->product_id);
            }

            if (!empty($request->sub_category_id)) {
                $products->where('products.sub_category_id', $request->sub_category_id);
            }

            $products = $products
                ->select(
                    'products.id as product_id',
                    'variations.id as variation_id',
                    'products.name as product',
                    'products.sku',
                    'products.category_id',
                    'products.sub_category_id',
                    'variations.dpp_inc_tax as unit_purchase_price',
                    'variations.sell_price_inc_tax as unit_sale_price',
                    'variations.default_sell_price',
                    DB::raw('SUM(COALESCE(variation_location_details.qty_available, 0)) as current_stock'),
                    'categories.name as category'
                )
                ->groupBy(
                    'products.id',
                    'variations.id',
                    'products.name',
                    'products.sku',
                    'products.category_id',
                    'products.sub_category_id',
                    'variations.dpp_inc_tax',
                    'variations.sell_price_inc_tax',
                    'variations.default_sell_price',
                    'categories.name'
                )
                ->get()
                ->keyBy(fn($x) => $x->product_id . '-' . $x->variation_id);

            /*
            //PURCHASE
            $purchases = Product::leftJoin('purchase_lines', 'products.id', 'purchase_lines.product_id')
                ->leftJoin('variations', 'products.id', 'variations.product_id')
                ->leftJoin('transactions', function($join) {
                    $join->on('purchase_lines.transaction_id', '=', 'transactions.id')
                        ->where('transactions.type', 'purchase')
                        ->where('transactions.status', 'received');
                })
                ->leftJoin(DB::raw('(
        SELECT return_parent_id, SUM(final_total) as total_return_amount
        FROM transactions
        WHERE type = "purchase_return"
            AND status = "final"
        GROUP BY return_parent_id
    ) as returns'), function($join) {
                    $join->on('returns.return_parent_id', '=', 'transactions.id');
                })
                ->whereDate('transactions.transaction_date', $date)
                ->where('products.business_id', $business_id);

            // --- Apply common filters ---
            if (!empty($request->location_id)) {
                $purchases->where('transactions.location_id', '=', $request->location_id);
            } else if ($permitted_locations != 'all') {
                $purchases->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->product_id)) {
                $purchases->where('products.id', request()->product_id);
            }
            if (!empty(request()->sub_category_id)) {
                $purchases->where('products.sub_category_id', request()->sub_category_id);
            }

            // --- Select ---
            $purchases = $purchases
                ->select(
                    'products.id as product_id',
                    'variations.id as variation_id',
                    DB::raw('SUM(transactions.final_total) - SUM(returns.total_return_amount) as total_purchase_price')
                )
                ->groupBy('products.id', 'variations.id')
                ->get()
                ->keyBy(fn($x) => $x->product_id . '-' . $x->variation_id);

            // SELL
            $sells = Product::leftJoin('transaction_sell_lines', 'products.id', 'transaction_sell_lines.product_id')
                ->leftJoin('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
                ->leftJoin('variations', 'products.id', 'variations.product_id')
                ->where('products.business_id', $business_id)
                ->whereDate('transactions.transaction_date', $date);

            // --- Apply common filters ---
            if (!empty($request->location_id)) {
                $sells->where('transactions.location_id', '=', $request->location_id);
            } else if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty($product_id)) {
                $sells->where('products.id', $product_id);
            }

            if (!empty($sub_category_id)) {
                $sells->where('products.sub_category_id', $sub_category_id);
            }

            // --- Select ---
            $sells = $sells
                ->select(
                    'products.id as product_id',
                    'variations.id as variation_id',
                    DB::raw('SUM(variations.sell_price_inc_tax * transaction_sell_lines.quantity) as total_sale_price')
                )
                ->groupBy('products.id', 'variations.id')
                ->get()
                ->keyBy(fn($x) => $x->product_id . '-' . $x->variation_id);
            */

            $result = $products->map(function ($product) {
                $product->total_purchase_price = 0;
                $product->total_sale_price = 0;
                return $product;
            })->values();

            return DataTables::of($result)
                ->addIndexColumn()
                ->removeColumn('id')
                ->editColumn('current_stock', function ($row) use ($qty_precision) {
                    /*
                     * IS1970: fuel now reads the SAME source as every other
                     * category - and the same source as Stock Center.
                     *
                     * Fuel alone was routed to getTankProductBalanceByProductId(),
                     * which sums the FUEL TANK balances (tank dips, purchases and
                     * tank sell lines). Every other category used
                     * $row->current_stock, which the query above builds as
                     *     SUM(COALESCE(variation_location_details.qty_available, 0))
                     * - exactly what Stock Center's Available column reads.
                     *
                     * Two different sources for the same figure will always drift
                     * apart, and they had: Auto Diesel showed 1,200.000 in Stock
                     * Center against 1,011.00 here. Lubricants and gas matched
                     * because they were never sent down the tank branch.
                     *
                     * The requirement is that this column mirrors Stock Center, so
                     * the special case is removed and fuel is treated like
                     * everything else. Tank balances remain available on the tank
                     * screens, where they belong.
                     */
                    $current_stock = number_format($row->current_stock, $qty_precision, '.', ',');

                    return '<input type="hidden" value="' . $current_stock . '" name="f22[' . $row->product_id 
                    . '][current_stock]" id="f22[' . $row->product_id 
                    . '][current_stock]" ><span class="display_currency current_stock" data-orig-value="' 
                    . $current_stock . '" data-currency_symbol = "false">' 
                    . number_format((float) str_replace(',', '', $current_stock), $qty_precision, '.', ',') . '</span>';
                })
                ->editColumn('unit_purchase_price', function ($row) use ($currency_precision) {
                    /*
                     * IS2218: F22 must calculate Total Purchase Price from the
                     * exact Unit Purchase Price shown to the user.
                     *
                     * Example from the reported issue:
                     *   stored variation price = 374.3825
                     *   displayed F22 price    = 374.38
                     *   stock count            = 5043
                     *
                     * The old HTML displayed 374.38 but kept 374.3825 in
                     * data-orig-value, so JavaScript calculated 1,888,010.95.
                     * F22 must instead use 374.38, giving 1,887,998.34.
                     *
                     * Keep a plain, comma-free calculation value in both the
                     * data attribute and hidden input. This also makes the same
                     * rounded value reach the server when the F22 form is saved.
                     */
                    $calculation_price = round((float) $row->unit_purchase_price, (int) $currency_precision);
                    $calculation_value = number_format($calculation_price, (int) $currency_precision, '.', '');
                    $formatted_price = number_format($calculation_price, (int) $currency_precision, '.', ',');

                    return '<span class="display_currency unit_purchase_price" data-orig-value="' . $calculation_value . '" data-currency_symbol="false">' . $formatted_price . '</span>
                    <input type="hidden" class="unit_purchase_price" name="f22[' . $row->product_id . '][unit_purchase_price]" value="' . $calculation_value . '">';

                })
                ->editColumn('total_purchase_price', function ($row) use ($currency_precision) {
                    $formatted_total_price = number_format($row->total_purchase_price, $currency_precision, '.', ',');

                    return '<span class="display_currency total_purchase_price" data-orig-value="' . $formatted_total_price . '" data-currency_symbol="false">' . $formatted_total_price . '</span>
                            <input type="hidden" class="total_purchase_price_input" name="f22[' . $row->product_id . '][total_purchase_price]" value="' . $formatted_total_price . '">';
                })
                ->editColumn('unit_sale_price', function ($row) use ($currency_precision) {
                    $calculated_sale_price = number_format($row->unit_sale_price, $currency_precision, '.', '');
                    $formatted_price = number_format($calculated_sale_price, $currency_precision, '.', ',');

                    return '<span class="display_currency unit_sale_price" data-orig-value="' . $calculated_sale_price . '" data-currency_symbol="false">' . $formatted_price . '</span>
                            <input type="hidden" class="unit_sale_price_input" name="f22[' . $row->product_id . '][unit_sale_price]" value="' . $formatted_price . '">';
                })
                ->editColumn('total_sale_price', function ($row) use ($currency_precision) {
                    $formatted_total_price = number_format($row->total_sale_price, $currency_precision, '.', '');
                    return '<span class="display_currency total_sale_price" data-orig-value="' . $formatted_total_price . '" data-currency_symbol="false">' . $formatted_total_price . '</span>
                            <input type="hidden" class="total_sale_price_input" name="f22[' . $row->product_id . '][total_sale_price]" value="' . $formatted_total_price . '">';
                })
                ->addColumn('stock_count', function ($row) use($qty_precision, $business_id) {
                    return '<input class="form-control stock_count" name="f22[' . $row->product_id . '][stock_count]" id="f22[' . $row->product_id . '][stock_count]" style="width: 100%;" value="0.00" >';
                })
                ->addColumn('qty_difference', function ($row) use($qty_precision, $business_id) {
//                    $difference = $row->current_stock;
                    $difference = 0;
                    $difference = number_format($difference, $qty_precision, '.', ',');
                    return '<input class="form-control qty_difference" name="f22[' . $row->product_id . '][qty_difference]" id="f22[' . $row->product_id . '][qty_difference]" style="width: 100%;" name="qty_difference" value="' . 0 . '" >';
                })
                ->editColumn('sku', function ($row) {
                    return '<input type="hidden" value="' . $row->sku . '" name="f22[' . $row->product_id . '][sku]" id="f22[' . $row->product_id . '][sku]" > ' . $row->sku;
                })
                ->editColumn('product', function ($row) {
                    return '<input type="hidden" value="' . $row->product . '" name="f22[' . $row->product_id . '][product]" id="f22[' . $row->product_id . '][product]" > ' . $row->product . ' <input type="hidden" value="' . $row->variation_id . '" name="f22[' . $row->product_id . '][variation_id]" id="f22[' . $row->product_id . '][variation_id]" >';
                })
                ->rawColumns(['total_purchase_price', 'total_sale_price', 'book_no', 'stock_count', 'qty_difference', 'unit_purchase_price', 'unit_sale_price', 'current_stock', 'sku', 'product'])
                ->make(true);
        }
    }

    public function getLastVerifiedF22Form(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $last_form = FormF22Header::where('business_id', $business_id)->orderBy('id', 'desc')->first();
            if (!empty($last_form)) {
                $last_form_header_id =  $last_form->id;
            } else {
                $last_form_header_id =  0;
            }
            
            $verified_form = FormF22Detail::leftJoin('form_f22_headers', 'form_f22_details.header_id', '=', 'form_f22_headers.id')
                ->where('form_f22_details.header_id', $last_form_header_id)
                ->where('form_f22_details.status', 1) // Only show active entries
                ->select('form_f22_details.*', 'form_f22_headers.form_date')
                ->orderBy('form_f22_details.id', 'asc'); // Order by ID to ensure consistent ordering

            // Note: Removed location filtering for Last Verified Stock to show ALL entries from the last form
            // Location filtering should only apply to the main form view, not the "Last Verified" view
            // This ensures all entries saved in the form are displayed, matching the totals

            // Note: product_id column doesn't exist in form_f22_details table
            // Filtering by product would need to be done by product name or SKU
            if (!empty(request()->product_id)) {
                $product = Product::find(request()->product_id);
                if ($product) {
                    $verified_form->where('form_f22_details.product', $product->name);
                }
            }
            
            $index = 0;

            return Datatables::of($verified_form)
                ->addIndexColumn()

                ->removeColumn('id')
                ->editColumn('current_stock', function ($row) {
                    return '<input type="hidden" value="' . $row->current_stock . '" name="f22[' . $row->id . '][current_stock]" id="f22[' . $row->id . '][current_stock]" ><span class="display_currency current_stock" data-orig-value="' . $row->current_stock . '" data-currency_symbol = "false">' . number_format($row->current_stock, 2, '.', ',') . '</span>';
                })
                 ->editColumn('form_date', function ($row) {
                        if (empty($row->form_date) || $row->form_date == '0000-00-00') {
                            return \Carbon\Carbon::parse($row->created_at)->format('Y-m-d');
                        }
                        return \Carbon\Carbon::parse($row->form_date)->format('Y-m-d');
                    })
                ->editColumn('unit_purchase_price', function ($row) {
                    return '<span class="display_currency unit_purchase_price" data-orig-value="' . $row->unit_purchase_price . '" data-currency_symbol = "false">' . number_format($row->unit_purchase_price, 2, '.', ',') . '</span><input type="hidden" value="' . $row->unit_purchase_price . '"  class="unit_purchase_price" name="f22[' . $row->id . '][unit_purchase_price]" >';
                })
                ->editColumn('total_purchase_price', function ($row) {
                    return '<span class="display_currency lf_total_purchase_price" data-orig-value="' . $row->purchase_price_total . '" data-currency_symbol = "false">' . number_format($row->purchase_price_total, 2, '.', ',') . '</span><input type="hidden"  class="total_purhcase_value" name="f22[' . $row->id . '][total_purhcase_value]" value="' . $row->purchase_price_total . '" >';
                })
                ->editColumn('unit_sale_price', function ($row) {
                    return '<span class="display_currency unit_sale_price" data-orig-value="' . $row->unit_sale_price . '" data-currency_symbol = "false">' . number_format($row->unit_sale_price, 2, '.', ',') . '</span><input type="hidden" value="' . $row->unit_sale_price . '"   class="unit_sale_price" name="f22[' . $row->id . '][unit_sale_price]" >';
                })
                ->editColumn('total_sale_price', function ($row) {
                    return '<span class="display_currency lf_total_sale_price" data-orig-value="' . $row->sales_price_total . '" data-currency_symbol = "false">' . number_format($row->sales_price_total, 2, '.', ',') . '</span><input type="hidden" class="total_sale_value" name="f22[' . $row->id . '][total_sale_value]" value="' . $row->sales_price_total . '">';
                })
                // ->addColumn('book_no', function ($row) {
                //     return '<span>' . $row->book_no . '</span><input type="hidden" class="form-control book_no" name="f22[' . $row->id . '][book_no]" id="f22[' . $row->id . '][book_no]"  value="' . $row->book_no . '" >';
                // })
                ->addColumn('stock_count', function ($row) {
                    return '<span class="display_currency stock_count">' . number_format($row->stock_count, 2, '.', ',') . '</span><input type="hidden" class="form-control stock_count"  name="f22[' . $row->id . '][stock_count]" id="f22[' . $row->id . '][stock_count]"  value="' . $row->stock_count . '" >';
                })
                ->addColumn('qty_difference', function ($row) {
                    return '<span class="display_currency qty_difference">' . number_format($row->difference_qty, 2, '.', ',') . '</span><input type="hidden"  class="form-control difference_qty" name="f22[' . $row->id . '][difference_qty]" id="f22[' . $row->id . '][difference_qty]" value="' . $row->difference_qty . '">';
                })
                ->editColumn('sku', function ($row) {
                    return '<input type="hidden" value="' . $row->product_code . '" name="f22[' . $row->id . '][sku]" id="f22[' . $row->id . '][sku]" > ' . $row->product_code;
                })
                ->editColumn('product', function ($row) {
                    return '<input type="hidden" value="' . $row->product . '" name="f22[' . $row->id . '][product]" id="f22[' . $row->id . '][product]" > ' . $row->product;
                })
                ->rawColumns(['total_purchase_price', 'total_sale_price', 'book_no', 'stock_count', 'qty_difference', 'unit_purchase_price', 'unit_sale_price', 'current_stock', 'sku', 'product'])
                ->make(true);
        }
    }
    
    public function getLastVerifiedF22FormHeader(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $last_form = FormF22Header::where('business_id', $business_id)->orderBy('id', 'desc')->first();
        
        // Get saved pump meters for this form
        $pumpMeters = [];
        if ($last_form) {
            $pumpMeters = \Modules\MPCS\Entities\FormF22PumpMeter::where('header_id', $last_form->id)->get();
        }
        
        return response()->json([
            'header' => $last_form,
            'pump_meters' => $pumpMeters
        ]);
    }

    public function printF22Form(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $data = array();
        parse_str($request->data, $data); // converting serialize string to array (fallback path)

        if ($request->filled('table_data')) {
            $decodedTable = json_decode($request->input('table_data'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedTable)) {
                $data['f22'] = $decodedTable;
            }
        }

        if ($request->filled('form_data')) {
            $decodedForm = json_decode($request->input('form_data'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedForm)) {
                foreach ($decodedForm as $item) {
                    if (isset($item['name'])) {
                        $data[$item['name']] = $item['value'] ?? null;
                    }
                }
            }
        }

        $settings = MpcsFormSetting::where('business_id',  $business_id)->select('F22_no_of_product_per_page')->first();
        $details = $data;

        $data = $data['f22'] ?? [];
        $date=$request->date_value;

        $requestForPumps = new Request([
            'sub_category_id' => $request->sub_category_id ?? null,
            'product_id' => $request->product_id ?? null,
            'date' => $request->date_value ?? null,
            'location_id' => $request->location_id ?? null,
        ]);
        $response = $this->fetchPumps($requestForPumps);
        $pumps = Collect($response->getData()->pumps);

        return view('mpcs::forms.F22.partials.print_f22_form')->with(compact('data', 'settings', 'details','date', 'pumps'));
    }

    public function printF22FormById($header_id, $pumps = null)
    {
        $business_id = request()->session()->get('user.business_id');

        $header = FormF22Header::leftjoin('business_locations', 'form_f22_headers.location_id', 'business_locations.id')
            ->where('form_f22_headers.id', $header_id)->select('business_locations.name as location_name', 'form_f22_headers.*')->first();
        // Get all active details for this header (no location filtering to show all entries)
        $details = FormF22Detail::where('header_id', $header_id)
            ->where('status', 1)
            ->orderBy('id', 'asc')
            ->get();
        $settings = MpcsFormSetting::where('business_id',  $business_id)->select('F22_no_of_product_per_page')->first();

        // Fetch saved pump meters for this form
        if ($pumps === null && $header) {
            $savedPumpMeters = \Modules\MPCS\Entities\FormF22PumpMeter::where('header_id', $header_id)->get();
            
            if ($savedPumpMeters->isNotEmpty()) {
                // Use saved pump meter data
                $pumps = $savedPumpMeters->map(function($meter) {
                    return (object)[
                        'id' => $meter->pump_id,
                        'pump_name' => $meter->pump_name,
                        'product_name' => $meter->product_name,
                        'last_meter_reading' => $meter->meter_reading,
                        'closing_meter' => $meter->meter_reading
                    ];
                });
            } else {
                // Fallback to fetching current pumps if no saved data
                $requestForPumps = new Request([
                    'sub_category_id' => null,
                    'product_id' => null,
                    'date' => $header->form_date,
                    'location_id' => $header->location_id,
                ]);
                $response = $this->fetchPumps($requestForPumps);
                $pumps = Collect($response->getData()->pumps);
            }
        } elseif ($pumps === null) {
            $pumps = collect();
        }

        return view('mpcs::forms.F22.partials.print_byID_f22_form')->with(compact('header', 'details', 'settings', 'pumps'));
    }

    public function saveF22Form(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        try {
            // Parse and validate incoming data
            $tableData = json_decode($request->input('table_data'), true);
            $formDataArray = json_decode($request->input('form_data'), true);
            $pumpMetersData = json_decode($request->input('pump_meters'), true);
            $dateValue = $request->input('date_value');

            // Log incoming data count
            Log::info('F22 Save - Received table rows: ' . count($tableData));
            
            // Convert form data array to associative array
            $formData = [];
            foreach ($formDataArray as $item) {
                $formData[$item['name']] = $item['value'];
            }
            
            // Convert pump meters to collection for easy access
            $pumpMeters = collect($pumpMetersData);


            if (empty($tableData) || empty($formData)) {
                return response()->json([
                    'success' => 0,
                    'msg' => __('messages.invalid_data')
                ], 400);
            }
            
            // Fetch settings BEFORE creating header to get fallback F22_form_tdate
            $settings = MpcsFormSetting::where('business_id', $business_id)->first();
            
            // Store the exact date selected in F22. Never silently replace an
            // invalid non-empty selected date with today's date.
            try {
                $formDate = $this->resolveF22FormDate(
                    $dateValue,
                    !empty($settings) ? $settings->F22_form_tdate : null
                );
            } catch (\InvalidArgumentException $e) {
                Log::warning('Invalid F22 selected date', ['date_value' => $dateValue]);

                return response()->json([
                    'success' => 0,
                    'msg' => $e->getMessage(),
                ], 422);
            }
            
            // Prepare header data
            // Resolve location_id; fallback to first permitted location if none selected
            $selected_location_id = $formData['f22_location_id'] ?? null;
            if (empty($selected_location_id)) {
                $permitted = $this->getPermittedLocations();
                if ($permitted !== 'all' && is_array($permitted) && count($permitted) > 0) {
                    $selected_location_id = $permitted[0];
                } else {
                    $selected_location_id = BusinessLocation::where('business_id', $business_id)->value('id');
                }
            }

            $headerData = [
                'form_no' => $formData['F22_from_no'] ?? null,
                'business_id' => $business_id,
                'location_id' => $selected_location_id,
                'manager_name' => $formData['manager_name'] ?? null,
                'approved_by' => $formData['approved_by'] ?? null,
                'is_approved' => !empty($formData['approved_by']) ? 1 : 0,
                'form_date' => $formDate,
                'purchase_price1' => $formData['purchase_price1'] ?? 0.00,
                'purchase_price2' => $formData['purchase_price2'] ?? 0.00,
                'purchase_price3' => $formData['purchase_price3'] ?? 0.00,
                'sales_price1' => $formData['sales_price1'] ?? 0.00,
                'sales_price2' => $formData['sales_price2'] ?? 0.00,
                'sales_price3' => $formData['sales_price3'] ?? 0.00,
                'status' => 1,
                'created_by' => Auth::user()->id
            ];

            DB::beginTransaction();

            // IS1960: counted quantities, applied after the stock adjustments below.
            $f22CountedStock = [];

            // Create the header record
            $header = FormF22Header::create($headerData);
            //dd(json_encode($header,JSON_PRETTY_PRINT));
            Log::info('Created header record:', ['header_id' => $header->id]);
            
            // Save pump meter readings
            if (!empty($pumpMetersData)) {
                foreach ($pumpMetersData as $pumpMeter) {
                    \Modules\MPCS\Entities\FormF22PumpMeter::create([
                        'header_id' => $header->id,
                        'pump_id' => $pumpMeter['pump_id'],
                        'pump_name' => $pumpMeter['pump_name'] ?? null,
                        'product_name' => $pumpMeter['product_name'] ?? null,
                        'meter_reading' => $pumpMeter['meter_reading'] ?? 0
                    ]);
                }
                Log::info('Saved ' . count($pumpMetersData) . ' pump meter readings');
            }

            // Fetch link account (required only when posting fallback financial transactions or overriding SA accounts)
            $linkAccount = FormF22LossGain::where('business_id', $business_id)->where('status', 'Enabled')->first();

            // Auto-create stock adjustments instead of direct VLD increments
            $autoCreateSA = true;
            $increaseProducts = [];
            $decreaseProducts = [];
            $saRefNos = [];


            $totalLoss = 0;
            $totalGain = 0;
            
            $savedCount = 0;
            $skippedCount = 0;

            // Process table data
            foreach ($tableData as $rowKey => $rowData) {
                try {
                    // Extract the actual item data from the nested structure
                    $item = [];
                    foreach ($rowData as $key => $value) {
                        if (strpos($key, 'f22[') === 0) {
                            // Extract the field name from keys like f22[10][product]
                            preg_match('/f22\[\d+\]\[(.+)\]/', $key, $matches);
                            if (isset($matches[1])) {
                                $item[$matches[1]] = $value;
                            }
                        } else {
                            $item[$key] = $value;
                        }
                    }

                    Log::debug('Processing item:', ['original' => $rowData, 'processed' => $item]);

                    if (!isset($item['product']) || !isset($item['sku'])) {
                        Log::warning('Skipping row - missing product or sku', ['item' => $item]);
                        $skippedCount++;
                        continue;
                    }

                    $difference = (isset($item['stock_count']) && isset($item['current_stock']))
                        ? (float)str_replace(',', '', $item['stock_count']) - (float)str_replace(',', '', $item['current_stock'])
                        : 0;


                    $stock_count = isset($item['stock_count']) ? str_replace(',', '', $item['stock_count']) : 0;
                    $unit_purchase_price = isset($item['unit_purchase_price']) ? str_replace(',', '', $item['unit_purchase_price']) : 0;
                    $unit_sale_price = isset($item['unit_sale_price']) ? str_replace(',', '', $item['unit_sale_price']) : 0;

                    // Compute clean prices using strict float casts after stripping formatting
                    $clean_unit_purchase_price = (float)$unit_purchase_price;
                    $clean_unit_sale_price = (float)$unit_sale_price;

                    $purchase_price_total = $stock_count * $clean_unit_purchase_price;
                    $sales_price_total = $stock_count * $clean_unit_sale_price;

                    // STEP 4 FIX: Resolve product/variation BEFORE saving to store product_id
                    $variation = null;
                    $product = null;
                    if (!empty($item['variation_id'])) {
                        $variation = Variation::where('id', $item['variation_id'])->first();
                        if ($variation) {
                            $product = Product::where('id', $variation->product_id)->first();
                        }
                    }
                    if (!$product) {
                        $product = Product::where('name', $item['product'])
                            ->where('business_id', $business_id)
                            ->first();
                        if ($product && !$variation) {
                            $variation = Variation::where('product_id', $product->id)->first();
                        }
                    }

                    $detailData = [
                        'header_id' => $header->id,
                        'business_id' => $business_id,
                        'form_no' => $headerData['form_no'],
                        'location_id' => $headerData['location_id'],
                        'product_code' => $item['sku'],
                        'product' => $item['product'],
                        'book_no' => $item['book_no'] ?? null,
                        'current_stock' => isset($item['current_stock']) ? str_replace(',', '', $item['current_stock']) : 0,
                        'stock_count' => $stock_count,
                        'unit_purchase_price' => $unit_purchase_price,
                        'unit_sale_price' => $unit_sale_price,
                        'purchase_price_total' => $purchase_price_total,
                        'sales_price_total' => $sales_price_total,
                        'difference_qty' => $difference,
                        'status' => 1,
                        'created_by' => Auth::user()->id
                    ];

                    Log::debug('Creating detail record:', $detailData);
                    FormF22Detail::create($detailData);
                    $savedCount++;

                    /*
                     * IS1960: remember the counted figure so available stock can be
                     * reset to it once the stock adjustments have been posted.
                     *
                     * A stock take is a physical count and is authoritative: after
                     * saving F22 the operation restarts from the counted quantity.
                     * Collected here rather than applied here because
                     * createStockAdjustmentFromF22() runs later and moves
                     * qty_available - setting it now would just be overwritten.
                     */
                    if ($product && $variation) {
                        $f22CountedStock[] = [
                            'product_id' => $product->id,
                            'variation_id' => $variation->id,
                            'counted_qty' => (float) $stock_count,
                        ];
                    }

                    // Adjust stock quantity if needed (skip if creating Stock Adjustments to avoid double-counting)
                    if (!$autoCreateSA && !empty($settings) && $settings->current_stock_aa_onstocktaking == 1 && isset($item['variation_id'])) {
                        VariationLocationDetails::where('variation_id', $item['variation_id'])
                            ->where('product_id', $item['product_id'] ?? null)
                            ->increment('qty_available', $difference);
                    }

                    // Skip if product/variation not resolved
                    if (!$product || !$variation) {
                        Log::warning('Skipping row - product/variation not resolved', ['item' => $item]);
                        continue;
                    }

                    $unitPrice = $variation->default_purchase_price ?? 0;

                    // Collect items for auto Stock Adjustment
                    if ($autoCreateSA && abs($difference) > 0) {
                        $sa_unit_price = isset($item['unit_purchase_price']) ? (float)str_replace(',', '', $item['unit_purchase_price']) : ((float)str_replace(',', '', $unitPrice) ?? 0);
                        $line = [
                            'product_id' => $product->id,
                            'variation_id' => $variation->id,
                            'quantity' => abs($difference),
                            'unit_price' => $sa_unit_price,
                        ];
                        if ($difference > 0) {
                            $increaseProducts[] = $line;
                        } elseif ($difference < 0) {
                            $decreaseProducts[] = $line;
                        }
                    }

                    if ($difference > 0) {
                        $totalGain += abs($difference) * $unitPrice; // positive diff = more stock found = GAIN
                    } elseif ($difference < 0) {
                        $totalLoss += abs($difference) * $unitPrice; // negative diff = less stock found = LOSS
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing table row: ' . $e->getMessage(), ['item' => $rowData]);
                    $skippedCount++;
                    continue;
                }
            }
            
            Log::info('F22 Save Summary - Saved: ' . $savedCount . ', Skipped: ' . $skippedCount);

            try {
                Log::debug('F22 Save Summary', [
                    'saved' => $savedCount,
                    'skipped' => $skippedCount,
                    'total_received' => count($tableData),
                ]);
            } catch (\Throwable $e) {
            }

            // Create Stock Adjustments from collected items
            $created_sa = false;
            if ($autoCreateSA) {
                if (!empty($increaseProducts)) {
                    $ref_no_inc = $this->createStockAdjustmentFromF22($business_id, $headerData['location_id'], $dateValue, 'increase', $increaseProducts, $linkAccount);
                    if (!empty($ref_no_inc)) { $saRefNos[] = $ref_no_inc; $created_sa = true; }
                }
                if (!empty($decreaseProducts)) {
                    $ref_no_dec = $this->createStockAdjustmentFromF22($business_id, $headerData['location_id'], $dateValue, 'decrease', $decreaseProducts, $linkAccount);
                    if (!empty($ref_no_dec)) { $saRefNos[] = $ref_no_dec; $created_sa = true; }
                }
                if (!empty($saRefNos)) {
                    // Save created Stock Adjustment form nos into header.pre_field for listing
                    $header->pre_field = implode(', ', $saRefNos);
                    $header->save();
                }
            }

            // Fallback financial transactions only if no Stock Adjustment created
            if (!$created_sa) {
                if (($totalLoss > 0 || $totalGain > 0) && !$linkAccount) {
                    DB::rollBack();
                    return response()->json([
                        'success' => 0,
                        'msg' => __('messages.link_account_not_found')
                    ], 400);
                }
                if ($totalLoss > 0) {
                    $this->createStockTransaction(
                        $business_id,
                        $headerData['location_id'],
                        $headerData['form_no'],
                        'shortage',
                        $totalLoss,
                        $linkAccount,
                        'Stock Loss'
                    );
                }
                if ($totalGain > 0) {
                    $this->createStockTransaction(
                        $business_id,
                        $headerData['location_id'],
                        $headerData['form_no'],
                        'overage',
                        $totalGain,
                        $linkAccount,
                        'Stock Gain'
                    );
                }
            }

            /*
             * IS1960: reset available stock to the counted quantity.
             *
             * The stock adjustments above move qty_available by the DIFFERENCE
             * between the system figure and the count. That only lands on the
             * counted value if the system figure was already correct - and for
             * fuel it was not: sales were never decrementing stock (IS1958 #1),
             * so qty_available had drifted far above reality (211,969 against a
             * counted 6,000 on Lanka Auto Diesel).
             *
             * A stock take is a physical count and is authoritative, so after
             * saving F22 the count becomes the new starting point regardless of
             * accumulated drift. This runs AFTER the adjustments so it is not
             * overwritten by them, and inside the same transaction so a later
             * failure rolls the reset back too.
             *
             * The stock adjustment records and their accounting entries are
             * untouched - only the on-hand figure is set.
             */
            if (!empty($f22CountedStock) && !empty($headerData['location_id'])) {
                foreach ($f22CountedStock as $counted) {
                    /*
                     * IS1971 #2: decide insert-vs-update by EXISTENCE, never by
                     * the return value of update().
                     *
                     * This previously ran the update first and inserted when it
                     * returned 0. update() reports rows CHANGED, not rows
                     * matched - so when the counted quantity already equalled
                     * the stored one (50.000 counted against 50.000 held, which
                     * is the normal case for a product that has not moved) MySQL
                     * returned 0, the code concluded no row existed, and it
                     * inserted a SECOND row for the same product, variation and
                     * location.
                     *
                     * Stock Center reads variation_location_details directly, so
                     * every such product then appeared twice - once per row -
                     * which is the duplication reported after saving an F22.
                     *
                     * exists() answers the question actually being asked.
                     */
                    $rowExists = DB::table('variation_location_details')
                        ->where('product_id', $counted['product_id'])
                        ->where('variation_id', $counted['variation_id'])
                        ->where('location_id', $headerData['location_id'])
                        ->exists();

                    if ($rowExists) {
                        DB::table('variation_location_details')
                            ->where('product_id', $counted['product_id'])
                            ->where('variation_id', $counted['variation_id'])
                            ->where('location_id', $headerData['location_id'])
                            ->update([
                                'qty_available' => $counted['counted_qty'],
                                'updated_at' => now(),
                            ]);
                    } else {
                        // Genuinely no row for this product at this location -
                        // create one so the counted figure still shows.
                        DB::table('variation_location_details')->insert([
                            'product_id' => $counted['product_id'],
                            'variation_id' => $counted['variation_id'],
                            'location_id' => $headerData['location_id'],
                            'qty_available' => $counted['counted_qty'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    Log::debug('IS1960 stock reset to F22 count', [
                        'product_id' => $counted['product_id'],
                        'variation_id' => $counted['variation_id'],
                        'location_id' => $headerData['location_id'],
                        'counted_qty' => $counted['counted_qty'],
                    ]);
                }
            }

            DB::commit();

            $requestForPumps = new Request([
                'sub_category_id' => $request->sub_category_id ?? null,
                'product_id' => $request->product_id ?? null,
                'date' => $request->date_value ?? null,
                'location_id' => $request->location_id ?? null,
            ]);
            $response = $this->fetchPumps($requestForPumps);
            $pumps = Collect($response->getData()->pumps);
            
            // Update pump meter readings with user input
            $pumps = $pumps->map(function($pump) use ($pumpMeters) {
                $userMeter = $pumpMeters->firstWhere('pump_id', $pump->id);
                if ($userMeter) {
                    $pump->last_meter_reading = $userMeter['meter_reading'];
                    $pump->closing_meter = $userMeter['meter_reading'];
                }
                return $pump;
            });
            
            return $this->printF22FormById($header->id, $pumps);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('Error saving F22 form: ' . $e->getMessage());
            return response()->json([
                'success' => 0,
                'msg' => __('messages.something_went_wrong') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    protected function createStockTransaction($businessId, $locationId, $formNo, $subType, $amount, $linkAccount, $description)
    {
        $transaction = Transaction::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'type' => 'stock_taking',
            'sub_type' => $subType,
            'status' => 'final',
            'ref_no' => 'F22 Form No ' . $formNo . ' ' . $description,
            'final_total' => $amount
        ]);

        /*
         * IS1975: post the one account this event actually concerns, on the
         * correct side.
         *
         * This used to debit the GAIN account and credit the LOSS account on
         * every call, whatever had happened. So a shortage credited the loss
         * book (wrong column) and also put an unrelated debit in the gain book,
         * and an overage did the same in reverse. Both books ended up carrying
         * an entry for an event that never touched them.
         *
         * A shortage debits the loss account; an overage credits the gain
         * account. Same rule as createStockAdjustmentFromF22() above, so the two
         * paths can no longer disagree about which column an F22 amount lands in.
         */
        $isShortage = ($subType === 'shortage');
        $accountId = $isShortage
            ? ($linkAccount->stock_loss_account ?? null)
            : ($linkAccount->stock_gain_account ?? null);

        if (empty($accountId)) {
            return;
        }

        AccountTransaction::createAccountTransaction([
            'amount' => $amount,
            'account_id' => $accountId,
            'type' => $isShortage ? 'debit' : 'credit',
            'sub_type' => 'ledger',
            'transaction_id' => $transaction->id,
            'created_by' => Auth::user()->id,
            'note' => 'Date: ' . \Carbon\Carbon::parse($transaction->transaction_date ?? now())->format('d/m/Y')
                . PHP_EOL . 'Stock adjustment No: ' . (string) $transaction->ref_no,
        ]);
    }

    public function store_stock_taking(Request $request)
    {



        try {
            $business_id = request()->session()->get('user.business_id');

            $data_header = [
                'business_id' => $business_id,
                'stock_loss_account' => $request->stock_loss_account ?? null,
                'stock_gain_account' => $request->stock_gain_account ?? null,
                'status' => "Enabled",
                'added_user' => Auth::user()->username
            ];

            DB::beginTransaction();
            DB::table('form_f22_loss_gains')
                ->where('business_id', $business_id)
                ->update(['status' => 'Disable']);
            $form_gain_loss= FormF22LossGain::create($data_header);

            DB::commit();

            return Redirect::back()->with('success');
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on error
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            return Redirect::back()->with('error', __('messages.something_went_wrong'));
        }
    }

    public function getF22FormListGainLoss(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if ($request->ajax()) {
            $header = FormF22LossGain::where('business_id', $business_id)
                    ->orderBy('updated_at', 'desc') // Sort by descending order
                    ->select('form_f22_loss_gains.*');

            return Datatables::of($header)
                ->addIndexColumn()
                ->removeColumn('id')
                ->addColumn('stock_loss_account', function ($row) {
                    $accountTypeGain = Account::where('id', $row->stock_loss_account)->first();
                    return $accountTypeGain ? $accountTypeGain->name : 'N/A'; // Avoid null errors
                })
                ->addColumn('stock_gain_account', function ($row) {
                    $accountTypeLoss = Account::where('id', $row->stock_gain_account)->first();
                    return $accountTypeLoss ? $accountTypeLoss->name : 'N/A'; // Fetch gain account name
                })
                ->addColumn('status', function ($row) {
                    if ($row->status === 'Enabled') {
                        return 'Enabled';
                    } else {
                        return 'Disable';
                    }
                })
             ->editColumn('action', function ($row) {
                $html = '';

                if (Gate::forUser(auth()->user())->check("edit_f22_stock_Taking_form")) {
                    $html = '<button
                        class="toggle-action-btn"
                        style="cursor: default;"
                        data-id="' . $row->id . '"
                        data-enabled="' . ($row->status === 'Enabled' ? 'Enabled' : 'Disable') . '"
                        aria-pressed="' . ($row->status === 'Enabled' ? 'Enabled' : 'Disable') . '">
                        ' . ($row->status === 'Enabled' ? 'Enabled' : 'Disable') . '
                    </button>';

                }

                return $html;
            })
            ->editColumn('updated_at', function ($row) {
                        return \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i:s');
                    })
            ->rawColumns(['status', 'action']) // Ensure HTML is not escaped
            ->make(true);
        }
    }

    public function checkUserExistence()
    {
        $business_id = request()->session()->get('user.business_id');
        $user_id = auth()->user()->username;

        // Check if the user already exists in the form_f22_loss_gains table
        $userExists = FormF22LossGain::where('business_id', $business_id)
            ->where('added_user', $user_id)
            ->exists();

        return response()->json(['exists' => $userExists]);
    }

    public function fetchPumps(Request $request)
    {
        try {
            $business_id = auth()->user()->business_id;
            $category = Category::where('business_id', $business_id)->where('name', 'Fuel')->first();

            $subCategoryIds = [];
            if (!empty($request->sub_category_id) && $request->sub_category_id != 'all') {
                $subCategoryIds[] = $request->sub_category_id;
            }
            $date = $request->date ? date('Y-m-d', strtotime($request->date)) : date('Y-m-d');

            $pumps = Pump::leftJoin('products', 'pumps.product_id', '=', 'products.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftjoin('business_locations', 'pumps.location_id', 'business_locations.id')
                ->leftjoin('fuel_tanks', 'pumps.fuel_tank_id', 'fuel_tanks.id')
                ->where('products.business_id', $business_id)
                ->where('categories.id', $category->id)
                ->select(
                    'pumps.*',
                    'products.name as product_name',
                    'pumps.pump_name as pump_name',

                )->when(!empty($subCategoryIds), function ($query) use ($subCategoryIds) {
                    $query->where('products.sub_category_id', $subCategoryIds);
                })
                ->when(!empty($request->product_id), function ($query) use ($request) {
                    $query->where('products.id', $request->product_id);
                })
                ->get()
                ->map(function ($pump) use ($date) {
                    if ($pump->transaction_date == $date) {
                        $pump->closing_meter = $pump->last_meter_reading;
                    } else {
                        $last = Pump::where('pump_name', $pump->pump_name)
                            ->where('transaction_date', '<', $date)
                            ->orderBy('transaction_date', 'desc')
                            ->value('last_meter_reading');
                        $pump->closing_meter = $last ?? $pump->starting_meter;
                    }
                    return $pump;
                });

            return response()->json(['pumps' => $pumps], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching pumps: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Failed to fetch pumps'], 500);
        }
    }

    public function getLinkaccountstate(Request $request)
    {

        $rowId = (int) $request->input('row_id');
        $isEnabled = $request->boolean('is_enabled');
        $business_id = request()->session()->get('user.business_id');
        \Illuminate\Support\Facades\Log::info('Link Account', ['Link' => $isEnabled]);
        if ($isEnabled) {
            // Disable all other rows
            DB::table('form_f22_loss_gains')
                ->where('business_id', $business_id)
                ->update(['status' => 'Disable']);
        }

        // Update the current row
        DB::table('form_f22_loss_gains')
            ->where('business_id', $business_id)
            ->where('id', $rowId)
            ->update(['status' => 'Enabled']);

        return response()->json(['success' => true]);
    }


    /**
     * Create a Stock Adjustment transaction (increase/decrease) from F22 data
     * Returns generated ref_no or null on failure
     */
    /**
     * IS1975: the description the account book is asked to show for an F22 posting.
     *
     *     Date: 10/08/2026
     *     Stock adjustment No: MK2026/0003
     *
     * Written into account_transactions.note, which is the only free-text field
     * carried on an accounting entry. Previously the note said 'F22 Stock Gain'
     * or 'F22 Stock Loss', which told the reader nothing about WHICH adjustment
     * an amount came from - the whole point of the requested format.
     *
     * NOTE FOR REVIEW: whether this reaches the screen depends on the Accounting
     * module's account book rendering the note column. In the reported
     * screenshots the description showed only the transaction type and its
     * reference, so that view may need a matching change; the data is correct
     * from this side either way.
     */
    protected function f22AccountNarration($stock_adjustment): string
    {
        $date = $stock_adjustment->transaction_date ?? null;

        try {
            $date = !empty($date)
                ? \Carbon\Carbon::parse($date)->format('d/m/Y')
                : \Carbon\Carbon::now()->format('d/m/Y');
        } catch (\Exception $e) {
            // A date that cannot be parsed must not stop the posting; the
            // reference below is enough to identify the adjustment.
            $date = (string) $date;
        }

        return 'Date: ' . $date . PHP_EOL
            . 'Stock adjustment No: ' . (string) ($stock_adjustment->ref_no ?? '');
    }

    protected function createStockAdjustmentFromF22($business_id, $location_id, $dateValue, $stock_adjustment_type, $products, $linkAccount = null)
    {
        if (empty($products)) { return null; }

        // Ensure session has business_id for ProductUtil calls
        if (!request()->session()->has('user.business_id')) {
            request()->session()->put('user.business_id', $business_id);
        }

        $input_data = [
            'location_id' => $location_id,
            'transaction_date' => $dateValue,
            'adjustment_type' => 'normal',
            'stock_adjustment_type' => $stock_adjustment_type,
            'additional_notes' => 'F22 Stock Taking',
            'total_amount_recovered' => 0,
            'final_total' => 0,
            'type' => 'stock_adjustment',
            'status' => 'received',
            'business_id' => $business_id,
            'created_by' => Auth::user()->id,
        ];

        // Format date & generate ref no
        try {
            if (!empty($input_data['transaction_date'])) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input_data['transaction_date'])) {
                    $input_data['transaction_date'] = $input_data['transaction_date'] . ' ' . date('H:i:s');
                } else {
                    $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
                }
            } else {
                $input_data['transaction_date'] = \Carbon\Carbon::now()->toDateTimeString();
            }
        } catch (\Exception $e) {
            $input_data['transaction_date'] = \Carbon\Carbon::now()->toDateTimeString();
        }
        $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment');
        $input_data['ref_no'] = $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count);

        // Compute final total
        $final_total = 0;
        foreach ($products as $p) {
            $final_total += ($this->productUtil->num_uf($p['quantity']) * $this->productUtil->num_uf($p['unit_price']));
        }
        $input_data['final_total'] = $final_total;

        $stock_adjustment = Transaction::create($input_data);

        $product_data = [];
        $linkAccountCoveredAmount = 0.0; // Tracks amounts already posted to gain/loss account per-product
        foreach ($products as $product) {
            $qty = $this->productUtil->num_uf($product['quantity']);
            $uprice = $this->productUtil->num_uf($product['unit_price']);
            $adjustment_line = [
                'product_id' => $product['product_id'],
                'variation_id' => $product['variation_id'],
                'quantity' => $qty,
                'unit_price' => $uprice,
                'type' => 'normal',
                'stock_adjustment_type' => $stock_adjustment_type,
            ];
            $product_data[] = $adjustment_line;

            // Map F22 terminology to generic ProductUtil terminology
            $util_adjustment_type = $stock_adjustment_type;

            // Adjust stock quantities
            $this->productUtil->decreaseProductQuantity(
                $product['product_id'],
                $product['variation_id'],
                $location_id,
                $qty,
                0,
                $util_adjustment_type
            );

            $store_id = $stock_adjustment->store_id ?? optional(Store::where('business_id', $business_id)->first())->id;
            if (!empty($store_id)) {
                $this->productUtil->decreaseProductQuantityStore(
                    $product['product_id'],
                    $product['variation_id'],
                    $location_id,
                    $qty,
                    $store_id,
                    $util_adjustment_type,
                    0
                );
            }

            // Accounting entries per product.
            // When F22 link accounts are configured, they should be the visible
            // gain/loss books for these postings, so prefer them over the
            // category-level price increment/reduction accounts.
            $this_product = Product::where('id', $product['product_id'])->first();
            if ($this_product) {
                $category  = Category::where('id', $this_product->sub_category_id)->first();
                $prod_amount = $qty * $uprice;

                if ($stock_adjustment_type === 'increase') {
                    if (!empty($this_product->stock_type)) {
                        AccountTransaction::createAccountTransaction([
                            'amount' => $prod_amount,
                            'account_id' => $this_product->stock_type,
                            'type' => 'debit',
                            'operation_date' => $stock_adjustment->transaction_date,
                            'created_by' => $stock_adjustment->created_by,
                            'transaction_id' => $stock_adjustment->id,
                            'transaction_payment_id' => null,
                            'note' => null,
                        ]);
                    }
                    $price_increment_acc = !empty($linkAccount->stock_gain_account)
                        ? $linkAccount->stock_gain_account
                        : optional($category)->price_increment_acc;
                    $setting = StockAdjustmentSetting::where('business_id', $business_id)
                        ->where('sub_category_id', $this_product->sub_category_id)
                        ->where('adjustment_type', 'increase')
                        ->first();
                    if (empty($linkAccount->stock_gain_account) && !is_null($setting)) {
                        $price_increment_acc = $setting->account_to_link;
                    }



                    if (!empty($price_increment_acc)) {
                        AccountTransaction::createAccountTransaction([
                            'amount' => $prod_amount,
                            'account_id' => $price_increment_acc,
                            /*
                             * IS1975: a stock GAIN is a CREDIT.
                             *
                             * This posted 'debit', which put the amount in the
                             * wrong column of the Linked stock gain account book.
                             * It also meant the entry did not balance: the stock
                             * asset account a few lines above is debited for the
                             * same amount, so both sides of one adjustment were
                             * being debited.
                             *
                             * Debit stock (the asset went up), credit the gain
                             * account (the offsetting income). That is what the
                             * issue asks for and it balances.
                             */
                            'type' => 'credit',
                            'operation_date' => $stock_adjustment->transaction_date,
                            'created_by' => $stock_adjustment->created_by,
                            'transaction_id' => $stock_adjustment->id,
                            'transaction_payment_id' => null,
                            'note' => $this->f22AccountNarration($stock_adjustment),
                        ]);
                        // Track amount covered by per-product accounting so we don't double-post via linkAccount.
                        $linkAccountCoveredAmount += $prod_amount;
                    }
                } else { // decrease
                    if (!empty($this_product->stock_type)) {
                        AccountTransaction::createAccountTransaction([
                            'amount' => $prod_amount,
                            'account_id' => $this_product->stock_type,
                            'type' => 'credit',
                            'operation_date' => $stock_adjustment->transaction_date,
                            'created_by' => $stock_adjustment->created_by,
                            'transaction_id' => $stock_adjustment->id,
                            'transaction_payment_id' => null,
                            'note' => null,
                        ]);
                    }
                    $price_reduction_acc = !empty($linkAccount->stock_loss_account)
                        ? $linkAccount->stock_loss_account
                        : optional($category)->price_reduction_acc;
                    $setting = StockAdjustmentSetting::where('business_id', $business_id)
                        ->where('sub_category_id', $this_product->sub_category_id)
                        ->where('adjustment_type', 'decrease')
                        ->first();
                    if (empty($linkAccount->stock_loss_account) && !is_null($setting)) {
                        $price_reduction_acc = $setting->account_to_link;
                    }



                    if (!empty($price_reduction_acc)) {
                        AccountTransaction::createAccountTransaction([
                            'amount' => $prod_amount,
                            'account_id' => $price_reduction_acc,
                            /*
                             * IS1975: a stock LOSS is a DEBIT.
                             *
                             * The mirror of the gain case above - this posted
                             * 'credit', landing in the wrong column of the Linked
                             * stock loss account book, and credited both this
                             * account and the stock asset account for the same
                             * amount.
                             *
                             * Credit stock (the asset went down), debit the loss
                             * account (the offsetting expense).
                             */
                            'type' => 'debit',
                            'operation_date' => $stock_adjustment->transaction_date,
                            'created_by' => $stock_adjustment->created_by,
                            'transaction_id' => $stock_adjustment->id,
                            'transaction_payment_id' => null,
                            'note' => $this->f22AccountNarration($stock_adjustment),
                        ]);
                        // Track amount covered by per-product accounting so we don't double-post via linkAccount.
                        $linkAccountCoveredAmount += $prod_amount;
                    }
                }
            }
        }

        // Post to linkAccount gain/loss accounts for any amount NOT already covered by per-product accounting.
        // Lubricant items have their own price_increment_acc/price_reduction_acc from the sub-category,
        // so their amounts are tracked in $linkAccountCoveredAmount and excluded here to avoid double-posting.
        // Fuel products (no category-level accounts) will have linkAccountCoveredAmount = 0, so the full
        // final_total is posted here for them.
        if ($linkAccount) {
            $residualAmount = round($final_total - $linkAccountCoveredAmount, 4);
            if ($stock_adjustment_type === 'increase' && !empty($linkAccount->stock_gain_account) && $residualAmount > 0) {
                AccountTransaction::createAccountTransaction([
                    'amount'                 => $residualAmount,
                    'account_id'             => $linkAccount->stock_gain_account,
                    // IS1975: gain = credit. Same correction as the per-product
                    // branch above; this residual path handles fuel products,
                    // which carry no category-level account.
                    'type'                   => 'credit',
                    'operation_date'         => $stock_adjustment->transaction_date,
                    'created_by'             => $stock_adjustment->created_by,
                    'transaction_id'         => $stock_adjustment->id,
                    'transaction_payment_id' => null,
                    'note'                   => $this->f22AccountNarration($stock_adjustment),
                ]);
            } elseif ($stock_adjustment_type === 'decrease' && !empty($linkAccount->stock_loss_account) && $residualAmount > 0) {
                AccountTransaction::createAccountTransaction([
                    'amount'                 => $residualAmount,
                    'account_id'             => $linkAccount->stock_loss_account,
                    // IS1975: loss = debit.
                    'type'                   => 'debit',
                    'operation_date'         => $stock_adjustment->transaction_date,
                    'created_by'             => $stock_adjustment->created_by,
                    'transaction_id'         => $stock_adjustment->id,
                    'transaction_payment_id' => null,
                    'note'                   => $this->f22AccountNarration($stock_adjustment),
                ]);
            }
        }

        // Persist lines & map purchase/stock
        $stock_adjustment->stock_adjustment_lines()->createMany($product_data);
        $business = [
            'id' => $business_id,
            'accounting_method' => request()->session()->get('business.accounting_method'),
            'location_id' => $location_id,
        ];
        $this->transactionUtil->mapPurchaseSell($business, $stock_adjustment->stock_adjustment_lines, 'stock_adjustment');

        return $stock_adjustment->ref_no;
    }

    public function getBySubCategory($id)
    {
        $products = Product::where('sub_category_id', $id)
            ->pluck('name', 'id');

        return response()->json($products);
    }
    public function approveF22(Request $request)
    {
        try {
            $user = auth()->user();

            // Check if user is superadmin OR has the approve_f22_stock_taking permission
            $canApprove = $user->is_superadmin_default == 1
                || Gate::forUser($user)->check('approve_f22_stock_taking');

            if (!$canApprove) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to approve this stock taking form.'
                ]);
            }

            $username = $user->username ?? ($user->first_name . ' ' . $user->last_name);

            return response()->json([
                'success'  => true,
                'username' => $username,
            ]);

        } catch (\Exception $e) {
         Log::error('approveF22 error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error. Please try again.'
            ]);
        }
    }

    /**
     * Get F22 Signatures for DataTable
     */
    public function getF22Signatures(Request $request)
    {
        if ($request->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $signatures = \Modules\MPCS\Entities\F22Signature::where('business_id', $business_id)
                ->with('createdBy:id,username,first_name,last_name')
                ->select('id', 'signature_path', 'created_by', 'created_at')
                ->orderBy('created_at', 'desc');

            return \Yajra\DataTables\Facades\DataTables::of($signatures)
                ->addColumn('date_time', function ($row) {
                    return $row->created_at->format('Y-m-d H:i');
                })
                ->addColumn('signature_preview', function ($row) {
                    if ($row->signature_path && file_exists(public_path('uploads/' . $row->signature_path))) {
                        return '<img src="' . asset('uploads/' . $row->signature_path) . '" style="max-width: 150px; max-height: 60px;" alt="Signature">';
                    }
                    return '-';
                })
                ->addColumn('added_by', function ($row) {
                    return $row->createdBy ? trim($row->createdBy->first_name . ' ' . $row->createdBy->last_name) : '-';
                })
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">';
                    $html .= '<button type="button" class="btn btn-info btn-xs btn-modal view_signature_btn" 
                        data-href="' . action([\Modules\MPCS\Http\Controllers\F22FormController::class, 'viewF22Signature'], [$row->id]) . '"
                        data-container=".signature_modal">
                        <i class="glyphicon glyphicon-eye-open"></i> ' . __('messages.view') . '
                    </button>';
                    $html .= '<button type="button" class="btn btn-primary btn-xs btn-modal edit_signature_btn" 
                        data-href="' . action([\Modules\MPCS\Http\Controllers\F22FormController::class, 'editF22Signature'], [$row->id]) . '"
                        data-container=".signature_modal">
                        <i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '
                    </button>';
                    $html .= '<button type="button" class="btn btn-danger btn-xs delete_signature_btn" 
                        data-href="' . action([\Modules\MPCS\Http\Controllers\F22FormController::class, 'destroyF22Signature'], [$row->id]) . '">
                        <i class="fa fa-trash"></i> ' . __('messages.delete') . '
                    </button>';
                    $html .= '</div>';
                    return $html;
                })
                ->rawColumns(['action', 'signature_preview'])
                ->make(true);
        }
    }

    /**
     * Store F22 signature
     */
    public function storeF22Signature(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $request->validate([
                'signature' => 'required|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:2048'
            ]);

            if ($request->hasFile('signature')) {
                $file = $request->file('signature');
                
                $uploadPath = public_path('uploads/f22_signatures');
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                
                $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                
                $file->move($uploadPath, $filename);
                
                \Modules\MPCS\Entities\F22Signature::create([
                    'business_id' => $business_id,
                    'signature_path' => 'f22_signatures/' . $filename,
                    'created_by' => auth()->id(),
                ]);

                return response()->json([
                    'success' => true,
                    'msg' => __('messages.saved_successfully')
                ], 200);
            }

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 400);
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * View F22 signature
     */
    public function viewF22Signature($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $signature = \Modules\MPCS\Entities\F22Signature::where('id', $id)
            ->where('business_id', $business_id)
            ->with('createdBy:id,username,first_name,last_name')
            ->firstOrFail();

        return view('mpcs::forms.F22.partials.f22_signature_view', compact('signature'));
    }

    /**
     * Edit F22 signature
     */
    public function editF22Signature($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $signature = \Modules\MPCS\Entities\F22Signature::where('id', $id)
            ->where('business_id', $business_id)
            ->firstOrFail();

        return view('mpcs::forms.F22.partials.f22_signature_edit', compact('signature'));
    }

    /**
     * Resolve the selected F22 date to an ISO database date.
     *
     * @throws \InvalidArgumentException
     */
    private function resolveF22FormDate($selectedDate, $settingsDate = null): string
    {
        $selectedDate = trim((string) $selectedDate);

        if ($selectedDate !== '') {
            /*
             * IS1958: resolve the date using the BUSINESS's configured format
             * before falling back to guesswork.
             *
             * This used to run the ambiguous list below first, and 'd/m/Y' sits
             * ahead of 'm/d/Y' in it. For a business whose date format is m/d/Y,
             * "08/02/2026" means 2 August - but 'd/m/Y' matches it strictly
             * first and yields 8 FEBRUARY. The strict match meant uf_date(),
             * which does know the configured format, was never reached.
             *
             * The saved header was therefore stamped 2026-02-08 instead of
             * 2026-08-02. Nothing looked broken at save time, but the F22 list
             * is ordered by form_date DESC, so the form sorted below every
             * earlier month and looked missing - the reported symptom.
             *
             * Order is now: unambiguous ISO, then the business format, then the
             * old list as a last resort.
             */

            // 1. Y-m-d is unambiguous - no locale can reinterpret it.
            try {
                $isoParsed = Carbon::createFromFormat('Y-m-d', $selectedDate);
                if ($isoParsed !== false && $isoParsed->format('Y-m-d') === $selectedDate) {
                    return $isoParsed->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // Not an ISO date - fall through.
            }

            // 2. The business's own configured date format. This is what the
            //    date picker actually produced, so it is authoritative.
            try {
                $parsed = $this->productUtil->uf_date($selectedDate);
                if (!empty($parsed)) {
                    return Carbon::parse($parsed)->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // Fall through to the tolerant list below.
            }

            // 3. Last resort for legacy or hand-typed values. Still ambiguous
            //    between d/m/Y and m/d/Y, which is exactly why it now runs last.
            foreach (['d/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'] as $format) {
                try {
                    $parsed = Carbon::createFromFormat($format, $selectedDate);
                    if ($parsed !== false && $parsed->format($format) === $selectedDate) {
                        return $parsed->format('Y-m-d');
                    }
                } catch (\Throwable $e) {
                    // Try the next supported format.
                }
            }

            throw new \InvalidArgumentException('Please select a valid F22 date.');
        }

        if (!empty($settingsDate)) {
            try {
                return Carbon::parse($settingsDate)->format('Y-m-d');
            } catch (\Throwable $e) {
                // Continue to the safe system default below.
            }
        }

        return Carbon::today()->format('Y-m-d');
    }

    /**
     * Update F22 signature
     */
    public function updateF22Signature(Request $request, $id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $signature = \Modules\MPCS\Entities\F22Signature::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            if ($request->hasFile('signature')) {
                $request->validate([
                    'signature' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
                ]);

                if ($signature->signature_path && file_exists(public_path('uploads/' . $signature->signature_path))) {
                    unlink(public_path('uploads/' . $signature->signature_path));
                }

                $file = $request->file('signature');
                
                $uploadPath = public_path('uploads/f22_signatures');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                
                $filename = time() . '_' . $file->getClientOriginalName();
                
                $file->move($uploadPath, $filename);
                
                $signature->update([
                    'signature_path' => 'f22_signatures/' . $filename,
                ]);

                return response()->json([
                    'success' => true,
                    'msg' => __('messages.updated_successfully')
                ]);
            }

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }

    /**
     * Delete F22 signature
     */
    public function destroyF22Signature($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $signature = \Modules\MPCS\Entities\F22Signature::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            // Delete file if exists
            if ($signature->signature_path && file_exists(public_path('uploads/' . $signature->signature_path))) {
                unlink(public_path('uploads/' . $signature->signature_path));
            }

            $signature->delete();

            return response()->json([
                'success' => true,
                'msg' => __('messages.deleted_success')
            ]);
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }
}

<?php

namespace Modules\MPCS\Http\Controllers;
use App\Account;
use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\Store;
use App\Unit;
use Modules\MPCS\Entities\Pump;
use App\AccountTransaction;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redirect;
use Modules\MPCS\Entities\FormF16Detail;
use Modules\MPCS\Entities\FormF17Detail;
use Modules\MPCS\Entities\FormF17Header;
use Modules\MPCS\Entities\FormF17HeaderController;
use Modules\MPCS\Entities\FormF22Header;
use Modules\MPCS\Entities\FormF22Detail;
use App\Contact;
use App\Transaction;
use App\MergedSubCategory;
use Modules\MPCS\Entities\Mpcs21cFormSettings;


class F21CFormController extends Controller
{
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
   
    
    
    public function index(Request $request)
{
    
        if (!auth()->check()) {
            return Redirect::route('login');
        } 
       
        // Resolve the active business from the authenticated/session context only.
        // Never fall back to Business::first() on the master system: a central
        // database can contain many businesses and that fallback silently loads
        // another business' data when the AJAX/session context is missing.
        $business_id = $request->session()->get('business.id')
            ?? $request->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id;

        if (empty($business_id)) {
            return Redirect::back()->with('status', [
                'success' => 0,
                'msg' => 'Business context is missing. Please sign in again.',
            ]);
        }

        $business_details = Business::find($business_id);

        if (empty($business_details)) {
            return Redirect::back()->with('status', [
                'success' => 0,
                'msg' => 'Business was not found in the active database.',
            ]);
        }

        $currency_precision = (int) ($business_details->currency_precision ?? 2);
        $qty_precision = (int) ($business_details->quantity_precision ?? 2);
      
        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();
        $business_locations = BusinessLocation::forDropdown($business_id);
        $user = auth()->user();
        $permitted_locations = (is_object($user) && method_exists($user, 'permitted_locations'))
            ? $user->permitted_locations()
            : 'all';
        $locations_for_user = $business_locations;
        if ($permitted_locations !== 'all' && is_array($permitted_locations)) {
            $locations_for_user = collect($business_locations)->only($permitted_locations);
        }
        $location_keys = collect($locations_for_user)->keys()->values();

        // Determine default value based on location count and request
        if (count($locations_for_user) === 1) {
            $default_location_id = $location_keys->first();
            $location_options = collect($locations_for_user)->toArray();
        } else {
            $default_location_id = $request->get('location_id', ''); 
            $location_options = ['' => __('lang_v1.all')] + collect($locations_for_user)->toArray();
        }


        // Filter to show only Fuel category sub-categories
        $fuelCategory = Category::subCategoryOnlyFuel($business_id)
            ->sortBy('name')
            ->pluck('name', 'id');
       
          $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();
          $settings = MpcsFormSetting::where('business_id', $business_id)->first();

          // Get the latest 21C form settings record (for the list tab display)
          $latestForm = Mpcs21cFormSettings::where('business_id', $business_id)
              ->orderBy('date', 'desc')
              ->first();

          // Allow creating settings only once per day
          $today = Carbon::today()->toDateString();
          $has_today_21c_settings = Mpcs21cFormSettings::where('business_id', $business_id)
              ->where('date', $today)
              ->exists();

          // Build $categoriesData keyed by category_id for list_f21c.blade.php
          // The val stored here = Qty * Unit Sale Price inc. tax (locked at save time - never changes with price changes)
          $categoriesData = [];
          if ($latestForm && $latestForm->categories) {
              $rawCategories = json_decode($latestForm->categories, true);
              if (is_array($rawCategories)) {
                  foreach ($rawCategories as $catId => $values) {
                      $categoriesData[$catId] = [
                          'previous_day'  => [
                              'qty' => $values['previous_day']['qty'] ?? '',
                              'val' => $values['previous_day']['val'] ?? 0,
                          ],
                          'opening_stock' => [
                              'qty' => $values['opening_stock']['qty'] ?? '',
                              'val' => $values['opening_stock']['val'] ?? 0,
                          ],
                          'total_issues'  => [
                              'qty' => $values['total_issues']['qty'] ?? '',
                              'val' => $values['total_issues']['val'] ?? 0,
                          ],
                      ];
                  }
              }
          }

          // Ensure all fuel sub-categories have an entry even if not saved yet
          foreach ($fuelCategory as $catId => $catName) {
              if (!isset($categoriesData[$catId])) {
                  $categoriesData[$catId] = [
                      'previous_day'  => ['qty' => '', 'val' => 0],
                      'opening_stock' => ['qty' => '', 'val' => 0],
                      'total_issues'  => ['qty' => '', 'val' => 0],
                  ];
              }
          }

          // Pump operator data for 21c_form tab (initial page load - today's date)
          $fuelCategoryIds = $fuelCategory->keys()->toArray();
          $start_date_today = Carbon::today()->format('Y-m-d');
          $meter_end_date = Carbon::tomorrow()->format('Y-m-d');
          $pump_operator = DB::table('pumps')
              ->leftJoin('products', 'pumps.product_id', '=', 'products.id')
              ->leftJoin('categories', 'products.sub_category_id', '=', 'categories.id')
              ->leftJoin('meter_sales', function ($join) use ($start_date_today, $meter_end_date) {
                  $join->on('pumps.id', '=', 'meter_sales.pump_id');
                  $join->whereBetween('meter_sales.created_at', [$start_date_today, $meter_end_date]);
              })
              ->leftJoin('settlements', 'meter_sales.settlement_no', '=', 'settlements.id')
              ->leftJoin('business_locations', 'settlements.location_id', '=', 'business_locations.id')
              ->leftJoin('pump_operators', 'settlements.pump_operator_id', '=', 'pump_operators.id')
              ->whereIn('categories.id', $fuelCategoryIds)
              ->where(function ($query) use ($business_id) {
                  $query->where('settlements.business_id', $business_id)
                        ->orWhereNull('settlements.business_id');
              })
              ->groupBy(
                  'categories.id', 'categories.name', 'pumps.pump_no', 'pumps.id',
                  'products.name', 'business_locations.name', 'pump_operators.name'
              )
              ->select([
                  'categories.id as category_id',
                  'categories.name as category_name',
                  'pumps.pump_no',
                  'pumps.id as pump_id',
                  'products.name as product_name',
                  'business_locations.name as location_name',
                  DB::raw("COALESCE(pump_operators.name, '-') as pump_operator_name"),
              ])
              ->get();

          if (!empty($settings)) {
            // Get today's date and the starting day from settings (assumes the field 'date' is the starting day)
            $current_date = Carbon::today();
            $starting_day = Carbon::parse($settings->date);
            $days_passed = $starting_day->diffInDays($current_date);
            // If no days have passed yet, use the starting number
            if ($days_passed === 0) {
                $F21c_from_no = $settings->starting_number;
            } else {
                // Otherwise, increment the form number based on the number of days passed
                // Add the number of days passed to the starting number
                $F21c_from_no = $settings->starting_number + $days_passed;
            }
        } else {
            $F21c_from_no = '';
        }
        $layout = 'layouts.app';
        return view('mpcs::forms.21CForm.F21_form')->with(compact(
           'F21c_from_no',
            'sub_categories',
            'currency_precision',
            'qty_precision',
            'merged_sub_categories',
             'business_locations',
             'location_options',
             'default_location_id',
            'layout',
            'fuelCategory',
            'latestForm',
            'has_today_21c_settings',
            'categoriesData',
            'settings',
            'pump_operator'
            ));

    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
        public function get21CForms(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
        if (!empty($settings)) {
            $F9C_sn = $settings->F9C_sn;
        } else {
            $F9C_sn = 1;
        }
        if (request()->ajax()) {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $location_id = $request->location_id;

            $credit_sales = $this->Form9CQuery($business_id, $start_date, $end_date, $location_id);

            $location = [];
            if (!empty($request->location_id)) {
                $location = BusinessLocation::findOrFail($request->location_id);
            }

            $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();

            return view('mpcs::forms.partials.9c_details_section')->with(compact(
                'credit_sales',
                'sub_categories',
                'start_date',
                'end_date',
                'location',
                'F9C_sn'
            ));
        }
    }
    
     public function get_21_c_form_all_query(Request $request)
    {
       
        // Keep the AJAX request on exactly the same business context as the page.
        // This is critical on the master/central system where multiple businesses
        // share one database. Falling back to Business::first() can make F21 look
        // empty even though the selected business has data.
        $business_id = $request->session()->get('business.id')
            ?? $request->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id;

        if (empty($business_id)) {
            return response()->json([
                'error' => 'Business context is missing. Please sign in again.',
            ], 403);
        }

        $business_details = Business::find($business_id);

        if (empty($business_details)) {
            return response()->json([
                'error' => 'Business was not found in the active database.',
            ], 404);
        }

        // Early return for form number only request
        if ($request->has('form_number_only') && $request->form_number_only) {
            $start_date = $request->start_date;
            $latest = Mpcs21cFormSettings::where('business_id', $business_id)
                ->where('date', '<=', $start_date)
                ->orderBy('date', 'desc')
                ->first();
            
            if (!$latest) {
                $latest = Mpcs21cFormSettings::where('business_id', $business_id)->orderBy('date', 'asc')->first();
            }
            
            $header = Mpcs21cFormSettings::where('business_id', $business_id)
                ->where('date', '<=', $start_date)
                ->orderBy('date', 'desc')
                ->get();
            
            return response()->json([
                'header' => $header->toArray(),
            ]);
        }

        $currency_precision = (int) ($business_details->currency_precision ?? 2);
        $qty_precision = (int) ($business_details->quantity_precision ?? 2);
        
        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();
        // Filter to show only Fuel category sub-categories
        $fuelCategoryIds = Category::subCategoryOnlyFuel($business_id)
            ->pluck('id')
            ->toArray();

        // Robust date parsing matching F16A's style
        $startDate = !empty($request->start_date) ? Carbon::parse($request->start_date)->startOfDay() : Carbon::today()->startOfDay();
        $endDate   = !empty($request->end_date) ? Carbon::parse($request->end_date)->endOfDay() : Carbon::today()->endOfDay();
        
        // Strings for strict string-matching columns (DATE types)
        $start_date = $startDate->format('Y-m-d');
        $end_date   = $endDate->format('Y-m-d');
        $location_id = $request->location_id;

        // 21C is a read-heavy report. Cache the fully aggregated payload briefly so
        // repeated date/location loads do not execute the same large query set again.
        // Database name is included because this installation is multi-tenant.
        $tenantDatabase = DB::connection()->getDatabaseName();
        $f22CacheQuery = DB::table('form_f22_headers')
            ->where('business_id', $business_id)
            ->whereDate('form_date', '<=', $start_date)
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('location_id', $location_id);
            });
        $latestF22CacheStamp = (clone $f22CacheQuery)->max('updated_at');
        $latestF22CacheId = (clone $f22CacheQuery)->max('id');

        $cacheKey = 'mpcs:21c:v28:' . sha1(implode('|', [
            $tenantDatabase,
            (string) $business_id,
            (string) ($location_id ?: 'all'),
            $start_date,
            $end_date,
            (string) ($latestF22CacheStamp ?: 'no-f22'),
            (string) ($latestF22CacheId ?: 'no-f22-id'),
        ]));

        if ($cachedPayload = Cache::get($cacheKey)) {
            return response()->json($cachedPayload)
                ->header('X-MPCS-21C-Cache', 'HIT');
        }

        // Initialize all variables to avoid undefined variable errors
        $today_sales = [];
        $pump_operator = [];
        $discount_previous = [];
        $discount_todays = [];
        $cash_sales_previous = [];
        $credit_sales_today = [];
        $cash_sales_today = [];
        $opening_stock = [];
        $previous_day = [];
        $total_issues = [];
        $header_latest = null;
        $mpcs21c_settings_header = [];
        $today_f16_nos = [];
        $previous_f16_nos = [];
        $credit_sales = [];
        $previous_credit_sales = [];
        $form22_details = null;
        $form17_increase = null;
        $form17_increase_previous = null;
        $form17_decrease = null;
        $form17_decrease_previous = null;
        $transaction = null;
        $previous_transaction = null;
        $own_group = null;
        $previous_own_group = null;
        $credit_sales_transaction = null;
        $previous_credit_sales_transaction = null;
        $account_transactions = [];
        
        // Filter to show only Fuel category sub-categories
        $fuelCategory = Category::subCategoryOnlyFuel($business_id)
            ->sortBy('name')
            ->pluck('name', 'id');
        $header = collect([]);

        // 1. Find the applicable settings record: the latest one on or before the selected start_date
        $latest = Mpcs21cFormSettings::where('business_id', $business_id)
            ->where('date', '<=', $start_date)
            ->orderBy('date', 'desc')
            ->first();

        // Baseline for accumulation: MUST be strictly before selected date to avoid self-referencing
        $latestBefore = Mpcs21cFormSettings::where('business_id', $business_id)
            ->where('date', '<', $start_date)
            ->orderBy('date', 'desc')
            ->first();

        // Fallback to the earliest available setting if no past setting is found
        if (!$latest) {
            $latest = Mpcs21cFormSettings::where('business_id', $business_id)->orderBy('date', 'asc')->first();
        }

        // Header info for form numbering and manager name
        $header = Mpcs21cFormSettings::where('business_id', $business_id)
            ->where('date', '<=', $start_date)
            ->orderBy('date', 'desc')
            ->get();

        $header_latest = $latest ? $latest->date : null;
        $starting_number = $latest ? $latest->starting_number : 0;
        $current_date = Carbon::parse($start_date);
        $starting_day = $latest ? Carbon::parse($latest->date) : Carbon::today();
        
        // Days passed since opening date (used for form number auto-increment)
        $days_passed = $starting_day->diffInDays($current_date, false);
        // Return null if no settings record exists for the selected date, so the
        // front-end can show "No form found for the selected date" instead of 0.
        $calculated_form_no = $latest
            ? ($starting_number + ($days_passed >= 0 ? (int)$days_passed : 0))
            : null;
        $calculated_manager_name = $latest ? $latest->manager_name : ($settings->manager_name ?? '');

        $previous_start_date = Carbon::parse($request->start_date)->subDay()->format('Y-m-d');
        $previous_end_date = Carbon::parse($request->start_date)->subDay()->format('Y-m-d');
        $previousDayEnd = Carbon::parse($previous_start_date)->endOfDay();
        
        $opening_date = $latestBefore ? $latestBefore->date : null;
        $isOpeningDateToday = ($latest && $latest->date == $start_date);

        // 1. Detect if an F22 Form is saved on the selected start_date (location-aware)
        $formF22ExistsToday = DB::table('form_f22_headers')
            ->where('business_id', $business_id)
            ->where(function ($q) {
                $q->where('status', 1)->orWhereNull('status');
            })
            ->whereDate('form_date', $start_date)
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('location_id', $location_id);
            })
            ->exists();

        // 2. Find the latest reset point (F22) strictly before start_date
        $latestF22Before = DB::table('form_f22_headers')
            ->where('business_id', $business_id)
            ->where(function ($q) {
                $q->where('status', 1)->orWhereNull('status');
            })
            ->whereDate('form_date', '<', $start_date)
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('location_id', $location_id);
            })
            ->orderBy('form_date', 'desc')
            ->orderBy('form_no', 'desc')
            ->first();

        // 3. Define accumulation start date (Reset point)
        $resetDate = $latestF22Before ? $latestF22Before->form_date : ($opening_date ?: (!empty($business_details->start_date) ? $business_details->start_date : null));
        $accStart = $resetDate ? Carbon::parse($resetDate)->startOfDay() : null;
        $accEnd   = $previousDayEnd;
        $hasAcc   = ($accStart && $accStart->lte($accEnd));

        // Previous Day Receipts carry-forward period.
        // The selected date's Previous Day row must equal the previous calendar day's
        // Total Receipts row, category-wise. Start from month start, or the day after
        // the latest F22 reset if that reset is later.
        $receiptCarryStart = Carbon::parse($start_date)->startOfMonth()->startOfDay();
        if ($latestF22Before && Carbon::parse($latestF22Before->form_date)->gte($receiptCarryStart)) {
            $receiptCarryStart = Carbon::parse($latestF22Before->form_date)->addDay()->startOfDay();
        }
        $receiptCarryEnd = $previousDayEnd;
        $hasReceiptCarry = $receiptCarryStart->lte($receiptCarryEnd);

        // 4. Fetch the baseline stock for the reset point (Requirement 1 baseline)
        $resetBaselineStock = collect([]);
        if ($latestF22Before) {
            $resetBaselineStock = DB::table('form_f22_headers')
                ->join('form_f22_details', function ($join) {
                    $join->on('form_f22_headers.form_no', '=', 'form_f22_details.form_no')
                        ->on('form_f22_headers.business_id', '=', 'form_f22_details.business_id');
                })
                ->join('products', function ($join) {
                    $join->on('form_f22_details.product', '=', 'products.name')
                        ->orOn('form_f22_details.product_code', '=', 'products.sku');
                })
                ->where('form_f22_details.business_id', $business_id)           
                ->where('form_f22_headers.form_no', $latestF22Before->form_no)
                ->when(!empty($location_id), function ($q) use ($location_id) {
                    $q->where('form_f22_headers.location_id', $location_id);
                })
                ->groupBy('products.sub_category_id')   
                ->select(
                    'products.sub_category_id as category_id', 
                    DB::raw('SUM(form_f22_details.stock_count) as total_quantity'),
                    DB::raw('SUM(form_f22_details.stock_count * form_f22_details.unit_sale_price) as total_sales')
                )->get()->keyBy('category_id');
        }

        $credit_sales = $this->Form21CQuery($business_id, $start_date, $end_date, $location_id);
        $previous_credit_sales = $this->Form21CQuery($business_id, $previous_end_date, $previous_end_date, $location_id);
        $form22_details = FormF22Detail::query()
                            ->join('form_f22_headers', function ($join) {
                                $join->on('form_f22_headers.id', '=', 'form_f22_details.header_id')
                                    ->orOn(function ($legacy) {
                                        $legacy->on('form_f22_headers.form_no', '=', 'form_f22_details.form_no')
                                            ->on('form_f22_headers.business_id', '=', 'form_f22_details.business_id')
                                            ->whereNull('form_f22_details.header_id');
                                    });
                            })
                            ->where('form_f22_details.business_id', $business_id)
                            ->whereDate('form_f22_headers.form_date', '>=', $start_date)
                            ->whereDate('form_f22_headers.form_date', '<=', $end_date)
                            ->when(!empty($location_id), function ($q) use ($location_id) {
                                $q->where('form_f22_headers.location_id', $location_id);
                            })
                            ->where(function ($q) {
                                $q->where('form_f22_details.status', 1)
                                    ->orWhereNull('form_f22_details.status');
                            })
                            ->select('form_f22_details.*', 'form_f22_headers.form_date as selected_form_date')
                            ->orderByDesc('form_f22_headers.form_date')
                            ->orderByDesc('form_f22_details.id')
                            ->first();
        $form17_increase = FormF17Detail::where('select_mode', 'increase')
                            ->whereDate('created_at', '>=', $startDate)
                            ->whereDate('created_at', '<=', $endDate) 
                            ->orderBy('id', 'DESC')               
                            ->first();

        $form17_increase_previous = FormF17Detail::where('select_mode', 'increase')
                            ->whereDate('created_at', '>=', $previous_start_date) 
                            ->orderBy('id', 'DESC')               
                            ->first();

        $form17_decrease = FormF17Detail::where('select_mode', 'descrease')
                            ->whereDate('created_at', '>=', $startDate)
                            ->whereDate('created_at', '<=', $endDate) 
                            ->orderBy('id', 'DESC')               
                            ->first();
                            
        $form17_decrease_previous = FormF17Detail::where('select_mode', 'decrease')
                            ->whereDate('created_at', '>=', $previous_start_date)
                            // ->whereDate('created_at', '<=', $previous_end_date) 
                            ->orderBy('id', 'DESC')               
                            ->first();

        $transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                        ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                        ->select(
                            'transactions.id',
                            'transactions.transaction_date',
                            'transactions.final_total',
                            'transaction_payments.method as payment_method',
                            'transaction_sell_lines.quantity',
                            'transaction_sell_lines.unit_price_inc_tax as unit_price',
                            'transactions.ref_no',
                            'transactions.invoice_no',
                            'transactions.invoice_no as order_no'
                        )
                        ->whereDate('transactions.transaction_date', '>=', $start_date)
                        ->whereDate('transactions.transaction_date', '<=', $end_date)
                        ->orWhere('transaction_payments.method', 'cash')
                        ->orWhere('transaction_payments.method', 'cheque')
                        ->orWhere('transaction_payments.method', 'card')
                        ->orderBy('id', 'DESC')               
                        ->first();

        $previous_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                        ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                        ->select(
                            'transactions.id',
                            'transactions.transaction_date',
                            'transactions.final_total',
                            'transaction_payments.method as payment_method',
                            'transaction_sell_lines.quantity',
                            'transaction_sell_lines.unit_price_inc_tax as unit_price',
                            'transactions.ref_no',
                            'transactions.invoice_no',
                            'transactions.invoice_no as order_no'
                        )
                        ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
                        // ->whereDate('transactions.transaction_date', '<=', $previous_end_date)
                        ->orWhere('transaction_payments.method', 'cash')
                        ->orWhere('transaction_payments.method', 'cheque')
                        ->orWhere('transaction_payments.method', 'card')
                        ->orderBy('id', 'DESC')               
                        ->first();

        $own_group = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                        ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                        ->select(
                            'transactions.id',
                            'transactions.transaction_date',
                            'transactions.final_total',
                            'transaction_payments.method as payment_method',
                            'transaction_sell_lines.quantity',
                            'transaction_sell_lines.unit_price_inc_tax as unit_price',
                            'transactions.ref_no',
                            'transactions.invoice_no',
                            'transactions.invoice_no as order_no'
                        )
                        ->whereDate('transactions.transaction_date', '>=', $start_date)
                        ->whereDate('transactions.transaction_date', '<=', $end_date)
                        ->orWhere('transaction_payments.method', 'custom_pay_1')
                        ->orWhere('transaction_payments.method', 'custom_pay_2')
                        ->orderBy('id', 'DESC')               
                        ->first();

        $previous_own_group = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                        ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                        ->select(
                            'transactions.id',
                            'transactions.transaction_date',
                            'transactions.final_total',
                            'transaction_payments.method as payment_method',
                            'transaction_sell_lines.quantity',
                            'transaction_sell_lines.unit_price_inc_tax as unit_price',
                            'transactions.ref_no',
                            'transactions.invoice_no',
                            'transactions.invoice_no as order_no'
                        )
                        ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
                        ->orWhere('transaction_payments.method', 'custom_pay_1')
                        ->orWhere('transaction_payments.method', 'custom_pay_2')
                        ->orderBy('id', 'DESC')               
                        ->first();

        $credit_sales_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                        ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                        ->select(
                            'transactions.id',
                            'transactions.transaction_date',
                            'transactions.final_total',
                            'transaction_payments.method as payment_method',
                            'transaction_sell_lines.quantity',
                            'transaction_sell_lines.unit_price_inc_tax as unit_price',
                            'transactions.ref_no',
                            'transactions.invoice_no',
                            'transactions.invoice_no as order_no'
                        )
                        ->whereDate('transactions.transaction_date', '>=', $start_date)
                        ->whereDate('transactions.transaction_date', '<=', $end_date)
                        ->where('transaction_payments.method', 'credit_sales')
                        ->orderBy('id', 'DESC')               
                        ->first();

        $previous_credit_sales_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                        ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                        ->select(
                            'transactions.id',
                            'transactions.transaction_date',
                            'transactions.final_total',
                            'transaction_payments.method as payment_method',
                            'transaction_sell_lines.quantity',
                            'transaction_sell_lines.unit_price_inc_tax as unit_price',
                            'transactions.ref_no',
                            'transactions.invoice_no',
                            'transactions.invoice_no as order_no'
                        )
                        ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
                        ->where('transaction_payments.method', 'credit_sales')
                        ->orderBy('id', 'DESC')               
                        ->first();

        $account_transactions = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
                ->where('account_transactions.business_id',$business_id)
                ->get();
             
        // Filter to show only Fuel category sub-categories
        $fuelCategory = Category::subCategoryOnlyFuel($business_id)
            ->whereIn('id', $fuelCategoryIds)
            ->sortBy('name')
            ->pluck('name', 'id');
   
        // --- Core Transaction and Receipt Summaries for Selected Range ---
        // Always calculated for the requested period to ensure data accuracy.
        
        // Total sales today (all payment types: cash, card, credit)
        $total_sales_today = DB::table('transactions')
            ->join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->join('categories', 'products.sub_category_id', '=', 'categories.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereIn('categories.id', $fuelCategoryIds)
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('transactions.location_id', $location_id);
            })
            ->groupBy('categories.id', 'categories.name')
            ->select(
                'categories.id as category_id', 'categories.name as category_name',
                DB::raw('SUM(transaction_sell_lines.quantity) as total_quantity'),
                DB::raw('SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) as total_sales')
            )->get()->keyBy('category_id');

        // Credit for Today — sourced from F9C Credit Form (settlement_credit_sale_payments)
        $credit_sales_today = DB::table('settlement_credit_sale_payments')
            ->join('transactions', 'transactions.id', '=', 'settlement_credit_sale_payments.transaction_id')
            ->join('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
            ->join('categories', 'products.sub_category_id', '=', 'categories.id')
            ->where('settlement_credit_sale_payments.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->where('transactions.is_credit_sale', 1)
            ->whereIn('categories.id', $fuelCategoryIds)
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('transactions.location_id', $location_id);
            })
            ->groupBy('categories.id', 'categories.name')
            ->select(
                'categories.id as category_id',
                'categories.name as category_name',
                DB::raw('SUM(settlement_credit_sale_payments.qty) as total_quantity'),
                DB::raw('SUM(settlement_credit_sale_payments.amount) as total_sales')
            )->get();

        // Cooperative Section sales (Custom Payment Methods) for selected range
        $cooperative_sales_today = DB::table('transactions')
            ->join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
            ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->join('categories', 'products.sub_category_id', '=', 'categories.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereIn('transaction_payments.method', ['custom_pay_1', 'custom_pay_2', 'custom_pay_3'])
            ->whereIn('categories.id', $fuelCategoryIds)
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('transactions.location_id', $location_id);
            })
            ->groupBy('categories.id', 'categories.name')
            ->select(
                'categories.id as category_id', 'categories.name as category_name',
                DB::raw('SUM(transaction_sell_lines.quantity) as total_quantity'),
                DB::raw('SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) as total_sales')
            )->get();

        // Cash for Today = Total sales (cash + card + credit + custom) - Credit sales - Cooperative sales
        // This ensures the "Cash" row accurately reflects only the non-credit, non-cooperative portion of the total.
        $credit_sales_lookup = $credit_sales_today->keyBy('category_id');
        $coop_sales_lookup = $cooperative_sales_today->keyBy('category_id');

        $cash_sales_today = $total_sales_today->map(function ($totalRow) use ($credit_sales_lookup, $coop_sales_lookup) {
            $catId = $totalRow->category_id;
            $creditRow = $credit_sales_lookup->get($catId);
            $coopRow = $coop_sales_lookup->get($catId);

            $creditQty = $creditRow ? (float)$creditRow->total_quantity : 0;
            $creditAmt = $creditRow ? (float)$creditRow->total_sales : 0;

            $coopQty = $coopRow ? (float)$coopRow->total_quantity : 0;
            $coopAmt = $coopRow ? (float)$coopRow->total_sales : 0;

            return (object)[
                "category_id"    => $totalRow->category_id,
                "category_name"  => $totalRow->category_name,
                "total_quantity" => max(0, (float)$totalRow->total_quantity - $creditQty - $coopQty),
                "total_sales"    => max(0, (float)$totalRow->total_sales - $creditAmt - $coopAmt),
            ];
        })->values();

        // Safety: this method is used by Ajax also. Ensure the carry-forward
        // variables always exist before they are used below, even if the installed
        // controller is merged with an older copy or a branch skips the earlier block.
        if (!isset($receiptCarryStart, $receiptCarryEnd, $hasReceiptCarry)) {
            $receiptCarryStart = Carbon::parse($start_date)->startOfMonth()->startOfDay();
            if (isset($latestF22Before) && $latestF22Before && Carbon::parse($latestF22Before->form_date)->gte($receiptCarryStart)) {
                $receiptCarryStart = Carbon::parse($latestF22Before->form_date)->addDay()->startOfDay();
            }
            $receiptCarryEnd = isset($previousDayEnd) ? $previousDayEnd : Carbon::parse($previous_start_date ?? $start_date)->endOfDay();
            $hasReceiptCarry = $receiptCarryStart->lte($receiptCarryEnd);
        }

        // Cooperative Section sales (Custom Payment Methods) up to last day
        $cooperative_sales_last = DB::table('transactions')
            ->join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
            ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->join('categories', 'products.sub_category_id', '=', 'categories.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->whereIn('transaction_payments.method', ['custom_pay_1', 'custom_pay_2', 'custom_pay_3'])
            ->whereIn('categories.id', $fuelCategoryIds)
            ->when($hasReceiptCarry, function ($q) use ($receiptCarryStart, $receiptCarryEnd) {
                $q->whereBetween('transactions.transaction_date', [$receiptCarryStart, $receiptCarryEnd]);
            }, function ($q) {
                $q->whereRaw('1=0');
            })
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('transactions.location_id', $location_id);
            })
            ->groupBy('categories.id', 'categories.name')
            ->select(
                'categories.id as category_id', 'categories.name as category_name',
                DB::raw('SUM(transaction_sell_lines.quantity) as total_quantity'),
                DB::raw('SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) as total_sales')
            )->get();

        // Today's receipts (F16-linked purchases) for selected range
        // Value = Qty * Unit Sale Price inc. taxes (matching F16A formula)
        $today_sales_query = DB::table('transactions')
            ->join('purchase_lines', 'transactions.id', '=', 'purchase_lines.transaction_id')
            ->join('products', 'purchase_lines.product_id', '=', 'products.id')
            ->leftJoin('variations', 'purchase_lines.variation_id', '=', 'variations.id')
            ->join('categories', 'products.sub_category_id', '=', 'categories.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'purchase')
            ->where('transactions.status', 'received')
            ->whereIn('categories.id', $fuelCategoryIds)
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('transactions.location_id', $location_id);
            })
            ->select(
                'categories.id as category_id', 'categories.name as category_name',
                DB::raw('SUM(purchase_lines.quantity) as total_quantity'),
                DB::raw('SUM(purchase_lines.quantity * COALESCE(NULLIF(purchase_lines.sell_price_at_purchase, 0), NULLIF(variations.sell_price_inc_tax, 0), variations.default_sell_price, 0)) as total_sales'),
                DB::raw('SUM(purchase_lines.quantity * purchase_lines.purchase_price_inc_tax) as total_purchase_val'),
                DB::raw('"purchase" as type')
            )->groupBy('categories.id', 'categories.name')->get();

        $today_sales = $today_sales_query;

        // Clone today's sales to create the "Total Purchase Amount" results (Image No. 6 requirement)
        $today_purchase_results = $today_sales_query->map(function($item) {
            $newItem = clone $item;
            $newItem->total_sales = $item->total_purchase_val;
            return $newItem;
        });


        // Previous day's receipts (F16-linked purchases) – strictly the day before start_date
        $previousDayStart = Carbon::parse($previous_start_date)->startOfDay();
        $previousDayEnd = Carbon::parse($previous_start_date)->endOfDay();

        $previous_receipts = DB::table('transactions')
            ->join('purchase_lines', 'transactions.id', '=', 'purchase_lines.transaction_id')
            ->join('products', 'purchase_lines.product_id', '=', 'products.id')
            ->leftJoin('variations', 'purchase_lines.variation_id', '=', 'variations.id')
            ->join('categories', 'products.sub_category_id', '=', 'categories.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'purchase')
            ->where('transactions.status', 'received')
            ->whereIn('categories.id', $fuelCategoryIds)
            ->whereBetween('transactions.transaction_date', [$previousDayStart, $previousDayEnd])
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('transactions.location_id', $location_id);
            })
            ->select(
                'categories.id as category_id', 'categories.name as category_name',
                DB::raw('SUM(purchase_lines.quantity) as total_quantity'),
                DB::raw('SUM(purchase_lines.quantity * COALESCE(NULLIF(purchase_lines.sell_price_at_purchase, 0), NULLIF(variations.sell_price_inc_tax, 0), variations.default_sell_price, 0)) as total_sales')
            )->groupBy('categories.id', 'categories.name')->get();


        $discount_todays = DB::table('transactions')
            ->join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->join('categories', 'products.sub_category_id', '=', 'categories.id')
            ->where('transactions.business_id', $business_id)
            ->whereIn('categories.id', $fuelCategoryIds)
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('transactions.location_id', $location_id);
            })
            ->groupBy('categories.id', 'categories.name')
            ->select(
                'categories.id as category_id', 'categories.name as category_name',
                DB::raw('SUM(transaction_sell_lines.quantity) as total_quantity'),
                DB::raw('SUM(transactions.discount_amount) as total_sales')
            )->get();

        // (accStart/accEnd/hasAcc already defined early to handle reset points)

        // Accumulated Discounts (Price Reductions) from Opening Date to Yesterday
        $discount_previous = DB::table('transactions')
            ->join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->join('categories', 'products.sub_category_id', '=', 'categories.id')
            ->where('transactions.business_id', $business_id)
            ->whereIn('categories.id', $fuelCategoryIds)
            ->when($hasAcc, function ($q) use ($accStart, $accEnd) {
                $q->whereBetween('transactions.transaction_date', [$accStart, $accEnd]);
            }, function ($q) {
                $q->whereRaw('1=0'); // No accumulation if today is opening day
            })
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('transactions.location_id', $location_id);
            })
            ->groupBy('categories.id')
            ->select(
                'categories.id as category_id',
                DB::raw('SUM(transaction_sell_lines.quantity) as total_quantity'),
                DB::raw('SUM(transactions.discount_amount) as total_sales')
            )->get()->keyBy('category_id');

        // --- Categorized Price Increment (Form 17 Increase) ---
        // Qty  = SUM(current_stock)
        // Value = SUM(current_stock * new_price)  — new_price is the historically frozen
        //         unit sale price (inc. tax) that was active at the time of the F17 form.
        //         Subsequent price changes do NOT affect this value.
        $price_inc_today_cats = DB::table('form_f17_details')
            ->join('form_f17_headers', 'form_f17_details.header_id', '=', 'form_f17_headers.id')
            ->join('products', 'form_f17_details.product_id', '=', 'products.id')
            ->join('categories', 'products.sub_category_id', '=', 'categories.id')
            ->where('form_f17_headers.business_id', $business_id)
            ->where('form_f17_details.select_mode', 'increase')
            ->whereIn('categories.id', $fuelCategoryIds)
            ->whereBetween('form_f17_headers.date', [$start_date, $end_date])
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('form_f17_headers.location_id', $location_id);
            })
            ->groupBy('categories.id', 'categories.name')
            ->select(
                'categories.id as category_id', 'categories.name as category_name',
                DB::raw('SUM(form_f17_details.current_stock) as total_quantity'),
                DB::raw('SUM(form_f17_details.current_stock * COALESCE(form_f17_details.unit_price_difference, 0)) as total_sales')
            )->get();

        // Accumulated Price Increments (Form 17 Increase) from Opening Date to Yesterday
        // Value = SUM(current_stock * unit_price_difference)
        // matching the same formula used for the Today row (item 18).
        $price_inc_previous_cats = DB::table('form_f17_details')
            ->join('form_f17_headers', 'form_f17_details.header_id', '=', 'form_f17_headers.id')
            ->join('products', 'form_f17_details.product_id', '=', 'products.id')
            ->join('categories', 'products.sub_category_id', '=', 'categories.id')
            ->where('form_f17_headers.business_id', $business_id)
            ->where('form_f17_details.select_mode', 'increase')
            ->whereIn('categories.id', $fuelCategoryIds)
            ->when($hasAcc, function ($q) use ($accStart, $accEnd) {
                $q->whereBetween('form_f17_headers.date', [$accStart->format('Y-m-d'), $accEnd->format('Y-m-d')]);
            }, function ($q) use ($accEnd) {
                $q->where('form_f17_headers.date', '<=', $accEnd->format('Y-m-d'));
            })
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('form_f17_headers.location_id', $location_id);
            })
            ->groupBy('categories.id', 'categories.name')
            ->select(
                'categories.id as category_id', 'categories.name as category_name',
                DB::raw('SUM(form_f17_details.current_stock) as total_quantity'),
                DB::raw('SUM(form_f17_details.current_stock * COALESCE(form_f17_details.unit_price_difference, 0)) as total_sales')
            )->get()->keyBy(fn($r) => (string)$r->category_id);

        // Price Reduction Today — F17 Decrease for selected date
        // Qty  = SUM(current_stock)
        // Value = SUM(new_price * current_stock)  — formula: Total amount = New price * Current stock
        $price_dec_today_cats = DB::table('form_f17_details')
            ->join('form_f17_headers', 'form_f17_details.header_id', '=', 'form_f17_headers.id')
            ->join('products', 'form_f17_details.product_id', '=', 'products.id')
            ->join('categories', 'products.sub_category_id', '=', 'categories.id')
            ->where('form_f17_headers.business_id', $business_id)
            ->where('form_f17_details.select_mode', 'decrease')
            ->whereIn('categories.id', $fuelCategoryIds)
            ->whereBetween('form_f17_headers.date', [$start_date, $end_date])
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('form_f17_headers.location_id', $location_id);
            })
            ->groupBy('categories.id', 'categories.name')
            ->select(
                'categories.id as category_id', 'categories.name as category_name',
                DB::raw('SUM(form_f17_details.current_stock) as total_quantity'),
                DB::raw('ABS(SUM(form_f17_details.current_stock * COALESCE(form_f17_details.unit_price_difference, 0))) as total_sales')
            )->get();

        // Price Reduction Previous Date — F17 Decrease accumulated from opening date to yesterday
        // Value = SUM(new_price * current_stock)
        $price_dec_previous_cats = DB::table('form_f17_details')
            ->join('form_f17_headers', 'form_f17_details.header_id', '=', 'form_f17_headers.id')
            ->join('products', 'form_f17_details.product_id', '=', 'products.id')
            ->join('categories', 'products.sub_category_id', '=', 'categories.id')
            ->where('form_f17_headers.business_id', $business_id)
            ->where('form_f17_details.select_mode', 'decrease')
            ->whereIn('categories.id', $fuelCategoryIds)
            ->when($hasAcc, function ($q) use ($accStart, $accEnd) {
                $q->whereBetween('form_f17_headers.date', [$accStart->format('Y-m-d'), $accEnd->format('Y-m-d')]);
            }, function ($q) use ($accEnd) {
                $q->where('form_f17_headers.date', '<=', $accEnd->format('Y-m-d'));
            })
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('form_f17_headers.location_id', $location_id);
            })
            ->groupBy('categories.id', 'categories.name')
            ->select(
                'categories.id as category_id',
                'categories.name as category_name',
                DB::raw('SUM(form_f17_details.current_stock) as total_quantity'),
                DB::raw('ABS(SUM(form_f17_details.current_stock * COALESCE(form_f17_details.unit_price_difference, 0))) as total_sales')
            )->get()->keyBy(fn($r) => (string)$r->category_id);

        // Accumulated Issues (Total Sales) from Opening Date to Yesterday
        $accIssues = DB::table('transactions')
            ->join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->join('categories', 'products.sub_category_id', '=', 'categories.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->whereIn('categories.id', $fuelCategoryIds)
            ->when($hasAcc, function ($q) use ($accStart, $accEnd) {
                $q->whereBetween('transactions.transaction_date', [$accStart, $accEnd]);
            }, function ($q) use ($accEnd) {
                $q->where('transactions.transaction_date', '<=', $accEnd);
            })
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('transactions.location_id', $location_id);
            })
            ->groupBy('categories.id')
            ->select(
                'categories.id as category_id',
                DB::raw('SUM(transaction_sell_lines.quantity) as total_quantity'),
                DB::raw('SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) as total_sales')
            )->get()->keyBy('category_id');
        // --- Core Form Component Mapping (Opening Stock / Previous Day) ---
        // Get category data breakdown for mapping
        $headerCategoriesMap = [];
        foreach ($header as $setting) {
            if (!empty($setting->categories)) {
                $categories = json_decode($setting->categories, true);
                if (is_array($categories)) {
                    foreach ($categories as $catId => $values) {
                        if (!isset($headerCategoriesMap[$catId])) $headerCategoriesMap[$catId] = $values;
                    }
                }
            }
        }
        $fuelCategories = Category::where('business_id', $business_id)->whereIn('id', $fuelCategoryIds)->orderBy('name')->pluck('name', 'id');
        $finalCategoryData = [];
        foreach ($fuelCategories as $catId => $catName) {
            $categoryData = $headerCategoriesMap[$catId] ?? [];
            $finalCategoryData[] = [
                'category_id' => $catId, 'category_name' => $catName,
                'previous_day' => ['qty' => $categoryData['previous_day']['qty'] ?? '', 'val' => $categoryData['previous_day']['val'] ?? 0],
                'opening_stock' => ['qty' => $categoryData['opening_stock']['qty'] ?? '', 'val' => $categoryData['opening_stock']['val'] ?? 0],
                'total_issues' => ['qty' => $categoryData['total_issues']['qty'] ?? '', 'val' => $categoryData['total_issues']['val'] ?? 0],
            ];
        }

        // Map previous day's F16 receipts by category for quick lookup
        $prevReceiptsByCategory = $previous_receipts->keyBy(fn($r) => (string)$r->category_id);

        // Determine if an F22 form exists on the previous day (location-aware)
        $formF22PrevExists = DB::table('form_f22_headers')
            ->where('business_id', $business_id)
            ->whereDate('form_date', $previous_start_date)
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('location_id', $location_id);
            })
            ->exists();

        // Date rule flags for "Previous Day" rows:
        // - zero when F22 is saved on selected day
        // - zero on month-start date
        $isMonthStartDate = Carbon::parse($start_date)->day === 1;
        $forceZeroPreviousDayRows = $formF22ExistsToday || $isMonthStartDate;

        // Always include Opening Stock from settings as baseline
        $opening_stock = array_map(fn($item) => [
            'category_id' => $item['category_id'],
            'category_name' => $item['category_name'],
            'total_quantity' => $item['opening_stock']['qty'] ?? '',
            'total_sales' => $item['opening_stock']['val'] ?? 0
        ], $finalCategoryData);

        // Initialize final data arrays
        $previous_day = [];
        $total_issues = [];
        $discount_previous_final = [];
        $price_inc_previous_final = [];

        // After opening date: baseline settings value + accumulated data
        // Value = Qty * Unit Sale Price inc. taxes (matching F16A formula)
        // Previous Day row must equal the previous calendar day's Total Receipts row.
        // Therefore receipts carry-forward starts from the first day of the selected month
        // (or the day after the latest F22 reset if that is later), and it does NOT add
        // the old opening/settings previous-day baseline again.
        $accReceipts = DB::table('transactions')
            ->join('purchase_lines', 'transactions.id', '=', 'purchase_lines.transaction_id')
            ->join('products', 'purchase_lines.product_id', '=', 'products.id')
            ->leftJoin('variations', 'purchase_lines.variation_id', '=', 'variations.id')
            ->join('categories', 'products.sub_category_id', '=', 'categories.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'purchase')
            ->where('transactions.status', 'received')
            ->whereIn('categories.id', $fuelCategoryIds)
            ->when($hasAcc, function ($q) use ($accStart, $accEnd) {
                $q->whereBetween('transactions.transaction_date', [$accStart, $accEnd]);
            }, function ($q) {
                $q->whereRaw('1=0');
            })
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('transactions.location_id', $location_id);
            })
            ->select(
                'categories.id as category_id', 
                DB::raw('SUM(purchase_lines.quantity) as total_quantity'),
                DB::raw('SUM(purchase_lines.quantity * COALESCE(NULLIF(purchase_lines.sell_price_at_purchase, 0), NULLIF(variations.sell_price_inc_tax, 0), variations.default_sell_price, 0)) as total_sales')
            )->groupBy('categories.id')->get()->keyBy(fn($r) => (string)$r->category_id);


        foreach ($finalCategoryData as $item) {
            $catId = $item['category_id'];
            
            // 1. Previous Day Receipts (Requirement 1, 2, 3, 4)
            if ($forceZeroPreviousDayRows) {
                // Requirement 2 & 4: Zero on F22 save date
                $previous_day[] = [
                    'category_id' => $catId, 'category_name' => $item['category_name'],
                    'total_quantity' => 0,
                    'total_sales' => 0,
                ];
            } elseif ($isOpeningDateToday) {
                // Requirement 3: Use settings on opening date
                $baselineRecQty = (float)($item['previous_day']['qty'] ?? 0);
                $baselineRecVal = (float)($item['previous_day']['val'] ?? 0);
                $previous_day[] = [
                    'category_id' => $catId, 'category_name' => $item['category_name'],
                    'total_quantity' => $baselineRecQty,
                    'total_sales' => $baselineRecVal,
                ];
            } else {
                // Requirement: the selected date's Previous Day row must equal the
                // previous day's Total Receipts row for the same fuel sub-category.
                // Total Receipts = Previous Day Receipts + Today Receipts.
                // It must NOT include Opening Stock or Price Increment values.
                $accRec = $accReceipts->get((string)$catId);
                
                // Do not add the opening/settings previous-day baseline here.
                // The Previous Day row is the carry-forward of the previous day's
                // Total Receipts row only.
                $totalPrevQty = $accRec ? (float)$accRec->total_quantity : 0;
                $totalPrevVal = $accRec ? (float)$accRec->total_sales : 0;

                $previous_day[] = [
                    'category_id' => $catId, 'category_name' => $item['category_name'],
                    'total_quantity' => $totalPrevQty,
                    'total_sales' => $totalPrevVal,
                ];
            }

            // 2. Issues up to Last Day (Row 21)
            $baselineIssQty = (float)($item['total_issues']['qty'] ?? 0);
            $baselineIssVal = (float)($item['total_issues']['val'] ?? 0);
            $accIss = $accIssues->get((string)$catId);
            $total_issues[] = [
                'category_id' => $catId, 'category_name' => $item['category_name'],
                // A saved F22 is a stock-taking reset point. On that exact date,
                // Issues up to Last Day must restart from zero for every product.
                'total_quantity' => $formF22ExistsToday ? 0 : ($baselineIssQty + ($accIss ? (float)$accIss->total_quantity : 0)),
                'total_sales' => $formF22ExistsToday ? 0 : ($baselineIssVal + ($accIss ? (float)$accIss->total_sales : 0)),
            ];

            // 3. Price Reduction Previous Date (Row 26)
            // No explicit baseline in some structures, but we use what's found in accumulation
            $accDisc = $price_dec_previous_cats->get((string)$catId);
            $discount_previous_final[] = [
                'category_id' => $catId, 'category_name' => $item['category_name'],
                'total_quantity' => $accDisc ? (float)$accDisc->total_quantity : 0,
                'total_sales' => $accDisc ? abs((float)$accDisc->total_sales) : 0,
            ];

            // 4. Price Increment Previous (Row 13 / Image 19)
            // Requirement 1: Total Price Increment from previous day (carry-over)
            $accInc = $price_inc_previous_cats->get((string)$catId);
            $price_inc_previous_final[] = [
                'category_id' => $catId, 'category_name' => $item['category_name'],
                'total_quantity' => $accInc ? (float)$accInc->total_quantity : 0,
                'total_sales' => $accInc ? (float)$accInc->total_sales : 0,
            ];
        }

        $discount_previous = $discount_previous_final;
        $price_inc_previous_cats = $price_inc_previous_final;
        $cash_sales_previous = $previous_day;

        // --- Stock Taking (F22) Overlay ---
        // Check if selected date is opening date
        $isOpeningDate = $latest && $latest->date == $start_date;
        
        // Find latest F22 form on or before selected date
        $latestF22 = FormF22Header::where('business_id', $business_id)
            ->where(function ($q) {
                $q->where('status', 1)->orWhereNull('status');
            })
            ->whereDate('form_date', '<=', $start_date)
            ->when(!empty($location_id), function ($q) use ($location_id) {
                $q->where('location_id', $location_id);
            })
            ->orderBy('form_date', 'desc')
            ->orderBy('form_no', 'desc')
            ->first();
        
        $f22FormNo = null;
        $formF22Exists = false;
        
        // Opening Stock Logic:
        // 1. Only for opening date: show values from 21C Settings
        // 2. If F22 exists on or before selected date: show F22 stock until next stock taking date
        // 3. Otherwise: use settings values
        if ($latestF22) {
            // Latest saved F22 is the authoritative opening stock from its form date
            // onward, including when the F22 date equals the 21C opening/settings date.
            $formF22Exists = true;
            $f22FormNo = $latestF22->form_no;
            
            // Read the exact saved F22 header. Using header_id prevents another
            // location/form with the same form number from being mixed into Opening Stock.
            $opening_stock = DB::table('form_f22_details')
                ->join('products', function ($join) {
                    $join->on('form_f22_details.product', '=', 'products.name')
                        ->orOn('form_f22_details.product_code', '=', 'products.sku');
                })
                ->join('categories', 'products.sub_category_id', '=', 'categories.id')
                ->where('form_f22_details.business_id', $business_id)
                ->where(function ($q) {
                    $q->where('form_f22_details.status', 1)
                        ->orWhereNull('form_f22_details.status');
                })
                ->where('products.business_id', $business_id)
                ->where(function ($q) use ($latestF22) {
                    $q->where('form_f22_details.header_id', $latestF22->id)
                        ->orWhere(function ($legacy) use ($latestF22) {
                            $legacy->whereNull('form_f22_details.header_id')
                                ->where('form_f22_details.form_no', $latestF22->form_no);
                        });
                })
                ->whereIn('categories.id', $fuelCategoryIds)
                ->groupBy('categories.id', 'categories.name')
                ->select(
                    'categories.id as category_id',
                    'categories.name as category_name',
                    DB::raw('SUM(COALESCE(form_f22_details.stock_count, 0)) as total_quantity'),
                    DB::raw('SUM(COALESCE(NULLIF(form_f22_details.sales_price_total, 0), form_f22_details.stock_count * form_f22_details.unit_sale_price, 0)) as total_sales'),
                    DB::raw('MAX(form_f22_details.form_no) as form_no')
                )
                ->get();
        }
        // else: use settings values (already set above)

        // Normalize key category arrays so every fuel sub-category always has data.
        $normalizeByFuelCategory = function ($rows) use ($fuelCategory) {
            $byCategory = [];
            foreach ($rows as $r) {
                $cid = (string)data_get($r, "category_id");
                if ($cid) $byCategory[$cid] = $r;
            }
            return $fuelCategory->map(function ($categoryName, $categoryId) use ($byCategory) {
                $row = $byCategory[(string)$categoryId] ?? null;
                return [
                    "category_id" => (int) $categoryId,
                    "category_name" => $categoryName,
                    "total_quantity" => $row ? (float) (data_get($row, "total_quantity") ?? 0) : 0.0,
                    "total_sales" => $row ? (float) (data_get($row, "total_sales") ?? 0) : 0.0,
                ];
            })->values()->all();
        };

        $opening_stock = $normalizeByFuelCategory($opening_stock);
        $previous_day = $normalizeByFuelCategory($previous_day);
        $today_sales = $normalizeByFuelCategory($today_sales);
        $price_inc_previous_cats = $normalizeByFuelCategory($price_inc_previous_cats);
        $cash_sales_today = $normalizeByFuelCategory($cash_sales_today);
        $credit_sales_today = $normalizeByFuelCategory($credit_sales_today);
        $cooperative_sales_today = $normalizeByFuelCategory($cooperative_sales_today);
        $discount_previous = $normalizeByFuelCategory($discount_previous);


       
                
     $incomeGrp_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')->where('accounts.business_id', $business_id)->where('account_groups.name', 'Sales Income Group')->select('accounts.id')->get()->pluck('id');
     

   
     /*
      * Pump meters must be available for every selected day, even when the day
      * has no settlement, sale or purchase transaction.
      *
      * Rules:
      *  - If the pump has meter sales on the selected day/range, use that day's
      *    first starting meter and last closing meter.
      *  - If it has no meter sale, carry forward the most recent closing meter
      *    before the selected date as BOTH opening and closing. Issued quantity
      *    therefore remains zero for a no-sale day.
      *  - Keep every configured pump in the result by using LEFT JOINs.
      */
     $meterSubquery = DB::table('meter_sales')
         ->join('settlements', 'meter_sales.settlement_no', '=', 'settlements.id')
         ->where('settlements.business_id', $business_id)
         ->whereBetween('settlements.transaction_date', [$start_date, $end_date])
         ->when(!empty($location_id), function ($q) use ($location_id) {
             $q->where('settlements.location_id', $location_id);
         })
         ->groupBy('meter_sales.pump_id')
         ->select([
             'meter_sales.pump_id',
             DB::raw('COUNT(meter_sales.id) as meter_sale_count'),
             DB::raw('MIN(meter_sales.starting_meter) as min_starting_meter'),
             DB::raw('MAX(meter_sales.closing_meter) as max_closing_meter'),
             DB::raw('COALESCE(SUM(meter_sales.qty), 0) as total_quantity'),
             DB::raw('COALESCE(SUM(meter_sales.sub_total), 0) as total_sales'),
             DB::raw('MAX(settlements.transaction_date) as transaction_date'),
         ]);

     // Find the latest meter_sale row for each pump before the selected date.
     $priorMeterIds = DB::table('meter_sales as prior_ms')
         ->join('settlements as prior_settlements', 'prior_ms.settlement_no', '=', 'prior_settlements.id')
         ->where('prior_settlements.business_id', $business_id)
         ->whereDate('prior_settlements.transaction_date', '<', $start_date)
         ->when(!empty($location_id), function ($q) use ($location_id) {
             $q->where('prior_settlements.location_id', $location_id);
         })
         ->groupBy('prior_ms.pump_id')
         ->select([
             'prior_ms.pump_id',
             DB::raw('MAX(prior_ms.id) as latest_meter_sale_id'),
         ]);

     $pump_operator = DB::table('pumps')
         ->join('products', 'pumps.product_id', '=', 'products.id')
         ->join('categories', 'products.sub_category_id', '=', 'categories.id')
         ->leftJoinSub($meterSubquery, 'meter_data', 'pumps.id', '=', 'meter_data.pump_id')
         ->leftJoinSub($priorMeterIds, 'prior_meter_ids', 'pumps.id', '=', 'prior_meter_ids.pump_id')
         ->leftJoin('meter_sales as prior_meter', 'prior_meter.id', '=', 'prior_meter_ids.latest_meter_sale_id')
         ->whereIn('categories.id', $fuelCategoryIds)
         ->where('pumps.business_id', $business_id)
         ->when(!empty($location_id), function ($q) use ($location_id) {
             $q->where('pumps.location_id', $location_id);
         })
         ->groupBy(
             'categories.id',
             'categories.name',
             'pumps.pump_no',
             'pumps.id',
             'products.name',
             'meter_data.meter_sale_count',
             'meter_data.min_starting_meter',
             'meter_data.max_closing_meter',
             'meter_data.total_quantity',
             'meter_data.total_sales',
             'meter_data.transaction_date',
             'prior_meter.closing_meter'
         )
         ->select([
             'categories.id as category_id',
             'categories.name as category_name',
             'pumps.pump_no',
             'pumps.id as pump_id',
             'products.name as product_name',
             DB::raw('CASE WHEN COALESCE(meter_data.meter_sale_count, 0) > 0 THEN COALESCE(meter_data.min_starting_meter, 0) ELSE COALESCE(prior_meter.closing_meter, 0) END as min_starting_meter'),
             DB::raw('CASE WHEN COALESCE(meter_data.meter_sale_count, 0) > 0 THEN COALESCE(meter_data.max_closing_meter, 0) ELSE COALESCE(prior_meter.closing_meter, 0) END as max_closing_meter'),
             DB::raw('COALESCE(meter_data.total_quantity, 0) as total_quantity'),
             DB::raw('COALESCE(meter_data.total_sales, 0) as total_sales'),
             DB::raw('meter_data.transaction_date as transaction_date'),
         ])
         ->get();
 
  // dd($pump_operator);
            // ── F16 form numbers for current date range ─────────────────────────────
            $today_f16_settings = \Modules\MPCS\Entities\Mpcs16aFormSettings::where('business_id', $business_id)->latest()->first();
            $today_f16_nos = [];
            if ($today_f16_settings && !empty($today_f16_settings->starting_number) && !empty($today_f16_settings->date)) {
                $openingDate = Carbon::parse($today_f16_settings->date);
                $today_purchase_dates = Transaction::where('business_id', $business_id)
                    ->where('type', 'purchase')
                    ->where('status', 'received')
                    ->when(!empty($location_id), function ($q) use ($location_id) {
                        $q->where('transactions.location_id', $location_id);
                    })
                    ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
                    ->selectRaw('DISTINCT DATE(transaction_date) as t_date')
                    ->pluck('t_date')
                    ->toArray();

                foreach ($today_purchase_dates as $t_date) {
                    $selectedDate = Carbon::parse($t_date);
                    if ($selectedDate->lt($openingDate)) {
                        $today_f16_nos[] = (int) $today_f16_settings->starting_number;
                    } else {
                        $daysDiff = $openingDate->diffInDays($selectedDate);
                        $today_f16_nos[] = (int) $today_f16_settings->starting_number + $daysDiff;
                    }
                }
                $today_f16_nos = collect($today_f16_nos)->unique()->sort()->values()->toArray();
            }

            // ── F16 form numbers for previous day ───────────────────────────────────
            $previous_f16_nos = [];
            if ($today_f16_settings && !empty($today_f16_settings->starting_number) && !empty($today_f16_settings->date)) {
                $openingDate = Carbon::parse($today_f16_settings->date);
                $prev_purchase_dates = Transaction::where('business_id', $business_id)
                    ->where('type', 'purchase')
                    ->where('status', 'received')
                    ->when(!empty($location_id), function ($q) use ($location_id) {
                        $q->where('transactions.location_id', $location_id);
                    })
                    ->whereBetween('transactions.transaction_date', [$previousDayStart, $previousDayEnd])
                    ->selectRaw('DISTINCT DATE(transaction_date) as t_date')
                    ->pluck('t_date')
                    ->toArray();

                foreach ($prev_purchase_dates as $t_date) {
                    $selectedDate = Carbon::parse($t_date);
                    if ($selectedDate->lt($openingDate)) {
                        $previous_f16_nos[] = (int) $today_f16_settings->starting_number;
                    } else {
                        $daysDiff = $openingDate->diffInDays($selectedDate);
                        $previous_f16_nos[] = (int) $today_f16_settings->starting_number + $daysDiff;
                    }
                }
                $previous_f16_nos = collect($previous_f16_nos)->unique()->sort()->values()->toArray();
            }

            // ── F17 form numbers for current date range (Price Increments) ──────────
            $today_f17_nos = FormF17Header::where('business_id', $business_id)
                ->when(!empty($location_id), function ($q) use ($location_id) {
                    $q->where('location_id', $location_id);
                })
                ->whereBetween('date', [$start_date, $end_date])
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('form_f17_details')
                        ->whereRaw('form_f17_details.header_id = form_f17_headers.id')
                        ->where('form_f17_details.select_mode', 'increase');
                })
                ->pluck('form_no')
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            // ── Use Mpcs21cFormSettings for header (correct form number auto-increment) ─
            $mpcs21c_settings_header = Mpcs21cFormSettings::where('business_id', $business_id)
                ->orderBy('date', 'desc')
                ->get()
                ->map(function($s) {
                    return [
                        'starting_number' => $s->starting_number,
                        'date'            => $s->date,
                        'manager_name'    => $s->manager_name,
                    ];
                });

            $payload = [
                "credit_sales" => $credit_sales, 
                "previous_credit_sales" => $previous_credit_sales,
                "today_f17_nos" => $today_f17_nos,
                "form22_details" => $form22_details,
                "form22_form_no" => $f22FormNo ?? null, // F22 Form Number for Opening Stock row
                "is_opening_date" => $isOpeningDate ?? false, // Flag to identify opening date
                "formF22Exists" => $formF22Exists ?? false, // Flag if F22 exists
                "formF22PrevExists" => $formF22PrevExists ?? false, // Flag if F22 exists on previous day
                "form17_increase" => $form17_increase,
                "form17_increase_previous" => $form17_increase_previous,
                "form17_decrease" => $form17_decrease,
                "form17_decrease_previous" => $form17_decrease_previous,
                "transaction" => $transaction,
                "own_group" => $own_group,
                "credit_sales_transaction" => $credit_sales_transaction,
                "previous_transaction" => $previous_transaction,
                "previous_own_group" => $previous_own_group,
                "previous_credit_sales_transaction" => $previous_credit_sales_transaction,
                "merged_sub_categories" => $merged_sub_categories,
                "account_transactions" => $account_transactions,
                'opening_stock' => $opening_stock,
                'previous_day' => $previous_day,
                "today_sales" => $today_sales ,
                "today_purchase_results" => $today_purchase_results,
                "cooperative_sales_today" => $cooperative_sales_today,
                "cooperative_sales_last" => $cooperative_sales_last,
                "fuelCategory" =>$fuelCategory,
                "total_receipts_last" =>$total_issues,
                "cash_sales_today" => $cash_sales_today,
                "credit_sales_today" => $credit_sales_today,
                'cash_sales_previous' =>$cash_sales_previous,
                'discount_todays'  => $discount_todays, 
                'discount_previous' => $discount_previous,
                'price_inc_today' => $normalizeByFuelCategory($price_inc_today_cats),
                'price_inc_previous' => $price_inc_previous_cats,
                'price_dec_today' => $normalizeByFuelCategory($price_dec_today_cats),
                'price_dec_previous' => $discount_previous,
                'header' => $mpcs21c_settings_header,   // switched to 21C settings
                'header_latest'  => $header_latest,
                'calculated_form_no' => $calculated_form_no,
                'calculated_manager_name' => $calculated_manager_name,
                'pump_operator' =>$pump_operator,               
                'today_f16_nos' => $today_f16_nos,      // F16 form numbers for selected date
                'previous_f16_nos' => $previous_f16_nos, // F16 form numbers for previous day
                // F16 total purchase qty per category for the selected date range (item 6 - No column)
                'f16_qty_by_category' => collect($today_sales)->keyBy('category_id')
                    ->map(fn($item) => (float)($item['total_quantity'] ?? 0)),
                // F16 total value per category using variation price tables (item 7 - Value column)
                // Formula: SUM(qty × variation sell_price_inc_tax/default_sell_price)
                'f16_val_by_category' => collect($today_sales)->keyBy('category_id')
                    ->map(fn($item) => (float)($item['total_sales'] ?? 0)),
                'currency_precision' => $currency_precision,
                'qty_precision' => $qty_precision
            ];


            /*
             * Month-start rule:
             * Every carried transaction amount starts at zero on the first day of
             * a new month. If an F22 is saved on that same date, its counted stock
             * remains the authoritative Opening Stock while all other rows reset.
             */
            if ($isMonthStartDate) {
                $f22OpeningStockAtMonthStart = $formF22ExistsToday
                    ? ($payload['opening_stock'] ?? [])
                    : null;
                $metricKeys = [
                    'total_quantity', 'total_sales', 'qty', 'quantity', 'amount',
                    'sub_total', 'total', 'final_total', 'unit_price', 'price',
                    'current_amount', 'balance_qty', 'min_starting_meter',
                    'max_closing_meter', 'starting_meter', 'closing_meter',
                    'opening_meter', 'issued_qty', 'testing_qty',
                    'discount_amount', 'total_amount', 'value', 'val',
                ];

                $zeroReportMetrics = function ($value) use (&$zeroReportMetrics, $metricKeys) {
                    if ($value instanceof \Illuminate\Support\Collection) {
                        return $value->map(function ($item) use (&$zeroReportMetrics) {
                            return $zeroReportMetrics($item);
                        });
                    }

                    if (is_object($value)) {
                        $value = (array) $value;
                    }

                    if (!is_array($value)) {
                        return $value;
                    }

                    $result = [];
                    foreach ($value as $key => $item) {
                        $normalizedKey = is_string($key) ? strtolower($key) : $key;

                        if (is_string($normalizedKey) && in_array($normalizedKey, $metricKeys, true)) {
                            $result[$key] = 0;
                            continue;
                        }

                        if (is_array($item) || is_object($item) || $item instanceof \Illuminate\Support\Collection) {
                            $result[$key] = $zeroReportMetrics($item);
                        } else {
                            $result[$key] = $item;
                        }
                    }

                    return $result;
                };

                /*
                 * IS2109 #1: only the CARRIED figures reset on the first of the
                 * month. Today's own transactions must not.
                 *
                 * The rule stated just above is "every CARRIED transaction amount
                 * starts at zero on the first day of a new month" - and that is
                 * right: last month's accumulations must not leak into the new
                 * one.
                 *
                 * But the list below also zeroed TODAY's keys - today_sales,
                 * today_purchase_results, cash_sales_today, credit_sales_today,
                 * cooperative_sales_today, transaction, own_group and the rest.
                 * So on the 1st, purchases and sales entered for that very day
                 * were wiped before display, and Receipts and Issues showed
                 * 0.00 - exactly what the ticket reports for 2026-07-01.
                 *
                 * The two groups are now separate. Anything carried FROM an
                 * earlier day resets; anything belonging TO the selected day is
                 * left alone.
                 */
                $monthStartDataKeys = [
                    // Carried from earlier days - these reset.
                    'previous_credit_sales',
                    'form17_increase_previous',
                    'form17_decrease_previous',
                    'previous_transaction',
                    'previous_own_group',
                    'previous_credit_sales_transaction',
                    'previous_day',
                    'cooperative_sales_last',
                    'total_receipts_last',
                    'cash_sales_previous',
                    'discount_previous',
                    'price_inc_previous',
                    'price_dec_previous',
                    // Opening Stock is carried too, but an F22 counted on this
                    // date overrides it again immediately below.
                    'opening_stock',
                ];

                foreach ($monthStartDataKeys as $dataKey) {
                    if (array_key_exists($dataKey, $payload)) {
                        $payload[$dataKey] = $zeroReportMetrics($payload[$dataKey]);
                    }
                }

                if ($f22OpeningStockAtMonthStart !== null) {
                    $payload['opening_stock'] = $f22OpeningStockAtMonthStart;
                }

                /*
                 * IS2109 #1: the per-category F16 maps are built from today_sales,
                 * so zeroing them blanked the day's own sale quantities and values
                 * even after today_sales itself was preserved. They stay.
                 *
                 * Likewise today's F16 and F17 reference numbers: they belong to
                 * the selected day and are what ties the displayed figures to real
                 * documents. Only the PREVIOUS day's references are cleared.
                 */
                $payload['previous_f16_nos'] = [];
                $payload['month_start_reset'] = true;
            } else {
                $payload['month_start_reset'] = false;
            }

            Cache::put($cacheKey, $payload, now()->addMinutes(5));

            return response()->json($payload)
                ->header('X-MPCS-21C-Cache', 'MISS');
    }

    public function Form21CQuery($business_id, $start_date, $end_date, $location_id = null)
    {
        // Credit sales for given date range & location, using tax-inclusive unit price
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.is_credit_sale', 1)
            ->whereNull('transactions.customer_group_id')
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select(
                'transactions.transaction_date',
                'transactions.final_total',
                'products.name as description',
                'products.sub_category_id',
                'transaction_sell_lines.quantity',
                // Use tax-inclusive unit price but expose it as unit_price so existing consumers keep working
                'transaction_sell_lines.unit_price_inc_tax as unit_price',
                'transactions.ref_no',
                'transactions.invoice_no',
                'contacts.name as customer',
                'transactions.invoice_no as order_no',
                'business.name as comapany',
                'business_locations.mobile as tel',
            );

        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }

        $credit_sales = $query->get();

        return $credit_sales;
    }
  
   
   
 
}

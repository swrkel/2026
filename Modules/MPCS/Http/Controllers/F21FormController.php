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
use Illuminate\Support\Facades\Log;
use Modules\MPCS\Entities\FormF16Detail;
use Modules\MPCS\Entities\FormF17Detail;
use Modules\MPCS\Entities\FormF17Header;
use Modules\MPCS\Entities\FormF17HeaderController;
use Modules\MPCS\Entities\FormF22Header;
use Modules\MPCS\Entities\FormF22Detail;
use App\Contact;
use App\Transaction;
use App\MergedSubCategory;
use App\User;
use Modules\MPCS\Entities\Mpcs21cFormSettings;
use Modules\MPCS\Entities\Settlement;

class F21FormController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;
    protected $util;
    protected $f21FormSettingsCache = [];
    protected $f21FormNumberCache = [];
    protected $f21DetailDateCountCache = [];
    protected $f21StartingQtyCache = [];
    protected $f21ProductVariationCache = [];
    
    /**
     * STEP 4: Cache for F22 Stock Taking data per request.
     * Structure keyed by "{business_id}|{date}|{location_id}" => ['is_stock_taking' => bool, 'quantities' => [product_id => stock_count]]
     */
    protected $f22StockTakingCache = [];
    protected $activeF22CacheKey = null;
    protected $bookNoFallbackLogged = [];

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
     * STEP 4: Check if a date is a Stock Taking date and cache F22 quantities.
     * STEP 5: Also caches F22 form_no for Book No display.
     * Called once per request. Results are cached for reuse across all endpoints.
     *
     * @param int $businessId
     * @param string $date (Y-m-d format)
     * @param int|null $locationId
     * @return bool True if date matches a Stock Taking date
     */
    protected function loadStockTakingDataIfNeeded($businessId, $date, $locationId = null)
    {
        $locationId = $this->normalizeLocationId($locationId);

        try {
            $resolvedDate = Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            $resolvedDate = $date;
        }

        $cacheKey = $this->buildStockTakingCacheKey($businessId, $resolvedDate, $locationId);

        if (isset($this->f22StockTakingCache[$cacheKey])) {
            $this->activeF22CacheKey = $cacheKey;
            return $this->f22StockTakingCache[$cacheKey]['is_stock_taking'];
        }

        $cache = [
            'is_stock_taking' => false,
            'quantities' => [],
            'form_no' => null, // STEP 5: Store F22 form_no for Book No display
            'context' => [
                'business_id' => $businessId,
                'date' => $resolvedDate,
                'location_id' => $locationId,
            ],
        ];

        if (!empty($businessId) && !empty($resolvedDate)) {
            $f22Header = $this->findStockTakingHeader($businessId, $resolvedDate, $locationId);

            if ($f22Header) {
                Log::debug('F21 stock taking detected', [
                    'business_id' => $businessId,
                    'date' => $resolvedDate,
                    'location_id' => $locationId,
                    'form_no' => $f22Header->form_no,
                    'header_id' => $f22Header->id,
                ]);

                // Load all F22 physical quantities for this Stock Taking date, keyed by product_id
                // Map F22 rows to product IDs using SKU or product name; table does not store product_id
                $quantities = FormF22Detail::where('form_f22_details.header_id', $f22Header->id)
                    ->when(!empty($locationId), function ($q) use ($locationId) {
                        $q->where('form_f22_details.location_id', $locationId);
                    })
                    ->leftJoin('products', function ($join) {
                        $join->on('form_f22_details.product_code', '=', 'products.sku')
                            ->orOn('form_f22_details.product', '=', 'products.name');
                    })
                    ->whereNotNull('products.id')
                    ->pluck('form_f22_details.stock_count', 'products.id')
                    ->toArray();

                $cache['is_stock_taking'] = true;
                $cache['quantities'] = $quantities;
                $cache['form_no'] = $f22Header->form_no;
            }
        }

        $this->f22StockTakingCache[$cacheKey] = $cache;
        $this->activeF22CacheKey = $cacheKey;

        return $cache['is_stock_taking'];
    }

    /**
     * STEP 4: Build a cache key for F22 lookups.
     */
    protected function buildStockTakingCacheKey($businessId, $date, $locationId = null)
    {
        return implode('|', [$businessId ?? 'null', $date ?? 'null', $locationId ?? 'null']);
    }

    /**
     * STEP 4: Normalize location filters (treat 'all' or empty as null).
     */
    protected function normalizeLocationId($locationId)
    {
        if ($locationId === 'all' || $locationId === '' || $locationId === null) {
            return null;
        }

        return $locationId;
    }

    /**
     * Locate Stock Taking header by date/location, checking both form_date and created_at.
     * Adds debug logs to prove which column/date was used.
     */
   protected function findStockTakingHeader($businessId, $date, $locationId = null)
{
    if (empty($businessId) || empty($date)) {
        return null;
    }

    try {
        $resolvedDate = Carbon::parse($date)->toDateString();
    } catch (\Exception $e) {
        Log::error('Date parsing error in findStockTakingHeader', [
            'date' => $date,
            'error' => $e->getMessage()
        ]);
        return null;
    }

    $baseContext = [
        'business_id' => $businessId,
        'date' => $resolvedDate,
        'table' => 'form_f22_headers',
    ];

    $header = \DB::table('form_f22_headers')
        ->where('business_id', $businessId)
        ->whereDate('form_date', $resolvedDate)
        /*
         * IS-1935: pick the LATEST F22 saved for this date, and respect the
         * location.
         *
         * This used to be a bare ->first() with no ordering and with the
         * $locationId argument accepted but never applied. MySQL is free to
         * return any matching row, and in practice that is the lowest id - the
         * OLDEST header. So when a new F22 was saved for a date that already had
         * one (a correction, a re-save, or simply a second location), the 21C
         * Opening Stock row kept reading the superseded form's stock_count and
         * the newly entered fuel quantities never appeared.
         *
         * Ordering by id desc makes the most recently saved F22 win, which is
         * what "after saving F22, show the new stocks as opening stock" means.
         * The location filter is applied only when one was passed, so callers
         * that legitimately want any location (normalizeLocationId returns null
         * for 'all'/empty) keep their existing behaviour.
         */
        ->when(!empty($locationId), function ($q) use ($locationId) {
            return $q->where('location_id', $locationId);
        })
        ->orderBy('id', 'desc')
        ->first();

    if ($header) {
        Log::debug('F21 stock taking header found', $baseContext + [
            'form_no' => $header->form_no,
            'header_id' => $header->id,
        ]);

        // Return as object that mimics the expected structure
        return (object)[
            'id' => $header->id,
            'form_no' => $header->form_no,
            'date' => $header->form_date,
            'business_id' => $header->business_id,
            'starting_number' => $header->form_no,
        ];
    }

    Log::debug('F21 stock taking header not found', $baseContext);
    return null;
}

    /**
     * STEP 4: Return the currently active F22 cache payload.
     *
     * @return array|null
     */
    protected function getActiveStockTakingCache()
    {
        if ($this->activeF22CacheKey !== null && isset($this->f22StockTakingCache[$this->activeF22CacheKey])) {
            return $this->f22StockTakingCache[$this->activeF22CacheKey];
        }
        return null;
    }

    /**
     * STEP 4: Sync cache context for a specific row date/location.
     *
     * @param int $businessId
     * @param string $date
     * @param int|null $locationId
     * @return void
     */
    protected function syncStockTakingContext($businessId, $date, $locationId = null)
    {
        if (empty($businessId) || empty($date)) {
            $this->activeF22CacheKey = null;
            return;
        }

        try {
            $parsedDate = Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            return;
        }

        $this->loadStockTakingDataIfNeeded($businessId, $parsedDate, $locationId);
    }

    /**
     * STEP 4: Get physical quantity from F22 for a given product_id.
     * Returns null if not a Stock Taking date or product not in F22.
     *
     * @param int $productId
     * @return float|null
     */
    protected function getF22StockQuantity($productId)
    {
        $cache = $this->getActiveStockTakingCache();
        if ($cache === null || !$cache['is_stock_taking']) {
            return null;
        }

        return $cache['quantities'][$productId] ?? null;
    }

    /**
     * Get F22 stock count for a specific product/date/location combination.
     */
    protected function getF22StockQuantityForDate($businessId, $productId, $date, $locationId = null)
    {
        if (empty($businessId) || empty($productId) || empty($date)) {
            return null;
        }

        $locationId = $this->normalizeLocationId($locationId);
        $header = $this->findStockTakingHeader($businessId, $date, $locationId);

        if (!$header) {
            return null;
        }

        $query = FormF22Detail::where('form_f22_details.header_id', $header->id)
            ->leftJoin('products', function ($join) {
                $join->on('form_f22_details.product_code', '=', 'products.sku')
                    ->orOn('form_f22_details.product', '=', 'products.name');
            })
            ->where('products.id', $productId);

        if ($locationId !== null) {
            $query->where('form_f22_details.location_id', $locationId);
        }

        $stockCount = $query->value('form_f22_details.stock_count');

        return $stockCount !== null ? (float) $stockCount : null;
    }

    /**
     * STEP 4: Check if current request is for a Stock Taking date.
     *
     * @return bool
     */
    protected function isStockTakingDate()
    {
        $cache = $this->getActiveStockTakingCache();
        return $cache !== null && $cache['is_stock_taking'];
    }

    /**
     * STEP 5: Get F22 form number for Stock Taking date Book No display.
     * Returns formatted string "F 22 / {form_no}" or null if not Stock Taking date.
     *
     * @return string|null
     */
    protected function getF22FormNo()
    {
        $cache = $this->getActiveStockTakingCache();
        if ($cache === null || !$cache['is_stock_taking'] || empty($cache['form_no'])) {
            return null;
        }
        return 'F22 / ' . $cache['form_no'];
    }

    /**
     * STEP 5: Temporary log to prove Book No fallback decisions without flooding logs.
     */
    protected function logBookNoFallback($transactionType, array $context = [])
    {
        if (isset($this->bookNoFallbackLogged[$transactionType])) {
            return;
        }

        $this->bookNoFallbackLogged[$transactionType] = true;

        Log::debug('F21 Book No defaulted', array_merge($context, [
            'transaction_type' => $transactionType,
            'book_no' => '-',
        ]));
    }

    /**
     * STEP 5: Get F16A form number for a purchase transaction.
     * Returns formatted string "F 16 A / {form_no}" or null if not found.
     * Matches by transaction_id first, then by invoice/ref numbers to improve hit rate.
     *
     * @param int|null $transactionId
     * @param string|null $invoiceNo
     * @param string|null $refNo
     * @return string|null
     */
    protected function getF16AFormNo($transactionId = null, $invoiceNo = null, $refNo = null)
    {
        if (empty($transactionId) && empty($invoiceNo) && empty($refNo)) {
            return null;
        }

        // Prefer transaction_id (exact match) so F21 shows the correct F16 for this purchase
        $f16Detail = null;
        if ($transactionId !== null && $transactionId !== '') {
            $f16Detail = FormF16Detail::where('transaction_id', (int) $transactionId)->latest('id')->first();
        }
        if (!$f16Detail && !empty($invoiceNo)) {
            $f16Detail = FormF16Detail::where('invoice_no', $invoiceNo)->latest('id')->first();
        }
        if (!$f16Detail && !empty($refNo)) {
            $f16Detail = FormF16Detail::where('invoice_no', $refNo)->latest('id')->first();
        }

        if ($f16Detail && $f16Detail->form_no !== null && $f16Detail->form_no !== '') {
            return 'F 16 A / ' . $f16Detail->form_no;
        }

        return null;
    }

    /**
     * Look up the latest F22 header for the selected date/location to override Book No.
     *
     * @param string|null $startDate
     * @param int|null $locationId
     * @return object|null
     */
    protected function getF22HeaderForDateAndLocation($businessId, $startDate, $locationId = null)
    {


        if (empty($startDate) || empty($businessId)) {
            return null;
        }
        
        return $this->findStockTakingHeader($businessId, $startDate, $locationId);
    }

    public function getOpeningStockFromF22()
{
    $business_id = request()->session()->get('business.id')
        ?? request()->session()->get('user.business_id')
        ?? optional(auth()->user())->business_id;
    $location_id = request()->get('location_id');
    $start_date = request()->get('start_date');
    $end_date = request()->get('end_date');

    $query = DB::table('form_f22_details')
        ->join('products', function ($join) {
            $join->on('form_f22_details.product', '=', 'products.name')
                ->on('form_f22_details.business_id', '=', 'products.business_id');
        })
        ->join('form_f22_headers', 'form_f22_details.header_id', '=', 'form_f22_headers.id')
        ->where('form_f22_details.business_id', $business_id)
        ->select([
            DB::raw('form_f22_headers.form_date as transaction_date'),
            DB::raw('"F22 Opening Balance" as invoice_no'),
            DB::raw('"Opening Stock" as transaction_type'),
            DB::raw('"Opening Stock" as transaction_type_label'),
            'products.sku as product_code',
            'products.name as product_name',
            DB::raw('0 as received_qty'),
            DB::raw('0 as sold_qty'),
            'form_f22_details.stock_count as balance_qty',
            'products.id as product_id',
            'form_f22_details.location_id as location_id',
            DB::raw('CONCAT("F22 / ", form_f22_headers.form_no) as book_no'),
            DB::raw('form_f22_headers.form_no as form_number'),
        ]);

    if (!empty($location_id)) {
        $query->where('form_f22_details.location_id', $location_id);
    }

    if (!empty($start_date) && !empty($end_date)) {
        $query->whereBetween('form_f22_headers.form_date', [$start_date, $end_date]);
    } elseif (!empty($start_date)) {
        $query->whereDate('form_f22_headers.form_date', $start_date);
    }

    /*
     * Each F22 detail is already one product/header row.  The previous partial
     * GROUP BY selected fourteen non-aggregated columns and is rejected when
     * MySQL ONLY_FULL_GROUP_BY is enabled.  getAllTransactions() caught that
     * exception and silently returned an empty F21 table.  A tenant-scoped join
     * plus DISTINCT gives the intended rows without invalid SQL.
     */
    return $query->distinct()->get();
}

  


    /**
     * Display a listing of the resource.
     * @return Response
     */
   
    
    
public function index(Request $request)
{
        if (!auth()->check()) {
            return redirect()->route('login');
        } 
       if (!auth()->check()) {
            return redirect()->route('login');
        } 
        $business_id = request()->session()->get('business.id') ?? request()->session()->get('user.business_id');
        if (empty($business_id)) {
            $business_id = optional(Business::first())->id;
        }      

               
         if (auth()->check() && auth()->user()->can('superadmin'))
            $settings = Mpcs21cFormSettings::first();
        else 
            $settings = Mpcs21cFormSettings::where('business_id', $business_id)->first();

        $bname = Business::where('id', $business_id)->first();
    
        $form_number = optional($settings)->starting_number ? $settings->starting_number : "";
        $date = optional($settings)->date ? $settings->date : "";
        $userAdded = $bname ? $bname->name : "";

        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();
        $business_locations = BusinessLocation::forDropdown($business_id);
        $permitted_locations = auth()->user()->permitted_locations();
        $locations_for_user = $business_locations;
        if ($permitted_locations !== 'all' && is_array($permitted_locations)) {
            $locations_for_user = collect($business_locations)->only($permitted_locations);
        }
        $location_keys = collect($locations_for_user)->keys()->values();

        // Determine default value based on location count
        if (count($locations_for_user) === 1) {
            $default_location_id = $location_keys->first();
            $location_options = collect($locations_for_user)->toArray();
        } else {
            $default_location_id = $request->get('location_id', '');
            $location_options = ['' => __('lang_v1.all')] + collect($locations_for_user)->toArray();
        }

      
        $business_details = Business::find($business_id);
        $currency_precision = (int) ($business_details->currency_precision ?? config('constants.currency_precision', 2));
        $qty_precision = (int) ($business_details->quantity_precision ?? config('constants.quantity_precision', 2));
      
       
          $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();
                     
       
       if (!empty($settings)) {
    $targetDateString = !empty($request->start_date) 
        ? $request->start_date 
        : Carbon::today()->toDateString();
        $F21c_from_no = $this->getF21FormNumberForDate($business_id, $targetDateString, $default_location_id);
    } else {
        $F21c_from_no = '';
}

       // Filter to show only Fuel category sub-categories
       $fuelCategory = Category::subCategoryOnlyFuel($business_id)
           ->sortBy('name')
           ->pluck('name', 'id');
        $previous_start_date = Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
        $previous_end_date = Carbon::parse($request->end_date)->subDays(1)->format('Y-m-d');
        $today = Transaction::leftJoin('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
                    ->leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
                    ->leftJoin('products', 'transaction_sell_lines.product_id', '=', 'products.id')
                    ->leftJoin('categories', 'products.sub_category_id', '=', 'categories.id')
                    ->select(
                        'categories.name as category_name',  
                                             
                        'transaction_payments.amount as amount',
                       'transaction_sell_lines.quantity as quantity' ,
                   
                       DB::raw('SUM(transaction_payments.amount) as total_final_amount'),
                       DB::raw('SUM(transaction_sell_lines.quantity) as total_quantity')
                    )
                    ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
                    ->whereDate('transactions.transaction_date', '<=', $previous_end_date)
                    ->groupBy('categories.name','products.id')
                    ->get();
            
                $previous_transaction =Transaction::leftJoin('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
                    ->leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
                    ->leftJoin('products', 'transaction_sell_lines.product_id', '=', 'products.id')
                    ->leftJoin('categories', 'products.sub_category_id', '=', 'categories.id')
                    ->select(
                        'categories.name as category_name',  
                                             
                        'transaction_payments.amount as amount',
                       'transaction_sell_lines.quantity as quantity' ,
                   
                       DB::raw('SUM(transaction_payments.amount) as total_final_amount'),
                       DB::raw('SUM(transaction_sell_lines.quantity) as total_quantity')
                    )
                    ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
                    ->groupBy('categories.name')
                                  
                    ->get();
                $query = Transaction::leftjoin('purchase_lines', 'transactions.id', '=', 'purchase_lines.transaction_id')
                    ->leftjoin('products', 'purchase_lines.product_id', 'products.id')
                    ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
                    ->leftjoin('business', 'transactions.business_id', 'business.id')
                    ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
                    ->where('transactions.type', 'purchase')
        
                    ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
                    ->whereDate('transactions.transaction_date', '<=', $previous_end_date)
                    ->select('transactions.id', 'transactions.transaction_date', 'transactions.final_total as final_total', 'products.name as description', 'products.sub_category_id', 'purchase_lines.quantity', 'purchase_lines.purchase_price', 'transactions.ref_no', 'transactions.invoice_no', 'contacts.name as customer', 'transactions.invoice_no as order_no', 'business.name as comapany', 'business_locations.mobile as tel');
               
                $receipts = $query->get();
                
        $layout = 'layouts.app';
       
         // Get the latest form setting record
         $latestForm = Mpcs21cFormSettings::latest()->first();
    
         $categoriesData = [];
         $opening_stock = DB::table('form_f22_details')        
         ->join('products', 'form_f22_details.product', '=', 'products.name')
         ->join('categories', 'products.sub_category_id', '=', 'categories.id')
         ->where('form_f22_details.business_id', $business_id)           
         ->groupBy('categories.id', 'categories.name')
         ->orderBy('form_no', 'desc') // Optional: applies to full result set
         ->select(
             'categories.id as category_id',
             'categories.name as category_name',
             DB::raw('SUM(form_f22_details.stock_count) as total_quantity'),
             DB::raw('SUM(form_f22_details.debit) as total_sales'),
             DB::raw('MAX(form_f22_details.form_no) as form_no') // Representative form_no
         )
         ->get();
     
         $formNo = optional($opening_stock->first())->form_no;
         if ($latestForm && $latestForm->categories) {
             $categoriesData = json_decode($latestForm->categories, true);
         }
           //today
           $start_today_date = Carbon::parse($request->start_date)->format('Y-m-d');//Carbon::now();
           $end_today_date =Carbon::parse($request->end_date)->format('Y-m-d');//Carbon::now();
 
           $pump_operator = DB::table('pumps')
           ->leftJoin('products', 'pumps.product_id', '=', 'products.id')
           ->leftJoin('categories', 'products.sub_category_id', '=', 'categories.id')
           ->groupBy(
               'categories.id',
               'categories.name',
               'pumps.pump_no',
               'pumps.id',
               'products.name'
           )
           ->select([
               'categories.id as category_id',
               'categories.name as category_name',
               'pumps.pump_no',
               'pumps.id as pump_id',
           ])
           ->get();
           
        return view('mpcs::forms.21CForm.F21_form')->with(compact(
            'today',
            'previous_transaction',
            'receipts',
           'F21c_from_no',
            'sub_categories',
            'fuelCategory',
            'currency_precision',
            'qty_precision',
            'merged_sub_categories',
             'business_locations',
             'location_options',
             'default_location_id',
             'settings',
             'form_number',
             'date',
             'formNo',
             'userAdded',
            'layout',
            'categoriesData',
            'latestForm',
            'pump_operator' 
            ));

    }

        /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function get21CFormSettings() {

        $business_id = request()->session()->get('business.id') ?? request()->session()->get('user.business_id');
        $business = Business::find($business_id);
        $quantity_precision = !empty($business->quantity_precision) ? $business->quantity_precision : 2;
        $currency_precision = !empty($business->currency_precision) ? $business->currency_precision : 2;

        // Use all sub-categories for this business (no special "Fuel" parent)
        $fuelCategory = Category::where('business_id', $business_id)
            ->where('parent_id', '!=', 0)
            ->select(['name', 'id'])
            ->orderBy('name')
            ->pluck('name', 'id');

        if (auth()->user()->can('superadmin')) {
            $pumps = Product::leftJoin('pumps', 'products.id', 'pumps.product_id')
                ->pluck('pumps.pump_name', 'pumps.id');
        } else {
            $pumps = Product::leftJoin('pumps', 'products.id', 'pumps.product_id')
                ->where('products.business_id', $business_id)
                ->pluck('pumps.pump_name', 'pumps.id');
        }

        $latest = Mpcs21cFormSettings::where('business_id', $business_id)->orderBy('date', 'desc')->first();
        if (!empty( $latest))
        {
            $current_date = Carbon::today();
            $starting_day = Carbon::parse($latest->date);
            $days_passed = $starting_day->diffInDays($current_date);
            $starting_number =$latest->starting_number + $days_passed;
        }
       else
       {
        $starting_number =1;
       }

        $latestForm = $latest;
        $latestData = ($latest && !empty($latest->categories)) ? json_decode($latest->categories, true) : [];
        // fuelPrices no longer used; do not compact an undefined variable
        return view('mpcs::forms.21CForm.create_21c_form_settings', compact('fuelCategory', 'pumps','starting_number', 'quantity_precision', 'currency_precision', 'latestForm', 'latestData'));

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
       
        $business_id = request()->session()->get('business.id');
        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();
        
       $start_date = $request->start_date;
            $end_date = $request->end_date;
            $location_id = $request->location_id;

            $settings = MpcsFormSetting::where('business_id', $business_id)->first();
            $F21C_form_tdate = $settings->F21C_form_tdate;
            $previous_start_date = Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
            $previous_end_date = Carbon::parse($request->end_date)->subDays(1)->format('Y-m-d');
            $startDate = Carbon::createFromFormat('Y-m-d', $start_date);
            $endDate = Carbon::createFromFormat('Y-m-d', $end_date);

            $credit_sales = $this->Form21CQuery($business_id, $start_date, $end_date, $location_id);
            $previous_credit_sales = $this->Form21CQuery($business_id, $previous_end_date, $previous_end_date, $location_id);
            $form22_details = FormF22Detail::where('business_id', $business_id)
                                ->whereDate('created_at', '>=', $startDate)
                                ->whereDate('created_at', '<=', $endDate) 
                                ->orderBy('id', 'DESC')               
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
                                'transaction_sell_lines.unit_price',
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
                                'transaction_sell_lines.unit_price',
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
                                'transaction_sell_lines.unit_price',
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
                                'transaction_sell_lines.unit_price',
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
                                'transaction_sell_lines.unit_price',
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
                                'transaction_sell_lines.unit_price',
                                'transactions.ref_no',
                                'transactions.invoice_no',
                                'transactions.invoice_no as order_no'
                            )
                            ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
                            ->where('transaction_payments.method', 'credit_sales')
                            ->orderBy('id', 'DESC')               
                            ->first();

            $account_transactions = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    ->whereDate('transactions.transaction_date', '>=', $start_date)
                    ->whereDate('transactions.transaction_date', '<=', $end_date)
                    ->where('account_transactions.business_id',$business_id)
                    ->get();
                    
            $opening_stock = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    //->whereDate('transactions.transaction_date', '>=', $start_date)
                    //->whereDate('transactions.transaction_date', '<=', $end_date)
                    ->where('account_transactions.business_id',$business_id)
                    ->where('transactions.type','opening_stock')
                    ->where('transactions.status','final')
                    ->sum('account_transactions.amount');
                    // ->get();
                    
            // $today = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
            //         ->whereDate('transactions.transaction_date', '=', Carbon::now())
            //         ->where('account_transactions.business_id',$business_id)
            //         ->sum('account_transactions.amount');   
                    // ->get();



                    $today = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                    ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                    ->select(
                        'transactions.id',
                        'transactions.transaction_date',
                        'transactions.final_total',
                        'transaction_payments.method as payment_method',
                        'transaction_sell_lines.quantity',
                        'transaction_sell_lines.unit_price',
                        'transactions.ref_no',
                        'transactions.invoice_no',
                        'transactions.invoice_no as order_no'
                    )
                    ->whereDate('transactions.transaction_date', '=', Carbon::now())                   
                    ->orderBy('id', 'DESC')               
                    ->first();        
            $previous_day = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    ->whereDate('transactions.transaction_date', '=', Carbon::now()->subDays(1))
                    ->where('account_transactions.business_id',$business_id)
                    ->sum('account_transactions.amount');
                    // ->get();
                        $incomeGrp_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')->where('accounts.business_id', $business_id)->where('account_groups.name', 'Sales Income Group')->select('accounts.id')->get()->pluck('id');
             $cash_sales_today = AccountTransaction::whereDate('account_transactions.operation_date','=', Carbon::now())
                ->join('transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
                ->where('account_transactions.business_id',$business_id)
                ->where('account_transactions.type','debit')
                ->get()->sum('amount');
             $credit_sales_today = AccountTransaction::whereDate('account_transactions.operation_date','=', Carbon::now())
                ->join('transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
                ->where('account_transactions.business_id',$business_id)
                ->where('account_transactions.type','credit')
                ->get()->sum('amount');
            
            return [
                "credit_sales" => $credit_sales, 
                "previous_credit_sales" => $previous_credit_sales,
                "form22_details" => $form22_details,
                "form17_increase" => $form17_increase,
                "form17_decrease" => $form17_decrease,
                "transaction" => $transaction,
                "own_group" => $own_group,
                "credit_sales_transaction" => $credit_sales_transaction,
                "previous_transaction" => $previous_transaction,
                "previous_own_group" => $previous_own_group,
                "previous_credit_sales_transaction" => $previous_credit_sales_transaction,
                "form17_increase_previous" => $form17_increase_previous,
                "form17_decrease_previous" => $form17_decrease_previous,
                "merged_sub_categories" => $merged_sub_categories,
                "account_transactions" => $account_transactions,
                "opening_stock" => $opening_stock,
                "previous_day" => (int)$previous_day,
                "today" => $today,
                "cash_sales_today" => (int)$cash_sales_today,
                "credit_sales_today" => (int)$credit_sales_today,
            ];
    }
    public function get_21_c_form_all_querys(Request $request)
    {
       
        $business_id = request()->session()->get('business.id');
        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();
        
       $start_date = $request->start_date;
            $end_date = $request->end_date;
            $location_id = $request->location_id;

            $settings = MpcsFormSetting::where('business_id', $business_id)->first();
            $F21C_form_tdate = $settings->F21C_form_tdate;
            $previous_start_date = Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
            $previous_end_date = Carbon::parse($request->end_date)->subDays(1)->format('Y-m-d');
            $startDate = Carbon::createFromFormat('Y-m-d', $start_date);
            $endDate = Carbon::createFromFormat('Y-m-d', $end_date);

            $credit_sales = $this->Form21CQuery($business_id, $start_date, $end_date, $location_id);
            $previous_credit_sales = $this->Form21CQuery($business_id, $previous_end_date, $previous_end_date, $location_id);
            $form22_details = FormF22Detail::where('business_id', $business_id)
                                ->whereDate('created_at', '>=', $startDate)
                                ->whereDate('created_at', '<=', $endDate) 
                                ->orderBy('id', 'DESC')               
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
                                'transaction_sell_lines.unit_price',
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
                                'transaction_sell_lines.unit_price',
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
                                'transaction_sell_lines.unit_price',
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
                                'transaction_sell_lines.unit_price',
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
                                'transaction_sell_lines.unit_price',
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
                                'transaction_sell_lines.unit_price',
                                'transactions.ref_no',
                                'transactions.invoice_no',
                                'transactions.invoice_no as order_no'
                            )
                            ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
                            ->where('transaction_payments.method', 'credit_sales')
                            ->orderBy('id', 'DESC')               
                            ->first();

            $account_transactions = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    ->whereDate('transactions.transaction_date', '>=', $start_date)
                    ->whereDate('transactions.transaction_date', '<=', $end_date)
                    ->where('account_transactions.business_id',$business_id)
                    ->get();
            
            $opening_stock = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    //->whereDate('transactions.transaction_date', '>=', $start_date)
                    //->whereDate('transactions.transaction_date', '<=', $end_date)
                    ->where('account_transactions.business_id',$business_id)
                    ->where('transactions.type','opening_stock')
                    ->where('transactions.status','final')
                    ->sum('account_transactions.amount');
                    // ->get();
                    
            $today = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    ->whereDate('transactions.transaction_date', '=', Carbon::now())
                    ->where('account_transactions.business_id',$business_id)
                    ->sum('account_transactions.amount');   
                    // ->get();
            $previous_day = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    ->whereDate('transactions.transaction_date', '=', Carbon::now()->subDays(1))
                    ->where('account_transactions.business_id',$business_id)
                    ->sum('account_transactions.amount');
                    // ->get();
                        $incomeGrp_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')->where('accounts.business_id', $business_id)->where('account_groups.name', 'Sales Income Group')->select('accounts.id')->get()->pluck('id');
             $cash_sales_today = AccountTransaction::whereDate('account_transactions.operation_date','=', Carbon::now())
                ->join('transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
                ->where('account_transactions.business_id',$business_id)
                ->where('account_transactions.type','debit')
                ->get()->sum('amount');
             $credit_sales_today = AccountTransaction::whereDate('account_transactions.operation_date','=', Carbon::now())
                ->join('transactions', 'transactions.id', '=', 'account_transactions.trfansaction_id')
                ->where('account_transactions.business_id',$business_id)
                ->where('account_transactions.type','credit')
                ->get()->sum('amount');
            
            return [
                "credit_sales" => $credit_sales, 
                "previous_credit_sales" => $previous_credit_sales,
                "form22_details" => $form22_details,
                "form17_increase" => $form17_increase,
                "form17_decrease" => $form17_decrease,
                "transaction" => $transaction,
                "own_group" => $own_group,
                "credit_sales_transaction" => $credit_sales_transaction,
                "previous_transaction" => $previous_transaction,
                "previous_own_group" => $previous_own_group,
                "previous_credit_sales_transaction" => $previous_credit_sales_transaction,
                "form17_increase_previous" => $form17_increase_previous,
                "form17_decrease_previous" => $form17_decrease_previous,
                "merged_sub_categories" => $merged_sub_categories,
                "account_transactions" => $account_transactions,
                "opening_stock" => $opening_stock,
                "previous_day" => (int)$previous_day,
                "today" => (int)$today,
                "cash_sales_today" => (int)$cash_sales_today,
                "credit_sales_today" => (int)$credit_sales_today,
            ];
    }
	 
    public function Form21CQuery($business_id, $start_date, $end_date, $location_id)
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
                // Use tax-inclusive unit price but expose it as unit_price so existing blades keep working
                'transaction_sell_lines.unit_price_inc_tax as unit_price',
                'transactions.ref_no',
                'transactions.invoice_no',
                'contacts.name as customer',
                'transactions.invoice_no as order_no',
                'business.name as comapany',
                'business_locations.mobile as tel'
            );

        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }

        $credit_sales = $query->get();

        return $credit_sales;
    }

    /**
     * Helper to strip commas from numeric inputs
     */
    private function clean_numeric($val) {
        if (is_array($val)) return $val;
        return str_replace(',', '', $val);
    }

    /**
     * Compute starting qty for F21 rows using:
     * starting = balance - received + sold
     */
    private function computeF21StartingQty($row, bool $absolute = false): string
    {
        $type = is_array($row)
            ? ($row['transaction_type'] ?? ($row['transaction_type_label'] ?? ''))
            : ($row->transaction_type ?? ($row->transaction_type_label ?? ''));

        $businessId = request()->session()->get('business.id') ?? request()->session()->get('user.business_id');
        $productId = is_array($row) ? ($row['product_id'] ?? null) : ($row->product_id ?? null);
        $locationId = is_array($row) ? ($row['location_id'] ?? null) : ($row->location_id ?? null);
        $transactionDate = is_array($row) ? ($row['transaction_date'] ?? null) : ($row->transaction_date ?? null);

        if (empty($businessId) || empty($productId) || empty($transactionDate)) {
            return number_format(0, 2);
        }

        try {
            $currentDate = Carbon::parse($transactionDate)->toDateString();
            $previousDate = Carbon::parse($transactionDate)->subDay()->toDateString();
        } catch (\Exception $e) {
            return number_format(0, 2);
        }

        if ($type === 'Opening Stock') {
            $openingStockQty = $this->getF22StockQuantityForDate($businessId, $productId, $currentDate, $locationId);

            if ($openingStockQty !== null) {
                return number_format($absolute ? abs($openingStockQty) : $openingStockQty, 2);
            }

            $balanceRaw = is_array($row) ? ($row['balance_qty'] ?? 0) : ($row->balance_qty ?? 0);
            $balanceVal = (float) str_replace(',', '', (string) $balanceRaw);

            return number_format($absolute ? abs($balanceVal) : $balanceVal, 2);
        }

        $startingQty = $this->getF21PreviousBalanceQty($businessId, $productId, $previousDate, $locationId);

        if ($startingQty == 0.0 && $this->isF21OpeningDate($businessId, $currentDate)) {
            $openingStockQty = $this->getF22StockQuantityForDate($businessId, $productId, $currentDate, $locationId);
            if ($openingStockQty !== null) {
                $startingQty = (float) $openingStockQty;
            }
        }

        return number_format($absolute ? abs($startingQty) : $startingQty, 2);
    }

    /**
     * Compute balance qty for F21 rows using:
     * balance = starting + received - sold
     */
    private function computeF21BalanceQty($row): string
    {
        $businessId = request()->session()->get('business.id') ?? request()->session()->get('user.business_id');
        $productId = is_array($row) ? ($row['product_id'] ?? null) : ($row->product_id ?? null);
        $locationId = is_array($row) ? ($row['location_id'] ?? null) : ($row->location_id ?? null);
        $transactionDate = is_array($row) ? ($row['transaction_date'] ?? null) : ($row->transaction_date ?? null);

        if (!empty($businessId) && !empty($productId) && !empty($transactionDate)) {
            $this->syncStockTakingContext($businessId, $transactionDate, $locationId);

            if ($this->isStockTakingDate()) {
                $f22Qty = $this->getF22StockQuantity($productId);
                if ($f22Qty !== null) {
                    return number_format((float) $f22Qty, 2);
                }

                return number_format(0, 2);
            }
        }

        $startingQty = (float) str_replace(',', '', $this->computeF21StartingQty($row));
        $receivedQty = (float) str_replace(',', '', (string) (is_array($row) ? ($row['received_qty'] ?? 0) : ($row->received_qty ?? 0)));
        $soldQty = (float) str_replace(',', '', (string) (is_array($row) ? ($row['sold_qty'] ?? 0) : ($row->sold_qty ?? 0)));

        return number_format($startingQty + $receivedQty - $soldQty, 2);
    }

    /**
     * Apply row-by-row F21 balances so each product starts from the previous
     * displayed entry's balance instead of recalculating every row from the
     * same opening/current stock figure.
     */
    protected function applyF21RunningBalances($rows)
    {
        $value = function ($row, $key) {
            if (is_array($row)) {
                return $row[$key] ?? null;
            }

            if (is_object($row)) {
                return $row->$key ?? null;
            }

            return null;
        };

        $number = function ($value) {
            return (float) str_replace(',', '', (string) ($value ?? 0));
        };

        $rows = collect($rows)->map(function ($row, $index) {
            $row = is_array($row) ? (object) $row : $row;
            $row->_f21_original_index = $index;

            return $row;
        });

        $runningBalances = [];

        return $rows
            ->sortBy(function ($row) use ($value) {
                $date = $value($row, 'transaction_date');
                try {
                    $timestamp = Carbon::parse($date)->timestamp;
                } catch (\Exception $e) {
                    $timestamp = 0;
                }

                return sprintf('%020d-%010d', $timestamp, $row->_f21_original_index ?? 0);
            })
            ->map(function ($row) use (&$runningBalances, $value, $number) {
                $productId = $value($row, 'product_id');
                $locationId = $this->normalizeLocationId($value($row, 'location_id')) ?? 'all';

                if (empty($productId)) {
                    unset($row->_f21_original_index);
                    return $row;
                }

                $balanceKey = $productId . '|' . $locationId;
                $startingQty = array_key_exists($balanceKey, $runningBalances)
                    ? $runningBalances[$balanceKey]
                    : $number($this->computeF21StartingQty($row));

                $receivedQty = $number($value($row, 'received_qty'));
                $soldQty = $number($value($row, 'sold_qty'));
                $balanceQty = $startingQty + $receivedQty - $soldQty;

                $row->starting_qty = number_format(abs($startingQty), 2);
                $row->balance_qty = number_format($balanceQty, 2);
                $runningBalances[$balanceKey] = $balanceQty;

                unset($row->_f21_original_index);

                return $row;
            })
            ->values();
    }

    /**
     * Check whether the given date is the configured F21 opening date.
     */
    protected function isF21OpeningDate($businessId, $date): bool
    {
        if (empty($businessId) || empty($date)) {
            return false;
        }

        if (!isset($this->f21FormSettingsCache[$businessId])) {
            $this->f21FormSettingsCache[$businessId] = MpcsFormSetting::where('business_id', $businessId)->first();
        }

        $settings = $this->f21FormSettingsCache[$businessId] ?? null;
        if (empty($settings) || empty($settings->F21_form_tdate)) {
            return false;
        }

        try {
            return Carbon::parse($settings->F21_form_tdate)->toDateString() === Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Resolve the previous balance qty for a product before the given report date.
     */
    protected function getF21PreviousBalanceQty($businessId, $productId, $previousDate, $locationId = null): float
    {
        if (empty($businessId) || empty($productId) || empty($previousDate)) {
            return 0.0;
        }

        $locationId = $this->normalizeLocationId($locationId);
        $cacheKey = implode('|', [$businessId, $productId, $previousDate, $locationId ?? 'null']);

        if (array_key_exists($cacheKey, $this->f21StartingQtyCache)) {
            return $this->f21StartingQtyCache[$cacheKey];
        }

        $f22Qty = $this->getF22StockQuantityForDate($businessId, $productId, $previousDate, $locationId);
        if ($f22Qty !== null) {
            return $this->f21StartingQtyCache[$cacheKey] = (float) $f22Qty;
        }

        if ($locationId === null) {
            return $this->f21StartingQtyCache[$cacheKey] = 0.0;
        }

        $variationId = $this->getF21VariationIdForProduct($productId);
        if (empty($variationId)) {
            return $this->f21StartingQtyCache[$cacheKey] = 0.0;
        }

        $history = $this->productUtil->getVariationStockHistory(
            $businessId,
            $variationId,
            $locationId,
            [
                'search_box' => null,
                'sales_form_no' => null,
                'purchase_order_no' => null,
                'bill_no' => null,
                'customer_id' => null,
                'supplier_id' => null,
                'date_range' => null,
            ],
            null,
            null,
            $previousDate
        );

        $previousBalance = 0.0;
        if (!empty($history)) {
            $lastRow = last($history);
            $previousBalance = (float) ($lastRow['stock'] ?? 0);
        }

        return $this->f21StartingQtyCache[$cacheKey] = $previousBalance;
    }

    /**
     * Resolve the first variation for a product so stock history can be calculated.
     */
    protected function getF21VariationIdForProduct($productId)
    {
        if (empty($productId)) {
            return null;
        }

        if (array_key_exists($productId, $this->f21ProductVariationCache)) {
            return $this->f21ProductVariationCache[$productId];
        }

        $variationId = DB::table('variations')
            ->where('product_id', $productId)
            ->orderBy('id')
            ->value('id');

        $this->f21ProductVariationCache[$productId] = $variationId;

        return $variationId;
    }
  
    
    public function store21cFormSettings(Request $request)
    {
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');

        // Only allow one 21C settings record per business per day
        try {
            $settings_date = Carbon::parse($request->datepicker)->toDateString();
        } catch (\Exception $e) {
            $settings_date = Carbon::today()->toDateString();
        }

        $already_exists = Mpcs21cFormSettings::where('business_id', $business_id)
            ->where('date', $settings_date)
            ->exists();

        if ($already_exists) {
            return back()->with('status', [
                'success' => 0,
                'msg' => '21C Form Settings already saved for this date.',
            ]);
        }
    
        // Step 1: Initialize an empty categories array
        $categories_data = [];
    
        // Step 2: Extract relevant fields from the request
        $previous_day = $request->input('previous_day', []);
        $opening_stock = $request->input('opening_stock', []);
        $total_issues = $request->input('total_issues', []);
    
        // Step 3: Get all category keys
        $category_keys = array_unique(array_merge(
            array_keys($previous_day),
            array_keys($opening_stock),
            array_keys($total_issues)
        ));
    
        // Step 4: Build categories data and calculate totals
        $rec_sec_prev_day_amt = 0;
        $rec_sec_opn_stock_amt = 0;
        $issue_section_previous_day_amount = 0;

        foreach ($category_keys as $category_id) {
            $prev_qty = $this->clean_numeric($previous_day[$category_id]['qty'] ?? null);
            $prev_val = $this->clean_numeric($previous_day[$category_id]['val'] ?? null);
            $opn_qty  = $this->clean_numeric($opening_stock[$category_id]['qty'] ?? null);
            $opn_val  = $this->clean_numeric($opening_stock[$category_id]['val'] ?? null);
            $iss_qty  = $this->clean_numeric($total_issues[$category_id]['qty'] ?? null);
            $iss_val  = $this->clean_numeric($total_issues[$category_id]['val'] ?? null);

            $categories_data[$category_id] = [
                'previous_day' => [
                    'qty' => $prev_qty,
                    'val' => $prev_val,
                ],
                'opening_stock' => [
                    'qty' => $opn_qty,
                    'val' => $opn_val,
                ],
                'total_issues' => [
                    'qty' => $iss_qty,
                    'val' => $iss_val,
                ],
                'today' => [
                    'qty' => null,
                    'val' => null,
                ]
            ];

            $rec_sec_prev_day_amt += (float)($prev_val ?? 0);
            $rec_sec_opn_stock_amt += (float)($opn_val ?? 0);
            $issue_section_previous_day_amount += (float)($iss_val ?? 0);
        }
    
        // Step 5: Prepare data for insertion
        $formData = [
            'business_id' => $business_id,
            'date' => $settings_date,
            'time' => $request->time,
            'starting_number' => $request->starting_number,
            'ref_pre_form_number' => $request->starting_number,
            'rec_sec_prev_day_amt' => $rec_sec_prev_day_amt,
            'rec_sec_opn_stock_amt' => $rec_sec_opn_stock_amt,
            'issue_section_previous_day_amount' => $issue_section_previous_day_amount,
            'manager_name' => $request->manager_name,
            'categories' => json_encode($categories_data),
        ];
    
        // Step 6: Insert into database
        Mpcs21cFormSettings::create($formData);
    
        $output = [
            'success' => 1,
            'msg' => __('success'),
        ];
        
        return back()->with('status', $output);
    }
    
    
    public function mpcs21cFormSettings()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('business.id') ?? request()->session()->get('user.business_id');
            $business = Business::find($business_id);
            $quantity_precision = !empty($business->quantity_precision) ? $business->quantity_precision : 2;

            $header = Mpcs21cFormSettings::where('business_id', $business_id)->select('*')
                ->groupBy('starting_number')
                ->get();
                 
            return DataTables::of($header)
            ->editColumn('date', function($row) {
                $formattedTime = Carbon::parse($row->time)->format('H:i');
                return $row->date.' '.$formattedTime;
            })
             ->editColumn('rec_sec_prev_day_amt', function($row) use ($quantity_precision) {
                return number_format($row->rec_sec_prev_day_amt, $quantity_precision, '.', ',');
            })
            ->editColumn('rec_sec_opn_stock_amt', function($row) use ($quantity_precision) {
                return number_format($row->rec_sec_opn_stock_amt, $quantity_precision, '.', ',');
            })
            ->editColumn('issue_section_previous_day_amount', function($row) use ($quantity_precision) {
                return number_format($row->issue_section_previous_day_amount, $quantity_precision, '.', ',');
            })
            ->addColumn('pumps_data', function ($row) {
                $pumps = json_decode($row->pumps, true);
                $meters = json_decode($row->meters, true);
        
                $rows = [];
        
                if (!empty($pumps)) {
                    foreach ($pumps as $index => $pump) {
                        $rows[] = [
                            'pump_name' => Pump::where('id', $pump)->value('pump_name'),
                            'last_meter_value' => isset($meters[$index]) ? $meters[$index] : null
                        ];
                    }
                }
        
                return $rows; // Return array of pump-meter pairs
            })
            ->make(true);
        }

    }


    public function edit21cFormSetting($id) {
        $business_id = request()->session()->get('business.id') ?? request()->session()->get('user.business_id');
        $business = Business::find($business_id);
        $quantity_precision = !empty($business->quantity_precision) ? $business->quantity_precision : 2;
        $currency_precision = !empty($business->currency_precision) ? $business->currency_precision : 2;

        // Filter to show only Fuel category sub-categories
        $fuelCategory = Category::subCategoryOnlyFuel($business_id)
            ->select(['name', 'id'])
            ->orderBy('name')
            ->pluck('name', 'id');

        if(auth()->user()->can('superadmin')) {
            $settings = Mpcs21cFormSettings::where('id', $id)->first();
        } else {
            $settings = Mpcs21cFormSettings::where('business_id', $business_id)->where('id', $id)->first();
        }    
        $latestForm = $settings;
    
        
           $categoriesData = [];
       
           if ($latestForm && $latestForm->categories) {
               $categoriesData = json_decode($latestForm->categories, true);
           }

        return view('mpcs::forms.21CForm.edit_21c_form_settings')->with(compact(
                    'categoriesData', 'fuelCategory', 'latestForm', 'quantity_precision', 'currency_precision'
        ));
    }


    public function mpcs21Update(Request $request, $id)
{
    $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');

    // Delete the previous entry
    Mpcs21cFormSettings::destroy($id);

    // Step 1: Initialize an empty categories array
    $categories_data = [];

    // Step 2: Extract relevant fields from the request
    $previous_day = $request->input('previous_day', []);
    $opening_stock = $request->input('opening_stock', []);
    $total_issues = $request->input('total_issues', []);

    // Step 3: Loop through the unique category IDs
    $category_keys = array_unique(array_merge(
        array_keys($previous_day),
        array_keys($opening_stock),
        array_keys($total_issues)
    ));

    $rec_sec_prev_day_amt = 0;
    $rec_sec_opn_stock_amt = 0;
    $issue_section_previous_day_amount = 0;

    foreach ($category_keys as $category_id) {
        $prev_qty = $this->clean_numeric($previous_day[$category_id]['qty'] ?? null);
        $prev_val = $this->clean_numeric($previous_day[$category_id]['val'] ?? null);
        $opn_qty  = $this->clean_numeric($opening_stock[$category_id]['qty'] ?? null);
        $opn_val  = $this->clean_numeric($opening_stock[$category_id]['val'] ?? null);
        $iss_qty  = $this->clean_numeric($total_issues[$category_id]['qty'] ?? null);
        $iss_val  = $this->clean_numeric($total_issues[$category_id]['val'] ?? null);

        $categories_data[$category_id] = [
            'previous_day' => [
                'qty' => $prev_qty,
                'val' => $prev_val,
            ],
            'opening_stock' => [
                'qty' => $opn_qty,
                'val' => $opn_val,
            ],
            'total_issues' => [
                'qty' => $iss_qty,
                'val' => $iss_val,
            ],
        ];

        $rec_sec_prev_day_amt += (float)($prev_val ?? 0);
        $rec_sec_opn_stock_amt += (float)($opn_val ?? 0);
        $issue_section_previous_day_amount += (float)($iss_val ?? 0);
    }

    // Step 4: Prepare the data for insertion
    $formData = [
        'business_id' => $business_id,
        'date' => $request->datepicker,
        'time' => $request->time,
        'starting_number' => $request->starting_number,
        'ref_pre_form_number' => $request->starting_number,
        'rec_sec_prev_day_amt' => $rec_sec_prev_day_amt,
        'rec_sec_opn_stock_amt' => $rec_sec_opn_stock_amt,
        'issue_section_previous_day_amount' => $issue_section_previous_day_amount,
        'manager_name' => $request->manager_name,
        'categories' => json_encode($categories_data),
    ];

    // Step 5: Insert into database
    Mpcs21cFormSettings::create($formData);

    $output = [
        'success' => 1,
        'msg' => __('success'),
    ];
    
    return back()->with('status', $output);
    
}


    
    /**  GET DISTRICT BASED ON PROVINCE */
    public function getSubcategoryPumps($subCategoryId)
    {

     $business_id = request()->session()->get('business.id');

     if(auth()->user()->can('superadmin')) {    
            $pumps = Product::leftjoin('pumps', 'products.id', 'pumps.product_id')
            ->where('products.sub_category_id', $subCategoryId)
            ->whereNotNull('pumps.id')
            ->pluck('pumps.pump_name', 'pumps.id');
    } else  {
            $pumps = Product::leftjoin('pumps', 'products.id', 'pumps.product_id')
            ->where('products.business_id', $business_id)
            ->where('products.sub_category_id', $subCategoryId)
            ->whereNotNull('pumps.id')
            ->pluck('pumps.pump_name', 'pumps.id');
    }

    return response()->json($pumps);

   }
   
   /**
    * Fetch sub categories for a given parent category.
    */
   public function getSubCategories(Request $request)
   {
       $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
       $categoryId = $request->input('category_id');

       $query = Category::where('business_id', $business_id)
           ->where('parent_id', '!=', 0);

       if (!empty($categoryId)) {
           $query->where('parent_id', $categoryId);
       }

       $subCategories = $query->pluck('name', 'id');

       return response()->json(['sub_categories' => $subCategories]);
   }
    
    public function addNewPumpRow(Request $request) {

        $business_id = request()->session()->get('business.id');

        // Filter to show only Fuel category sub-categories
        $fuelCategory = Category::subCategoryOnlyFuel($business_id)
            ->select(['name', 'id'])
            ->orderBy('name')
            ->get();

        $html = '<tr>
        <td>
            <select class="form-control select2 category_select" style="width: 100% !important;" name="category[]" required onChange=loadPump(this)>
                <option selected value="">Please select</option>';

    // Loop through categories and append options
    foreach ($fuelCategory as $category) {
        $html .= '<option value="' . $category->id . '">' . $category->name . '</option>';
    }

    $html .= '</select>
        </td>
        <td>
            <select class="form-control select2 pump_select" style="width: 100% !important;" name="pump[]" required>
              
            </select>
        </td>
        <td>
            <input type="number" step="0.01" name="meter[]" class="form-control meter" required>
        </td>
        <td>
            <button type="button" class="btn btn-danger remove_row"><i class="fa fa-minus-circle"></i></button>
        </td>
    </tr>';

    return response()->json(['html' => $html]);
   

    }
   
    public function printF21cForm(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $data = array();
        parse_str($request->data, $data); // converting serielize string to array
       
         // Filter to show only Fuel category sub-categories
         $fuelCategory = Category::subCategoryOnlyFuel($business_id)
        ->select(['name', 'id'])
        ->orderBy('name')
        ->get()->pluck('name', 'id');
       
        $details = $data;
        //$data = $data['f21c'];
        return view('mpcs::forms.21CForm.print_f21c_form')->with(compact('details', 'fuelCategory'));
    }
 

    /**
     * F21 Form
     * 07-02-2025
     * Intrithm
     */

     public function get21Form(Request $request){
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
    
        $bname = Business::where('id', $business_id)->first();
        $products = Product::where('business_id', $business_id)->pluck('name', 'id');

        $selectedDate = $request->input('start_date') ?? Carbon::today()->format('Y-m-d');
        $form_number = $this->safeGetF21FormNumberForDate($business_id, $selectedDate);
        $date = optional($settings)->date ? $settings->date : "";
        $userAdded = $bname ? $bname->name : "";
    
       $dateRange = $request->input('form_16a_date_range');
        if ($dateRange) {
            $dates = explode(' - ', $dateRange);
            $start_date = $dates[0];
            $end_date = $dates[1];
        } else {
            // Set default date range if the request parameter is empty
            $start_date = Carbon::now()->subDays(7)->format('Y-m-d');
            $end_date = Carbon::now()->format('Y-m-d');
        }
    
    
        $form16a = FormF16Detail::latest()->first();
    
        if (!empty($settings)) {
            $F16a_from_no = !empty($form16a) ? $form16a->form_no + 1 : $settings->starting_number;
        } else {
            $F16a_from_no = '';
        }
    
        $suppliers = Contact::suppliersDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $categories = Category::where('business_id', $business_id)
            ->where(function ($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->pluck('name', 'id');
        $sub_categories = Category::where('business_id', $business_id)
            ->where('parent_id', '!=', 0)
            ->pluck('name', 'id');

        $transactionTypes = array(
            0 => 'POS Sale',
            1 => 'Settlement',
            2 => 'Purchase Order',
            3 => 'Sales Return',
            4 => 'Purchase return'
        );
     
      //$form_f16a = FormF16Detail::where('transaction_id', $lastRecord['id'])->first();
        return view('mpcs::forms.21Form.F21')->with(compact(
            'business_locations',
            'products',
            'categories',
            'sub_categories',
            'F16a_from_no',
            'settings',
            'form_number',
            'date',
            'userAdded',
            'transactionTypes'
        ));
     }


public function getPos() {

    $business_id = $this->resolveF21BusinessId();
    
    // STEP 4: Load Stock Taking data once per request
    $start_date = request()->start_date;
    $locationIdFilter = $this->normalizeLocationId(request()->get('location_id'));
    $f22ForBookNo = $this->getF22HeaderForDateAndLocation($business_id, $start_date, $locationIdFilter);
    if (!empty($start_date)) {
        $this->loadStockTakingDataIfNeeded($business_id, $start_date, $locationIdFilter);
    }
    $formNumberForRequest = $this->safeGetF21FormNumberForDate($business_id, $start_date, $locationIdFilter);

    $type = ['sell'];
    $status = ['final', 'order'];

    $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
                ->leftJoin('products', 'tsl.product_id', '=', 'products.id')
                ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
                ->leftJoin('users as ss', 'transactions.res_waiter_id', '=', 'ss.id')
                ->leftjoin('users as deleted','transactions.deleted_by','deleted.id')
                ->leftJoin('res_tables as tables', 'transactions.res_table_id', '=', 'tables.id')
                ->join(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',
                    'bl.id'
                )
                ->leftJoin(
                    'transactions AS SR',
                    'transactions.id',
                    '=',
                    'SR.return_parent_id'
                )
                ->leftJoin(
                    'types_of_services AS tos',
                    'transactions.types_of_service_id',
                    '=',
                    'tos.id'
                )
                ->where('transactions.business_id', $business_id)
                ->whereIn('transactions.type', $type)
                ->whereIn('transactions.status', $status)
                ->where(function ($query) {
                    $query->whereNull('transactions.is_settlement')
                        ->orWhere('transactions.is_settlement', 0);
                })
                ->select(
                    'transactions.id',
                    'transactions.final_total',
                    'tsl.id as sell_line_id',
                    'deleted.username as deletedBy',
                    'transactions.transaction_date',
                    'transactions.is_direct_sale',
                    'transactions.invoice_no',
                    'contacts.name',
                    'contacts.mobile',
                    'transactions.price_later',
                    'transactions.payment_status',
                    // 'transactions.final_total',
                    'transactions.tax_amount',
                    'transactions.discount_amount',
                    'transactions.discount_type',
                    'transactions.total_before_tax',
                    'transactions.rp_redeemed',
                    'transactions.rp_redeemed_amount',
                    'transactions.rp_earned',
                    'transactions.types_of_service_id',
                    'transactions.shipping_status',
                    'transactions.pay_term_number',
                    'transactions.pay_term_type',
                    'transactions.additional_notes',
                    'transactions.staff_note',
                    'transactions.shipping_details',
                    'transactions.commission_agent',
                    'transactions.ref_no as ref_no',
                    'products.id as product_id', // STEP 4: Add product_id for F22 matching
                    'products.sku',
                    'products.name as productname',
                    'tsl.quantity as recd_qty',
                    'transactions.sub_type as the_transaction_sub_type',
                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                    DB::raw('(SELECT SUM(transaction_payments.amount) FROM transaction_payments WHERE
                        transaction_payments.transaction_id=transactions.id) as total_paid'),
                    'bl.name as business_location',
                    DB::raw('(SELECT SUM(TP2.amount) FROM transaction_payments AS TP2 WHERE
                        TP2.transaction_id=SR.id ) as return_paid'),
                    DB::raw('COALESCE(SR.final_total, 0) as amount_return'),
                    'SR.id as return_transaction_id',
                    'tos.name as types_of_service_name',
                    'transactions.service_custom_field_1',
                    DB::raw('CASE WHEN SR.id IS NULL THEN 0 ELSE 1 END as return_exists'),
                    DB::raw('1 as total_items'),
                    DB::raw("CONCAT(COALESCE(ss.surname, ''),' ',COALESCE(ss.first_name, ''),' ',COALESCE(ss.last_name,'')) as waiter"),
                    'tables.name as table_name',
                    'transactions.location_id as location_id',
                    DB::raw("'POS Sale' as transaction_type")
                )->with('sell_lines')
                ->orderBy('transactions.id','DESC')
                ->withTrashed();

                if (!empty(request()->start_date) && !empty(request()->end_date)) {
                    $start = request()->start_date;
                    $end =  request()->end_date;
                    $sells->whereDate('transactions.transaction_date', '>=', $start)
                        ->whereDate('transactions.transaction_date', '<=', $end);
                }


                $permitted_locations = auth()->user()->permitted_locations();
                if ($permitted_locations != 'all') {
                    $sells->whereIn('transactions.location_id', $permitted_locations);
                }

                if ($locationIdFilter !== null) {
                    $sells->where('transactions.location_id', $locationIdFilter);
                }
                if (request()->has('category_id')) {
                    $category_id = request()->get('category_id');
                    if (!empty($category_id)) {
                        $sells->where('products.category_id', $category_id);
                    }
                }
                if (request()->has('sub_category_id')) {
                    $sub_category_id = request()->get('sub_category_id');
                    if (!empty($sub_category_id)) {
                        $sells->where('products.sub_category_id', $sub_category_id);
                    }
                }
                if (request()->has('product_id')) {
                    $product_id = request()->get('product_id');
                    if (!empty($product_id)) {
                        $sells->where('tsl.product_id', $product_id);
                    }
                }

                $final_sells = $sells->whereNotNull('tsl.id')->get();

        $datatable = Datatables::of($final_sells)
        ->removeColumn('id')
        ->editColumn(
            'bill_no',
            function ($row) {
                return $row->invoice_no;
            }
        )
        ->addColumn(
            'book_no',
            function ($row) use ($business_id, $locationIdFilter) {
                $contextLocationId = $row->location_id ?? $locationIdFilter;
                $this->syncStockTakingContext($business_id, $row->transaction_date, $contextLocationId);
                // STEP 5: Show F22 form number only on Stock Taking day
                if ($this->isStockTakingDate()) {
                    $f22FormNo = $this->getF22FormNo();
                    if ($f22FormNo) {
                        return $f22FormNo;
                    }
                }
                // Allow F16 to show for POS when an F16 record matches by invoice_no (no purchase needed)
                $f16ByInvoice = $this->getF16AFormNo(null, $row->invoice_no ?? null, $row->ref_no ?? null);
                if ($f16ByInvoice) {
                    return $f16ByInvoice;
                }
                return '-';
            }
        )
                ->editcolumn('received_qty', function($row){
                    return '0.00';
                })
                ->editcolumn('transaction_type', function($row){
                        return $row->transaction_type ?? $row->transaction_type_label ?? '';
                    }
                )
                ->addColumn('starting_qty', function ($row) {
                    return $this->computeF21StartingQty($row, true);
                })
                ->addColumn('transaction_type_label', function () {
                    return 'POS Sale';
                })
                ->editColumn(
                    'product_code',
                    function ($row) {
                        return $row->sku;
                    }
                )
                ->editColumn(
                    'product_name',
                    function ($row) {
                        return $row->productname;
                    }
                )
                ->editColumn(
                    'sold_qty',
                    function ($row) use ($business_id, $locationIdFilter) {
                        $contextLocationId = $row->location_id ?? $locationIdFilter;
                        $this->syncStockTakingContext($business_id, $row->transaction_date, $contextLocationId);
                        // STEP 4: Override Qty on Stock Taking date
                        if ($this->isStockTakingDate() && $row->product_id) {
                            $f22Qty = $this->getF22StockQuantity($row->product_id);
                            if ($f22Qty !== null) {
                                return number_format($f22Qty, 2);
                            }
                            // Product exists in F21 but not in F22: show 0
                            return '0.00';
                        }
                        return number_format($row->recd_qty, 2);
                })
                ->editColumn(
                    'balance_qty',
                    function ($row) use ($business_id, $locationIdFilter) {
                        return $this->computeF21BalanceQty((object) [
                            'product_id' => $row->product_id ?? null,
                            'location_id' => $row->location_id ?? $locationIdFilter,
                            'transaction_date' => $row->transaction_date ?? null,
                            'transaction_type' => $row->transaction_type ?? ($row->transaction_type_label ?? null),
                            'received_qty' => 0,
                            'sold_qty' => $row->recd_qty ?? 0,
                            'balance_qty' => $row->balance_qty ?? 0,
                        ]);
                  })
                ->addColumn('form_number', function () use ($formNumberForRequest) {
                    return $formNumberForRequest;
                });

               return $datatable->make(true);

}     


/**
 * Filter purchase order
 * 10-02-2025
 * Sandy
 */

public function getPurchaseOrder() {

    $business_id = $this->resolveF21BusinessId();

    // STEP 4: Load Stock Taking data once per request
    $start_date = request()->start_date;
    $locationIdFilter = $this->normalizeLocationId(request()->get('location_id'));
    $f22ForBookNo = $this->getF22HeaderForDateAndLocation($business_id, $start_date, $locationIdFilter);
    if (!empty($start_date)) {
        $this->loadStockTakingDataIfNeeded($business_id, $start_date, $locationIdFilter);
    }
    $formNumberForRequest = $this->safeGetF21FormNumberForDate($business_id, $start_date, $locationIdFilter);

    $purchases = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')

                ->join(

                    'business_locations AS BS',

                    'transactions.location_id',

                    '=',

                    'BS.id'

                )

                ->leftJoin('transaction_payments AS TP', function ($join) {

                    $join->on('transactions.id', '=', 'TP.transaction_id')

                        ->whereNull('TP.deleted_at');
                })

                ->leftJoin(

                    'transactions AS PR',

                    'transactions.id',

                    '=',

                    'PR.return_parent_id'

                )

                ->leftjoin('users as deleted', 'transactions.deleted_by', 'deleted.id')

                ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')

                ->leftJoin('purchase_lines as pl', 'pl.transaction_id', '=', 'transactions.id')

                ->join('products', 'pl.product_id', '=', 'products.id')
                // Join F16A details BEFORE select for Book No (F 16 A / XXXX) - match by transaction or invoice/ref no
                // Join F16A for Book No: show F 16 A / XXXX for purchases (match by transaction_id)
                ->leftJoin('form_f16_details as f16a', 'f16a.transaction_id', '=', 'transactions.id')

                ->where('transactions.business_id', $business_id)

                ->where('transactions.type', 'purchase')

                ->select(

                    'deleted.username as deletedBy',

                    'transactions.id',

                    'transactions.document',

                    'transactions.transaction_date',

                    'transactions.invoice_date',

                    'transactions.ref_no',

                    'transactions.invoice_no',

                    'transactions.purchase_entry_no',

                    'contacts.name',

                    'transactions.status',

                    'transactions.payment_status',

                    'transactions.final_total',

                    'BS.name as location_name',
                    'transactions.location_id as location_id',

                    'transactions.pay_term_number',

                    'transactions.pay_term_type',

                    'transactions.overpayment_setoff',

                    'PR.id as return_transaction_id',

                    'TP.method',

                    'TP.id as tp_id',

                    'TP.account_id',

                    'TP.cheque_number',

                    'pl.lot_number',

                    'pl.quantity as recd_qty',
                    
                    'products.id as product_id', // STEP 4: Add product_id for F22 matching

                    'products.sku',

                    'products.name as productname',

                    DB::raw('MAX(f16a.form_no) as f16a_form_no'),
                    DB::raw('(SELECT SUM(transaction_payments.amount) FROM transaction_payments WHERE

                    transaction_payments.transaction_id = transactions.id AND transaction_payments.deleted_at IS NULL) as amount_paid'),

                    DB::raw('(SELECT SUM(TP2.amount) FROM transaction_payments AS TP2 WHERE

                        TP2.transaction_id=PR.id ) as return_paid'),

                    DB::raw('COUNT(PR.id) as return_exists'),

                    DB::raw('COALESCE(PR.final_total, 0) as amount_return'),

                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                    DB::raw('(SELECT SUM(pl.purchase_price * pl.quantity)) as total_before_tax'),
                    DB::raw('(SELECT SUM(pl.item_tax * pl.quantity)) as item_tax'),
                    DB::raw("'Purchase order' as transaction_type"),
                )

                ->groupBy('transactions.id');

                $permitted_locations = auth()->user()->permitted_locations();

                if (!empty(request()->start_date) && !empty(request()->end_date)) {
                    $start = request()->start_date;
                    $end =  request()->end_date;
                    $purchases->whereDate('transactions.transaction_date', '>=', $start)
                        ->whereDate('transactions.transaction_date', '<=', $end);
                }

                if ($permitted_locations != 'all') {
                    $purchases->whereIn('transactions.location_id', $permitted_locations);
                }

                if ($locationIdFilter !== null) {
                    $purchases->where('transactions.location_id', $locationIdFilter);
                }
                if (request()->has('category_id')) {
                    $category_id = request()->get('category_id');
                    if (!empty($category_id)) {
                        $purchases->where('products.category_id', $category_id);
                    }
                }
                if (request()->has('sub_category_id')) {
                    $sub_category_id = request()->get('sub_category_id');
                    if (!empty($sub_category_id)) {
                        $purchases->where('products.sub_category_id', $sub_category_id);
                    }
                }
                if (request()->has('product_id')) {
                    $product_id = request()->get('product_id');
                    if (!empty($product_id)) {
                        $purchases->where('pl.product_id', $product_id);
                    }
                }

                $datatable = Datatables::of($purchases)
                ->removeColumn('id')
                ->editColumn(
                    'bill_no',
                    function ($row) {
                        return $row->invoice_no;
                    }
                )
                ->addColumn( 'book_no',
                function ($row) use ($business_id) {
                    // First check if this purchase has an F16A form number
                    if (!empty($row->f16a_form_no)) {
                        return 'F 16 A / ' . $row->f16a_form_no;
                    }
                    
                    // Then check if this date is a stock taking day (F22 form)
                    $t_date = date('Y-m-d', strtotime($row->transaction_date));
                    $f22Header = \DB::table('form_f22_headers')
                        ->where('business_id', $business_id)
                        ->whereDate('form_date', $t_date)
                        ->first();
                    
                    if ($f22Header) {
                        return 'F 22 / ' . $f22Header->form_no;
                    }
                    
                    return '-';
                }
)
              

                ->editcolumn('received_qty', function($row) use ($business_id, $locationIdFilter){
                    $contextLocationId = $row->location_id ?? $locationIdFilter;
                    $this->syncStockTakingContext($business_id, $row->transaction_date, $contextLocationId);
                    // STEP 4: Override Qty on Stock Taking date
                    if ($this->isStockTakingDate() && $row->product_id) {
                        $f22Qty = $this->getF22StockQuantity($row->product_id);
                        if ($f22Qty !== null) {
                            return number_format($f22Qty, 2);
                        }
                        return '0.00';
                    }
                    return number_format($row->recd_qty, 2);
                })
                ->editcolumn('transaction_type', function($row){
                        return $row->transaction_type ?? $row->transaction_type_label ?? '';
                    }
                )
                ->addColumn('starting_qty', function ($row) {
                    return $this->computeF21StartingQty($row, true);
                })
                ->addColumn('transaction_type_label', function () {
                    return 'Purchase order';
                })
                ->editColumn(
                    'product_code',
                    function ($row) {
                        return $row->sku;
                    }
                )
                ->editColumn(
                    'product_name',
                    function ($row) {
                        return $row->productname;
                    }
                )
                ->editColumn(
                    'sold_qty',
                    function ($row) {
                        return '0.00';
                })
                ->editColumn(
                    'balance_qty',
                    function ($row) use ($business_id, $locationIdFilter) {
                        return $this->computeF21BalanceQty((object) [
                            'product_id' => $row->product_id ?? null,
                            'location_id' => $row->location_id ?? $locationIdFilter,
                            'transaction_date' => $row->transaction_date ?? null,
                            'transaction_type' => $row->transaction_type ?? ($row->transaction_type_label ?? null),
                            'received_qty' => $row->recd_qty ?? 0,
                            'sold_qty' => 0,
                            'balance_qty' => $row->balance_qty ?? 0,
                        ]);
                  })
                ->addColumn('form_number', function () use ($formNumberForRequest) {
                    return $formNumberForRequest;
                });

               return $datatable->make(true);


}

/**
 * SALES RETURN FILTER
 */

public function getSellReturn() {

    $business_id = $this->resolveF21BusinessId();

    // STEP 4: Load Stock Taking data once per request
    $start_date = request()->start_date;
    $locationIdFilter = $this->normalizeLocationId(request()->get('location_id'));
    $f22ForBookNo = $this->getF22HeaderForDateAndLocation($business_id, $start_date, $locationIdFilter);
    if (!empty($start_date)) {
        $this->loadStockTakingDataIfNeeded($business_id, $start_date, $locationIdFilter);
    }
    $formNumberForRequest = $this->safeGetF21FormNumberForDate($business_id, $start_date, $locationIdFilter);

    $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')

        ->join(
            'business_locations AS bl',
            'transactions.location_id',
            '=',
            'bl.id'
        )
        ->join(
            'transactions as T1',
            'transactions.return_parent_id',
            '=',
            'T1.id'
        )
        ->leftJoin('transaction_sell_lines as tsl', 'transactions.return_parent_id', '=', 'tsl.transaction_id')
        ->join('products', 'tsl.product_id', '=', 'products.id')    
        ->leftJoin(
            'transaction_payments AS TP',
            'transactions.id',
            '=',
            'TP.transaction_id'
        )
        ->where('transactions.business_id', $business_id)
        ->where('transactions.type', 'sell_return')
        ->where('transactions.status', 'final')
        ->select(
            'transactions.id',
            'transactions.transaction_date',
            'transactions.invoice_no',
            'contacts.name',
            'transactions.final_total',
            'transactions.payment_status',
            'bl.name as business_location',
            'transactions.location_id as location_id',
            'T1.invoice_no as parent_sale',
            'T1.id as parent_sale_id',
            'products.id as product_id', // STEP 4: Add product_id for F22 matching
            'products.sku',
            'products.name as productname',
            'tsl.quantity_returned as recd_qty',
            DB::raw('SUM(TP.amount) as amount_paid'),
            DB::raw("'Sales Return' as transaction_type")
        );


                $permitted_locations = auth()->user()->permitted_locations();

                if (!empty(request()->start_date) && !empty(request()->end_date)) {
                    $start = request()->start_date;
                    $end =  request()->end_date;
                    $sells->whereDate('transactions.transaction_date', '>=', $start)
                        ->whereDate('transactions.transaction_date', '<=', $end);
                }

                if ($permitted_locations != 'all') {
                    $sells->whereIn('transactions.location_id', $permitted_locations);
                }

                if ($locationIdFilter !== null) {
                    $sells->where('transactions.location_id', $locationIdFilter);
                }
                if (request()->has('category_id')) {
                    $category_id = request()->get('category_id');
                    if (!empty($category_id)) {
                        $sells->where('products.category_id', $category_id);
                    }
                }
                if (request()->has('sub_category_id')) {
                    $sub_category_id = request()->get('sub_category_id');
                    if (!empty($sub_category_id)) {
                        $sells->where('products.sub_category_id', $sub_category_id);
                    }
                }
                if (request()->has('product_id')) {
                    $product_id = request()->get('product_id');
                    if (!empty($product_id)) {
                        $sells->where('tsl.product_id', $product_id);
                    }
                }

                $sells->groupBy('transactions.id');

                $datatable = Datatables::of($sells)
                ->removeColumn('id')
                ->editColumn(
                    'bill_no',
                    function ($row) {
                        return $row->invoice_no;
                    }
                )
                ->addColumn(
                    'book_no',
                    function ($row) use ($business_id, $locationIdFilter) {
                        $contextLocationId = $row->location_id ?? $locationIdFilter;
                        $this->syncStockTakingContext($business_id, $row->transaction_date, $contextLocationId);
                        // STEP 5: Show F22 form number only on Stock Taking day
                        if ($this->isStockTakingDate()) {
                            $f22FormNo = $this->getF22FormNo();
                            if ($f22FormNo) {
                                return $f22FormNo;
                            }
                        }
                        // Allow F16 to show when an F16 record matches by invoice_no
                        $f16ByInvoice = $this->getF16AFormNo(null, $row->invoice_no ?? null, $row->ref_no ?? null);
                        if ($f16ByInvoice) {
                            return $f16ByInvoice;
                        }
                        return '-';
                    }
                )
                ->editcolumn('received_qty', function($row) use ($business_id, $locationIdFilter){
                    $contextLocationId = $row->location_id ?? $locationIdFilter;
                    $this->syncStockTakingContext($business_id, $row->transaction_date, $contextLocationId);
                    // STEP 4: Override Qty on Stock Taking date
                    if ($this->isStockTakingDate() && $row->product_id) {
                        $f22Qty = $this->getF22StockQuantity($row->product_id);
                        if ($f22Qty !== null) {
                            return number_format($f22Qty, 2);
                        }
                        return '0.00';
                    }
                    return number_format($row->recd_qty, 2);
                })
                ->editcolumn('transaction_type', function($row){
                        return $row->transaction_type ?? $row->transaction_type_label ?? '';
                    }
                )
                ->addColumn('starting_qty', function ($row) {
                    return $this->computeF21StartingQty($row, true);
                })
                ->addColumn('transaction_type_label', function () {
                    return 'Sales Return';
                })
                ->editColumn(
                    'product_code',
                    function ($row) {
                        return $row->sku;
                    }
                )
                ->editColumn(
                    'product_name',
                    function ($row) {
                        return $row->productname;
                    }
                )
                ->editColumn(
                    'sold_qty',
                    function ($row) {
                        return '0.00';
                })
                ->editColumn(
                    'balance_qty',
                    function ($row) use ($business_id, $locationIdFilter) {
                        return $this->computeF21BalanceQty((object) [
                            'product_id' => $row->product_id ?? null,
                            'location_id' => $row->location_id ?? $locationIdFilter,
                            'transaction_date' => $row->transaction_date ?? null,
                            'transaction_type' => $row->transaction_type ?? ($row->transaction_type_label ?? null),
                            'received_qty' => $row->recd_qty ?? 0,
                            'sold_qty' => 0,
                            'balance_qty' => $row->balance_qty ?? 0,
                        ]);
                  })
                ->addColumn('form_number', function () use ($formNumberForRequest) {
                    return $formNumberForRequest;
                });

               return $datatable->make(true);         
    }


/**
 * PURCHASE RETURN
 */
public function getPurchaseReturn(){
    $business_id = $this->resolveF21BusinessId();

    // STEP 4: Load Stock Taking data once per request
    $start_date = request()->start_date;
    $locationIdFilter = $this->normalizeLocationId(request()->get('location_id'));
    $f22ForBookNo = $this->getF22HeaderForDateAndLocation($business_id, $start_date, $locationIdFilter);
    if (!empty($start_date)) {
        $this->loadStockTakingDataIfNeeded($business_id, $start_date, $locationIdFilter);
    }
    $formNumberForRequest = $this->safeGetF21FormNumberForDate($business_id, $start_date, $locationIdFilter);

    $purchases_returns = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->join(
                    'business_locations AS BS',
                    'transactions.location_id',
                    '=',
                    'BS.id'
                )
                ->leftJoin(
                    'transactions AS T',
                    'transactions.return_parent_id',
                    '=',
                    'T.id'
                )
                ->leftJoin('purchase_lines as pl', 'transactions.return_parent_id', '=', 'pl.transaction_id')
                ->join('products', 'pl.product_id', '=', 'products.id')  
                ->leftJoin(
                    'transaction_payments AS TP',
                    'transactions.id',
                    '=',
                    'TP.transaction_id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'purchase_return')
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.invoice_no as invoiceno',
                    'transactions.ref_no as invoice_no',
                    'contacts.name',
                    'transactions.status',
                    'transactions.payment_status',
                    'transactions.final_total',
                    'transactions.return_parent_id',
                    'BS.name as location_name',
                    'transactions.location_id as location_id',
                    'T.ref_no as parent_purchase',
                    'products.id as product_id', // STEP 4: Add product_id for F22 matching
                    'products.sku',
                    'products.name as productname',
                    'pl.quantity_returned as recd_qty',
                    DB::raw('SUM(TP.amount) as amount_paid'),
                    DB::raw("'Purchase return' as transaction_type")
                );

                $permitted_locations = auth()->user()->permitted_locations();

                if (!empty(request()->start_date) && !empty(request()->end_date)) {
                    $start = request()->start_date;
                    $end =  request()->end_date;
                    $purchases_returns->whereDate('transactions.transaction_date', '>=', $start)
                        ->whereDate('transactions.transaction_date', '<=', $end);
                }

                if ($permitted_locations != 'all') {
                    $purchases_returns->whereIn('transactions.location_id', $permitted_locations);
                }

                if ($locationIdFilter !== null) {
                    $purchases_returns->where('transactions.location_id', $locationIdFilter);
                }
                if (request()->has('category_id')) {
                    $category_id = request()->get('category_id');
                    if (!empty($category_id)) {
                        $purchases_returns->where('products.category_id', $category_id);
                    }
                }
                if (request()->has('sub_category_id')) {
                    $sub_category_id = request()->get('sub_category_id');
                    if (!empty($sub_category_id)) {
                        $purchases_returns->where('products.sub_category_id', $sub_category_id);
                    }
                }
                if (request()->has('product_id')) {
                    $product_id = request()->get('product_id');
                    if (!empty($product_id)) {
                        $purchases_returns->where('pl.product_id', $product_id);
                    }
                }

                $purchases_returns->groupBy('transactions.id');

                $datatable = Datatables::of($purchases_returns)
                ->removeColumn('id')
                ->editColumn(
                    'bill_no',
                    function ($row) {
                        return $row->ref_no;
                    }
                )
                ->addColumn(
                    'book_no',
                    function ($row) use ($business_id, $locationIdFilter) {
                        $contextLocationId = $row->location_id ?? $locationIdFilter;
                        $this->syncStockTakingContext($business_id, $row->transaction_date, $contextLocationId);
                        // STEP 5: Show F22 form number only on Stock Taking day
                        if ($this->isStockTakingDate()) {
                            $f22FormNo = $this->getF22FormNo();
                            if ($f22FormNo) {
                                return $f22FormNo;
                            }
                        }
                        // Allow F16 to show when an F16 record matches by invoice_no
                        $f16ByInvoice = $this->getF16AFormNo($row->id ?? null, $row->invoice_no ?? null, $row->ref_no ?? null);
                        if ($f16ByInvoice) {
                            return $f16ByInvoice;
                        }
                        return '-';
                    }
                )
                ->editcolumn('received_qty', function($row){
                    return '0.00';
                })
                ->editcolumn('transaction_type', function($row){
                        return $row->transaction_type ?? $row->transaction_type_label ?? '';
                    }
                )
                ->addColumn('starting_qty', function ($row) {
                    return $this->computeF21StartingQty($row, true);
                })
                ->addColumn('transaction_type_label', function () {
                    return 'Purchase Return';
                })
                ->editColumn(
                    'product_code',
                    function ($row) {
                        return $row->sku;
                    }
                )
                ->editColumn(
                    'product_name',
                    function ($row) {
                        return $row->productname;
                    }
                )
                ->editColumn(
                    'sold_qty',
                    function ($row) use ($business_id, $locationIdFilter) {
                        $contextLocationId = $row->location_id ?? $locationIdFilter;
                        $this->syncStockTakingContext($business_id, $row->transaction_date, $contextLocationId);
                        // STEP 4: Override Qty on Stock Taking date
                        if ($this->isStockTakingDate() && $row->product_id) {
                            $f22Qty = $this->getF22StockQuantity($row->product_id);
                            if ($f22Qty !== null) {
                                return number_format($f22Qty, 2);
                            }
                            return '0.00';
                        }
                        return number_format($row->recd_qty, 2);
                })
                ->editColumn(
                    'balance_qty',
                    function ($row) use ($business_id, $locationIdFilter) {
                        return $this->computeF21BalanceQty((object) [
                            'product_id' => $row->product_id ?? null,
                            'location_id' => $row->location_id ?? $locationIdFilter,
                            'transaction_date' => $row->transaction_date ?? null,
                            'transaction_type' => $row->transaction_type ?? ($row->transaction_type_label ?? null),
                            'received_qty' => 0,
                            'sold_qty' => $row->recd_qty ?? 0,
                            'balance_qty' => $row->balance_qty ?? 0,
                        ]);
                  })
                ->addColumn('form_number', function () use ($formNumberForRequest) {
                    return $formNumberForRequest;
                });

               return $datatable->make(true);       

}


/**
 * SETTLEMENT FILTER
 * 11-02-2025
 */

public function getSettlement() {

    $business_id = $this->resolveF21BusinessId();

    // STEP 4: Load Stock Taking data once per request
    $start_date = request()->start_date;
    $locationIdFilter = $this->normalizeLocationId(request()->get('location_id'));
    $f22ForBookNo = $this->getF22HeaderForDateAndLocation($business_id, $start_date, $locationIdFilter);
    if (!empty($start_date)) {
        $this->loadStockTakingDataIfNeeded($business_id, $start_date, $locationIdFilter);
    }
    $formNumberForRequest = $this->safeGetF21FormNumberForDate($business_id, $start_date, $locationIdFilter);
    
    // Store reference to $this for use in closure
    $controller = $this;

    $settlement = Settlement::leftJoin('business_locations', 'settlements.location_id', '=', 'business_locations.id')

                ->leftJoin('pump_operators', 'settlements.pump_operator_id', '=', 'pump_operators.id')

                ->leftJoin('pump_operator_assignments', function ($join) {
                    $join->on('settlements.id', '=', 'pump_operator_assignments.settlement_id');
                })

                ->where('settlements.business_id', $business_id)

                ->select([
                    'pump_operators.name as pump_operator_name',
                    'business_locations.name as location_name',
                    'settlements.*',
                    'pump_operator_assignments.shift_number',
                ]);



                $permitted_locations = auth()->user()->permitted_locations();

                if (!empty(request()->start_date) && !empty(request()->end_date)) {
                    $start = request()->start_date;
                    $end =  request()->end_date;
                    $settlement->whereDate('settlements.transaction_date', '>=', $start)
                        ->whereDate('settlements.transaction_date', '<=', $end);
                }

                if ($permitted_locations != 'all') {
                    $settlement->whereIn('settlements.location_id', $permitted_locations);
                }

                if ($locationIdFilter !== null) {
                    $settlement->where('settlements.location_id', $locationIdFilter);
                }

                $settlement->with(['meter_sales', 'other_sales']);

                $settlementRows = $settlement->get()->flatMap(function ($row) use ($business_id, $controller, $formNumberForRequest, $locationIdFilter, $f22ForBookNo) {
                    $rows = [];
                    $contextLocationId = $row->location_id ?? $locationIdFilter;
                    $controller->syncStockTakingContext($business_id, $row->transaction_date, $contextLocationId);
                
                    if (!empty($row->meter_sales)) {
                        foreach ($row->meter_sales as $meter_sale) {

                            $product_id = request()->get('product_id'); 

                            if (!empty($product_id)) {
                                if ($meter_sale->product_id != $product_id) {
                                    continue; 
                                }
                            }
                            $category_id = request()->get('category_id');
                            $sub_category_id = request()->get('sub_category_id');
                            $productModel = \App\Product::find($meter_sale->product_id);
                            if (!empty($category_id) && optional($productModel)->category_id != $category_id) {
                                continue;
                            }
                            if (!empty($sub_category_id) && optional($productModel)->sub_category_id != $sub_category_id) {
                                continue;
                            }

                            $product = \App\Product::find($meter_sale->product_id);

                            if(!empty($product)) {
                                // STEP 4: Override Qty on Stock Taking date
                                $qty = $meter_sale->qty;
                                if ($controller->isStockTakingDate() && $product->id) {
                                    $f22Qty = $controller->getF22StockQuantity($product->id);
                                    if ($f22Qty !== null) {
                                        $qty = $f22Qty;
                                    } else {
                                        $qty = 0; // Product exists in F21 but not in F22
                                    }
                                }

                                $bookNo = $controller->isStockTakingDate() ? ($controller->getF22FormNo() ?? '-') : '-';
                                if ($bookNo !== '-') {
                                    Log::debug('F21 Book No resolved via F22 (Settlement meter)', [
                                        'settlement_no' => $row->settlement_no,
                                        'transaction_date' => $row->transaction_date,
                                        'product_id' => $product->id,
                                        'book_no' => $bookNo,
                                    ]);
                                } else {
                                    $controller->logBookNoFallback('Settlement', [
                                        'settlement_no' => $row->settlement_no,
                                        'transaction_date' => $row->transaction_date,
                                        'location_id' => $contextLocationId,
                                        'stock_taking' => $controller->isStockTakingDate(),
                                    ]);
                                }
                                
                                $rows[] = [
                                    'transaction_date' => $row->transaction_date,
                                    'transaction_type' => 'Settlement',
                                    'transaction_type_label' => 'Settlement',
                                    'invoice_no' => $row->settlement_no,
                                    'book_no' => $bookNo, // STEP 5: Show F22 form on Stock Taking day
                                    'location_id' => $contextLocationId,
                                    'product_id' => $product->id, // STEP 4: Add product_id
                                    'product_code' => $product->sku,
                                    'product_name' => $product->name,
                                    'received_qty' => '0.00',
                                    'sold_qty' => number_format($qty, 2),
                                    'balance_qty' => number_format($qty, 2),
                                    'form_number' => $formNumberForRequest,
                                ];
                            }
                        }
                    }
                
                    if (!empty($row->other_sales)) {
                        foreach ($row->other_sales as $other_sale) {

                            $product_id = request()->get('product_id'); 

                            if (!empty($product_id)) {
                                if ($other_sale->product_id != $product_id) {
                                    continue; 
                                }
                            }
                            $category_id = request()->get('category_id');
                            $sub_category_id = request()->get('sub_category_id');
                            $productModel = \App\Product::find($other_sale->product_id);
                            if (!empty($category_id) && optional($productModel)->category_id != $category_id) {
                                continue;
                            }
                            if (!empty($sub_category_id) && optional($productModel)->sub_category_id != $sub_category_id) {
                                continue;
                            }

                            $product = \App\Product::find($other_sale->product_id);
                            
                            if(!empty($product)) {
                                // STEP 4: Override Qty on Stock Taking date
                                $qty = $other_sale->qty;
                                if ($controller->isStockTakingDate() && $product->id) {
                                    $f22Qty = $controller->getF22StockQuantity($product->id);
                                    if ($f22Qty !== null) {
                                        $qty = $f22Qty;
                                    } else {
                                        $qty = 0; // Product exists in F21 but not in F22
                                    }
                                }

                                $bookNo = $controller->isStockTakingDate() ? ($controller->getF22FormNo() ?? '-') : '-';
                                if ($bookNo !== '-') {
                                    Log::debug('F21 Book No resolved via F22 (Settlement other)', [
                                        'settlement_no' => $row->settlement_no,
                                        'transaction_date' => $row->transaction_date,
                                        'product_id' => $product->id,
                                        'book_no' => $bookNo,
                                    ]);
                                } else {
                                    $controller->logBookNoFallback('Settlement', [
                                        'settlement_no' => $row->settlement_no,
                                        'transaction_date' => $row->transaction_date,
                                        'location_id' => $contextLocationId,
                                        'stock_taking' => $controller->isStockTakingDate(),
                                    ]);
                                }
                                
                                $rows[] = [
                                    'transaction_date' => $row->transaction_date,
                                    'transaction_type' => 'Settlement',
                                    'transaction_type_label' => 'Settlement',
                                    'invoice_no' => $row->settlement_no,
                                    'book_no' => $bookNo, // STEP 5: Show F22 form on Stock Taking day
                                    'location_id' => $contextLocationId,
                                    'product_id' => $product->id, // STEP 4: Add product_id
                                    'product_code' => $product->sku,
                                    'product_name' => $product->name,
                                    'received_qty' => '0.00',
                                    'sold_qty' => number_format($qty, 2),
                                    'balance_qty' => number_format($qty, 2),
                                    'form_number' => $formNumberForRequest,
                                ];
                            }
                        }
                    }
                
                    return $rows; 
                });

                $settlementRows = $this->applyF21RunningBalances($settlementRows);

                $datatable = Datatables::of($settlementRows)
                ->editColumn('balance_qty', function ($row) {
                    return $row->balance_qty ?? $this->computeF21BalanceQty($row);
                })
                ->addColumn('starting_qty', function ($row) {
                    $qty = $row->starting_qty ?? $this->computeF21StartingQty($row, true);
                    return is_string($qty) ? str_replace('-', '', $qty) : $qty;
                });  

                return $datatable->make(true);

    }


    /**
     * PRINT FORM21
     */

    public function printF21Form(Request $request) {
        $business_id = request()->session()->get('user.business_id');

        $data = array();
        parse_str($request->data, $data); // converting serielize string to array
       
        $details = $data;
        //$data = $data['f21c'];
        return view('mpcs::forms.21Form.print_f21_form')->with(compact('details'));
    }
    public function showLatest21cFormSettings()
    {
        // Filter to show only Fuel category sub-categories
        $business_id = request()->session()->get('business.id') ?? request()->session()->get('user.business_id');
        $fuelCategory = Category::subCategoryOnlyFuel($business_id)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->pluck('name', 'id');
    
        // Get the latest form setting record
        $latestForm = Mpcs21cFormSettings::latest()->first();
    
        $categoriesData = [];
    
        if ($latestForm && $latestForm->categories) {
            $categoriesData = json_decode($latestForm->categories, true);
        }
     
        return view('mpcs::forms.21CForm.list_f21c', compact('fuelCategory', 'categoriesData'));
    }
    
    /**
     * IS2349: resolve the tenant business id consistently for every F21 data endpoint.
     * Some layouts populate business.id while older AJAX code looked only at user.business_id.
     */
    protected function resolveF21BusinessId()
    {
        return request()->session()->get('business.id')
            ?? request()->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id;
    }

    /**
     * F21 details must never fail just because the auxiliary form-number sequence
     * cannot be calculated for a tenant. Keep the report data available and log
     * the sequence problem for diagnosis.
     */
    protected function safeGetF21FormNumberForDate($businessId, $date, $locationId = null)
    {
        try {
            return $this->getF21FormNumberForDate($businessId, $date, $locationId);
        } catch (\Throwable $e) {
            Log::warning('F21 form number lookup failed; continuing with report data', [
                'business_id' => $businessId,
                'date' => $date,
                'location_id' => $locationId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Return the F21 form number for a given date using configured opening date/starting number.
     */
    public function getF21FormNumber(Request $request)
    {
        $businessId = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
        $targetDate = $request->input('date') ?? $request->input('start_date') ?? Carbon::today()->format('Y-m-d');
        $locationId = $this->normalizeLocationId($request->input('location_id'));

        $formNumber = $this->safeGetF21FormNumberForDate($businessId, $targetDate, $locationId);

        return response()->json(['form_number' => $formNumber]);
    }
    

    /**
     * Fetch all transaction types when "All" is selected on the F21 filter.
     * Reuses existing endpoints and forces them to return full result sets for aggregation.
     */
    public function getAllTransactions()
    {
        $business_id = $this->resolveF21BusinessId();
        $date = request()->get('start_date') ?? \Carbon\Carbon::today()->toDateString();
        $location_id = request()->get('location_id');
    
        $collectRows = function (callable $callback) {
            $httpRequest = request();
            // Isolate the nested source DataTables from the outer F21 table.
            // Passing the outer columns/order/search definitions into a source with
            // different SQL aliases can make an otherwise valid source query fail.
            $keys = ['start', 'length', 'draw', 'columns', 'order', 'search', '_'];
            $original = [];

            foreach ($keys as $key) {
                $original[$key] = [
                    'query_has' => $httpRequest->query->has($key),
                    'query_value' => $httpRequest->query->get($key),
                    'request_has' => $httpRequest->request->has($key),
                    'request_value' => $httpRequest->request->get($key),
                ];
            }

            // DataTables parameters arrive in the query bag on these GET routes.
            // Update both bags so every supported Laravel/Yajra version sees the
            // unpaginated request while the individual sources are aggregated.
            // Remove outer DataTables search/order/column metadata while each
            // source is being collected, then request the complete source set.
            foreach (['columns', 'order', 'search', '_'] as $key) {
                $httpRequest->query->remove($key);
                $httpRequest->request->remove($key);
            }

            $aggregateParameters = ['start' => 0, 'length' => -1, 'draw' => 1];
            foreach ($aggregateParameters as $key => $value) {
                $httpRequest->query->set($key, $value);
                $httpRequest->request->set($key, $value);
            }

            try {
                $response = $callback();

                if ($response instanceof \Illuminate\Http\JsonResponse) {
                    $payload = $response->getData(true);
                } elseif ($response instanceof \Symfony\Component\HttpFoundation\JsonResponse) {
                    $payload = json_decode($response->getContent(), true) ?: [];
                } elseif (is_array($response)) {
                    $payload = $response;
                } else {
                    $payload = [];
                }

                return collect($payload['data'] ?? []);
            } finally {
                foreach ($keys as $key) {
                    if ($original[$key]['query_has']) {
                        $httpRequest->query->set($key, $original[$key]['query_value']);
                    } else {
                        $httpRequest->query->remove($key);
                    }

                    if ($original[$key]['request_has']) {
                        $httpRequest->request->set($key, $original[$key]['request_value']);
                    } else {
                        $httpRequest->request->remove($key);
                    }
                }
            }
        };

        /* Count every source separately so a tenant-specific data problem can
         * be diagnosed without stopping the remaining F21 details from loading. */
        $sourceCounts = [];

        $countedSource = function (string $label, callable $fetch) use (&$sourceCounts) {
            try {
                $rows = $fetch();
                $sourceCounts[$label] = $rows instanceof \Illuminate\Support\Collection
                    ? $rows->count()
                    : count((array) $rows);

                return $rows;
            } catch (\Throwable $e) {
                // Recorded rather than hidden - this is what $collectRows lost.
                $sourceCounts[$label] = 'ERROR: ' . $e->getMessage();

                return collect();
            }
        };

        $allRows = collect()
            ->concat($countedSource('opening_stock_f22', function () {
                return $this->getOpeningStockFromF22();
            }))
            ->concat($countedSource('pos', function () use ($collectRows) {
                return $collectRows(function () {
                    return $this->getPos();
                });
            }))
            ->concat($countedSource('settlement', function () use ($collectRows) {
                return $collectRows(function () {
                    return $this->getSettlement();
                });
            }))
            ->concat($countedSource('purchase_order', function () use ($collectRows) {
                return $collectRows(function () {
                    return $this->getPurchaseOrder();
                });
            }))
            ->concat($countedSource('sell_return', function () use ($collectRows) {
                return $collectRows(function () {
                    return $this->getSellReturn();
                });
            }))
            ->concat($countedSource('purchase_return', function () use ($collectRows) {
                return $collectRows(function () {
                    return $this->getPurchaseReturn();
                });
            }))
            ->values();

        Log::info('IS2131 F21 source counts', [
            'business_id' => $business_id,
            'start_date' => request()->get('start_date'),
            'end_date' => request()->get('end_date'),
            'location_id' => request()->get('location_id'),
            'sources' => $sourceCounts,
            'combined' => $allRows->count(),
        ]);

        // When combining datasets, Settlement rows also appear as POS sales because their
        // invoice numbers match. Prefer the explicit Settlement rows and drop duplicate
        // POS Sale entries that refer to the same invoice/product/date.
        $value = function ($row, $key) {
            if (is_array($row)) {
                return $row[$key] ?? null;
            }
            if (is_object($row)) {
                return $row->$key ?? null;
            }
            return null;
        };

        $rowKey = function ($row) use ($value) {
            $invoice = $value($row, 'invoice_no');
            $productId = $value($row, 'product_id');
            $date = $value($row, 'transaction_date');

            if ($invoice === null || $productId === null) {
                return null;
            }

            return implode('|', [$invoice, $productId, $date]);
        };

        $settlementKeys = $allRows->filter(function ($row) use ($value) {
            $type = $value($row, 'transaction_type') ?? $value($row, 'transaction_type_label');
            return $type === 'Settlement';
        })->map(function ($row) use ($rowKey) {
            return $rowKey($row);
        })->filter()->values();

        $allRows = $allRows->reject(function ($row) use ($settlementKeys, $value, $rowKey) {
            $type = $value($row, 'transaction_type') ?? $value($row, 'transaction_type_label');
            if ($type !== 'POS Sale') {
                return false;
            }

            $key = $rowKey($row);
            return $key !== null && $settlementKeys->contains($key);
        })->values();

        $allRows = $allRows->map(function ($row) {
            if (is_array($row)) {
                $row = (object) $row;
            }

            if (!isset($row->book_no) || $row->book_no === null) {
                $row->book_no = '';
            }

            if (!isset($row->transaction_type) || $row->transaction_type === null) {
                $row->transaction_type = $row->transaction_type_label ?? '';
            }

            return $row;
        })->values();

        $allRows = $this->applyF21RunningBalances($allRows);

        return Datatables::of($allRows)
            ->editColumn('transaction_type', function ($row) {
                return $row->transaction_type ?? $row->transaction_type_label ?? '';
            })
            ->with('calculated_form_number', function () use ($business_id, $date, $location_id) {
                return $this->safeGetF21FormNumberForDate($business_id, $date, $location_id);
            })
            ->addColumn('starting_qty', function ($row) {
                $qty = $row->starting_qty ?? $this->computeF21StartingQty($row, true);
                return is_string($qty) ? str_replace('-', '', $qty) : $qty;
            })
            ->editColumn('balance_qty', function ($row) {
                return $row->balance_qty ?? $this->computeF21BalanceQty($row);
            })
            ->make(true);
    }


    /**
     * Count unique dates that have any F21-relevant details between the opening date and target date (inclusive).
     */
    protected function countF21DetailDates($businessId, Carbon $openingDate, Carbon $targetDate, $locationId = null)
    {
        if (empty($businessId) || $targetDate->lt($openingDate)) {
            return 0;
        }

        $locationId = $this->normalizeLocationId($locationId);
        $permittedLocations = auth()->user()->permitted_locations();
        $cacheKey = implode('|', [
            $businessId ?? 'null',
            $openingDate->toDateString(),
            $targetDate->toDateString(),
            $locationId ?? 'null',
        ]);

        if (isset($this->f21DetailDateCountCache[$cacheKey])) {
            return $this->f21DetailDateCountCache[$cacheKey];
        }

        $start = $openingDate->toDateString();
        $end = $targetDate->toDateString();
        $dates = [];

        $transactionTypes = ['sell', 'purchase', 'sell_return', 'purchase_return'];
        foreach ($transactionTypes as $type) {
            $query = Transaction::where('business_id', $businessId)
                ->where('type', $type)
                ->whereDate('transaction_date', '>=', $start)
                ->whereDate('transaction_date', '<=', $end);

            if ($permittedLocations != 'all') {
                $query->whereIn('location_id', (array) $permittedLocations);
            }

            if ($locationId !== null) {
                $query->where('location_id', $locationId);
            }

            if ($type === 'sell') {
                $query->whereIn('transactions.status', ['final', 'order']);
            }

            if ($type === 'sell_return') {
                $query->where('transactions.status', 'final');
            }

            $dates = array_merge(
                $dates,
                $query->select(DB::raw('DATE(transaction_date) as tx_date'))->distinct()->pluck('tx_date')->toArray()
            );
        }

        $settlementQuery = Settlement::where('business_id', $businessId)
            ->whereDate('transaction_date', '>=', $start)
            ->whereDate('transaction_date', '<=', $end);

        if ($permittedLocations != 'all') {
            $settlementQuery->whereIn('location_id', (array) $permittedLocations);
        }

        if ($locationId !== null) {
            $settlementQuery->where('location_id', $locationId);
        }

        $dates = array_merge(
            $dates,
            $settlementQuery->select(DB::raw('DATE(transaction_date) as tx_date'))->distinct()->pluck('tx_date')->toArray()
        );

        $uniqueCount = count(array_unique($dates));
        $this->f21DetailDateCountCache[$cacheKey] = $uniqueCount;

        return $uniqueCount;
    }

    /**
     * Build a cache key for form number lookups.
     */
    protected function buildF21FormNumberCacheKey($businessId, $date, $locationId = null)
    {
        return implode('|', [
            $businessId ?? 'null',
            $date ?? 'null',
            $this->normalizeLocationId($locationId) ?? 'null',
        ]);
    }

    /**
     * Calculate F21 form number for a given business and date.
     * Uses starting number and opening date from mpcs_form_settings (F21_form_sn, F21_form_tdate).
     * Returns null when data is incomplete; ignores dates that have no details to keep sequence stable.
     */
    protected function getF21FormNumberForDate($businessId, $date, $locationId = null)
    {
        if (empty($businessId)) {
            return null;
        }

        if (!isset($this->f21FormSettingsCache[$businessId])) {
            $this->f21FormSettingsCache[$businessId] = MpcsFormSetting::where('business_id', $businessId)->first();
        }

        $settings = $this->f21FormSettingsCache[$businessId];

        // Require explicit configuration; if missing, signal the UI to show "-".
        if (empty($settings) || $settings->F21_form_sn === null || empty($settings->F21_form_tdate)) {
            return null;
        }

        try {
            $openingDate = Carbon::parse($settings->F21_form_tdate)->startOfDay();
            $targetDate = Carbon::parse($date ?? Carbon::today()->toDateString())->startOfDay();
        } catch (\Exception $e) {
            return null;
        }

        $cacheKey = $this->buildF21FormNumberCacheKey($businessId, $targetDate->toDateString(), $locationId);
        if (isset($this->f21FormNumberCache[$cacheKey])) {
            return $this->f21FormNumberCache[$cacheKey];
        }

        // Do not allow the sequence to move backwards before the opening date.
        if ($targetDate->lt($openingDate)) {
            $this->f21FormNumberCache[$cacheKey] = (int) $settings->F21_form_sn;
            return $this->f21FormNumberCache[$cacheKey];
        }

        $uniqueDetailDates = $this->countF21DetailDates($businessId, $openingDate, $targetDate, $locationId);
        $formNumber = (int) $settings->F21_form_sn;

        if ($uniqueDetailDates > 1) {
            $formNumber += ($uniqueDetailDates - 1);
        }

        $this->f21FormNumberCache[$cacheKey] = $formNumber;

        return $formNumber;
    }

}

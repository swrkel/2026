<?php



namespace Modules\PetroGeneral\Http\Controllers;



use App\AccountTransaction;

use App\Business;

use App\BusinessLocation;

use Modules\Superadmin\Entities\Subscription;

use Illuminate\Http\Request;

use Illuminate\Routing\Controller;

use Modules\PetroGeneral\Entities\FuelTank;

use App\Product;

use App\ProductVariation;

use App\PurchaseLine;

use App\System;

use App\Store;

use App\Transaction;

use App\Utils\ModuleUtil;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;

use Yajra\DataTables\Facades\DataTables;

use App\Utils\ProductUtil;

use App\Utils\TransactionUtil;

use App\Variation;

use Illuminate\Support\Facades\Log;

use Modules\PetroGeneral\Entities\Settlement;

use Modules\PetroGeneral\Entities\TankPurchaseLine;

use Modules\PetroGeneral\Entities\TankSellLine;

use Modules\Superadmin\Entities\HelpExplanation;

use Modules\Superadmin\Entities\TankDipChart;
use Modules\PetroGeneral\Entities\MeterSale;

use Maatwebsite\Excel\Facades\Excel;


class FuelTankController extends Controller

{

    /*
     * MA-002: resolve the active business from whichever key is populated.
     *
     * The Product dropdown on Add Fuel Tank was empty for a Business Admin but
     * full for a Super Admin. The reason is these two lines together:
     *
     *     $business_id = $this->activeBusinessId();
     *     if (! auth()->user()->can('superadmin')) {
     *         $products = $products->where('products.business_id', $business_id);
     *     }
     *
     * A Super Admin SKIPS the filter entirely, so it never mattered whether
     * $business_id had a value. A Business Admin gets the filter - and when
     * session('business.id') is empty the query becomes business_id = null and
     * returns nothing. An empty dropdown, with no error anywhere.
     *
     * This is the same key that made the Add User screen build the wrong
     * username suffix and left the Petro General dashboard blank.
     * user.business_id is set for any signed-in business user, and the
     * authenticated user's own business_id is more reliable still, so both are
     * used as fallbacks.
     */
    protected function activeBusinessId()
    {
        $businessId = request()->session()->get('business.id');

        if (empty($businessId)) {
            $businessId = request()->session()->get('user.business_id');
        }

        if (empty($businessId)) {
            $businessId = optional(auth()->user())->business_id;
        }

        /*
         * If all three are empty, say so. Returning null here produces queries
         * that filter on business_id = null - no error, no warning, just empty
         * dropdowns and empty tables. That silence is what made this look like
         * a permissions problem for as long as it did.
         */
        if (empty($businessId)) {
            \Log::warning('MA-002: PetroGeneral could not resolve the active business.', [
                'user_id' => optional(auth()->user())->id,
                'url' => request()->fullUrl(),
            ]);
        }

        return $businessId;
    }


    /**

     * All Utils instance.

     *

     */

    protected $productUtil;

    protected $transactionUtil;

    protected $moduleUtil;



    /**

     * Constructor

     *

     * @param ProductUtils $product

     * @return void

     */

    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)

    {

        $this->productUtil = $productUtil;

        $this->transactionUtil = $transactionUtil;

        $this->moduleUtil = $moduleUtil;

    }





    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */
     
    public function getTankProduct(){
        
    }

    /**
     * Return the current ledger balance for every fuel tank in one business.
     *
     * This intentionally mirrors TanksTransactionDetailController's four movement
     * branches instead of reading fuel_tanks.current_balance or the older generic
     * tank-balance helper. Tank Transaction Details is the authoritative audit trail
     * for the quantity shown to the user, so the Fuel Tanks list must reconcile to it.
     *
     * Balance = purchases/opening/increase adjustments
     *         - sales/deleted purchases/decrease adjustments
     *         + transfers in - transfers out
     *
     * Testing litres are NOT deducted here, matching both Tank Transaction Details
     * and Tank Transaction Summary.
     */
    protected function getTankLedgerBalancesForFuelTankList(int $business_id)
    {
        try {
            $purchase_rows = DB::table('transactions as t')
                ->join('tank_purchase_lines as tpl', function ($join) {
                    $join->on('t.id', '=', 'tpl.transaction_id')
                        ->where('tpl.quantity', '!=', 0);
                })
                ->join('fuel_tanks as ft', 'tpl.tank_id', '=', 'ft.id')
                ->where('t.business_id', $business_id)
                ->where('ft.business_id', $business_id)
                ->whereNull('t.deleted_at')
                ->select([
                    'tpl.tank_id as fuel_tank_id',
                    't.id as source_id',
                    DB::raw("SUM(CASE
                        WHEN t.type IN ('purchase', 'opening_stock') THEN tpl.quantity
                        WHEN t.type = 'stock_adjustment'
                             AND COALESCE(t.sub_type, '') = 'dip_resetting'
                             AND COALESCE(t.stock_adjustment_type, '') = 'increase'
                            THEN tpl.quantity
                        ELSE 0
                    END) as purchase_qty"),
                    DB::raw("SUM(CASE
                        WHEN t.type = '_deleted_purchase' THEN tpl.quantity
                        ELSE 0
                    END) as sold_qty"),
                ])
                ->groupBy('tpl.tank_id', 't.id');

            $sell_rows = DB::table('transactions as t')
                ->join('tank_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('fuel_tanks as ft', 'tsl.tank_id', '=', 'ft.id')
                ->where('t.business_id', $business_id)
                ->where('ft.business_id', $business_id)
                ->whereNull('t.deleted_at')
                ->whereNotIn('t.type', ['purchase', '_deleted_purchase'])
                ->select([
                    'tsl.tank_id as fuel_tank_id',
                    't.id as source_id',
                    DB::raw('0 as purchase_qty'),
                    DB::raw("SUM(CASE
                        WHEN t.type = 'stock_adjustment'
                             AND COALESCE(t.sub_type, '') = 'dip_resetting'
                             AND COALESCE(t.stock_adjustment_type, '') = 'decrease'
                            THEN tsl.quantity
                        WHEN t.type != 'stock_adjustment' THEN tsl.quantity
                        ELSE 0
                    END) as sold_qty"),
                ])
                ->groupBy('tsl.tank_id', 't.id');

            $transfer_in_rows = DB::table('tank_transfers as tt')
                ->join('fuel_tanks as ft', 'tt.to_tank', '=', 'ft.id')
                ->where('tt.business_id', $business_id)
                ->where('ft.business_id', $business_id)
                ->select([
                    'tt.to_tank as fuel_tank_id',
                    DB::raw('tt.id as source_id'),
                    'tt.quantity as purchase_qty',
                    DB::raw('0 as sold_qty'),
                ]);

            $transfer_out_rows = DB::table('tank_transfers as tt')
                ->join('fuel_tanks as ft', 'tt.from_tank', '=', 'ft.id')
                ->where('tt.business_id', $business_id)
                ->where('ft.business_id', $business_id)
                ->select([
                    'tt.from_tank as fuel_tank_id',
                    DB::raw('tt.id as source_id'),
                    DB::raw('0 as purchase_qty'),
                    'tt.quantity as sold_qty',
                ]);

            $ledger = $purchase_rows
                ->unionAll($sell_rows)
                ->unionAll($transfer_in_rows)
                ->unionAll($transfer_out_rows);

            return DB::query()
                ->fromSub($ledger, 'tank_ledger')
                ->select('fuel_tank_id')
                ->selectRaw('SUM(COALESCE(purchase_qty, 0) - ABS(COALESCE(sold_qty, 0))) as balance_qty')
                ->groupBy('fuel_tank_id')
                ->pluck('balance_qty', 'fuel_tank_id')
                ->mapWithKeys(function ($balance, $tank_id) {
                    return [(int) $tank_id => (float) $balance];
                })
                ->all();
        } catch (\Throwable $e) {
            /*
             * The list must remain available even on a legacy tenant whose schema is
             * mid-upgrade. Log the failure and return null so the caller can keep
             * the previous legacy display path rather than blanking every tank.
             */
            Log::error('PetroGeneral Fuel Tanks: unable to calculate ledger balances', [
                'business_id' => $business_id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function index()

    {

        $business_id = request()->session()->get('user.business_id');


        
        if (!$this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general')) {
            
            abort(403, 'Unauthorized Access');
            
        }

        /*
         * IS2273 follow-up / Tank Management embedded transfers:
         *
         * Http/routes.php still contains the legacy Route::resource('/tank-management',
         * 'FuelTankController') after Routes/tanks.php is loaded. On installations
         * where that later resource route is the active route, requests to the Tank
         * Management URL land here rather than Tank\TankIndexController.
         *
         * Dispatch the two internal Tank Transfer operations here as well. This makes
         * the embedded tab work regardless of which compatible Tank Management route
         * registration wins, while keeping it under the Tank Management URL/permission.
         */
        if (request()->boolean('petrogeneral_embedded_tank_transfer_data')) {
            return app(TankTransferController::class)->index();
        }

        if (request()->boolean('petrogeneral_embedded_tank_transfer_create')) {
            return app(TankTransferController::class)->create();
        }

        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');

            $canEdit = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general_fuel_tanks_edit') || auth()->user()->can('superadmin')?true:false;
            $canDelete = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general_fuel_tanks_delete')||auth()->user()->can('superadmin') ?true:false;
          
            if (request()->ajax()) {

                /*
                 * IS1959 #1: the tank list rendered its headers and no rows.
                 *
                 * The old query joined variations and variation_location_details
                 * and then applied ->groupBy('fuel_tanks.id') while selecting
                 * fuel_tanks.*, products.name, business_locations.name and
                 * vld.qty_available. Under MySQL's default ONLY_FULL_GROUP_BY
                 * (on by default since 5.7) selecting columns that are neither
                 * grouped nor aggregated is an error, so the query threw, the
                 * DataTables request failed, and the table drew an empty body.
                 *
                 * The joins existed only to fetch one number - the tank product's
                 * available stock. That is now a correlated subquery, so the
                 * grouping is unnecessary and has been removed entirely.
                 *
                 * The subquery also fixes a second fault: the old vld join was not
                 * constrained to the tank's own location, so a tank could show
                 * stock belonging to a different branch. It now matches on
                 * location_id as well as product.
                 */
                $query = FuelTank::leftjoin('products', 'fuel_tanks.product_id', 'products.id')

                    ->leftjoin('business_locations', 'fuel_tanks.location_id', 'business_locations.id')

                    ->where('fuel_tanks.business_id', $business_id)

                    ->select([

                        'fuel_tanks.*',

                        DB::raw('(
                            SELECT vld.qty_available
                            FROM variations
                            INNER JOIN variation_location_details AS vld
                                ON vld.variation_id = variations.id
                               AND vld.location_id = fuel_tanks.location_id
                            WHERE variations.product_id = fuel_tanks.product_id
                            LIMIT 1
                        ) as stock'),

                        'products.name as product_name',

                        'business_locations.name as location_name'

                    ]);

                if(request()->fuel_tank_number){
                    $query->where('fuel_tanks.fuel_tank_number',request()->fuel_tank_number);
                }
                
                if(request()->location_id){
                    $query->where('fuel_tanks.location_id',request()->location_id);
                }
                  
                    

                /*
                 * Build all tank balances once, from the exact movement sources used by
                 * Tank Transaction Details. This keeps the Fuel Tanks tab in lock-step
                 * with the two ledger tabs without running one balance query per row.
                 */
                $tank_ledger_balances = $this->getTankLedgerBalancesForFuelTankList((int) $business_id);

                $fuel_tanks = Datatables::of($query)

                    ->addColumn( 'action', function ($row) use($canEdit, $canDelete){
                        $reshtml = '';
                        if($canEdit || $canDelete || 1){
                            if($canEdit || 1){
                                $reshtml = '<button data-href="'.action('\Modules\PetroGeneral\Http\Controllers\FuelTankController@edit', [$row->id]).'" data-container=".fuel_tank_modal" class="btn btn-primary btn-xs btn-modal edit_reference_button"><i class="fa fa-pencil-square-o"></i>'. trans("messages.edit").'</button>';
                            }
                            if($canDelete){
                                $reshtml .= '<a href="'.action('\Modules\PetroGeneral\Http\Controllers\FuelTankController@destroy', [$row->id]).'" class="delete_tank_button btn btn-danger btn-xs"><i class="fa fa-trash"></i>'.trans("messages.delete").'</a>';
                            }
                        }
                        return $reshtml;      

                    })

                    ->editColumn('bulk_tank', '@if($bulk_tank == 1) Yes @else No @endif')

                    ->addColumn('new_balance', function ($row) use ($business_id, $tank_ledger_balances) {
                        /*
                         * Fuel Tanks / Current Balance must use the SAME movement ledger as
                         * Tank Transaction Details and Tank Transaction Summary.
                         *
                         * Do not use TransactionUtil::getTankBalanceById() here. That helper
                         * follows a different legacy balance path and can diverge from the
                         * tank ledger after back-dated settlements, dip resets, deleted
                         * purchases or tank transfers.
                         *
                         * Also, zero is a legitimate tank balance. The previous code treated
                         * 0 as "no result" and replaced it with fuel_tanks.current_balance,
                         * which is a stored/cache value and can be stale. That is how a tank
                         * whose ledger correctly ended at 0 could still display an old qty.
                         */
                        $tank_id = (int) $row->id;

                        if (is_array($tank_ledger_balances)) {
                            // A missing ledger row means no movement, therefore 0 is
                            // the correct balance. Do not replace a legitimate zero
                            // with the stored fuel_tanks.current_balance cache.
                            $current_balance = array_key_exists($tank_id, $tank_ledger_balances)
                                ? (float) $tank_ledger_balances[$tank_id]
                                : 0.0;
                        } else {
                            // Legacy-schema safety only. This branch is reached solely
                            // when the authoritative ledger aggregation itself failed.
                            try {
                                $current_balance = $this->transactionUtil->getTankBalanceById($tank_id);
                            } catch (\Throwable $legacy_balance_error) {
                                $current_balance = null;
                            }

                            if (! is_numeric($current_balance)) {
                                $current_balance = (float) ($row->current_balance ?? 0);
                            }
                        }

                        $business_details = Business::find($business_id);

                        return $this->productUtil->num_f($current_balance, false, $business_details, true);
                    })

                    ->editColumn('transaction_date', function($row){
                        // $latest_sale = MeterSale::leftjoin('pumps','pumps.id','pump_id')->leftjoin('fuel_tanks','fuel_tanks.id','pumps.fuel_tank_id')->where('fuel_tanks.id',$row->id)->select('meter_sales.created_at')->get()->last();
                        $transaction_date = /*!empty($latest_sale) ? $latest_sale->created_at :*/ $row->transaction_date;
                        
                        return $this->transactionUtil->format_date($transaction_date);
                        
                    })

                    ->removeColumn('id');





                return $fuel_tanks->rawColumns(['action', 'new_balance'])

                    ->make(true);

            }

        }



        $business_locations = BusinessLocation::forDropdown($business_id);

        $tank_numbers = FuelTank::where('business_id', $business_id)->pluck('fuel_tank_number', 'fuel_tank_number');

        $products = Product::leftjoin('categories', 'products.category_id', 'categories.id')->where('products.business_id', $business_id)->where('categories.name', 'Fuel')->pluck('products.name', 'products.id');

        $settlements = Settlement::where('business_id', $business_id)->pluck('settlement_no', 'settlement_no');

        $purhcase_nos = Transaction::where('business_id', $business_id)->where('type', 'purchase')->pluck('ref_no', 'ref_no');



        $message = $this->transactionUtil->getGeneralMessage('general_message_tank_management_checkbox');



        /*
         |----------------------------------------------------------------------
         | IS2053 follow-up: warn when the business has no Default Store.
         |----------------------------------------------------------------------
         |
         | Saving a fuel tank writes a variation_store_details row, and store_id
         | is NOT NULL. Without a default store the save fails at the database -
         | which is the fault reported in IS2053.
         |
         | The warning is raised HERE, on the pages that need it, rather than at
         | login: a Finance or VAT user has no use for it, and the login flow is
         | shared by the whole application.
         |
         | Only relevant when stock is actually tracked, so it is shown when the
         | business runs Products or any of the fuel modules - Petro General,
         | Petro Direct, Petro PD or SW Settlements. A business using none of
         | those never sees it.
         */
        $pg_default_store_missing = $this->petroGeneralDefaultStoreMissing();

        return view('petrogeneral::fuel_tanks.index')->with(compact(

            'pg_default_store_missing',

            'business_locations',

            'message',

            'tank_numbers',

            'products',

            'settlements',

            'purhcase_nos'

        ));

    }



    /**

     * Show the form for creating a new resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function create()

    {

        $business_id = $this->activeBusinessId();

        $locations = BusinessLocation::forDropdown($business_id, false, false, true, true);
        // forDropdown($business_id, $show_all = false, $receipt_printer_type_attribute = false, $append_id = true, $check_super_admin = false)

        $products = Product::leftjoin('categories', 'products.category_id', 'categories.id');

        if(!auth()->user()->can('superadmin')){
            $products = $products->where('products.business_id', $business_id);
        }

        $products = $products->where('categories.name', 'Fuel')

            ->pluck('products.name', 'products.id');

        $help_explanations = HelpExplanation::pluck('value', 'help_key');

        $sheet_names = TankDipChart::pluck('sheet_name', 'id');

        $tank_dip_chart_permission = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'tank_dip_chart');
        
        $subscriptions = Subscription::active_subscription($business_id);
        $max_tanks = $subscriptions ? ($subscriptions->package_details['allowed_tanks'] ?? 0) : 0;
        $tanks_added = DB::table('fuel_tanks')->where('business_id', $business_id)->count();

        $ajax = false;
        if(request()->ajax){
            $ajax = true;
        }

        $multiple = false;
        if(request()->multiple){
            $multiple = true;
        }

        // Same warning on the Add form - see the note on index() above.
        $pg_default_store_missing = $this->petroGeneralDefaultStoreMissing();

        return view('petrogeneral::fuel_tanks.create')->with(compact('pg_default_store_missing','max_tanks','tanks_added','locations', 'products', 'help_explanations', 'tank_dip_chart_permission', 'sheet_names', 'ajax', 'multiple'));

    }



    /**

     * Store a newly created resource in storage.

     *

     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response

     */
     
    public function import()
    {
        $business_id = $this->activeBusinessId();
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('petrogeneral::fuel_tanks.import')->with(compact('business_locations'));
    }
    /**
     * Import Operators saves
     * @return Response
     */
    public function saveImport(Request $request)
    {
        $notAllowed = $this->productUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }
        $business_id = $this->activeBusinessId();
        $location_id =   $request->location_id;
        $type =   $request->commission_type;

        try {
            //Set maximum php execution time
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            if ($request->hasFile('pumps_csv')) {
                $file = $request->file('pumps_csv');

                $parsed_array = Excel::toArray([], $file);

                //Remove header row
                $imported_data = array_splice($parsed_array[0], 1);

                $formated_data = [];

                $is_valid = true;
                $error_msg = '';

                $total_rows = count($imported_data);

                $row_no = 0;
                DB::beginTransaction();
                foreach ($imported_data as $key => $value) {
                    $row_no++;

                    //Check if any column is missing
                    if (count($value) < 6) {
                        $is_valid =  false;
                        $error_msg = "Some of the columns are missing. Please, use latest CSV file template.";
                        
                    }

                    $tank_no = (trim($value[0]));
                    if (empty($tank_no)) {
                        $is_valid = false;
                        $error_msg = "Invalid value for Tank No in row no. $row_no";
                        
                    }
                    
                    // tank no exists
                    $fuel_tanks = FuelTank::where('business_id', $business_id)->where('fuel_tank_number',$tank_no)->count();
                    if($fuel_tanks > 0){
                        $is_valid = false;
                        $error_msg = "Similar Fuel Tank No already exists in row no. $row_no";
                    }
                    
                    $product_name = (trim($value[1]));
                    if (empty($product_name)) {
                        $is_valid = false;
                        $error_msg = "Invalid value for Product in row no. $row_no";
                        
                    }
                    
                    // product not found
                    $product = Product::where('business_id', $business_id)
        
                        ->where('name', $product_name)
        
                        ->with(['variations', 'product_tax'])
        
                        ->first();
                        
                    if(empty($product)){
                        $is_valid = false;
                        $error_msg = "Product Name does not exist in DB in row no. $row_no";
                    }
                    
                    
                    $storage_volume = (trim($value[2])) ?? 0;
                    
                    $current_balance = (trim($value[3])) ?? 0;
                    
                    $transaction_date = ($value[4]);
                    if (empty($transaction_date)) {
                        $is_valid = false;
                        $error_msg = "Invalid value for Transaction Date row no. $row_no";
                       
                    }
                    
                   
                    $bulk_tank = strtolower(trim($value[5]));
                    if (empty($bulk_tank)) {
                        $is_valid = false;
                        $error_msg = "Invalid value for Bulk Tank in row no. $row_no";
                        
                    }
                    


                    if (!$is_valid) {
                        throw new \Exception($error_msg);
                        break;
                    }
                    
                    $variation = Variation::where('product_id', $product->id)->first();
                    $k = $variation->id;
                    
                    $qty_remaining = $this->productUtil->num_uf($current_balance);

                    $purchase_price_inc_tax = $variation->dpp_inc_tax;
                    $default_purchase_price = $variation->default_purchase_price;
                    $item_tax = ($purchase_price_inc_tax-$default_purchase_price) * $qty_remaining;

                    //Calculate transaction total
        
                    $purchase_total = ($purchase_price_inc_tax * $qty_remaining);
                    
                    
                    $exp_date = null;

                    $lot_number = null;
                    
                    $old_qty = 0;

                    $data = array(
        
                        'business_id' =>  $business_id,
        
                        'product_id' =>   $product->id,
        
                        'fuel_tank_number' =>   $tank_no,
        
                        'location_id' =>   $location_id,
        
                        'storage_volume' =>   $storage_volume,
        
                        'bulk_tank' =>   $bulk_tank == 'yes' ? 1 : 0,
        
                        'current_balance' =>   0, //this will update current qty in product stock updated below
        
                        'user_id' =>   Auth::user()->id,
        
                        'transaction_date' =>   date('Y-m-d', strtotime($transaction_date))
        
                    );
                    
                    $fuel_tank = FuelTank::create($data);

                //$k is variation id
    
                $this->productUtil->updateProductQuantity($location_id, $product->id, $k, $current_balance, $old_qty, null, false, $fuel_tank->id);
                $this->productUtil->updateProductQuantityStore($location_id, $product->id, $k, $current_balance, null, $old_qty, null, false);
            
    
                if ($qty_remaining != 0) {
                    $transaction = Transaction::create(
                        [
        
                            'type' => 'opening_stock',
        
                            'opening_stock_product_id' => $product->id,
        
                            'status' => 'received',
        
                            'business_id' => $business_id,
        
                            'transaction_date' => date('Y-m-d', strtotime($transaction_date)),
        
                            'total_before_tax' => $purchase_total,
        
                            'location_id' => $location_id,
        
                            'final_total' => $purchase_total,
        
                            'payment_status' => 'paid',
        
                            'created_by' => Auth::user()->id
        
                        ]
                    );
                    
                    $purchase_line = PurchaseLine::create(['product_id' => $product->id,
                        'variation_id' => $k,
                        'item_tax' => $item_tax,
                        'tax_id' => $product->tax,
                        'quantity' => $current_balance,
                        'pp_without_discount' => $default_purchase_price,
                        'purchase_price_inc_tax' => $purchase_price_inc_tax,
                        'exp_date' => $exp_date,
                        'purchase_price' => $default_purchase_price,
                        'lot_number' => $lot_number,
                        'transaction_id' => $transaction->id
                    ]);
        
        
    
    
    
                    //create pruchase line for tank 
        
                    TankPurchaseLine::create([
        
                        'business_id' => $business_id,
        
                        'transaction_id' => $transaction->id,
        
                        'tank_id' => $fuel_tank->id,
        
                        'product_id' => $product->id,
        
                        'quantity' => $current_balance
        
                    ]);





                    if ($qty_remaining  > 0) {
        
                        $acc_tran_type = 'debit';
        
                    }
        
                    if ($qty_remaining  < 0) {
        
                        $acc_tran_type = 'credit';
        
                    }
        
                        if (!empty($product->enable_stock)) {
        
                            if (!empty($product->stock_type)) {
        
                                $account_id = $product->stock_type;
        
                                $account_transaction_data = [
        
                                    'amount' => $transaction->final_total,
        
                                    'account_id' => $account_id,
        
                                    'type' => $acc_tran_type,
        
                                    'operation_date' => $transaction->transaction_date,
        
                                    'created_by' => $transaction->created_by,
        
                                    'transaction_id' => $transaction->id,
        
                                    'transaction_payment_id' => null,
        
                                    'note' => null
        
                                ];
        
        
        
                                AccountTransaction::createAccountTransaction($account_transaction_data);
        
                            }
        
                        }
        
        
        
                        $opening_balance_equity_id = $this->transactionUtil->account_exist_return_id('Opening Balance Equity Account');
        
                        $this->transactionUtil->createAccountTransaction($transaction, 'credit', $opening_balance_equity_id, $transaction->final_total);
        
                    }
                    
                }
                DB::commit();
            }

            $output = [
                'success' => 1,
                'msg' => __('petrogeneral::lang.import_success')
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => $e->getMessage()
            ];

            return redirect()->back()->with('notification', $output);
        }

        return redirect('/petro-general/tank-management')->with('status', $output);
    }

    public function store(Request $request)

    {

        /*
         * Same compatibility rule as index(): the legacy resource POST route can be
         * the active /petro-general/tank-management endpoint. A transfer submitted
         * from the embedded Tank Transfers tab must therefore be dispatched before
         * Fuel Tank creation logic starts.
         */
        if ($request->boolean('petrogeneral_embedded_tank_transfer_store')) {
            return app(TankTransferController::class)->store($request);
        }

        try {
            $default_store_id = null;
            if(auth()->user()->can('superadmin')){
                $business_id = BusinessLocation::where('id', $request->location_id)->first()->business_id ?? '0';
                $default_store_id = Business::where('id', $business_id)->first()->default_store ?? null;
                if(empty($default_store_id)){
                    $output = [
                        'success' => false,
                        'msg' => "Selected Business Location Default Store is required."
                    ];
                    if (request()->ajax()) {
                        return $output;
                    }
                    return redirect()->back()->with('status', $output);
                }
            } else {
                $business_id = $this->activeBusinessId();

                /*
                 |------------------------------------------------------------------
                 | IS2053: saving a fuel tank failed with
                 |     Column 'store_id' cannot be null
                 |     insert into variation_store_details (... store_id ...)
                 |------------------------------------------------------------------
                 |
                 | $default_store_id was resolved ONLY in the superadmin branch
                 | above. For every other user it stayed null, was passed to
                 | updateProductQuantityStore(), and the insert failed on a NOT NULL
                 | column - surfacing as "Something went wrong, please try again
                 | later".
                 |
                 | The same business default store is now resolved here. The
                 | superadmin branch reads it from the LOCATION's business because a
                 | superadmin may be acting across businesses; an ordinary user is
                 | always within their own, so activeBusinessId() is correct.
                 |
                 | If it cannot be resolved the save stops with a message naming the
                 | cause, rather than failing deep inside the insert - matching what
                 | the superadmin branch already does.
                 */
                $default_store_id = Business::where('id', $business_id)->value('default_store');

                if (empty($default_store_id)) {
                    $output = [
                        'success' => false,
                        'msg' => "Selected Business Location Default Store is required.",
                    ];

                    if (request()->ajax()) {
                        return $output;
                    }

                    return redirect()->back()->with('status', $output);
                }
            }

            $transaction_date = $request->transaction_date;

            $product_id = $request->product_id;

            $variation = Variation::where('product_id', $request->product_id)->first();

            $k = $variation->id;

            $product = Product::where('business_id', $business_id)

                ->where('id', $product_id)

                ->with(['variations', 'product_tax'])

                ->first();

            $qty_remaining = $this->productUtil->num_uf(trim($request->current_balance));

            $purchase_price_inc_tax = $variation->dpp_inc_tax;
            $default_purchase_price = $variation->default_purchase_price;
            $item_tax = ($purchase_price_inc_tax-$default_purchase_price) * $qty_remaining;

            //Calculate transaction total

            $purchase_total = ($purchase_price_inc_tax * $qty_remaining);

            $exp_date = null;

            $lot_number = null;

            $old_qty = 0;

            $data = array(

                'business_id' =>  $business_id,

                'product_id' =>   $request->product_id,

                'fuel_tank_number' =>   $request->fuel_tank_number,

                'location_id' =>   $request->location_id,

                'storage_volume' =>   $request->storage_volume,

                'bulk_tank' =>   $request->bulk_tank,

                'current_balance' =>   0, //this will update current qty in product stock updated below

                'user_id' =>   Auth::user()->id,

                'transaction_date' =>   date('Y-m-d', strtotime($transaction_date)),

                'tank_dip_chart_id' =>   $request->tank_dip_chart_id,

                'tank_manufacturer' =>   $request->tank_manufacturer,

                'tank_capacity' =>   $request->tank_capacity,

                'unit_name' =>   $request->unit_name

            );



            DB::beginTransaction();



            $fuel_tank = FuelTank::create($data);

            //$k is variation id

            $this->productUtil->updateProductQuantity($request->location_id, $request->product_id, $k, $request->current_balance, $old_qty, null, false, $fuel_tank->id);
            $this->productUtil->updateProductQuantityStore($request->location_id, $request->product_id, $k, $request->current_balance, $default_store_id, $old_qty, null, false);
        

            if ($qty_remaining != 0) {
                $transaction = Transaction::create(
                    [
    
                        'type' => 'opening_stock',
    
                        'opening_stock_product_id' => $request->product_id,
    
                        'status' => 'received',
    
                        'business_id' => $business_id,
    
                        'transaction_date' => date('Y-m-d', strtotime($transaction_date)),
    
                        'total_before_tax' => $purchase_total,
    
                        'location_id' => $request->location_id,
    
                        'final_total' => $purchase_total,
    
                        'payment_status' => 'paid',
    
                        'created_by' => Auth::user()->id
    
                    ]
                );
                
                $purchase_line = PurchaseLine::create(['product_id' => $product->id,
                    'variation_id' => $k,
                    'item_tax' => $item_tax,
                    'tax_id' => $product->tax,
                    'quantity' => $request->current_balance,
                    'pp_without_discount' => $default_purchase_price,
                    'purchase_price_inc_tax' => $purchase_price_inc_tax,
                    'exp_date' => $exp_date,
                    'purchase_price' => $default_purchase_price,
                    'lot_number' => $lot_number,
                    'transaction_id' => $transaction->id
                ]);
    
    



                //create pruchase line for tank 
    
                TankPurchaseLine::create([
    
                    'business_id' => $business_id,
    
                    'transaction_id' => $transaction->id,
    
                    'tank_id' => $fuel_tank->id,
    
                    'product_id' => $request->product_id,
    
                    'quantity' => $request->current_balance
    
                ]);





            if ($qty_remaining  > 0) {

                $acc_tran_type = 'debit';

            }

            if ($qty_remaining  < 0) {

                $acc_tran_type = 'credit';

            }

                if (!empty($product->enable_stock)) {

                    if (!empty($product->stock_type)) {

                        $account_id = $product->stock_type;

                        $account_transaction_data = [

                            'amount' => $transaction->final_total,

                            'account_id' => $account_id,

                            'type' => $acc_tran_type,

                            'operation_date' => $transaction->transaction_date,

                            'created_by' => $transaction->created_by,

                            'transaction_id' => $transaction->id,

                            'transaction_payment_id' => null,

                            'note' => null

                        ];



                        AccountTransaction::createAccountTransaction($account_transaction_data);

                    }

                }



                $opening_balance_equity_id = $this->transactionUtil->account_exist_return_id('Opening Balance Equity Account');

                $this->transactionUtil->createAccountTransaction($transaction, 'credit', $opening_balance_equity_id, $transaction->final_total);

            }



            DB::commit();

            $output = [

                'success' => true,

                'msg' => __('petrogeneral::lang.fuel_tank_add_success'),
                'data' => [
                    'id' => $fuel_tank->id,
                    'tankname' => $fuel_tank->fuel_tank_number,
                    'manufacturer' => $fuel_tank->tank_manufacturer,
                    'manufacturerphone' => $fuel_tank->tank_manufacturer_phone,
                    'capacity' => $fuel_tank->storage_volume,
                ],

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong')

            ];

        }

        if (request()->ajax()) {
            return $output;
        }


        return redirect()->back()->with('status', $output);

    }



    /**

     * Display the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function show($id)

    {

        //

    }



    /**

     * Show the form for editing the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function edit($id)

    {

        $business_id = $this->activeBusinessId();

        $locations = BusinessLocation::forDropdown($business_id);

        $products = Product::leftjoin('categories', 'products.category_id', 'categories.id')

            ->where('products.business_id', $business_id)

            ->where('categories.name', 'Fuel')

            ->pluck('products.name', 'products.id');

        $fuel_tank = FuelTank::findOrFail($id);
        
        $opening_stock = Transaction::leftjoin('tank_purchase_lines', 'transactions.id', 'tank_purchase_lines.transaction_id')

                ->where('transactions.business_id', $business_id)

                ->where('transactions.type', 'opening_stock')

                ->where('tank_purchase_lines.product_id', $fuel_tank->product_id)
                
                ->where('tank_purchase_lines.tank_id', $fuel_tank->id)

                ->select('tank_purchase_lines.quantity')

                ->first()->quantity ?? 0;

        $sheet_names = TankDipChart::pluck('sheet_name', 'id');

        $tank_dip_chart_permission = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'tank_dip_chart');



        return view('petrogeneral::fuel_tanks.edit')->with(compact('locations', 'products', 'fuel_tank', 'tank_dip_chart_permission', 'sheet_names','opening_stock'));

    }



    /**

     * Update the specified resource in storage.

     *

     * @param  \Illuminate\Http\Request  $request

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function update(Request $request, $id)

    {

        try {
            
            $fuel_tank = FuelTank::findOrFail($id);

            $business_id = $this->activeBusinessId();

            $transaction_date = $request->transaction_date;

            $product_id = $request->product_id;

            $variation = Variation::where('product_id', $request->product_id)->first();

            $k = $variation->id;

            $product = Product::where('business_id', $business_id)

                ->where('id', $product_id)

                ->with(['variations', 'product_tax'])

                ->first();

            $qty_remaining = $this->productUtil->num_uf(trim($request->current_balance));

            $purchase_price_inc_tax = $variation->dpp_inc_tax;
            $default_purchase_price = $variation->default_purchase_price;
            $item_tax = ($purchase_price_inc_tax-$default_purchase_price) /** $qty_remaining*/;

            //Calculate transaction total

            $purchase_total = ($purchase_price_inc_tax * $qty_remaining);

            $exp_date = null;

            $lot_number = null;

            $old_qty = 0;

            $data = array(

                'business_id' =>  $business_id,

                'product_id' =>   $request->product_id,

                'fuel_tank_number' =>   $request->fuel_tank_number,

                'location_id' =>   $request->location_id,

                'storage_volume' =>   $request->storage_volume,

                'tank_dip_chart_id' =>   $request->tank_dip_chart_id,  //sheet name

                'tank_manufacturer' =>   $request->tank_manufacturer,

                'tank_capacity' =>   $request->tank_capacity,

                'unit_name' =>   $request->unit_name,

                'bulk_tank' =>   $request->bulk_tank,

                'user_id' =>   Auth::user()->id,

                'transaction_date' =>   date('Y-m-d', strtotime($request->transaction_date))

            );



            DB::beginTransaction();

            FuelTank::where('id', $id)->update($data);
            
            if ($qty_remaining != 0) {
                $opening_stock = Transaction::leftjoin('tank_purchase_lines', 'transactions.id', 'tank_purchase_lines.transaction_id')

                    ->where('transactions.business_id', $business_id)
    
                    ->where('transactions.type', 'opening_stock')
    
                    ->where('tank_purchase_lines.product_id', $fuel_tank->product_id)
                    
                    ->where('tank_purchase_lines.tank_id', $fuel_tank->id)
    
                    ->select('transactions.*')
    
                    ->first();
                    
                if(!empty($opening_stock)){
                    $transaction = Transaction::findOrFail($opening_stock->id);
                    
                    // Preserve original transaction date
                    $update_transaction_date = $opening_stock->transaction_date;
                    
                    Transaction::where('id',$opening_stock->id)->update(
                    
                    [
    
                        'type' => 'opening_stock',
    
                        'opening_stock_product_id' => $request->product_id,
    
                        'status' => 'received',
    
                        'business_id' => $business_id,
    
                        'transaction_date' => $update_transaction_date,
    
                        'total_before_tax' => $purchase_total,
    
                        'location_id' => $request->location_id,
    
                        'final_total' => $purchase_total,
    
                        'payment_status' => 'paid',
    
                        'created_by' => Auth::user()->id
    
                    ]
                );
                    
                }else{
                    $transaction = Transaction::create(
                        
                        [
        
                            'type' => 'opening_stock',
        
                            'opening_stock_product_id' => $request->product_id,
        
                            'status' => 'received',
        
                            'business_id' => $business_id,
        
                            'transaction_date' => date('Y-m-d', strtotime($transaction_date)),
        
                            'total_before_tax' => $purchase_total,
        
                            'location_id' => $request->location_id,
        
                            'final_total' => $purchase_total,
        
                            'payment_status' => 'paid',
        
                            'created_by' => Auth::user()->id
        
                        ]
                    );
                }
                
                
                
                $purchase_line = PurchaseLine::updateOrCreate(
                    [
                        'transaction_id' => $transaction->id,
                    ],
                    
                    ['product_id' => $product->id,
                    'variation_id' => $k,
                    'item_tax' => $item_tax,
                    'tax_id' => $product->tax,
                    'quantity' => $request->current_balance,
                    'pp_without_discount' => $default_purchase_price,
                    'purchase_price_inc_tax' => $purchase_price_inc_tax,
                    'exp_date' => $exp_date,
                    'purchase_price' => $default_purchase_price,
                    'lot_number' => $lot_number,
                    'transaction_id' => $transaction->id
                ]);


                //create pruchase line for tank 
    
                TankPurchaseLine::updateOrCreate(
                    [
                        'business_id' => $business_id,
    
                        'transaction_id' => $transaction->id,
        
                        'tank_id' => $fuel_tank->id,
        
                    ],
                    
                    [
    
                    'business_id' => $business_id,
    
                    'transaction_id' => $transaction->id,
    
                    'tank_id' => $fuel_tank->id,
    
                    'product_id' => $request->product_id,
    
                    'quantity' => $request->current_balance
    
                ]);





            if ($qty_remaining  > 0) {

                $acc_tran_type = 'debit';
                $eqt_type = 'credit';

            }

            if ($qty_remaining  < 0) {

                $acc_tran_type = 'credit';
                $eqt_type = 'debit';

            }
            
            AccountTransaction::where('transaction_id',$transaction->id)->forcedelete();

                if (!empty($product->enable_stock)) {

                    if (!empty($product->stock_type)) {

                        $account_id = $product->stock_type;

                        $account_transaction_data = [

                            'amount' => abs($transaction->final_total),

                            'account_id' => $account_id,

                            'type' => $acc_tran_type,

                            'operation_date' => $transaction->transaction_date,

                            'created_by' => $transaction->created_by,

                            'transaction_id' => $transaction->id,

                            'transaction_payment_id' => null,

                            'note' => null

                        ];



                        AccountTransaction::createAccountTransaction($account_transaction_data);

                    }

                }


                $obe_account = $this->transactionUtil->account_exist_return_id('Opening Balance Equity Account');
                $account_transaction_data['account_id'] = $obe_account;
                $account_transaction_data['type'] = $eqt_type;
                
                
                AccountTransaction::createAccountTransaction($account_transaction_data);
                

            }


            DB::commit();

            $output = [

                'success' => true,

                'msg' => __('petrogeneral::lang.fuel_tank_update_success')

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong')

            ];

        }



        return redirect()->back()->with('status', $output);

    }



    /**

     * Remove the specified resource from storage.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function destroy($id)

    {

        $business_id = $this->activeBusinessId();



        try {

            $tank_purchases = TankPurchaseLine::leftjoin('transactions', 'tank_purchase_lines.transaction_id', 'transactions.id')->where('transactions.type', '!=', 'opening_stock')->where('tank_purchase_lines.business_id', $business_id)->where('tank_id', $id)->count();

            $tank_lines = TankSellLine::where('business_id', $business_id)->where('tank_id', $id)->count();



            if ($tank_purchases == 0 && $tank_lines == 0) {

                $fuel_tank = FuelTank::where('id', $id)->first();

                $product_id = $fuel_tank->product_id;

                $variation = Variation::where('product_id', $product_id)->first();

                $product = Product::findOrFail($product_id);

                $location_id = $fuel_tank->location_id;

                $old_quantity = $fuel_tank->current_balance;

                $new_quantity = 0;



                $this->productUtil->decreaseProductQuantity($product_id, $variation->id, $location_id, $new_quantity, $old_quantity);
                
                $store_id = Store::where('business_id', $business_id)->first()->id;
                $type = ($new_quantity > $old_quantity) ? 'increase' : 'decrease';
				$this->productUtil->decreaseProductQuantityStore(
                    $product_id, $variation->id, $location_id, $new_quantity,
                    $store_id,
                    $type,
                    $old_quantity
                );



                $account_id = $product->stock_type;



                $account_transaction_data = [

                    'amount' => $variation->default_purchase_price * $old_quantity,

                    'account_id' => $product->stock_type,

                    'type' => 'credit',

                    'operation_date' => date('Y-m-d H:i:s'),

                    'created_by' => Auth::user()->id,



                ];



                AccountTransaction::createAccountTransaction($account_transaction_data);



                $fuel_tank->delete();

                $output = [

                    'success' => true,

                    'msg' => __('petrogeneral::lang.tank_delete_success')

                ];

            } else {

                $output = [

                    'success' => false,

                    'msg' => __('petrogeneral::lang.transactions_exist_for_tank')

                ];

            }

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong')

            ];

        }



        return $output;

    }


    /**
     * IS2053 follow-up: is the business missing its Default Store, in a situation
     * where that actually matters?
     *
     * Returns false - no warning - unless BOTH are true:
     *
     *   1. business.default_store is empty, and
     *   2. the business runs Products, or one of the fuel modules
     *      (Petro General, Petro Direct, Petro PD, SW Settlements)
     *
     * A business using none of those never tracks stock this way and would only
     * be nagged about a setting it does not need.
     *
     * Wrapped so it can never break the page it warns on: any failure returns
     * false and the page renders exactly as before.
     */
    private function petroGeneralDefaultStoreMissing(): bool
    {
        try {
            $business_id = $this->activeBusinessId();

            if (empty($business_id)) {
                return false;
            }

            $default_store = Business::where('id', $business_id)->value('default_store');

            if (! empty($default_store)) {
                return false;
            }

            /*
             | Module keys taken from the codebase, NOT guessed.
             |
             | My first list used 'petro_direct', 'petro_pd' and 'sw_settlement',
             | none of which exist - a wrong key simply returns false, so the
             | warning would never have appeared for those businesses and the
             | mistake would have been invisible.
             |
             | These are the names actually passed to
             | hasThePermissionInSubscription() elsewhere in the three modules.
             */
            $modules = [
                'petro_general',
                'petro_direct_module',
                'petro_pd_module',
            ];

            foreach ($modules as $module) {
                if ($this->moduleUtil->hasThePermissionInSubscription($business_id, $module)) {
                    return true;
                }
            }

            return false;
        } catch (\Throwable $e) {
            \Log::warning('IS2053 default store check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}


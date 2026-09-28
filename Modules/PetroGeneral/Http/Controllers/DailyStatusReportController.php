<?php

namespace Modules\PetroGeneral\Http\Controllers;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Response;
use DB;
use Session;
;
use App\Services\Documents\GlobalMpdf as Mpdf;
use Yajra\DataTables\Facades\DataTables;

use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Product;
use App\Contact;
use App\System;
use App\Transaction;
use App\TransactionSellLine;
use App\PurchaseLine;
use App\Store;
use App\Category;
use App\Utils\Util;
use App\Utils\ProductUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use App\Utils\BusinessUtil;

use Modules\PetroGeneral\Entities\DipReading;
use Modules\PetroGeneral\Entities\Pump;
use Modules\PetroGeneral\Entities\DipResetting;
use Modules\PetroGeneral\Entities\FuelTank;
use Modules\PetroGeneral\Entities\Settlement;
use Modules\PetroGeneral\Entities\MeterSale;
use Modules\PetroGeneral\Entities\OtherSale;
use Modules\PetroGeneral\Entities\PumpOperatorOtherSale;
use Modules\Superadmin\Entities\TankDipChart;
use Modules\Superadmin\Entities\TankDipChartDetail;
use Modules\PetroGeneral\Entities\TankPurchaseLine;
use Modules\PetroGeneral\Entities\TankSellLine;
use Carbon\Carbon;


class DailyStatusReportController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $moduleUtil;
    protected $transactionUtil;
    protected $commonUtil;
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
     * Resolve the business from the authenticated user on the active tenant
     * connection. Session business ids can be stale when an AJAX request first
     * arrives on a tenant domain, so they are fallbacks rather than the source
     * of truth for this report.
     */
    protected function activeBusinessId(Request $request): int
    {
        $candidates = array_values(array_unique(array_filter([
            (int) (optional(auth()->user())->business_id ?? 0),
            (int) ($request->session()->get('user.business_id') ?? 0),
            (int) ($request->session()->get('business.id') ?? 0),
        ])));

        foreach ($candidates as $businessId) {
            if ($businessId > 0 && Business::where('id', $businessId)->exists()) {
                return $businessId;
            }
        }

        abort(403, 'Unable to resolve the active business for Daily Status Report.');
    }

    public function index(Request $request)
    {
        $business_id = $this->activeBusinessId($request);
        $default_start = Carbon::today();
        $default_end = Carbon::today();
        $start_date = !empty($request->get('start_date')) ? date('Y-m-d', strtotime($request->get('start_date'))) : $default_start->format('Y-m-d');
        $end_date = !empty($request->get('end_date')) ? date('Y-m-d', strtotime($request->get('end_date'))) : $default_end->format('Y-m-d');
        if ($request->ajax()) {
            $dip_details = $this->_getDipDetails($business_id, $start_date, $end_date);    
            $business_details = Business::find($business_id);
            $datatable = Datatables::of($dip_details)
                ->editColumn('dip_reading', function($row) use ($business_details) {
                    return $this->productUtil->num_f($row->dip_reading, false, $business_details, true); 
                })
                ->editColumn('qty_liters', function($row) use ($business_details) {
                    return $this->productUtil->num_f($row->fuel_balance_dip_reading, false, $business_details, true); 
                })
                ->editColumn('qty_system', function($row) use ($business_details) {
                    return $this->productUtil->num_f($row->current_qty, false, $business_details, true); 
                })
                ->addColumn('difference', function($row) use ($business_details) {
                    return $this->productUtil->num_f($row->fuel_balance_dip_reading - $row->current_qty, false, $business_details, true); 
                });
            return $datatable->make(true);
        }
        
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('petrogeneral::daily_status_report.index')->with(compact(
            'business_locations'
        ));
    }

    public function getTotalPayments(Request $request) {
        $business_id = $this->activeBusinessId($request);
        $default_start = Carbon::today();
        $default_end = Carbon::today();
        $start_date = !empty($request->get('start_date')) ? date('Y-m-d', strtotime($request->get('start_date'))) : $default_start->format('Y-m-d');
        $end_date = !empty($request->get('end_date')) ? date('Y-m-d', strtotime($request->get('end_date'))) : $default_end->format('Y-m-d');
        if ($request->ajax()) {
            return $this->_getTotalPayments($business_id, $start_date, $end_date);
        }
    }
    public function getPumpSales(Request $request)
    {
        $draw = (int) $request->input('draw', 0);

        if (!$request->ajax()) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $business_id = $this->activeBusinessId($request);
        $default_start = Carbon::today();
        $default_end = Carbon::today();
        $start_date = !empty($request->get('start_date'))
            ? date('Y-m-d', strtotime($request->get('start_date')))
            : $default_start->format('Y-m-d');
        $end_date = !empty($request->get('end_date'))
            ? date('Y-m-d', strtotime($request->get('end_date')))
            : $default_end->format('Y-m-d');

        try {
            $results = $this->_getPumpSales($business_id, $start_date, $end_date);
            $business_details = Business::find($business_id);

            $formatNumber = function ($value) use ($business_details) {
                $value = is_numeric($value) ? (float) $value : 0;

                if (!empty($business_details)) {
                    try {
                        return $this->productUtil->num_f($value, false, $business_details, true);
                    } catch (\Throwable $exception) {
                        // Use a safe local formatter if one old tenant has incomplete business settings.
                    }
                }

                return number_format($value, 2, '.', ',');
            };

            $data = collect($results)->map(function ($row) use ($formatNumber) {
                return [
                    'pump_no' => (string) ($row->pump_no ?? ''),
                    'location_name' => (string) ($row->location_name ?? ''),
                    'previous_meter' => $formatNumber($row->starting_meter ?? 0),
                    'today_meter' => $formatNumber($row->closing_meter ?? 0),
                    'sold_qty' => $formatNumber($row->sold_qty ?? 0),
                    'amount' => $formatNumber($row->amount ?? 0),
                    'banked' => $formatNumber($row->banked ?? 0),
                    'locker' => $formatNumber($row->locker ?? 0),
                    'card' => $formatNumber($row->card ?? 0),
                ];
            })->values();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $data->count(),
                'recordsFiltered' => $data->count(),
                'data' => $data,
            ]);
        } catch (\Throwable $exception) {
            /*
             * IS2281: never let one tenant-specific legacy schema turn this
             * report into a DataTables JavaScript warning. The exact cause is
             * still written to the Laravel log for diagnosis, while the rest of
             * the Daily Status Report remains fully usable.
             */
            Log::error('PetroGeneral Daily Status: getPumpSales failed safely', [
                'business_id' => $business_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'location_id' => $request->get('location_id'),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }
    
    public function getFuelSale(Request $request) 
    {
        if ($request->ajax()) {
            $business_id = $this->activeBusinessId($request);
            $default_start = Carbon::today();
            $default_end = Carbon::today();
            $start_date = !empty($request->get('start_date')) ? date('Y-m-d', strtotime($request->get('start_date'))) : $default_start->format('Y-m-d');
            $end_date = !empty($request->get('end_date')) ? date('Y-m-d', strtotime($request->get('end_date'))) : $default_end->format('Y-m-d');
            $category = Category::where('business_id', $business_id)
                ->where('parent_id', 0)
                ->where('name', 'Fuel')
                ->first();

            $sub_categories = Category::subCategoryOnlyFuel($business_id);
            
            $fuel_sales = $this->_getFuelSales($business_id, $category, $start_date, $end_date);
            
            $business_details = Business::find($business_id);

            $datatable = Datatables::of($fuel_sales)
                ->editColumn('qty', function($row) use ($business_details) {
                    return $this->productUtil->num_f($row->qty, false, $business_details, true); 
                })
                ->editColumn('value', function($row) use ($business_details) {
                    return $this->productUtil->num_f($row->value, false, $business_details, true); 
                })
                ->make(true);
                
            return $datatable;
        }
    }

    public function getLubricantSale(Request $request) 
    {
        if ($request->ajax()) {
            $business_id = $this->activeBusinessId($request);
            $default_start = Carbon::today();
            $default_end = Carbon::today();
            $start_date = !empty($request->get('start_date')) ? date('Y-m-d', strtotime($request->get('start_date'))) : $default_start->format('Y-m-d');
            $end_date = !empty($request->get('end_date')) ? date('Y-m-d', strtotime($request->get('end_date'))) : $default_end->format('Y-m-d');

            $datatable = $this->makeDataTable($business_id, 'Lubricants', 'Lubricants', $start_date, $end_date);
            
            return $datatable->make(true);
        }
    }
    
    public function getOtherSale(Request $request) 
    {
        if ($request->ajax()) {
            $business_id = $this->activeBusinessId($request);
            $default_start = Carbon::today();
            $default_end = Carbon::today();
            $start_date = !empty($request->get('start_date')) ? date('Y-m-d', strtotime($request->get('start_date'))) : $default_start->format('Y-m-d');
            $end_date = !empty($request->get('end_date')) ? date('Y-m-d', strtotime($request->get('end_date'))) : $default_end->format('Y-m-d');
            
            $other_sales = OtherSale::join('settlements', function ($join) {
                                        $join->on('other_sales.settlement_no', '=', 'settlements.id')
                                            ->orOn('other_sales.settlement_no', '=', 'settlements.settlement_no');
                                    })
                                    ->leftjoin('business_locations','business_locations.id','settlements.location_id')
                                    ->join('products','products.id','other_sales.product_id')
                                    ->where('settlements.business_id', $business_id)
                                    ->whereDate('settlements.transaction_date','>=',$start_date)
                                    ->whereDate('settlements.transaction_date','<=',$end_date);

            if (!empty($request->get('location_id'))) {
                $other_sales->where('settlements.location_id', $request->get('location_id'));
            }

            $other_sales = $other_sales->select([
                                        'products.name as product',
                                        'business_locations.name as location_name',
                                        'other_sales.qty as sold_qty',
                                        'other_sales.sub_total as amount',
                                        'other_sales.balance_stock as balance_qty'
                                    ])->get();
        

            $datatable = Datatables::of($other_sales)
            ->editColumn('sold_qty', function ($row) {
                        return $this->productUtil->num_f($row->sold_qty);
                })
            ->addColumn('balance_qty', function ($row) {
                    
                    return $this->productUtil->num_f($row->balance_qty);
                })
                ->editColumn('amount', function ($row) {
                    return $this->productUtil->num_f($row->amount);
                });
            
            return $datatable->make(true);
            
        }
    }
    
    public function getGasSale(Request $request) 
    {
        if ($request->ajax()) {
            $business_id = $this->activeBusinessId($request);
            $default_start = Carbon::today();
            $default_end = Carbon::today();
            $start_date = !empty($request->get('start_date')) ? date('Y-m-d', strtotime($request->get('start_date'))) : $default_start->format('Y-m-d');
            $end_date = !empty($request->get('end_date')) ? date('Y-m-d', strtotime($request->get('end_date'))) : $default_end->format('Y-m-d');

            $datatable = $this->makeDataTable($business_id, 'Gas', '', $start_date, $end_date);
            
            return $datatable->make(true);
        }
    }
    
    public function getCreditSale(Request $request) {
        if ($request->ajax()) {
            $business_id = $this->activeBusinessId($request);
            $default_start = Carbon::today();
            $default_end = Carbon::today();
            $start_date = !empty($request->get('start_date')) ? date('Y-m-d', strtotime($request->get('start_date'))) : $default_start->format('Y-m-d');
            $end_date = !empty($request->get('end_date')) ? date('Y-m-d', strtotime($request->get('end_date'))) : $default_end->format('Y-m-d');
            $settlement = $this->_getCreditSales($business_id, $start_date, $end_date);

            $business_details = Business::find($business_id);

            $datatable = Datatables::of($settlement)
                ->editColumn('amount', function($row) use ($business_details) {
                    return $this->productUtil->num_f($row->amount, false, $business_details, true); 
                })
                ->make(true);
                
            return $datatable;
        }
    }
    
    public function makeDataTable($business_id, $category, $sub_category, $start_date, $end_date) {
        
            $products = $this->_getCategorySales($business_id, $category, $sub_category, $start_date, $end_date);
            $business_details = Business::find($business_id);

            $datatable = Datatables::of($products)
            
                ->removeColumn('enable_stock')

                ->removeColumn('unit')

                ->removeColumn('id')
                
                ->addColumn('purchase_qty', function ($row) use ($start_date, $end_date, $business_details) {

                    $html = "";

                    if ($row->tran_type == 'purchase_return') {

                        $res = $this->productUtil->num_f($row->returned_qty, false, $business_details, true);

                        if ($res > 0.00) {

                            $html = '-' . $res;
                        } else {

                            $html = $res;
                        }
                    } else if ($row->tran_type == 'stock_adjustment') {

                        if ($row->addjust_type == 'increase') {

                            $html = $this->productUtil->num_f($row->stock_qty, false, $business_details, true);
                            
                        } else if ($row->addjust_type == 'decrease') {

                            $res = $this->productUtil->num_f($row->stock_qty, false, $business_details, true);

                            if ($res > 0.00) {

                                $html = '-' . $res;
                            } else {

                                $html = $res;
                            }
                        }
                    } else {

                        $purchase_qty = 0.0;

                        if (!empty($row->purchase_qty)) {

                            $purchase_qty = $row->purchase_qty;
                        }

                        $html = $this->productUtil->num_f($purchase_qty);
                    }

                    return $html;
                })

                ->addColumn('sold_qty', function ($row) use ($business_details) {

                    if ($row->tran_type == 'sell_return') {

                        $res = $this->productUtil->num_f($row->sell_return, false, $business_details, true);

                        if ($res > 0.00) {

                            $html = '-' . $res;

                            return $html;
                        } else {

                            return $res;
                        }
                    } else {

                        $sold_qty = 0.0;

                        if (!empty($row->sold_qty)) {

                            $sold_qty = $row->sold_qty;
                        }

                        return $this->productUtil->num_f($sold_qty, false, $business_details, true);
                    }
                })

                ->addColumn('starting_qty', function ($row) use ($business_details) {

                    // first time new value for product next time previous

                    if (Session::get($row->product_id)) {

                        $balance = str_replace(',', '', Session::get($row->product_id));
                    } else {

                        $balance = 0;

                        $balance = ($row->purchase_qty + $row->sell_return) - ($row->sold_qty

                            + $row->purchase_return);

                        $balance = $balance;

                        Session::put($row->product_id, $balance);
                    }

                    return $this->productUtil->num_f($balance, false, $business_details, true);
                })

                ->addColumn('balance_qty', function ($row) use ($business_details) {

                    $starting_qty = ($row->purchase_qty + $row->sell_return) - ($row->sold_qty + $row->purchase_return);

                    if ($row->tran_type == 'stock_adjustment') {

                        if ($row->addjust_type == 'increase') {

                            $row->purchase_qty = $this->productUtil->num_uf($row->stock_qty);
                        } else if ($row->addjust_type == 'decrease') {

                            $res = $this->productUtil->num_uf($row->stock_qty);

                            $row->purchase_return = $res;
                        }
                    }

                    $balance = 0;

                    if (Session::get($row->product_id)) {

                        $oldbalance = str_replace(',', '', Session::get($row->product_id));

                        $balance = ($oldbalance + $row->purchase_qty + $row->sell_return)

                            - ($row->sold_qty + $row->purchase_return);

                        $balance = $balance;

                        Session::put($row->product_id, $balance);
                    } else {

                        $balance = ($starting_qty + $row->purchase_qty + $row->sell_return)

                            - ($row->sold_qty + $row->purchase_return);

                        $balance = $balance;

                        Session::put($row->product_id, $balance);
                    }

                    return $this->productUtil->num_f($balance, false, $business_details, true);
                })
                ->editColumn('amount', function ($row) use ($business_details) {
                    return $this->productUtil->num_f($row->amount, false, $business_details, true);
                });

                return $datatable;    
    }
    
    public function printReport(Request $request) {

        $business_id = $this->activeBusinessId($request);
        $default_start = Carbon::today();
        $default_end = Carbon::today();
        $start_date = !empty($request->get('start_date')) ? date('Y-m-d', strtotime($request->get('start_date'))) : $default_start->format('Y-m-d');
        $end_date = !empty($request->get('end_date')) ? date('Y-m-d', strtotime($request->get('end_date'))) : $default_end->format('Y-m-d');

        $dip_sales = $this->_getDipDetails($business_id, $start_date, $end_date);
        
        $pump_sales = $this->_getPumpSales($business_id, $start_date, $end_date);
        
        $category = Category::where('business_id', $business_id)
                ->where('parent_id', 0)
                ->where('name', 'Fuel')
                ->first();
                
        $sub_categories = Category::subCategoryOnlyFuel($business_id);
        
        $fuel_sales = $this->_getFuelSales($business_id, $category , $start_date, $end_date);
        
        $lubricant_sales = $this->_getCategorySales($business_id, 'Lubricants', 'Lubricants', $start_date, $end_date);
        
        $other_sales = $this->_getCategorySales($business_id, 'Others', 'Others', $start_date, $end_date);
        
        $gas_sales = $this->_getCategorySales($business_id, 'Gas', 'Gas', $start_date, $end_date);
        
        $credit_sales = $this->_getCreditSales($business_id, $start_date, $end_date);
        
        // $total_payments = $this->_getTotalPayments($business_id, $start_date, $end_date);
        $total_payments = (object) $this->_getTotalPayments($business_id, $start_date, $end_date);


        return view('petrogeneral::daily_status_report.print')->with(compact(
                'dip_sales',
                'pump_sales',
                'fuel_sales',
                'lubricant_sales',
                'other_sales',
                'gas_sales',
                'credit_sales',
                'total_payments',
                'start_date',
                'end_date'
            ));
    }
    
    public function downloadPdf( Request $request){
        $html = $request->get('html');
        $mpdf = new Mpdf();
        $mpdf->SetFont('Calibri', '', 12);
        $mpdf->WriteHTML($html);
        
        $directoryPath = config('constants.reports_directory');
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }
        
        $filename = Str::random(40).".pdf";
        $filePath = config('constants.reports_directory').$filename;
        $mpdf->Output($filePath, 'F');
        
        return response()->json(['path' => url("reports/".$filename)]);

    }
    
    public function _getDipDetails($business_id, $start_date, $end_date)
    {
        $fuel_tanks = FuelTank::leftjoin('business_locations','business_locations.id','fuel_tanks.location_id')->select([
                                    'fuel_tanks.fuel_tank_number as tank_no',
                                    'business_locations.name as location_name',
                                    DB::raw('(SELECT dip_reading FROM dip_readings as DR WHERE DR.tank_id = fuel_tanks.id AND STR_TO_DATE(DR.date_and_time, "%m/%d/%Y") <= "'.$end_date.'" ORDER BY DR.id DESC LIMIT 1) as dip_reading'),
                                    
                                    DB::raw('(SELECT fuel_balance_dip_reading FROM dip_readings as DR WHERE DR.tank_id = fuel_tanks.id AND STR_TO_DATE(DR.date_and_time, "%m/%d/%Y") <= "'.$end_date.'" ORDER BY DR.id DESC LIMIT 1) as fuel_balance_dip_reading'),
                                    
                                    DB::raw('(SELECT current_qty FROM dip_readings as DR WHERE DR.tank_id = fuel_tanks.id AND STR_TO_DATE(DR.date_and_time, "%m/%d/%Y") <= "'.$end_date.'" ORDER BY DR.id DESC LIMIT 1) as current_qty'),
                                ])
                                 ->where('fuel_tanks.business_id', $business_id)
                                ->groupBy('fuel_tanks.id');
            if(!empty(request()->location_id)){
                $fuel_tanks->where('fuel_tanks.location_id',request()->location_id);
            }
                                

        return $fuel_tanks->get();;
    }
    
    /**
     * Build the Pump Sales section for the Daily Status Report.
     *
     * IS2278:
     * - Some tenant databases are on slightly different Petro schemas.
     * - settlement_no is varchar in meter/payment tables while settlements.id
     *   is numeric and settlements.settlement_no can use a different collation.
     * - Direct OR joins between those columns can therefore raise SQL/collation
     *   errors and DataTables only shows the generic "Ajax error" warning.
     *
     * Keep this report backward compatible by:
     * 1) only reading optional columns/tables when they exist;
     * 2) comparing settlement references through one explicit utf8mb4
     *    collation so both ID-style and settlement-number-style references work;
     * 3) treating optional payment totals as zero rather than breaking the
     *    whole report when an older tenant does not have one optional table.
     */
    /**
     * Build the Pump Sales section without cross-collation SQL joins.
     *
     * IS2281 / IS2278:
     * Historical tenant databases store meter_sales.settlement_no and payment
     * settlement references in two valid forms: the numeric settlements.id or
     * the textual settlements.settlement_no. Joining those columns directly is
     * fragile because older databases use different column charsets/collations.
     *
     * This implementation resolves settlement references in PHP and then uses
     * simple indexed WHERE IN queries. It therefore works with both historical
     * formats without CONVERT/COLLATE expressions and without changing any data.
     */
    public function _getPumpSales($business_id, $start_date, $end_date)
    {
        if (!Schema::hasTable('pumps')) {
            return [];
        }

        $pumpQuery = Pump::leftJoin(
            'business_locations',
            'business_locations.id',
            '=',
            'pumps.location_id'
        )
            ->where('pumps.business_id', $business_id)
            ->select([
                'pumps.pump_no',
                'pumps.id',
                'business_locations.name as location_name',
            ]);

        if (Schema::hasColumn('pumps', 'is_other_sales_pump')) {
            $pumpQuery->addSelect('pumps.is_other_sales_pump');
        } else {
            $pumpQuery->addSelect(DB::raw('0 as is_other_sales_pump'));
        }

        if (!empty(request()->location_id)) {
            $pumpQuery->where('pumps.location_id', request()->location_id);
        }

        $pumps = $pumpQuery->get();

        if ($pumps->isEmpty()) {
            return [];
        }

        foreach ($pumps as $pump) {
            $pump->starting_meter = 0;
            $pump->closing_meter = 0;
            $pump->amount = 0;
            $pump->sold_qty = 0;
            $pump->banked = 0;
            $pump->locker = 0;
            $pump->card = 0;
        }

        /*
         * Other-sales pumps retain the existing Petro General behaviour. They
         * are not meter pumps, so meter figures remain zero and their sales are
         * sourced from pump_operator_other_sales.
         */
        foreach ($pumps as $pump) {
            if ((int) ($pump->is_other_sales_pump ?? 0) !== 1) {
                continue;
            }

            if (!$this->dailyStatusCanReadOtherSalesPump()) {
                continue;
            }

            try {
                $otherSaleQuery = PumpOperatorOtherSale::join(
                    'pump_operator_assignments',
                    'pump_operator_assignments.shift_id',
                    '=',
                    'pump_operator_other_sales.shift_id'
                )
                    ->join(
                        'pump_operators',
                        'pump_operator_assignments.pump_operator_id',
                        '=',
                        'pump_operators.id'
                    )
                    ->where('pump_operators.business_id', $business_id)
                    ->whereDate('pump_operator_other_sales.created_at', '>=', $start_date)
                    ->whereDate('pump_operator_other_sales.created_at', '<=', $end_date);

                if (
                    !empty(request()->location_id)
                    && Schema::hasColumn('pump_operators', 'location_id')
                ) {
                    $otherSaleQuery->where('pump_operators.location_id', request()->location_id);
                }

                $otherSale = $otherSaleQuery
                    ->select([
                        DB::raw('COALESCE(SUM(pump_operator_other_sales.sub_total), 0) as amount'),
                        DB::raw('COALESCE(SUM(pump_operator_other_sales.qty), 0) as sold_qty'),
                    ])
                    ->first();

                $pump->amount = (float) ($otherSale->amount ?? 0);
                $pump->sold_qty = (float) ($otherSale->sold_qty ?? 0);
            } catch (\Throwable $exception) {
                Log::warning('PetroGeneral Daily Status: other-sales pump query skipped', [
                    'business_id' => $business_id,
                    'pump_id' => $pump->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if (!$this->dailyStatusCanReadMeterSales()) {
            return $pumps->all();
        }

        $meterPumpIds = $pumps
            ->filter(function ($pump) {
                return (int) ($pump->is_other_sales_pump ?? 0) !== 1;
            })
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();

        if (empty($meterPumpIds)) {
            return $pumps->all();
        }

        /* Previous and closing meter readings are found by walking settlements
         * from newest to oldest until each pump has a reading. This avoids a
         * large historical JOIN and stops as soon as all pumps are resolved. */
        $previousMeters = $this->dailyStatusLatestMeterByPump(
            $business_id,
            $meterPumpIds,
            $start_date,
            false
        );

        $closingMeters = $this->dailyStatusLatestMeterByPump(
            $business_id,
            $meterPumpIds,
            $end_date,
            true
        );

        foreach ($pumps as $pump) {
            $pumpId = (int) $pump->id;
            if (array_key_exists($pumpId, $previousMeters)) {
                $pump->starting_meter = (float) $previousMeters[$pumpId];
            }
            if (array_key_exists($pumpId, $closingMeters)) {
                $pump->closing_meter = (float) $closingMeters[$pumpId];
            }
        }

        $periodSettlements = Settlement::where('business_id', $business_id)
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date)
            ->get(['id', 'settlement_no']);

        $periodContext = $this->dailyStatusBuildSettlementReferenceContext($periodSettlements);

        if (empty($periodContext['references'])) {
            return $pumps->all();
        }

        $periodMeterQuery = MeterSale::query()
            ->whereIn('pump_id', $meterPumpIds)
            ->select([
                'id',
                'pump_id',
                'settlement_no',
                'sub_total',
                'qty',
            ]);

        $this->dailyStatusApplyReferenceFilter(
            $periodMeterQuery,
            'settlement_no',
            $periodContext['references']
        );

        $periodMeterSales = $periodMeterQuery->get();
        $salesByPump = [];
        $settlementsByPump = [];

        foreach ($periodMeterSales as $sale) {
            $pumpId = (int) $sale->pump_id;
            if (!isset($salesByPump[$pumpId])) {
                $salesByPump[$pumpId] = ['amount' => 0.0, 'sold_qty' => 0.0];
            }

            $salesByPump[$pumpId]['amount'] += (float) ($sale->sub_total ?? 0);
            $salesByPump[$pumpId]['sold_qty'] += (float) ($sale->qty ?? 0);

            $reference = $this->dailyStatusNormalizeReference($sale->settlement_no);
            $canonical = $periodContext['reference_to_canonical'][$reference] ?? null;

            if ($canonical !== null) {
                $settlementsByPump[$pumpId][$canonical] = true;
            }
        }

        $bankedBySettlement = $this->dailyStatusPaymentTotalsBySettlement(
            'settlement_cash_deposits',
            $business_id,
            $periodContext
        );
        $lockerBySettlement = $this->dailyStatusPaymentTotalsBySettlement(
            'settlement_cash_payments',
            $business_id,
            $periodContext
        );
        $cardBySettlement = $this->dailyStatusPaymentTotalsBySettlement(
            'settlement_card_payments',
            $business_id,
            $periodContext
        );

        foreach ($pumps as $pump) {
            $pumpId = (int) $pump->id;

            if (isset($salesByPump[$pumpId])) {
                $pump->amount = $salesByPump[$pumpId]['amount'];
                $pump->sold_qty = $salesByPump[$pumpId]['sold_qty'];
            }

            if (empty($settlementsByPump[$pumpId])) {
                continue;
            }

            foreach (array_keys($settlementsByPump[$pumpId]) as $canonical) {
                $pump->banked += (float) ($bankedBySettlement[$canonical] ?? 0);
                $pump->locker += (float) ($lockerBySettlement[$canonical] ?? 0);
                $pump->card += (float) ($cardBySettlement[$canonical] ?? 0);
            }
        }

        return $pumps->all();
    }

    /**
     * Return the latest closing meter before/on a cut-off date for every pump.
     * The settlement lookup is chunked, so large historical databases do not
     * build one huge IN list and do not require a collation-sensitive JOIN.
     */
    private function dailyStatusLatestMeterByPump(
        $businessId,
        array $pumpIds,
        $cutoffDate,
        $inclusive
    ) {
        $remaining = array_fill_keys(array_map('intval', $pumpIds), true);
        $result = [];
        $page = 1;
        $perPage = 250;

        while (!empty($remaining)) {
            $settlementQuery = Settlement::where('business_id', $businessId);

            if ($inclusive) {
                $settlementQuery->whereDate('transaction_date', '<=', $cutoffDate);
            } else {
                $settlementQuery->whereDate('transaction_date', '<', $cutoffDate);
            }

            $settlements = $settlementQuery
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->forPage($page, $perPage)
                ->get(['id', 'settlement_no']);

            if ($settlements->isEmpty()) {
                break;
            }

            $context = $this->dailyStatusBuildSettlementReferenceContext($settlements);
            if (empty($context['references'])) {
                $page++;
                continue;
            }

            $meterQuery = MeterSale::query()
                ->whereIn('pump_id', array_keys($remaining))
                ->select(['id', 'pump_id', 'closing_meter', 'settlement_no']);

            $this->dailyStatusApplyReferenceFilter(
                $meterQuery,
                'settlement_no',
                $context['references']
            );

            $sales = $meterQuery->orderBy('id', 'desc')->get();
            $bestByPump = [];

            foreach ($sales as $sale) {
                $pumpId = (int) $sale->pump_id;
                if (!isset($remaining[$pumpId])) {
                    continue;
                }

                $reference = $this->dailyStatusNormalizeReference($sale->settlement_no);
                $canonical = $context['reference_to_canonical'][$reference] ?? null;
                if ($canonical === null) {
                    continue;
                }

                $rank = $context['canonical_rank'][$canonical] ?? PHP_INT_MAX;
                $saleId = (int) ($sale->id ?? 0);

                if (
                    !isset($bestByPump[$pumpId])
                    || $rank < $bestByPump[$pumpId]['rank']
                    || ($rank === $bestByPump[$pumpId]['rank'] && $saleId > $bestByPump[$pumpId]['sale_id'])
                ) {
                    $bestByPump[$pumpId] = [
                        'rank' => $rank,
                        'sale_id' => $saleId,
                        'closing_meter' => (float) ($sale->closing_meter ?? 0),
                    ];
                }
            }

            foreach ($bestByPump as $pumpId => $best) {
                $result[$pumpId] = $best['closing_meter'];
                unset($remaining[$pumpId]);
            }

            if ($settlements->count() < $perPage) {
                break;
            }

            $page++;
        }

        return $result;
    }

    private function dailyStatusBuildSettlementReferenceContext($settlements)
    {
        $references = [];
        $referenceToCanonical = [];
        $canonicalToReferences = [];
        $canonicalRank = [];
        $rank = 0;

        foreach ($settlements as $settlement) {
            $canonical = (string) $settlement->id;
            $canonicalRank[$canonical] = $rank++;
            $candidateReferences = [
                $this->dailyStatusNormalizeReference($settlement->id),
                $this->dailyStatusNormalizeReference($settlement->settlement_no),
            ];

            foreach (array_unique($candidateReferences) as $reference) {
                if ($reference === '') {
                    continue;
                }

                $references[$reference] = $reference;
                $referenceToCanonical[$reference] = $canonical;
                $canonicalToReferences[$canonical][$reference] = $reference;
            }
        }

        return [
            'references' => array_values($references),
            'reference_to_canonical' => $referenceToCanonical,
            'canonical_to_references' => $canonicalToReferences,
            'canonical_rank' => $canonicalRank,
        ];
    }

    private function dailyStatusNormalizeReference($value)
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * Add a safely chunked WHERE IN predicate to a query builder.
     */
    private function dailyStatusApplyReferenceFilter($query, $column, array $references)
    {
        $references = array_values(array_unique(array_filter(
            array_map([$this, 'dailyStatusNormalizeReference'], $references),
            function ($value) {
                return $value !== '';
            }
        )));

        if (empty($references)) {
            $query->whereRaw('1 = 0');
            return $query;
        }

        $query->where(function ($subQuery) use ($column, $references) {
            foreach (array_chunk($references, 500) as $index => $chunk) {
                if ($index === 0) {
                    $subQuery->whereIn($column, $chunk);
                } else {
                    $subQuery->orWhereIn($column, $chunk);
                }
            }
        });

        return $query;
    }

    private function dailyStatusPaymentTotalsBySettlement(
        $paymentTable,
        $businessId,
        array $context
    ) {
        if (!$this->dailyStatusHasColumns($paymentTable, ['settlement_no', 'amount'])) {
            return [];
        }

        $paymentTable = preg_replace('/[^A-Za-z0-9_]/', '', (string) $paymentTable);
        if ($paymentTable === '' || empty($context['references'])) {
            return [];
        }

        try {
            $query = DB::table($paymentTable)
                ->select([
                    'settlement_no',
                    DB::raw('COALESCE(SUM(amount), 0) as total_amount'),
                ])
                ->groupBy('settlement_no');

            $this->dailyStatusApplyReferenceFilter(
                $query,
                'settlement_no',
                $context['references']
            );

            if (Schema::hasColumn($paymentTable, 'business_id')) {
                $query->where('business_id', $businessId);
            }

            $totals = [];
            foreach ($query->get() as $row) {
                $reference = $this->dailyStatusNormalizeReference($row->settlement_no);
                $canonical = $context['reference_to_canonical'][$reference] ?? null;
                if ($canonical === null) {
                    continue;
                }

                $totals[$canonical] = ($totals[$canonical] ?? 0)
                    + (float) ($row->total_amount ?? 0);
            }

            return $totals;
        } catch (\Throwable $exception) {
            Log::warning('PetroGeneral Daily Status: payment total skipped', [
                'business_id' => $businessId,
                'payment_table' => $paymentTable,
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function dailyStatusCanReadMeterSales()
    {
        return $this->dailyStatusHasColumns('meter_sales', [
            'id',
            'settlement_no',
            'pump_id',
            'closing_meter',
            'sub_total',
            'qty',
        ])
            && $this->dailyStatusHasColumns('settlements', [
                'id',
                'settlement_no',
                'business_id',
                'transaction_date',
            ]);
    }

    private function dailyStatusCanReadOtherSalesPump()
    {
        return $this->dailyStatusHasColumns('pump_operator_other_sales', [
            'shift_id',
            'sub_total',
            'qty',
            'created_at',
        ])
            && $this->dailyStatusHasColumns('pump_operator_assignments', [
                'shift_id',
                'pump_operator_id',
            ])
            && $this->dailyStatusHasColumns('pump_operators', [
                'id',
                'business_id',
            ]);
    }

    private function dailyStatusHasColumns($table, array $columns)
    {
        static $capabilityCache = [];

        $cacheKey = $table . ':' . implode(',', $columns);

        if (array_key_exists($cacheKey, $capabilityCache)) {
            return $capabilityCache[$cacheKey];
        }

        try {
            if (!Schema::hasTable($table)) {
                return $capabilityCache[$cacheKey] = false;
            }

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    return $capabilityCache[$cacheKey] = false;
                }
            }

            return $capabilityCache[$cacheKey] = true;
        } catch (\Throwable $exception) {
            Log::warning('PetroGeneral Daily Status: schema capability check failed', [
                'table' => $table,
                'message' => $exception->getMessage(),
            ]);

            return $capabilityCache[$cacheKey] = false;
        }
    }


    public function _getFuelSales($business_id, $category, $start_date, $end_date) {
        if (empty($category)) {
            return collect();
        }
        
        $query = TransactionSellLine::leftjoin(

                'transactions as t',

                'transaction_sell_lines.transaction_id',

                '=',

                't.id'

            )

                ->leftjoin(

                    'variations as v',

                    'transaction_sell_lines.variation_id',

                    '=',

                    'v.id'

                )
                
                ->leftjoin('business_locations','business_locations.id','t.location_id')

                ->leftjoin('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')

                ->leftjoin('contacts as c', 't.contact_id', '=', 'c.id')
                
                ->leftjoin('products as p', 'pv.product_id', '=', 'p.id')
                
                ->leftJoin('categories as c2', 'p.sub_category_id', '=', 'c2.id')

                ->leftjoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')

                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')

                ->where('t.business_id', $business_id)

                ->where('t.type', 'sell')

                ->where('t.status', 'final')

                ->where(function ($q) {

                    $q->where('t.sub_type', '!=', 'credit_sale')->orWhereNull('t.sub_type');
                })
                ->where('p.category_id', $category->id)

                ->select(

                    'c2.name as name',
                    
                    'business_locations.name as location_name',

                    DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as qty'),

                    DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as value')

                )

                ->groupBy(['p.sub_category_id']);
                
            if (!empty($start_date) && !empty($end_date)) {

                $query->whereBetween('transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            }
            
            if(!empty(request()->location_id)){
                $query->where('t.location_id',request()->location_id);
            }
            
            
            return $query->get();
    }
    
    public function _getCategorySales($business_id, $category_name, $sub_category_name, $start_date, $end_date) {
        
        $category = Category::where('business_id', $business_id)
        ->where('parent_id', 0)
        ->where('name', $category_name)
        ->first();
        
        $query = Transaction::leftjoin('purchase_lines as pl', 'transactions.id', 'pl.transaction_id')
        
                ->leftjoin('business_locations','business_locations.id','transactions.location_id')

                ->leftjoin('purchase_lines as PRL', 'transactions.return_parent_id', 'PRL.transaction_id')

                ->leftjoin('transaction_sell_lines as tsl', function ($join) {

                    $join->on('transactions.id', 'tsl.transaction_id');
                })

                ->leftjoin('transaction_sell_lines as SRL', 'transactions.return_parent_id', 'SRL.transaction_id')

                ->leftjoin('stock_adjustment_lines', 'transactions.id', 'stock_adjustment_lines.transaction_id')

                ->leftjoin('products as p', function ($join) {

                    $join->on('pl.product_id', 'p.id')

                        ->orOn('tsl.product_id', 'p.id')

                        ->orOn('stock_adjustment_lines.product_id', 'p.id')

                        ->orOn('PRL.product_id', 'p.id')

                        ->orOn('SRL.product_id', 'p.id');
                })

                ->leftjoin('variations', 'p.id', 'variations.product_id')

                ->leftjoin('units', 'p.unit_id', '=', 'units.id')

                ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')

                ->leftjoin('variation_store_details as vsd', 'variations.id', '=', 'vsd.variation_id')

                ->leftjoin('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')

                ->where('transactions.sub_type', '!=', 'credit_sale')

                ->where('p.business_id', $business_id)->withTrashed();
                
                
            if (!empty($start_date) && !empty($end_date)) {

                $query->whereDate('transaction_date', '>=', $start_date)

                    ->whereDate('transaction_date', '<=', $end_date);
            }
            
            if (!empty($category)) {
                
                $query->where('p.category_id', $category->id);
                
                $sub_category = Category::where('business_id', $business_id)
                            ->where('parent_id', $category->id)
                            ->where('name', $sub_category_name)
                            ->select(['name', 'id'])
                            ->first();
            }
            
            if (!empty($sub_category)) {
                
                $query->where('p.sub_category_id', $sub_category->id);
                
            }

            // Daily Status lubricant section must contain lubricant products only.
            // Fuel products are identified operationally by their assignment to pumps/tanks;
            // exclude them even if legacy product/category data was misclassified.
            if (strcasecmp((string) $category_name, 'Lubricants') === 0) {
                $fuel_product_ids = Pump::where('business_id', $business_id)
                    ->whereNotNull('product_id')
                    ->pluck('product_id')
                    ->merge(
                        FuelTank::where('business_id', $business_id)
                            ->whereNotNull('product_id')
                            ->pluck('product_id')
                    )
                    ->filter()
                    ->unique()
                    ->values();

                if ($fuel_product_ids->isNotEmpty()) {
                    $query->whereNotIn('p.id', $fuel_product_ids->all());
                }
            }

            if(!empty(request()->location_id)){
                $query->where('transactions.location_id',request()->location_id);
            }
                
            $products = $query->select(
                
                DB::raw('SUM(IF(transactions.deleted_at IS NULL, tsl.quantity, -1 * tsl.quantity) ) as sold_qty'),

                DB::raw('SUM(SRL.quantity_returned) as sell_return'),

                DB::raw('SUM(IF(transactions.deleted_at IS NULL, PRL.quantity, -1* PRL.quantity) ) as purchase_qty'),

                DB::raw('SUM(PRL.quantity_returned ) as returned_qty'),
                
                DB::raw('SUM(PRL.quantity_returned) as purchase_return'),

                DB::raw('SUM(stock_adjustment_lines.quantity ) as stock_qty'),

                DB::raw('stock_adjustment_lines.type as addjust_type'),
                

                'p.name as product',

                'p.id as product_id',

                'p.enable_stock as enable_stock',

                'pv.name as product_variation',

                'variations.name as variation_name',

                'pl.purchase_price_inc_tax as purchase_price',

                'pl.bonus_qty as bonus_qty',
                
                DB::raw('SUM((tsl.quantity - tsl.quantity_returned) * tsl.unit_price_inc_tax) as amount'),

                'transactions.transaction_date as transaction_date',

                'transactions.id as transaction_id',
                'business_locations.name as location_name'
            )

                ->orderBy('transactions.id', 'asc')

                ->groupBy(['transactions.id', 'p.id']);
            return $products->get();
    }
    
    public function _getCreditSales($business_id, $start_date, $end_date) {
        // settlement_credit_sale_payments.settlement_no exists in two legacy formats:
        // the numeric settlement id and the printable settlement number.  Daily Status
        // must recognise both, otherwise credit sales entered during settlement disappear.
        $settlement = Settlement::where('settlements.business_id', $business_id)
            ->leftJoin('settlement_credit_sale_payments as cs', function ($join) {
                $join->on('cs.settlement_no', '=', 'settlements.id')
                    ->orOn('cs.settlement_no', '=', 'settlements.settlement_no');
            })
            ->leftjoin('contacts', 'contacts.id', 'cs.customer_id')
            ->leftjoin('business_locations','business_locations.id','settlements.location_id')
            ->where(function ($query) use ($business_id) {
                $query->whereNull('cs.business_id')
                    ->orWhere('cs.business_id', $business_id);
            })
            ->select(
                'contacts.name as customer',
                'business_locations.name as location_name',
                DB::raw('SUM(COALESCE(cs.amount, (COALESCE(cs.qty, 0) * COALESCE(cs.price, 0)), 0)) as amount')
            )
            ->whereNotNull('contacts.name')->where('contacts.name', '<>', '')
            ->groupBy(['contacts.id', 'business_locations.id', 'business_locations.name', 'contacts.name']);

       if (!empty($start_date) && !empty($end_date)) {
            $settlement->whereDate('settlements.transaction_date', '>=', $start_date);
            $settlement->whereDate('settlements.transaction_date', '<=', $end_date);
        }

        if(!empty(request()->location_id)){
            $settlement->where('settlements.location_id',request()->location_id);
        }

        return $settlement->get();
    }

    /**
     * Sum one settlement payment table while supporting both historic settlement_no
     * formats (settlements.id and settlements.settlement_no).
     */
    protected function settlementPaymentSum($business_id, $table, $amountColumn, $start_date, $end_date)
    {
        $query = Settlement::join($table, function ($join) use ($table) {
                $join->on($table . '.settlement_no', '=', 'settlements.id')
                    ->orOn($table . '.settlement_no', '=', 'settlements.settlement_no');
            })
            ->where('settlements.business_id', $business_id)
            ->whereDate('settlements.transaction_date', '>=', $start_date)
            ->whereDate('settlements.transaction_date', '<=', $end_date);

        if (!empty(request()->location_id)) {
            $query->where('settlements.location_id', request()->location_id);
        }

        return $query->sum($table . '.' . $amountColumn);
    }

     public function _getTotalPayments($business_id, $start_date, $end_date) {
        $banked = $this->settlementPaymentSum($business_id, 'settlement_cash_deposits', 'amount', $start_date, $end_date);
        $locker = $this->settlementPaymentSum($business_id, 'settlement_cash_payments', 'amount', $start_date, $end_date);
        $card = $this->settlementPaymentSum($business_id, 'settlement_card_payments', 'amount', $start_date, $end_date);
        $credit = $this->settlementPaymentSum($business_id, 'settlement_credit_sale_payments', 'amount', $start_date, $end_date);

        $meter_query = MeterSale::join('settlements', function ($join) {
                $join->on('meter_sales.settlement_no', '=', 'settlements.id')
                    ->orOn('meter_sales.settlement_no', '=', 'settlements.settlement_no');
            })
            ->where('settlements.business_id', $business_id)
            ->whereDate('settlements.transaction_date','>=',$start_date)
            ->whereDate('settlements.transaction_date','<=',$end_date);

        $other_query = OtherSale::join('settlements', function ($join) {
                $join->on('other_sales.settlement_no', '=', 'settlements.id')
                    ->orOn('other_sales.settlement_no', '=', 'settlements.settlement_no');
            })
            ->where('settlements.business_id', $business_id)
            ->whereDate('settlements.transaction_date','>=',$start_date)
            ->whereDate('settlements.transaction_date','<=',$end_date);

        if (!empty(request()->location_id)) {
            $meter_query->where('settlements.location_id', request()->location_id);
            $other_query->where('settlements.location_id', request()->location_id);
        }

        $meter_sales = $meter_query->sum('meter_sales.sub_total');
        $other_sales = $other_query->sum('other_sales.sub_total');

        return array(
            'total_card_payments' => $card,
            'total_cash_payments' => $locker,
            'total_cash_deposits' => $banked,
            'total_credit_sale_payments' => $credit,
            'total_sales' => $meter_sales + $other_sales
        );
    }

    public function store(Request $request)
    {
    }
    /**
     * Show the specified resource.
     * @return Response
     */
    
    public function show()
    {
        return view('petrogeneral::show');
    }
    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    
    public function edit($id)
    {
      

    }
    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    
    public function update(Request $request, $id)
    {
      
    }
    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    
    public function destroy()
    {
    }
    /**
     * Get tank product details
     * @return Response
     */
    
}

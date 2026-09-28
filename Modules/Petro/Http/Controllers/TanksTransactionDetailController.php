<?php
namespace Modules\Petro\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Product;
use App\Transaction;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\TankTransfer;
use Yajra\DataTables\Facades\DataTables;

class TanksTransactionDetailController extends Controller
{

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

    public function index(Request $request)
    {
        \Modules\Petro\Support\PetroDebug::info('TanksTransactionDetailController index called');
        DB::enableQueryLog();
        set_time_limit(0);

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $purchase_query = $this->_getPurchaseQuery($business_id);
            $sell_query     = $this->_getSellQuery($business_id);
            $transfer_in    = $this->_getTransferInQuery($business_id);
            $transfer_out   = $this->_getTransferOutQuery($business_id);
            $main_query = $sell_query->unionAll($purchase_query)->unionAll($transfer_in)->unionAll($transfer_out);
            $business_details = Business::find(session('user.business_id'));
            $rows = DB::query()
                ->fromSub($main_query, 'tank_transactions')
                ->orderBy('fuel_tank_id')
                ->orderBy('created_at')
                ->orderBy('source_id')
                ->get();

            $testingCache = [];
            $ledgerRows = collect();

            foreach ($rows->groupBy('fuel_tank_id') as $tankRows) {
                $tankRows = $tankRows->sortBy([
                    ['created_at', 'asc'],
                    ['source_id', 'asc'],
                ])->values();

                $firstRow = $tankRows->first();
                $runningBalance = $firstRow
                    ? $this->getTankBalanceBeforeDateTime($firstRow->fuel_tank_id, $firstRow->created_at)
                    : 0.0;

                foreach ($tankRows as $row) {
                    $purchaseQty = (float) ($row->purchase_qty ?? 0);
                    $soldQty = abs((float) ($row->sold_qty ?? 0));
                    $testingQty = $this->getTankTestingQtyForRow($row, $testingCache);

                    /*
                     * ZIP 038 / Opening Stock Starting Qty Fix:
                     * Opening Stock rows are created when fuel tanks are created.
                     * The added opening stock quantity must show in the Starting Qty column,
                     * not as 0.00.
                     */
                    $startingQty = $runningBalance;
                    if ($row->type == 'opening_stock' && $purchaseQty > 0) {
                        $startingQty = $purchaseQty;
                    }

                    // Testing Qty is displayed separately and must not affect the running tank balance.
                    $balanceQty = $runningBalance + $purchaseQty - $soldQty;

                    $row->opening_balance_qty_raw = $startingQty;
                    $row->testing_qty_raw = $testingQty;
                    $row->purchase_qty_raw = $purchaseQty;
                    $row->sold_qty_raw = $soldQty;
                    $row->balance_qty_raw = $balanceQty;
                    $row->purchase_order_no = $row->invoice_no ?? $row->ref_no;
                    $row->ref_no = $this->formatTankTransactionType($row);

                    $ledgerRows->push($row);
                    $runningBalance = $balanceQty;
                }
            }

            $ledgerRows = $ledgerRows->sort(function ($a, $b) {
                if ($a->created_at == $b->created_at) {
                    return ($b->source_id ?? 0) <=> ($a->source_id ?? 0);
                }

                return strcmp((string) $b->created_at, (string) $a->created_at);
            })->values();

            $tanks_transaction_details = Datatables::of($ledgerRows)
                ->editColumn('created_at', function ($row) {
                    $date = $row->created_at;
                    return trim(str_ireplace('DIP', '', $this->productUtil->format_date($date, true)));
                })
                ->editColumn('transaction_date', function ($row) {
                    $date = !empty($row->transaction_date) ? $row->transaction_date : (!empty($row->created_at) ? $row->created_at : null);
                    return $date ? $this->productUtil->format_date($date) : '';
                })
                ->addColumn('opening_balance_qty', function ($row) use ($business_details) {
                    return $this->productUtil->num_f($row->opening_balance_qty_raw ?? 0, false, $business_details, true);
                })
                ->addColumn('testing_qty', function ($row) {
                    $raw_value = (float) ($row->testing_qty_raw ?? 0);
                    $formatted = number_format($raw_value, 3);
                    return "<span class='testing_qty_transaction' data-orig-value='{$raw_value}'>{$formatted}</span>";
                })
                ->editColumn('purchase_qty', function ($row) use ($business_details) {
                    $raw_value = (float) ($row->purchase_qty_raw ?? 0);
                    $formatted = $this->productUtil->num_f($raw_value, false, $business_details, true);
                    return "<span class='purchase_qty_transaction' data-orig-value='{$raw_value}'>{$formatted}</span>";
                })
                ->editColumn('sold_qty', function ($row) use ($business_details) {
                    $raw_value = (float) ($row->sold_qty_raw ?? 0);
                    $formatted = $this->productUtil->num_f($raw_value, false, $business_details, true);
                    return "<span class='sold_qty_transaction' data-orig-value='{$raw_value}'>{$formatted}</span>";
                })
                ->addColumn('balance_qty', function ($row) use ($business_details) {
                    return $this->productUtil->num_f($row->balance_qty_raw ?? 0, false, $business_details, true);
                });

            return $tanks_transaction_details->rawColumns([
                'transaction_date', 'balance_qty', 'ref_no', 'purchase_order_no', 'sold_qty', 'purchase_qty', 'testing_qty',
            ])->make(true);
        }

        return view('petro::tanks_transaction_details.index');

    }

    protected function getTankTestingQtyForRow($row, array &$testingCache): float
    {
        try {
            if (empty($row->invoice_no) || empty($row->fuel_tank_id)) {
                return 0.0;
            }

            $cacheKey = $row->invoice_no . ':' . $row->fuel_tank_id;
            if (array_key_exists($cacheKey, $testingCache)) {
                return $testingCache[$cacheKey];
            }

            $settlement = Settlement::where('settlement_no', $row->invoice_no)
                ->orWhere('id', $row->invoice_no)
                ->select('id', 'settlement_no')
                ->first();

            if (empty($settlement)) {
                return $testingCache[$cacheKey] = 0.0;
            }

            /*
             * ZIP 040:
             * Testing litres can come from:
             * 1. meter_sales.testing_qty
             * 2. pump_operator_meter_sales.testing_qty entered in Dashboard / Close Pump
             */
            $meterSalesTesting = (float) DB::table('meter_sales')
                ->join('pumps', 'meter_sales.pump_id', '=', 'pumps.id')
                ->where(function ($query) use ($settlement) {
                    $query->where('meter_sales.settlement_no', $settlement->id)
                        ->orWhere('meter_sales.settlement_no', $settlement->settlement_no);
                })
                ->where('pumps.fuel_tank_id', $row->fuel_tank_id)
                ->sum('meter_sales.testing_qty');

            $operatorTesting = 0.0;

            try {
                $operatorTesting = (float) DB::table('pump_operator_meter_sales as poms')
                    ->join('pump_operator_meter_sale_details as pomsd', 'poms.id', '=', 'pomsd.sale_id')
                    ->join('pumps', 'pomsd.pump_id', '=', 'pumps.id')
                    ->where(function ($query) use ($settlement) {
                        $query->where('poms.settlement_no', $settlement->id)
                            ->orWhere('poms.settlement_no', $settlement->settlement_no);
                    })
                    ->where('pumps.fuel_tank_id', $row->fuel_tank_id)
                    ->sum('poms.testing_qty');
            } catch (\Throwable $operatorException) {
                \Log::warning('Tank transaction operator testing qty lookup skipped', [
                    'message' => $operatorException->getMessage(),
                    'settlement_no' => $row->invoice_no ?? null,
                    'fuel_tank_id' => $row->fuel_tank_id ?? null,
                ]);
            }

            // Avoid double counting if both tables have the same testing qty.
            return $testingCache[$cacheKey] = max($meterSalesTesting, $operatorTesting);
        } catch (\Throwable $e) {
            \Log::warning('Tank transaction testing qty lookup skipped', [
                'message' => $e->getMessage(),
                'invoice_no' => $row->invoice_no ?? null,
                'fuel_tank_id' => $row->fuel_tank_id ?? null,
            ]);

            return $testingCache[($row->invoice_no ?? '') . ':' . ($row->fuel_tank_id ?? '')] = 0.0;
        }
    }

    protected function getTankBalanceBeforeDateTime($tankId, $beforeDateTime): float
    {
        $purchaseQty = (float) DB::table('tank_purchase_lines')
            ->join('transactions', 'transactions.id', '=', 'tank_purchase_lines.transaction_id')
            ->where('tank_purchase_lines.tank_id', $tankId)
            ->whereNull('tank_purchase_lines.new_deleted_at')
            ->where('transactions.created_at', '<', $beforeDateTime)
            ->where('transactions.type', '!=', '_deleted_purchase')
            ->sum('tank_purchase_lines.quantity');

        $soldQty = (float) DB::table('tank_sell_lines')
            ->join('transactions', 'transactions.id', '=', 'tank_sell_lines.transaction_id')
            ->where('tank_sell_lines.tank_id', $tankId)
            ->where('transactions.created_at', '<', $beforeDateTime)
            ->sum('tank_sell_lines.quantity');

        $transferInQty = (float) DB::table('tank_transfers')
            ->where('to_tank', $tankId)
            ->where('created_at', '<', $beforeDateTime)
            ->sum('quantity');

        $transferOutQty = (float) DB::table('tank_transfers')
            ->where('from_tank', $tankId)
            ->where('created_at', '<', $beforeDateTime)
            ->sum('quantity');

        return $purchaseQty - $soldQty + $transferInQty - $transferOutQty;
    }

    protected function formatTankTransactionType($row)
    {
        if ($row->type == 'sell') {
            return __('petro::lang.settlment');
        }

        if ($row->type == 'transfer_in' || $row->type == 'transfer_out') {
            return __('petro::lang.tank_transfer');
        }

        if ($row->type == 'opening_stock') {
            return __('petro::lang.opening_stock');
        }

        if ($row->type == 'purchase') {
            return __('petro::lang.purchase_reference_no') . ': ' . $row->ref_no;
        }

        if ($row->type == 'stock_adjustment') {
            return "<span class='text-danger'>" . __('petro::lang.dip_reset') . "</span>";
        }

        if ($row->type == '_deleted_purchase') {
            return "<span class='text-danger'>" . __('petro::lang.deleted_purchase') . ': ' . $row->ref_no . "</span>";
        }

        return $row->ref_no;
    }

    public function _getSellQuery($business_id)
    {
        $query = Transaction::leftjoin('tank_sell_lines', 'transactions.id', 'tank_sell_lines.transaction_id')
            ->join('fuel_tanks', function ($join) {
                $join->on('tank_sell_lines.tank_id', 'fuel_tanks.id');
            })
            ->leftjoin('products', 'fuel_tanks.product_id', 'products.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->where('transactions.business_id', $business_id)
            ->whereNotIn('transactions.type', ['purchase', '_deleted_purchase'])
            ->select(
                'transactions.ref_no',
                'transactions.invoice_no',
                'transactions.transaction_date',
                'transactions.created_at',
                'transactions.id as source_id',
                'transactions.type',
                DB::raw("COALESCE(transactions.sub_type, '') as sub_type"),
                DB::raw("COALESCE(transactions.stock_adjustment_type, '') as stock_adjustment_type"),
                DB::raw('0 as purchase_qty'),
                DB::raw("
                    SUM(
                        CASE
                            WHEN transactions.type = 'stock_adjustment'
                                 AND COALESCE(transactions.sub_type, '') = 'dip_resetting'
                                 AND COALESCE(transactions.stock_adjustment_type, '') = 'decrease'
                                THEN tank_sell_lines.quantity
                            WHEN transactions.type != 'stock_adjustment'
                                THEN tank_sell_lines.quantity
                            ELSE 0
                        END
                    ) as sold_qty
                "),
                'fuel_tanks.id as fuel_tank_id',
                'business_locations.name as location_name',
                'products.name as product_name',
                'fuel_tanks.fuel_tank_number'
            )
            ->groupBy(['transactions.id', 'products.id', 'fuel_tanks.id'])
            ->orderby('transactions.id');

        if (! empty(request()->start_date) && ! empty(request()->end_date)) {
            $query->whereDate('transactions.transaction_date', '>=', request()->start_date);
            $query->whereDate('transactions.transaction_date', '<=', request()->end_date);
        }

        if (! empty(request()->location_id)) {
            $query->where('transactions.location_id', request()->location_id);
        }

        if (! empty(request()->fuel_tank_number)) {
            $query->where('fuel_tanks.fuel_tank_number', request()->fuel_tank_number);
        }

        if (! empty(request()->product_id)) {
            $query->where('fuel_tanks.product_id', request()->product_id);
        }

        if (! empty(request()->settlement_id)) {
            $query->where('transactions.invoice_no', request()->settlement_id);
        }

        if (! empty(request()->purchase_no)) {
            $query->where('transactions.ref_no', request()->purchase_no);
        }

        return $query;
    }

    public function _getPurchaseQuery($business_id)
    {
        $query = Transaction::leftjoin('tank_purchase_lines', function ($join) {
            $join->on('transactions.id', 'tank_purchase_lines.transaction_id')->where('tank_purchase_lines.quantity', '!=', 0);
        })
            ->join('fuel_tanks', function ($join) {
                $join->on('tank_purchase_lines.tank_id', 'fuel_tanks.id');
            })
            ->leftjoin('products', 'fuel_tanks.product_id', 'products.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->where('transactions.business_id', $business_id)
            ->select(
                'transactions.ref_no',
                'transactions.invoice_no',
                'transactions.transaction_date',
                'transactions.created_at',
                'transactions.id as source_id',
                'transactions.type',
                DB::raw("COALESCE(transactions.sub_type, '') as sub_type"),
                DB::raw("COALESCE(transactions.stock_adjustment_type, '') as stock_adjustment_type"),
                DB::raw("
                    SUM(
                        CASE 
                            WHEN transactions.type IN ('purchase', 'opening_stock') THEN tank_purchase_lines.quantity
                            WHEN transactions.type = 'stock_adjustment'
                                 AND COALESCE(transactions.sub_type, '') = 'dip_resetting'
                                 AND COALESCE(transactions.stock_adjustment_type, '') = 'increase'
                                THEN tank_purchase_lines.quantity
                            ELSE 0
                        END
                    ) as purchase_qty
                "),
                DB::raw("
                    SUM(
                        CASE 
                            WHEN transactions.type = '_deleted_purchase' THEN tank_purchase_lines.quantity
                            ELSE 0
                        END
                    ) as sold_qty
                "),
                'fuel_tanks.id as fuel_tank_id',
                'business_locations.name as location_name',
                'products.name as product_name',
                'fuel_tanks.fuel_tank_number'
            )
            ->groupBy(['transactions.id', 'products.id', 'fuel_tanks.id']);

        if (! empty(request()->start_date) && ! empty(request()->end_date)) {
            $query->whereDate('transactions.transaction_date', '>=', request()->start_date);
            $query->whereDate('transactions.transaction_date', '<=', request()->end_date);
        }

        if (! empty(request()->location_id)) {
            $query->where('transactions.location_id', request()->location_id);
        }

        if (! empty(request()->fuel_tank_number)) {
            $query->where('fuel_tanks.fuel_tank_number', request()->fuel_tank_number);
        }

        if (! empty(request()->product_id)) {
            $query->where('fuel_tanks.product_id', request()->product_id);
        }

        if (! empty(request()->settlement_id)) {
            $query->where('transactions.invoice_no', request()->settlement_id);
        }

        if (! empty(request()->purchase_no)) {
            $query->where('transactions.ref_no', request()->purchase_no);
        }

        return $query;
    }

    public function _getTransferInQuery($business_id)
    {
        $query = TankTransfer::join('fuel_tanks', function ($join) {
            $join->on('tank_transfers.to_tank', 'fuel_tanks.id');
        })
            ->leftjoin('products', 'fuel_tanks.product_id', 'products.id')

            ->leftjoin('business_locations', 'fuel_tanks.location_id', 'business_locations.id')

            ->where('tank_transfers.business_id', $business_id)

            ->select(

                DB::raw('"" as ref_no'),

                'tank_transfers.transfer_no as invoice_no',

                'tank_transfers.date as transaction_date',

                'tank_transfers.created_at',

                'tank_transfers.id as source_id',

                DB::raw('"transfer_in" as type'),

                DB::raw('"" as sub_type'),

                DB::raw('"" as stock_adjustment_type'),

                'tank_transfers.quantity as purchase_qty',

                DB::raw('0 as sold_qty'),

                'fuel_tanks.id as fuel_tank_id',

                'business_locations.name as location_name',

                'products.name as product_name',

                'fuel_tanks.fuel_tank_number'

            )

            ->groupBy(['tank_transfers.id', 'products.id', 'fuel_tanks.id']);

        if (! empty(request()->start_date) && ! empty(request()->end_date)) {
            $query->whereDate('tank_transfers.date', '>=', request()->start_date);
            $query->whereDate('tank_transfers.date', '<=', request()->end_date);
        }

        if (! empty(request()->location_id)) {
            $query->where('fuel_tanks.location_id', request()->location_id);
        }

        if (! empty(request()->fuel_tank_number)) {
            $query->where('fuel_tanks.fuel_tank_number', request()->fuel_tank_number);
        }

        if (! empty(request()->product_id)) {
            $query->where('fuel_tanks.product_id', request()->product_id);
        }

        return $query;
    }

    public function _getTransferOutQuery($business_id)
    {
        $query = TankTransfer::join('fuel_tanks', function ($join) {
            $join->on('tank_transfers.from_tank', 'fuel_tanks.id');
        })
            ->leftjoin('products', 'fuel_tanks.product_id', 'products.id')

            ->leftjoin('business_locations', 'fuel_tanks.location_id', 'business_locations.id')

            ->where('tank_transfers.business_id', $business_id)

            ->select(

                DB::raw('"" as ref_no'),

                'tank_transfers.transfer_no as invoice_no',

                'tank_transfers.date as transaction_date',

                'tank_transfers.created_at',

                'tank_transfers.id as source_id',

                DB::raw('"transfer_out" as type'),

                DB::raw('"" as sub_type'),

                DB::raw('"" as stock_adjustment_type'),

                DB::raw('0 as purchase_qty'),

                'tank_transfers.quantity as sold_qty',

                'fuel_tanks.id as fuel_tank_id',

                'business_locations.name as location_name',

                'products.name as product_name',

                'fuel_tanks.fuel_tank_number'

            )

            ->groupBy(['tank_transfers.id', 'products.id', 'fuel_tanks.id']);

        if (! empty(request()->start_date) && ! empty(request()->end_date)) {
            $query->whereDate('tank_transfers.date', '>=', request()->start_date);
            $query->whereDate('tank_transfers.date', '<=', request()->end_date);
        }

        if (! empty(request()->location_id)) {
            $query->where('fuel_tanks.location_id', request()->location_id);
        }

        if (! empty(request()->fuel_tank_number)) {
            $query->where('fuel_tanks.fuel_tank_number', request()->fuel_tank_number);
        }

        if (! empty(request()->product_id)) {
            $query->where('fuel_tanks.product_id', request()->product_id);
        }

        return $query;
    }

    /**

     * Show tank transaction summary

     *

     * @return \Illuminate\Http\Response

     */

    public function generateDateArray($startDate, $endDate)
    {
        $dates = [];

        $start = \Carbon::parse($startDate);
        $end   = \Carbon::parse($endDate);

        // Loop through each day and add it to the array
        for ($date = $start; $date->lte($end); $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        return $dates;
    }

    public function tankTransactionSummary()
    {

        set_time_limit(0);

        if (request()->ajax()) {

            $this->settleTransactionSummary();

            $business_id = request()->session()->get('user.business_id');

            $business_details = Business::find($business_id);

            if (request()->ajax()) {

                $start_date = request()->start_date;

                \Modules\Petro\Support\PetroDebug::debug('start date' . $start_date);

                $end_date = request()->end_date;

                \Modules\Petro\Support\PetroDebug::debug('end date' . $end_date);

                $dates = $this->generateDateArray($start_date, $end_date);

                $query = DB::table('fuel_tanks')
                    ->select('fuel_tanks.*',
                        'products.name as product_name', 'products.id as product_id')
                    ->leftJoin('products', 'fuel_tanks.product_id', '=', 'products.id')
                    ->leftjoin('business_locations', 'business_locations.id', 'fuel_tanks.location_id')
                    ->where('fuel_tanks.business_id', $business_id)->select('fuel_tanks.*', 'business_locations.name as location_name', 'products.name as product_name');

                if (! empty(request()->location_id)) {

                    $query->where('fuel_tanks.location_id', request()->location_id);

                }

                if (! empty(request()->fuel_tank_number)) {
                    $query->where('fuel_tanks.fuel_tank_number', request()->fuel_tank_number);
                }

                if (! empty(request()->product_id)) {
                    $query->where('fuel_tanks.product_id', request()->product_id);

                }

                $tanks = [];

                foreach ($dates as $date) {
                    foreach ($query->get() as $tank) {
                        $date_obj = Carbon::parse($date);

                        if ($date_obj->startOfDay()->isAfter(now())) {
                            continue;
                        }

                        $tankTransactionDate = Carbon::parse($tank->transaction_date);

                        if ($tankTransactionDate->greaterThan($date_obj)) {
                            continue;
                        }

                        $tank->start_date = $date_obj->copy()->startOfDay();
                        $tank->end_date   = $date_obj->copy()->endOfDay();

                        // IS1467: Starting Quantity must match the Tank Transaction Details opening balance.
                        // The older util method can return 0 for current tenant/day rows after recent changes.
                        // Use the same ledger balance logic used by Tank Transaction Details, with a safe fallback.
                        $tank->starting_qty = $this->getTankBalanceBeforeDateTime($tank->id, $tank->start_date);
                        if ((float) $tank->starting_qty == 0.0) {
                            $tank->starting_qty = (float) ($this->transactionUtil->getTankBalanceByDate($tank->id, $tank->start_date) ?? 0);
                        }
                        $tank->purchase_qty = $this->transactionUtil->__totalPurchaseAndTransferIn(
                            $business_id,
                            $tank->start_date,
                            $tank->end_date,
                            $tank->id
                        );
                        $tank->testing_qty  = $this->_getTankTestingQty(
                            $business_id,
                            $tank->id,
                            $tank->start_date,
                            $tank->end_date
                        );
                        $raw_sold = $this->transactionUtil->__totalSellAndTransferOut(
                            $business_id,
                            $tank->start_date,
                            $tank->end_date,
                            $tank->id
                        );
                        $tank->sold_qty = max(0, $raw_sold);
                        // IS1460-002: Keep Summary calculation aligned with Tank Transaction Details.
                        // Testing quantity must be shown in its own column and must not be added
                        // into purchase quantity or balance. Sold Qty already represents stock out.
                        $tank->balance_qty = $tank->starting_qty + $tank->purchase_qty - $tank->sold_qty;

                        $tanks[] = $tank;

                        \Modules\Petro\Support\PetroDebug::info("Tank {$tank->fuel_tank_number} purchase: {$tank->purchase_qty}, testing: {$tank->testing_qty}, balance: {$tank->balance_qty}, end_date: {$tank->end_date}");

                    }
                }

                usort($tanks, function ($a, $b) {
                    return $b->end_date <=> $a->end_date;
                });
                $tanks_transaction_details = Datatables::of($tanks)

                    ->addColumn('transaction_date', function ($row) use ($business_id) {

                        // if($row->transaction_date){
                        //     $this->productUtil->format_date($row->transaction_date);
                        // }

                        // // if no transaction, then return date for opening stock
                        // $opening_Stock = Transaction::where('type','opening_stock')->where('business_id',$business_id)->where('opening_stock_product_id',$row->product_id)->first();

                        // if(!empty($opening_Stock) && $opening_Stock->transaction_date){
                        //     return $this->productUtil->format_date($opening_Stock->transaction_date);
                        // }

                        // return "";
                        return $this->productUtil->format_date($row->end_date);

                    })

                    ->editColumn('created_at', function ($row) {

                        // return $this->productUtil->format_date($row->created_at,true);
                        return $this->productUtil->format_date($row->end_date);

                    })

                    ->editColumn('starting_qty', function ($row) use ($business_details) {
                        // return $this->productUtil->num_f($row->starting_qty, false, $business_details, true);
                        return number_format($row->starting_qty, 3, '.', ',');

                    })

                    ->editColumn('sold_qty', function ($row) use ($business_details) {
                        $raw_value = $row->sold_qty;
                        $value     = number_format($raw_value, 3, '.', ',');
                        return '<span class="sold_qty" data-orig-value="' . $raw_value . '">' . $value . '</span>';
                    })

                    ->editColumn('purchase_qty', function ($row) use ($business_details) {
                        $raw_value = (float) $row->purchase_qty;
                        $value     = number_format($raw_value, 3, '.', ',');
                        return '<span class="purchase_qty" data-orig-value="' . $raw_value . '">' . $value . '</span>';
                    })

                    ->addColumn('testing_qty', function ($row) {
                        $raw_value = (float) ($row->testing_qty ?? 0);
                        $value     = number_format($raw_value, 3, '.', ',');
                        return '<span class="testing_qty" data-orig-value="' . $raw_value . '">' . $value . '</span>';
                    })

                    ->editColumn('balance_qty', function ($row) {
                        // Use the balance_qty that was already calculated with stock adjustments
                        $balance = $row->balance_qty;
                        $value   = number_format($balance, 3, '.', ',');
                        return '<span class="balance_qty" data-orig-value="' . $balance . '">' . $value . '</span>';
                    });

                return $tanks_transaction_details->rawColumns(['balance_qty', 'opening_stock', 'total_stock', 'sold_qty', 'purchase_qty', 'testing_qty'])

                    ->make(true);

            }

        }

        return view('petro::tanks_transaction_details.tank_transactions_summary');

    }

    public function settleTransactionSummary()
    {

        $business_id = request()->session()->get('user.business_id');

        $business_details = Business::find($business_id);

        $business_location_id = BusinessLocation::where('business_id', $business_id)->get()->first()->id;

        $fuel_tanks = DB::select("SELECT * FROM `fuel_tanks` WHERE  business_id = $business_id");

        $start_date = date('Y-m-d', strtotime($business_details->start_date));

        $end_date = date('2020-12-31');

        while (strtotime($start_date) <= strtotime($end_date)) {

            foreach ($fuel_tanks as $fuel_tank_id) {

                $query = Transaction::leftjoin('tank_purchase_lines', 'transactions.id', 'tank_purchase_lines.transaction_id')

                    ->leftjoin('tank_sell_lines', 'transactions.id', 'tank_sell_lines.transaction_id')

                    ->join('fuel_tanks', function ($join) {

                        $join->on('tank_sell_lines.tank_id', 'fuel_tanks.id');

                    })

                    ->leftjoin('products', 'fuel_tanks.product_id', 'products.id')

                    ->where('fuel_tanks.business_id', $business_id)

                    ->Where('fuel_tanks.id', $fuel_tank_id->id)

                    ->whereDate('transactions.transaction_date', '>=', $start_date)

                    ->whereDate('transactions.transaction_date', '<=', $start_date)

                    ->where('transactions.type', '!=', 'opening_stock')

                    ->select(

                        'fuel_tanks.fuel_tank_number',

                        'fuel_tanks.id as fuel_tank_id',

                        'transactions.transaction_date',

                        'transactions.created_at',

                        DB::raw('SUM(tank_sell_lines.quantity) as sell_qty'),

                        DB::raw("fuel_tanks.current_balance as total_stock"),

                        'products.name as product_name'

                    )->orderBy('transactions.transaction_date')

                    ->groupBy('fuel_tanks.id', 'transactions.transaction_date');

                if (! ($query->count() > 0)) {

                    $transaction = new Transaction();

                    $transaction->business_id = $business_id;

                    $transaction->location_id = $business_location_id;

                    $transaction->type = 'sell';

                    $transaction->status = 'final';

                    $transaction->transaction_date = $start_date;

                    $transaction->created_by = 2;

                    $transaction->save();

                    $transaction_id = $transaction->id;

                    DB::insert('insert into tank_sell_lines (business_id, transaction_id,tank_id,product_id,quantity) values (?, ?,?, ?,?)', [$business_id, $transaction_id, $fuel_tank_id->id, $fuel_tank_id->product_id, '0.00000']);

                    echo $start_date;

                }

            }

            $start_date = strtotime("1 day", strtotime($start_date));

            $start_date = date('Y-m-d', $start_date);

        }

    }

    private function _getTankTestingQty($business_id, $tank_id, $start_date, $end_date)
    {
        $start = Carbon::parse($start_date)->startOfDay();
        $end   = Carbon::parse($end_date)->endOfDay();

        $meter_sales_testing_qty = (float) DB::table('meter_sales')
            ->join('pumps', 'meter_sales.pump_id', '=', 'pumps.id')
            ->where('pumps.fuel_tank_id', $tank_id)
            ->when(
                \Modules\Petro\Support\SchemaCapabilityCache::hasColumn('meter_sales', 'business_id'),
                fn($q) => $q->where('meter_sales.business_id', $business_id)
            )
            ->whereBetween('meter_sales.created_at', [$start, $end])
            ->sum('meter_sales.testing_qty');

        $operator_testing_qty = 0.0;

        try {
            if (\Modules\Petro\Support\SchemaCapabilityCache::hasTable('pump_operator_meter_sales') && \Modules\Petro\Support\SchemaCapabilityCache::hasTable('pump_operator_meter_sale_details')) {
                $operator_testing_qty = (float) DB::table('pump_operator_meter_sales as poms')
                    ->join('pump_operator_meter_sale_details as pomsd', 'poms.id', '=', 'pomsd.sale_id')
                    ->join('pumps', 'pomsd.pump_id', '=', 'pumps.id')
                    ->where('pumps.fuel_tank_id', $tank_id)
                    ->when(
                        \Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pump_operator_meter_sales', 'business_id'),
                        fn($q) => $q->where('poms.business_id', $business_id)
                    )
                    ->whereBetween('poms.date_time', [$start, $end])
                    ->sum('poms.testing_qty');
            }
        } catch (\Throwable $e) {
            Log::warning('Tank summary operator testing qty lookup skipped', [
                'message' => $e->getMessage(),
                'tank_id' => $tank_id,
                'from' => (string) $start,
                'to' => (string) $end,
            ]);
        }

        // Use the larger source to avoid double counting when meter_sales and
        // pump_operator_meter_sales carry the same testing litres.
        $testing_qty = max($meter_sales_testing_qty, $operator_testing_qty);

        \Modules\Petro\Support\PetroDebug::info("Testing Qty => Tank: {$tank_id}, From: {$start}, To: {$end}, Meter: {$meter_sales_testing_qty}, Operator: {$operator_testing_qty}, Qty: {$testing_qty}");

        return $testing_qty;
    }

}

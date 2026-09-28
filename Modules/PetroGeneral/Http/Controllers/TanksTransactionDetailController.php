<?php
namespace Modules\PetroGeneral\Http\Controllers;

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
use Modules\PetroGeneral\Entities\Settlement;
use Modules\PetroGeneral\Entities\TankTransfer;
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
        Log::info('TanksTransactionDetailController index called');
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
                ->orderBy('transaction_date')
                ->orderBy('created_at')
                ->orderBy('source_id')
                ->get();

            $testingCache = [];
            $ledgerRows = collect();

            foreach ($rows->groupBy('fuel_tank_id') as $tankRows) {
                /*
                 * IS2074 - Tank Transaction Details / Starting Qty carry-forward.
                 *
                 * The tank ledger must follow the ACTUAL transaction sequence, not the
                 * time the record happened to be entered into the system.  Back-dated
                 * purchases / settlements / transfers are therefore ordered first by
                 * transaction_date, with created_at and source_id used only as stable
                 * tie-breakers for transactions on the same date/time.
                 */
                $tankRows = $tankRows->sort(function ($a, $b) {
                    $aDate = $this->getTankLedgerEffectiveDate($a);
                    $bDate = $this->getTankLedgerEffectiveDate($b);

                    if ($aDate !== $bDate) {
                        return strcmp($aDate, $bDate);
                    }

                    $aCreated = (string) ($a->created_at ?? '');
                    $bCreated = (string) ($b->created_at ?? '');

                    if ($aCreated !== $bCreated) {
                        return strcmp($aCreated, $bCreated);
                    }

                    return ((int) ($a->source_id ?? 0)) <=> ((int) ($b->source_id ?? 0));
                })->values();

                $firstRow = $tankRows->first();
                $runningBalance = $firstRow
                    ? $this->getTankBalanceBeforeLedgerRow($firstRow)
                    : 0.0;

                foreach ($tankRows as $row) {
                    $purchaseQty = (float) ($row->purchase_qty ?? 0);
                    $soldQty = abs((float) ($row->sold_qty ?? 0));
                    $testingQty = $this->getTankTestingQtyForRow($row, $testingCache);

                    /*
                     * IS2074: Starting Qty has one rule only:
                     *     CURRENT Starting Qty = PREVIOUS ledger entry Balance Qty.
                     *
                     * Do not special-case opening stock, purchases, settlements,
                     * adjustments or transfers here.  The current movement belongs in
                     * Purchase/Transferred In or Sold/Transferred Out and affects the
                     * CURRENT row's Balance Qty, never its Starting Qty.
                     */
                    $startingQty = $runningBalance;

                    // Testing Qty is displayed separately and must not affect the running tank balance.
                    $balanceQty = $startingQty + $purchaseQty - $soldQty;

                    $row->opening_balance_qty_raw = $startingQty;
                    $row->testing_qty_raw = $testingQty;
                    /*
                     * Opening stock is a ledger movement, not a purchase receipt.
                     * It therefore changes this row's Balance Qty but remains hidden
                     * from Purchase Qty / Transferred In.  IS2074 intentionally does
                     * NOT copy opening stock into Starting Qty: Starting Qty must stay
                     * equal to the preceding ledger entry's Balance Qty.
                     */
                    $row->purchase_qty_raw = $row->type == 'opening_stock' ? 0.0 : $purchaseQty;
                    $row->sold_qty_raw = $soldQty;
                    $row->balance_qty_raw = $balanceQty;
                    $row->purchase_order_no = $row->invoice_no ?? $row->ref_no;
                    $row->ref_no = $this->formatTankTransactionType($row);

                    $ledgerRows->push($row);
                    $runningBalance = $balanceQty;
                }
            }

            $ledgerRows = $ledgerRows->sort(function ($a, $b) {
                $aDate = $this->getTankLedgerEffectiveDate($a);
                $bDate = $this->getTankLedgerEffectiveDate($b);

                if ($aDate !== $bDate) {
                    return strcmp($bDate, $aDate);
                }

                $aCreated = (string) ($a->created_at ?? '');
                $bCreated = (string) ($b->created_at ?? '');

                if ($aCreated !== $bCreated) {
                    return strcmp($bCreated, $aCreated);
                }

                return ((int) ($b->source_id ?? 0)) <=> ((int) ($a->source_id ?? 0));
            })->values();

            $tanks_transaction_details = Datatables::of($ledgerRows)
                ->editColumn('created_at', function ($row) {
                    return $this->productUtil->format_date($row->created_at, true);
                })
                /*
                 * MA-002 (IS-1910): Transaction Date rendered blank on every row.
                 *
                 * It used the Blade-string form:
                 *     ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                 *
                 * Inside {{ }} that leading @ is PHP's error-suppression
                 * operator, not a Blade directive - so if anything at all goes
                 * wrong in there the cell silently renders empty and nothing is
                 * logged. A column that fails without saying so is the reason
                 * this took a ticket to find.
                 *
                 * Replaced with a closure that formats the value explicitly and
                 * falls back to the row's own timestamp when the joined
                 * transaction has no date - the four union branches feeding this
                 * table do not all carry one.
                 */
                ->editColumn('transaction_date', function ($row) {
                    $value = $row->transaction_date ?? null;

                    if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                        $value = $row->created_at ?? null;
                    }

                    if (empty($value)) {
                        /*
                         * MA-002: show a dash rather than an empty cell.
                         *
                         * A blank cell is indistinguishable from a broken column,
                         * which is what made this take three attempts to pin down.
                         * If BOTH transaction_date and created_at are empty on a
                         * row, that is a data question and not a formatting one -
                         * this makes it visible instead of silent.
                         *
                         * Logged ONCE per request, not per row, so a wide date
                         * range cannot flood the log.
                         */
                        if (! isset($GLOBALS['ma002_txdate_logged'])) {
                            $GLOBALS['ma002_txdate_logged'] = true;
                            \Log::warning('MA-002: tank transaction row has neither transaction_date nor created_at', [
                                'row' => json_encode($row),
                            ]);
                        }

                        return '<span class="text-muted">-</span>';
                    }

                    try {
                        return \Carbon\Carbon::parse($value)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        return '<span class="text-muted">-</span>';
                    }
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

        return view('petrogeneral::tanks_transaction_details.index');

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

            /*
             * IS1959 #2: resolve the reference to EVERY settlement that carries
             * it - not just the first one found.
             *
             * This used ->first(). Settlement numbers are not unique in the
             * data: ST1 exists twice (id 1 and id 2). ->first() with no
             * ordering returned id 1, while the meter sale sat on settlement_no
             * 2, so the sum below matched nothing and the Testing column showed
             * 0.000 for a tank that had 4.000 litres recorded against it.
             *
             * Matching every settlement with that number is correct whichever
             * way the duplicate arose: if it is legitimate (one per shift or
             * location) the testing is summed across both, and if it is a data
             * error the real figure is still found instead of silently reading
             * as zero.
             *
             * The old orWhere('id', $row->invoice_no) is kept for references
             * that are already numeric, but scoped inside its own group so it
             * cannot widen the settlement_no match.
             */
            $settlements = Settlement::where(function ($query) use ($row) {
                    $query->where('settlement_no', $row->invoice_no);

                    if (is_numeric($row->invoice_no)) {
                        $query->orWhere('id', $row->invoice_no);
                    }
                })
                ->select('id', 'settlement_no')
                ->get();

            if ($settlements->isEmpty()) {
                return $testingCache[$cacheKey] = 0.0;
            }

            $settlementIds = $settlements->pluck('id')->all();
            $settlementNos = $settlements->pluck('settlement_no')->filter()->unique()->all();

            /*
             * ZIP 040:
             * Testing litres can come from:
             * 1. meter_sales.testing_qty
             * 2. pump_operator_meter_sales.testing_qty entered in Dashboard / Close Pump
             */
            // IS1959 #2: meter_sales.settlement_no holds either the numeric id or
            // the text code depending on which screen wrote it, so both are matched.
            $meterSalesTesting = (float) DB::table('meter_sales')
                ->join('pumps', 'meter_sales.pump_id', '=', 'pumps.id')
                ->where(function ($query) use ($settlementIds, $settlementNos) {
                    $query->whereIn('meter_sales.settlement_no', $settlementIds);

                    if (!empty($settlementNos)) {
                        $query->orWhereIn('meter_sales.settlement_no', $settlementNos);
                    }
                })
                ->where('pumps.fuel_tank_id', $row->fuel_tank_id)
                ->sum('meter_sales.testing_qty');

            $operatorTesting = 0.0;

            try {
                /*
                 * IS1959 #2: count each operator sale's testing ONCE.
                 *
                 * This used to JOIN pump_operator_meter_sale_details - one row per
                 * pump - and then SUM the PARENT's poms.testing_qty. The join
                 * multiplies the parent row, so the same testing figure was added
                 * once per pump detail: a sale of 3 litres testing across two pumps
                 * on the tank reported 6, three pumps reported 9, and so on. The
                 * max() below then preferred that inflated number over the correct
                 * meter_sales figure, so the Testing column showed a quantity far
                 * larger than what was entered.
                 *
                 * whereExists filters to sales that touch this tank without
                 * duplicating the parent row, so each sale contributes its testing
                 * exactly once.
                 */
                $operatorTesting = (float) DB::table('pump_operator_meter_sales as poms')
                    ->where(function ($query) use ($settlementIds, $settlementNos) {
                        $query->whereIn('poms.settlement_no', $settlementIds);

                        if (!empty($settlementNos)) {
                            $query->orWhereIn('poms.settlement_no', $settlementNos);
                        }
                    })
                    ->whereExists(function ($query) use ($row) {
                        $query->select(DB::raw(1))
                            ->from('pump_operator_meter_sale_details as pomsd')
                            ->join('pumps', 'pomsd.pump_id', '=', 'pumps.id')
                            ->whereColumn('pomsd.sale_id', 'poms.id')
                            ->where('pumps.fuel_tank_id', $row->fuel_tank_id);
                    })
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

    /**
     * Return the ledger-effective transaction date/time for one tank row.
     *
     * transaction_date is authoritative. created_at is only a legacy fallback for
     * records that genuinely have no transaction date.
     */
    protected function getTankLedgerEffectiveDate($row): string
    {
        $value = $row->transaction_date ?? null;

        if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            $value = $row->created_at ?? null;
        }

        if (empty($value)) {
            return '0000-00-00 00:00:00';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    /**
     * IS2074 - Balance immediately BEFORE a displayed tank-ledger row.
     *
     * This is used for the first row of a filtered result set.  It deliberately
     * uses each source's transaction date (transactions.transaction_date or
     * tank_transfers.date), not created_at, so a back-dated entry is carried into
     * the correct chronological position.  created_at is used only to order rows
     * that share the same transaction date/time.
     */
    protected function getTankBalanceBeforeLedgerRow($row): float
    {
        $tankId = (int) ($row->fuel_tank_id ?? 0);
        if ($tankId <= 0) {
            return 0.0;
        }

        $effectiveDate = $this->getTankLedgerEffectiveDate($row);
        $createdAt = (string) ($row->created_at ?? '');

        $applyBeforeCondition = static function ($query, string $dateColumn, string $createdColumn) use ($effectiveDate, $createdAt) {
            /*
             * Some legacy rows have NULL / zero transaction dates.  Keep those
             * rows in the ledger by falling back to created_at only when the real
             * transaction date is unavailable.  Column names here are internal,
             * fixed controller constants - no request input is interpolated.
             */
            $effectiveSql = "COALESCE(NULLIF({$dateColumn}, '0000-00-00 00:00:00'), NULLIF({$dateColumn}, '0000-00-00'), {$createdColumn})";

            $query->where(function ($before) use ($effectiveSql, $createdColumn, $effectiveDate, $createdAt) {
                $before->whereRaw("{$effectiveSql} < ?", [$effectiveDate]);

                if ($createdAt !== '') {
                    $before->orWhere(function ($sameDate) use ($effectiveSql, $createdColumn, $effectiveDate, $createdAt) {
                        $sameDate->whereRaw("{$effectiveSql} = ?", [$effectiveDate])
                            ->where($createdColumn, '<', $createdAt);
                    });
                }
            });
        };

        $purchaseQuery = DB::table('tank_purchase_lines')
            ->join('transactions', 'transactions.id', '=', 'tank_purchase_lines.transaction_id')
            ->where('tank_purchase_lines.tank_id', $tankId)
            ->whereNull('tank_purchase_lines.new_deleted_at')
            ->where('transactions.type', '!=', '_deleted_purchase');
        $applyBeforeCondition($purchaseQuery, 'transactions.transaction_date', 'transactions.created_at');
        $purchaseQty = (float) $purchaseQuery->sum('tank_purchase_lines.quantity');

        $soldQuery = DB::table('tank_sell_lines')
            ->join('transactions', 'transactions.id', '=', 'tank_sell_lines.transaction_id')
            ->where('tank_sell_lines.tank_id', $tankId);
        $applyBeforeCondition($soldQuery, 'transactions.transaction_date', 'transactions.created_at');
        $soldQty = (float) $soldQuery->sum('tank_sell_lines.quantity');

        $transferInQuery = DB::table('tank_transfers')
            ->where('to_tank', $tankId);
        $applyBeforeCondition($transferInQuery, 'tank_transfers.date', 'tank_transfers.created_at');
        $transferInQty = (float) $transferInQuery->sum('quantity');

        $transferOutQuery = DB::table('tank_transfers')
            ->where('from_tank', $tankId);
        $applyBeforeCondition($transferOutQuery, 'tank_transfers.date', 'tank_transfers.created_at');
        $transferOutQty = (float) $transferOutQuery->sum('quantity');

        return $purchaseQty - $soldQty + $transferInQty - $transferOutQty;
    }

    /**
     * Return the tank balance immediately before a summary date/time.
     *
     * IS2075 - Tank Transaction Summary / Starting Qty carry-forward.
     * The transaction date is authoritative. created_at is only a fallback for
     * genuinely legacy rows that do not have a usable transaction date. This
     * keeps the Summary in the same chronological sequence as Transaction Details.
     */
    protected function getTankBalanceBeforeDateTime($tankId, $beforeDateTime): float
    {
        $before = Carbon::parse($beforeDateTime)->format('Y-m-d H:i:s');

        $purchaseQty = (float) DB::table('tank_purchase_lines')
            ->join('transactions', 'transactions.id', '=', 'tank_purchase_lines.transaction_id')
            ->where('tank_purchase_lines.tank_id', $tankId)
            ->whereNull('tank_purchase_lines.new_deleted_at')
            ->where('transactions.type', '!=', '_deleted_purchase')
            ->whereRaw(
                "COALESCE(NULLIF(transactions.transaction_date, '0000-00-00 00:00:00'), NULLIF(transactions.transaction_date, '0000-00-00'), transactions.created_at) < ?",
                [$before]
            )
            ->sum('tank_purchase_lines.quantity');

        $soldQty = (float) DB::table('tank_sell_lines')
            ->join('transactions', 'transactions.id', '=', 'tank_sell_lines.transaction_id')
            ->where('tank_sell_lines.tank_id', $tankId)
            ->whereRaw(
                "COALESCE(NULLIF(transactions.transaction_date, '0000-00-00 00:00:00'), NULLIF(transactions.transaction_date, '0000-00-00'), transactions.created_at) < ?",
                [$before]
            )
            ->sum('tank_sell_lines.quantity');

        $transferInQty = (float) DB::table('tank_transfers')
            ->where('to_tank', $tankId)
            ->whereRaw(
                "COALESCE(NULLIF(tank_transfers.date, '0000-00-00 00:00:00'), NULLIF(tank_transfers.date, '0000-00-00'), tank_transfers.created_at) < ?",
                [$before]
            )
            ->sum('quantity');

        $transferOutQty = (float) DB::table('tank_transfers')
            ->where('from_tank', $tankId)
            ->whereRaw(
                "COALESCE(NULLIF(tank_transfers.date, '0000-00-00 00:00:00'), NULLIF(tank_transfers.date, '0000-00-00'), tank_transfers.created_at) < ?",
                [$before]
            )
            ->sum('quantity');

        return $purchaseQty - $soldQty + $transferInQty - $transferOutQty;
    }

    protected function formatTankTransactionType($row)
    {
        if ($row->type == 'sell') {
            return __('petrogeneral::lang.settlment');
        }

        if ($row->type == 'transfer_in' || $row->type == 'transfer_out') {
            return __('petrogeneral::lang.tank_transfer');
        }

        if ($row->type == 'opening_stock') {
            return __('petrogeneral::lang.opening_stock');
        }

        if ($row->type == 'purchase') {
            return __('petrogeneral::lang.purchase_reference_no') . ': ' . $row->ref_no;
        }

        if ($row->type == 'stock_adjustment') {
            return "<span class='text-danger'>" . __('petrogeneral::lang.dip_reset') . "</span>";
        }

        if ($row->type == '_deleted_purchase') {
            return "<span class='text-danger'>" . __('petrogeneral::lang.deleted_purchase') . ': ' . $row->ref_no . "</span>";
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

                Log::debug('start date' . $start_date);

                $end_date = request()->end_date;

                Log::debug('end date' . $end_date);

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

                /*
                 * IS1959: fetch the tank list ONCE.
                 *
                 * $query->get() sat inside the date loop, so the whole joined
                 * tank query was re-executed for every single day in the range.
                 */
                $tank_rows = $query->get();

                /*
                 * IS2075 - Tank Transaction Summary / Starting Qty carry-forward.
                 *
                 * Keep one chronological running balance per tank while walking the
                 * report dates in ascending order.  For the first visible Summary
                 * row, initialise from the complete tank ledger immediately before
                 * that row's transaction day.  Every row after that obeys one strict
                 * rule only:
                 *
                 *     CURRENT Starting Qty = PREVIOUS Summary Balance Qty
                 *
                 * This mirrors Tank Transaction Details and prevents created_at,
                 * back-dated entry timing, or a fallback balance helper from changing
                 * the displayed carry-forward between consecutive Summary rows.
                 */
                $summaryRunningBalance = [];

                foreach ($dates as $date) {
                    $date_obj_for_day = Carbon::parse($date);

                    // Future days have nothing to report - skip the whole day
                    // rather than testing it once per tank.
                    if ($date_obj_for_day->copy()->startOfDay()->isAfter(now())) {
                        continue;
                    }

                    foreach ($tank_rows as $tankTemplate) {
                        $date_obj = $date_obj_for_day->copy();

                        /*
                         * S700: do not hide a tank that has movement on this day.
                         *
                         * This skipped a tank on any day EARLIER than the tank's
                         * own fuel_tanks.transaction_date - the date the tank
                         * record was created.
                         *
                         * Tank 47 (B-LAD) was created on 2026-09-01, so it was
                         * omitted from every earlier day. But it has settlement
                         * sales dated 2026-08-31 (9,900 across two settlements),
                         * entered on the 1st for the previous day - which is
                         * normal practice. The tank vanished from the 31 August
                         * summary while Tank Transaction DETAILS, which has no
                         * such condition, showed those sales correctly.
                         *
                         * The intent was reasonable: don't show a tank before it
                         * existed. But the creation date of the RECORD is not the
                         * date the tank came into use, and a transaction dated
                         * before it proves the tank was in use then.
                         *
                         * So the tank is now skipped only when it has NO movement
                         * on that day. A day with sales or purchases is always
                         * shown, whatever the record's own date says.
                         */
                        $tankTransactionDate = Carbon::parse($tankTemplate->transaction_date);

                        if ($tankTransactionDate->greaterThan($date_obj)) {
                            $movementOnDay = (float) $this->transactionUtil->__totalSellAndTransferOut(
                                $business_id,
                                $date_obj->copy()->startOfDay(),
                                $date_obj->copy()->endOfDay(),
                                (int) $tankTemplate->id
                            );

                            if ($movementOnDay <= 0) {
                                $movementOnDay = (float) $this->transactionUtil->__totalPurchaseAndTransferIn(
                                    $business_id,
                                    $date_obj->copy()->startOfDay(),
                                    $date_obj->copy()->endOfDay(),
                                    (int) $tankTemplate->id
                                );
                            }

                            if ($movementOnDay <= 0) {
                                continue;
                            }
                        }

                        // A fresh object is required for every date. Reusing the same
                        // stdClass from $tank_rows would make multiple Summary rows
                        // share the last mutated day's values.
                        $tank = clone $tankTemplate;
                        $tank->start_date = $date_obj->copy()->startOfDay();
                        $tank->end_date   = $date_obj->copy()->endOfDay();

                        $tankId = (int) $tank->id;

                        if (! array_key_exists($tankId, $summaryRunningBalance)) {
                            // First visible row: balance immediately before this day,
                            // using transaction-date chronology (not system entry time).
                            $summaryRunningBalance[$tankId] = $this->getTankBalanceBeforeDateTime(
                                $tankId,
                                $tank->start_date
                            );
                        }

                        // IS2075: always carry forward the previous Summary balance.
                        $tank->starting_qty = (float) $summaryRunningBalance[$tankId];

                        $tank->purchase_qty = $this->transactionUtil->__totalPurchaseAndTransferIn(
                            $business_id,
                            $tank->start_date,
                            $tank->end_date,
                            $tankId
                        );
                        $tank->testing_qty  = $this->_getTankTestingQty(
                            $business_id,
                            $tankId,
                            $tank->start_date,
                            $tank->end_date
                        );
                        $raw_sold = $this->transactionUtil->__totalSellAndTransferOut(
                            $business_id,
                            $tank->start_date,
                            $tank->end_date,
                            $tankId
                        );
                        $tank->sold_qty = max(0, $raw_sold);

                        // Testing quantity is displayed separately and does not affect
                        // the tank stock balance, matching Tank Transaction Details.
                        $tank->balance_qty = $tank->starting_qty + $tank->purchase_qty - $tank->sold_qty;

                        // The next chronological Summary row starts from this exact
                        // displayed balance, guaranteeing the carry-forward relationship.
                        $summaryRunningBalance[$tankId] = (float) $tank->balance_qty;
                        $tanks[] = $tank;

                        Log::debug("Tank {$tank->fuel_tank_number} purchase: {$tank->purchase_qty}, testing: {$tank->testing_qty}, balance: {$tank->balance_qty}, end_date: {$tank->end_date}");
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

        return view('petrogeneral::tanks_transaction_details.tank_transactions_summary');

    }

    public function settleTransactionSummary()
    {

        $business_id = request()->session()->get('user.business_id');

        $business_details = Business::find($business_id);

        $business_location_id = BusinessLocation::where('business_id', $business_id)->get()->first()->id;

        $fuel_tanks = DB::select("SELECT * FROM `fuel_tanks` WHERE  business_id = ?", [$business_id]);

        /*
         * IS1959: guard this legacy backfill - it was hanging the Summary page.
         *
         * The loop below walks one day at a time from the business start date to
         * a hardcoded 2020-12-31, running a multi-join query per tank per day.
         *
         * When $business_details->start_date is null or unparseable, strtotime()
         * returns false and date('Y-m-d', false) yields 1970-01-01 - so the loop
         * ran roughly 18,600 days x every fuel tank, tens of thousands of queries,
         * on EVERY request to Tank Transaction Summary. tankTransactionSummary()
         * calls set_time_limit(0), so PHP never gave up and the request ran until
         * Cloudflare cut it off:
         *
         *     tanks-transaction-summary ... 524 (gateway timeout)
         *
         * which surfaced as "DataTables warning: table id=tank_transaction_summary_table
         * - Ajax error".
         *
         * The end date is hardcoded to 2020-12-31, so for any business started
         * after that the loop is a no-op anyway - this backfill is spent. The
         * guard below skips it when the start date is missing or the range is
         * empty, which removes the pathological case without changing behaviour
         * for any business the loop could legitimately still apply to.
         */
        $configured_start = $business_details->start_date ?? null;
        $start_timestamp = !empty($configured_start) ? strtotime($configured_start) : false;

        if ($start_timestamp === false) {
            Log::warning('Tank summary backfill skipped - business start_date missing or invalid', [
                'business_id' => $business_id,
                'start_date' => $configured_start,
            ]);

            return;
        }

        $start_date = date('Y-m-d', $start_timestamp);

        $end_date = '2020-12-31';

        if (strtotime($start_date) > strtotime($end_date)) {
            // Nothing to backfill - the range closed before this business began.
            return;
        }

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

        /*
         * IS2071 - Tank Transaction Summary / Testing In date correction.
         *
         * Testing litres must belong to the business transaction date, not to
         * the timestamp at which the operator/system happened to enter the row.
         *
         * The old code filtered meter_sales.created_at. If a settlement dated
         * 2026-08-19 was entered on 2026-08-20, its testing litres appeared on
         * 20 Aug. The authoritative date for a settled meter sale is the linked
         * settlements.transaction_date, which is also what the Petro reports use.
         *
         * whereExists() is intentional. Some legacy databases contain duplicate
         * settlement_no values; joining settlements directly could multiply a
         * meter sale and inflate Testing In. Existence filtering keeps each
         * meter_sales row counted exactly once.
         */
        $meter_sales_testing_qty = (float) DB::table('meter_sales')
            ->join('pumps', 'meter_sales.pump_id', '=', 'pumps.id')
            ->where('pumps.fuel_tank_id', $tank_id)
            ->when(
                Schema::hasColumn('meter_sales', 'business_id'),
                fn($q) => $q->where('meter_sales.business_id', $business_id)
            )
            ->whereExists(function ($query) use ($business_id, $start, $end) {
                $query->select(DB::raw(1))
                    ->from('settlements as testing_settlements')
                    ->where('testing_settlements.business_id', $business_id)
                    ->whereBetween('testing_settlements.transaction_date', [$start, $end])
                    ->where(function ($match) {
                        $match->whereColumn('testing_settlements.settlement_no', 'meter_sales.settlement_no')
                            ->orWhereRaw('CAST(testing_settlements.id AS CHAR) = CAST(meter_sales.settlement_no AS CHAR)');
                    });
            })
            ->sum('meter_sales.testing_qty');

        $operator_testing_qty = 0.0;

        try {
            if (Schema::hasTable('pump_operator_meter_sales') && Schema::hasTable('pump_operator_meter_sale_details')) {
                /*
                 * IS1959 #2 / #4: same fan-out as the details screen above.
                 *
                 * Joining pump_operator_meter_sale_details (one row per pump) and
                 * then summing the PARENT's poms.testing_qty multiplied the figure
                 * by the number of pump rows on the tank, inflating the Testing
                 * column on the Tank Transaction Summary as well. whereExists
                 * selects the same sales without duplicating the parent row.
                 */
                $operator_testing_qty = (float) DB::table('pump_operator_meter_sales as poms')
                    ->whereExists(function ($query) use ($tank_id) {
                        $query->select(DB::raw(1))
                            ->from('pump_operator_meter_sale_details as pomsd')
                            ->join('pumps', 'pomsd.pump_id', '=', 'pumps.id')
                            ->whereColumn('pomsd.sale_id', 'poms.id')
                            ->where('pumps.fuel_tank_id', $tank_id);
                    })
                    ->when(
                        Schema::hasColumn('pump_operator_meter_sales', 'business_id'),
                        fn($q) => $q->where('poms.business_id', $business_id)
                    )
                    /*
                     * IS2071: poms.date_time is the entry timestamp, not the
                     * transaction date. Gate the row through its saved settlement
                     * and use settlements.transaction_date instead.
                     *
                     * POMS settlement_no is legacy-mixed: some rows hold the
                     * numeric settlement id, others the textual settlement code.
                     * Match both forms without joining (and therefore without
                     * duplicating a POMS row when old duplicate codes exist).
                     */
                    ->whereExists(function ($query) use ($business_id, $start, $end) {
                        $query->select(DB::raw(1))
                            ->from('settlements as testing_settlements')
                            ->where('testing_settlements.business_id', $business_id)
                            ->whereBetween('testing_settlements.transaction_date', [$start, $end])
                            ->where(function ($match) {
                                $match->whereColumn('testing_settlements.settlement_no', 'poms.settlement_no')
                                    ->orWhereRaw('CAST(testing_settlements.id AS CHAR) = CAST(poms.settlement_no AS CHAR)');
                            });
                    })
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

        Log::info("Testing Qty => Tank: {$tank_id}, From: {$start}, To: {$end}, Meter: {$meter_sales_testing_qty}, Operator: {$operator_testing_qty}, Qty: {$testing_qty}");

        return $testing_qty;
    }

}

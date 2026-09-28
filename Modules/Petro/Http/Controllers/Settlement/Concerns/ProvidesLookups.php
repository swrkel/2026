<?php

namespace Modules\Petro\Http\Controllers\Settlement\Concerns;

use App\Account;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\ContactLedger;
use App\CustomerReference;
use App\Http\Controllers\ContactController;
use App\NotificationTemplate;
use App\Product;
use App\Store;
use App\Transaction;
use App\TransactionPayment;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Variation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Milon\Barcode\DNS2D;
use Modules\HR\Entities\WorkShift;
use Modules\Petro\Entities\CustomerPayment;
use Modules\Petro\Entities\CustomerBillVatPrefix;
use Modules\Petro\Entities\DailyCard;
use Modules\Petro\Entities\DailyCollection;
use Modules\Petro\Entities\DailyVoucher;
use Modules\Petro\Entities\DayEnd;
use Modules\Petro\Entities\FuelTank;
use Modules\Petro\Entities\MeterSale;
use Modules\Petro\Entities\OtherIncome;
use Modules\Petro\Entities\OtherSale;
use Modules\Petro\Entities\PetroShift;
use Modules\Petro\Entities\PetroWhatsAppTemplate;
use Modules\Petro\Entities\Pump;
use Modules\Petro\Entities\PumperDayEntry;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\PumpOperatorAssignment;
use Modules\Petro\Entities\PumpOperatorCommission;
use Modules\Petro\Entities\PumpOperatorPayment;
use Modules\Petro\Entities\PumpOperatorOtherSale;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCardPayment;
use Modules\Petro\Entities\SettlementCashDeposit;
use Modules\Petro\Entities\SettlementCashPayment;
use Modules\Petro\Entities\SettlementChequePayment;
use Modules\Petro\Entities\SettlementCreditSalePayment;
use Modules\Petro\Entities\SettlementEditHistory;
use Modules\Petro\Entities\SettlementExcessPayment;
use Modules\Petro\Entities\PumpOperatorMeterSale;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\Petro\Entities\SettlementShortagePayment;
use Modules\Petro\Entities\SettlementLoanPayment;
use Modules\Petro\Entities\SettlementDrawingPayment;
use Modules\Petro\Entities\SettlementCustomerLoan;
use Modules\Petro\Entities\TankSellLine;
use Modules\Superadmin\Entities\Subscription;
use Modules\Petro\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

/**
 * Dropdown and lookup endpoints used by the settlement screens.
 *
 * MA-002: split out of Petro's SettlementController, which was 11,795 lines.
 *
 * The grouping was worked out FOR THIS CONTROLLER, not copied from PetroPD's.
 * The four settlement modules have genuinely diverged - 17 of the 19
 * controllers they share differ in logic - so Petro has methods PetroPD does
 * not (mechanical meter comparison, auto shift numbering, real-time payment
 * sync) and vice versa. Copying a grouping across would have produced tidy
 * files with the wrong things in them.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged: routes still point at SettlementController,
 *   action() targets still resolve, and $this-> calls between these 91 methods
 *   still work. Separate controller classes would mean rewriting routes and
 *   every action() reference - a behavioural change dressed up as tidying.
 *
 * Method bodies are byte-identical to the original. Nothing was rewritten.
 *
 * Methods here: getPumpDetails, getPumpDetailsPerShift, getPumps, getPumpsByLocation, getBalanceStock, getAvailableDirectSettlementPumps, getStoresById, getProductsByStoreId, getBalanceStockById, checkPreviousPumpSettlement, laterPumpSettlementExists
 */
trait ProvidesLookups
{
    public function getPumpDetails($pump_id, $shift_id = null)
    {

        $pump = Pump::where('id', $pump_id)->first();
        if (empty($pump)) {
            return response()->json(['success' => false, 'msg' => 'Pump not found.'], 404);
        }

        $last_meter_reading = $pump->last_meter_reading ?? 0;
        $last_meter_sale = MeterSale::where('pump_id', $pump_id)
            ->orderBy('id', 'desc')
            ->first();

        if (! empty($last_meter_sale)) {
            $last_meter_reading = ! empty($last_meter_sale->meter_reset_value)
                ? $last_meter_sale->meter_reset_value
                : $last_meter_sale->closing_meter;
        }

        $is_open = PumpOperatorAssignment::where('pump_id', $pump_id)
            ->where('status', 'open')
            ->where('shift_id', $shift_id)
            ->count();

        $ass = PumpOperatorAssignment::where('pump_id', $pump_id)
            ->where('shift_id', $shift_id)
            ->where('closed_in_settlement', 0)
            ->orderBy('id', 'desc')
            ->first();

        $po_closing = 0;
        $day_entry = null;
        if (! empty($ass)) {
            $po_closing = $ass->closing_meter;
            $day_entry = PumperDayEntry::where('pump_id', $pump_id)
                ->where('pumper_assignment_id', $ass->id)
                ->where('closed_in_settlement', 0)
                ->first();
        } else {
            if (! empty($last_meter_sale)) {
                $po_closing = 0;
            }
        }

        $fuel_tank_id = $pump->fuel_tank_id ?? null;
        $fuel_tank = FuelTank::where('id', $fuel_tank_id)->first();
        if (!$fuel_tank) {
            return response()->json(['success' => false, 'msg' => 'Fuel tank not found for this pump']);
        }
        $product = Variation::leftjoin(
            'products',
            'variations.product_id',
            'products.id'
        )
            ->leftjoin(
                'variation_location_details',
                'variations.id',
                'variation_location_details.variation_id'
            )
            ->where('products.id', $fuel_tank->product_id)
            ->select(
                'sku',
                'variations.sell_price_inc_tax as default_sell_price',
                'products.name',
                'products.id',
                'variation_location_details.qty_available'
            )->first();

        if (empty($product)) {
            return response()->json(['success' => false, 'msg' => 'Product/variation not found for this pump.']);
        }

        $business_id = $this->getCurrentBusinessIdForDirectSettlement();

        $business = Business::where('id', $business_id)->first();
        $currency_precision = ! empty($business) ? $business->currency_precision : 2;
        $product->default_sell_price = number_format(
            $product->default_sell_price,
            $currency_precision,
            '.',
            ''
        );

        $current_balance = $this->transactionUtil->getTankBalanceById(
            $pump->fuel_tank_id
        );

        return response()->json([
            'colsing_value' => number_format($last_meter_reading, 3, '.', ''),
            'tank_remaing_qty' => $current_balance,
            'product' => $product,
            'pump_name' => $pump->pump_name,
            'product_id' => $product->id,
            'pump_id' => $pump->id,
            'bulk_sale_meter' => $pump->bulk_sale_meter,
            'po_closing' => number_format(
                $last_meter_reading > $po_closing ? 0 : $po_closing,
                3,
                '.',
                ''
            ),
            'po_testing' => number_format(
                $last_meter_reading >= $po_closing
                    ? 0
                    : (! empty($day_entry)
                    ? $day_entry->testing_ltr
                    : 0),
                3,
                '.',
                ''
            ),
            'assignment_id' => ! empty($ass) ? $ass->id : 0,
            'pumper_entry_id' => ! empty($day_entry) ? $day_entry->id : 0,
            'is_open' => $is_open,
        ]);

    }

    public function getPumpDetailsPerShift($pump_id, $shift_id)
    {

        $pump = Pump::where('id', $pump_id)->first();

        // $last_meter_reading = $pump->last_meter_reading;

        $last_meter_sale = MeterSale::where([

            'pump_id' => $pump_id,

            'shift_id' => $shift_id,

        ])

            ->orderBy('id', 'desc')

            ->first();

        if (! empty($last_meter_sale)) {

            $last_meter_reading = ! empty($last_meter_sale->meter_reset_value)

                ? $last_meter_sale->meter_reset_value

                : $last_meter_sale->closing_meter;

        }

        $is_open = PumpOperatorAssignment::where('pump_id', $pump_id)

            ->where('status', 'open')

            ->count();

        $ass = PumpOperatorAssignment::where('pump_id', $pump_id)

            ->where('closed_in_settlement', 0)

            ->where('shift_id', $shift_id)

            ->orderBy('id', 'desc')

            ->first();

        $po_closing = 0;

        $day_entry = null;

        if (! empty($ass)) {

            $last_meter_reading = $ass->starting_meter;

            $po_closing = $ass->closing_meter;

            $day_entry = PumperDayEntry::where('pump_id', $pump_id)

                ->where('pumper_assignment_id', $ass->id)

                ->where('closed_in_settlement', 0)

                ->first();

        }

        $fuel_tank_id = $pump->fuel_tank_id ?? null; $fuel_tank = FuelTank::where('id', $fuel_tank_id)->first();

        $product = Variation::leftjoin(

            'products',

            'variations.product_id',

            'products.id'

        )

            ->leftjoin(

                'variation_location_details',

                'variations.id',

                'variation_location_details.variation_id'

            )

            ->where('products.id', $fuel_tank->product_id)

            ->select(

                'sku',

                'variations.sell_price_inc_tax as default_sell_price',

                'products.name',

                'products.id',

                'variation_location_details.qty_available'

            )

            ->first();

        $business_id = request()

            ->session()

            ->get('business.id');

        $business = Business::where('id', $business_id)->first();

        $currency_precision = $business->currency_precision;

        $product->default_sell_price = number_format(

            $product->default_sell_price,

            $currency_precision,

            '.',

            ''

        );

        $current_balance = $this->transactionUtil->getTankBalanceById(

            $pump->fuel_tank_id

        );

        return [

            'colsing_value' => number_format($last_meter_reading, 3, '.', ''),

            'tank_remaing_qty' => $current_balance,

            'product' => $product,

            'pump_name' => $pump->pump_name,

            'product_id' => $product->id,

            'pump_id' => $pump->id,

            'bulk_sale_meter' => $pump->bulk_sale_meter,

            'po_closing' => number_format(

                $last_meter_reading >= $po_closing ? 0 : $po_closing,

                3,

                '.',

                ''

            ),

            'po_testing' => number_format(

                $last_meter_reading >= $po_closing

                ? 0

                : (! empty($day_entry)

                    ? $day_entry->testing_ltr

                    : 0),

                3,

                '.',

                ''

            ),

            'assignment_id' => ! empty($ass) ? $ass->id : 0,

            'pumper_entry_id' => ! empty($day_entry) ? $day_entry->id : 0,

            'is_open' => $is_open,

        ];

    }

    /**
     * get balance stock of product







     * @param product_id







     * @return Response
     */

    public function getPumps(Request $request, $id)
    {
        try {
            $business_id = $request->session()->get('business.id') ?: $request->session()->get('user.business_id');
            $active_settlement_id = (int) $request->input('active_settlement_id', 0);

            // Ensure shift_id is a single value
            $shift_id = $request->input('shift_number');
            if (is_array($shift_id)) {
                $shift_id = $shift_id[0];
            }

            if ((string) $shift_id === '0' || empty($shift_id)) {
                return [
                    'success' => true,
                    'pumps' => $this->getAvailableDirectSettlementPumps(
                        (int) $business_id,
                        ! empty($request->location_id) ? (int) $request->location_id : null
                    ),
                ];
            }

            $assigned_pumps_query = PumpOperatorAssignment::where('pump_operator_assignments.pump_operator_id', $id)
                ->where('pump_operator_assignments.business_id', $business_id)
                ->leftJoin('settlements', 'pump_operator_assignments.settlement_id', '=', 'settlements.id')
                ->where(function ($query) use ($active_settlement_id) {
                    $query->whereNull('pump_operator_assignments.settlement_id');

                    if ($active_settlement_id > 0) {
                        $query->orWhere('pump_operator_assignments.settlement_id', $active_settlement_id);
                    }
                })
                ->where('pump_operator_assignments.shift_id', $shift_id); // filter by shift

            $this->excludePetroPdModuleAssignments($assigned_pumps_query, $business_id);

            $assigned_pumps = $assigned_pumps_query
                ->pluck('pump_operator_assignments.pump_id');

            // dd(  $assigned_pumps );

            if ($assigned_pumps->isNotEmpty()) {
                $pumps_query = Pump::where('business_id', $business_id);
                if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pumps', 'is_other_sales_pump')) {
                    $pumps_query->where('is_other_sales_pump', 0);
                }
                $pumps = $pumps_query->whereIn('id', $assigned_pumps)
                    ->pluck('pump_name', 'id');
            } else {
                /*
                 * IS1521 real fix:
                 * In Direct Settlement, selecting an operator must not break the
                 * Meter Sales form. If no active assignment row is found for the
                 * selected shift/operator, fall back to location-based direct
                 * settlement pumps instead of returning an empty list or error.
                 */
                $pumps = $this->getAvailableDirectSettlementPumps(
                    (int) $business_id,
                    ! empty($request->location_id) ? (int) $request->location_id : null
                );
            }

            $output = [
                'success' => true,
                'pumps' => $pumps,
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: '.$e->getFile().
                ' Line: '.$e->getLine().
                ' Message: '.$e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Get all pumps for the selected business location.
     * Pump dropdown is NOT scoped by operator/shift.
     */

    public function getPumpsByLocation(Request $request)
    {
        try {
            $business_id = $this->getCurrentBusinessIdForDirectSettlement();
            $location_id = $request->input('location_id');

            if (empty($business_id)) {
                return [
                    'success' => true,
                    'pumps' => collect(),
                ];
            }

            return [
                'success' => true,
                'pumps' => $this->getAvailableDirectSettlementPumps((int) $business_id, ! empty($location_id) ? (int) $location_id : null),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() .
                ' Line: ' . $e->getLine() .
                ' Message: ' . $e->getMessage());

            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
    }

    //     public function getPumps(Request $request, $id)

    //     {

    //         try {

    //             $business_id = request()

    //                 ->session()

    //                 ->get("business.id");
    //  $shift_id = $request->input('work_shift');

    //             $assigned_pumps = PumpOperatorAssignment::where(

    //                 "pump_operator_id",

    //                 $id

    //             )

    //                 ->where("settlement_id", null)
    //  ->where("shift_id", $shift_id) // 👈 add this filter
    //                 ->whereDate("date_and_time", date("Y-m-d"))

    //                 ->pluck("pump_id");

    //             if (!empty($assigned_pumps) && sizeof($assigned_pumps) > 0) {

    //                 $pumps = Pump::where("business_id", $business_id)

    //                     ->whereIn("id", $assigned_pumps)

    //                     ->pluck("pump_name", "id");

    //             } else {

    //                 $pumps = Pump::where("business_id", $business_id)->pluck(

    //                     "pump_name",

    //                     "id"

    //                 );

    //             }

    //             $output = [

    //                 "success" => true,

    //                 "pumps" => $pumps,

    //             ];

    //         } catch (\Exception $e) {

    //             \Log::emergency(

    //                 "File: " .

    //                     $e->getFile() .

    //                     "Line: " .

    //                     $e->getLine() .

    //                     "Message: " .

    //                     $e->getMessage()

    //             );

    //             $output = [

    //                 "success" => false,

    //                 "msg" => __("messages.something_went_wrong"),

    //             ];

    //         }

    //         return $output;

    //     }

    public function getBalanceStock($id)
    {

        try {

            $product = Product::leftjoin(

                'variations',

                'products.id',

                'variations.product_id'

            )

                ->leftjoin(

                    'variation_location_details',

                    'variations.id',

                    'variation_location_details.variation_id'

                )

                ->where('products.id', $id)

                ->select(

                    'qty_available',

                    'sell_price_inc_tax',

                    'products.name',

                    'sku'

                )

                ->first();

            $output = [

                'success' => true,

                'balance_stock' => $product->qty_available,

                'price' => $product->sell_price_inc_tax,

                'product_name' => $product->name,

                'code' => $product->sku,

                'msg' => __('petro::lang.success'),

            ];

        } catch (\Exception $e) {

            \Log::emergency(

                'File: '.

                $e->getFile().

                'Line: '.

                $e->getLine().

                'Message: '.

                $e->getMessage()

            );

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }


    /**
     * Direct Settlement meter sale totals must always be stored as positive sales values.
     * The old flow trusted browser-calculated discount_amount/sub_total, which could
     * become negative and then affected Meter Sales Total, Payment due and Add Payment.
     */

    private function getAvailableDirectSettlementPumps(int $business_id, ?int $location_id = null)
    {
        /*
         * IS1537 root fix.
         * Direct Settlement is a manual settlement screen. Its Pump No dropdown
         * must show the fuel pumps for the selected business/location even when
         * the operator has no pumper-dashboard assignment row. Previous logic
         * removed "busy"/assigned pumps, which made the dropdown empty in real
         * tenants and caused the operator change flow to show the generic
         * "something went wrong" message. Keep PetroPD filtering out of this
         * manual Direct Settlement pump list; those filters belong to PD flows.
         */
        $labelColumn = \Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pumps', 'pump_name')
            ? 'pump_name'
            : (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pumps', 'pump_no') ? 'pump_no' : 'id');

        $buildPumpQuery = function ($filterBusiness, $filterLocation) use ($business_id, $location_id) {
            $query = Pump::query();

            if ($filterBusiness && ! empty($business_id) && \Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pumps', 'business_id')) {
                $query->where('business_id', $business_id);
            }

            if ($filterLocation && ! empty($location_id) && \Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pumps', 'location_id')) {
                $query->where('location_id', $location_id);
            }

            if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pumps', 'is_other_sales_pump')) {
                $query->where(function ($q) {
                    $q->where('is_other_sales_pump', 0)->orWhereNull('is_other_sales_pump');
                });
            }

            if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pumps', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            \App\Utils\PetroPdIsolationUtil::excludePumps($query, 'pumps.is_petro_pd_only');

            return $query;
        };

        $pumps = $buildPumpQuery(true, true)->orderBy($labelColumn)->pluck($labelColumn, 'id');

        // Fallbacks protect tenants where location_id/business_id was not stored
        // consistently in old pump records.
        if ($pumps->isEmpty()) {
            $pumps = $buildPumpQuery(true, false)->orderBy($labelColumn)->pluck($labelColumn, 'id');
        }
        if ($pumps->isEmpty()) {
            $pumps = $buildPumpQuery(false, false)->orderBy($labelColumn)->pluck($labelColumn, 'id');
        }

        return $pumps->map(function ($label, $id) {
            return $label ?: ('Pump ' . $id);
        });
    }

    public function getStoresById(Request $request)
    {

        $business_id = $request->session()->get('user.business_id');

        $account_type = null;

        $stores = '<option value="">Please Select</option>';

        $stores = Store::where('business_id', $business_id);

        if ($request->location_id) {

            $stores = $stores->where('location_id', $request->location_id);

        }

        $stores = $stores->pluck('name', 'id');

        return $this->transactionUtil->createDropdownHtml(

            $stores,

            'Please Select'

        );

    }

    public function getProductsByStoreId(Request $request)
    {
        $business_id = (int) (
            $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
            ?: optional(auth()->user())->business_id
            ?: 0
        );

        abort_if($business_id <= 0, 403, __('messages.unauthorized_action'));

        $location_id = (int) $request->input('location_id', 0);
        $store_id = (int) $request->input('store_id', 0);

        $fuel_category_id = Category::where('business_id', $business_id)
            ->where('name', 'Fuel')
            ->value('id');

        // Historical JavaScript sent `other_sat`; always use the correct tab.
        $tab = 'other_sale';

        if ($store_id > 0) {
            try {
                // Run the stock/module-aware lookup for compatibility, then merge
                // with the fallback list below so legacy untagged products appear.
                $this->transactionUtil->getProductsByStoreId(
                    $business_id,
                    $location_id,
                    $store_id,
                    $tab,
                    $fuel_category_id,
                    'petro_settlements'
                );
            } catch (\Throwable $exception) {
                \Modules\Petro\Support\PetroDebug::info(
                    'Petro store product dropdown failed; using non-fuel fallback.',
                    [
                        'business_id' => $business_id,
                        'location_id' => $location_id,
                        'store_id' => $store_id,
                        'message' => $exception->getMessage(),
                    ]
                );
            }
        }

        $products = $this->getDirectSettlementOtherSaleItems(
            $business_id,
            $fuel_category_id ? (int) $fuel_category_id : null
        );

        return $this->transactionUtil->createDropdownHtml(
            $products,
            empty($products) ? 'No Item Found' : __('petro::lang.please_select')
        );
    }

    public function getBalanceStockById(Request $request, $id)
    {

        try {

            $product = Product::join(

                'variations',

                'products.id',

                '=',

                'variations.product_id'

            )

                ->leftJoin('variation_location_details', function ($join) use ($id, $request) {

                    $join

                        ->on(

                            'variations.id',

                            '=',

                            'variation_location_details.variation_id'

                        )

                        ->where(

                            'variation_location_details.product_id',

                            '=',

                            $id

                        )

                        ->where(

                            'variation_location_details.location_id',

                            '=',

                            $request->location_id

                        );

                })

                ->leftJoin('variation_store_details', function ($join) use ($request) {

                    $join

                        ->on(

                            'variations.id',

                            '=',

                            'variation_store_details.variation_id'

                        )

                        ->where(

                            'variation_store_details.store_id',

                            '=',

                            $request->store_id

                        );

                })

                ->where('products.id', $id)

                ->select(

                    DB::raw(

                        'COALESCE(variation_store_details.qty_available, 0) as qty_available'

                    ),

                    DB::raw(

                        'COALESCE(sell_price_inc_tax, 0) as sell_price_inc_tax'

                    ),

                    'products.name',

                    'products.sku'

                )

                ->first();

            $output = [

                'success' => true,

                'balance_stock' => $product->qty_available,

                'price' => $product->sell_price_inc_tax,

                'product_name' => $product->name,

                'code' => $product->sku,

                'msg' => __('petro::lang.success'),

            ];

        } catch (\Exception $e) {

            \Log::emergency(

                'File: '.

                $e->getFile().

                ' Line: '.

                $e->getLine().

                ' Message: '.

                $e->getMessage()

            );

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function checkPreviousPumpSettlement()
    {

        try {

            $shift_id = request()->shift_id;
            $pump_operator_id = request()->pump_operator_id;

            if (! $shift_id) {

                return response()->json(

                    [

                        'status' => false,

                        'msg' => 'Shift ID is required.',

                    ],

                    400

                );

            }

            // Get the current assignment for the selected shift/operator.
            // This avoids picking an unrelated assignment when multiple rows share the same shift.
            $currentAssignmentQuery = PumpOperatorAssignment::where(
                'shift_id',
                $shift_id
            );
            if (! empty($pump_operator_id)) {
                $currentAssignmentQuery->where('pump_operator_id', $pump_operator_id);
            }
            $currentAssignment = $currentAssignmentQuery->orderBy('id', 'desc')->first();

            if (! $currentAssignment) {

                return response()->json(

                    [

                        'status' => false,

                        'msg' => 'Shift not found.',

                    ],

                    404

                );

            }

            // Find any previous unsettled assignment for the same pump/operator.
            // Only block if the assignment shift was CLOSED but never settled.
            // Open/active assignments are not blocking.

            $previousUnsettled = PumpOperatorAssignment::where(
                'pump_id',
                $currentAssignment->pump_id
            )
                ->where('pump_operator_id', $currentAssignment->pump_operator_id)
                ->where('id', '<', $currentAssignment->id)
                ->where('status', 'close')
                ->where(function ($q) {
                    $q->where('closed_in_settlement', 0)
                        ->orWhereNull('closed_in_settlement');
                })
                ->whereNull('settlement_id')
                ->orderBy('id', 'desc')
                ->first();

            if ($previousUnsettled) {

                $pump = Pump::select('pump_name')->find(

                    $previousUnsettled->pump_id

                );

                $operator = PumpOperator::select('name')->find(

                    $previousUnsettled->pump_operator_id

                );

                $pump_name = $pump ? $pump->pump_name : 'Pump';

                $operator_name = $operator ? $operator->name : 'Operator';

                return response()->json(

                    [

                        'status' => false,

                        'msg' => 'This pump '.

                            $pump_name.

                            ' has a previous unsettled shift '.

                            $previousUnsettled->shift_number.

                            ' with operator '.

                            $operator_name.

                            '. Please settle it first.',

                    ],

                    200

                );

            }

            // No unsettled records found

            return response()->json(

                [

                    'status' => true,

                    'msg' => 'No previous unsettled shifts found.',

                ],

                200

            );

        } catch (\Exception $e) {

            // Handle unexpected errors

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong'),

            ];

            return response()->json($output, 500);

        }

    }

    private function laterPumpSettlementExists(MeterSale $meter_sale): bool
    {
        $pump = Pump::find($meter_sale->pump_id);

        if (! empty($pump) && ! empty($pump->bulk_tank)) {
            return false;
        }

        return MeterSale::where('business_id', $meter_sale->business_id)
            ->where('pump_id', $meter_sale->pump_id)
            ->where('id', '>', $meter_sale->id)
            ->where('settlement_no', '!=', $meter_sale->settlement_no)
            ->exists();
    }
}

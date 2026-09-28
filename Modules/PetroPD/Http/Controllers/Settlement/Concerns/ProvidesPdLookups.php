<?php

namespace Modules\PetroPD\Http\Controllers\Settlement\Concerns;

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
use Modules\PetroPD\Entities\CustomerPayment;
use Modules\PetroPD\Entities\DailyCollection;
use Modules\PetroPD\Entities\DailyVoucher;
use Modules\PetroPD\Entities\DayEnd;
use Modules\PetroPD\Entities\FuelTank;
use Modules\PetroPD\Entities\MeterSale;
use Modules\PetroPD\Entities\OtherIncome;
use Modules\PetroPD\Entities\OtherSale;
use Modules\PetroPD\Entities\PetroShift;
use Modules\PetroPD\Entities\PetroWhatsAppTemplate;
use Modules\PetroPD\Entities\Pump;
use Modules\PetroPD\Entities\PumperDayEntry;
use Modules\PetroPD\Entities\PumpOperator;
use Modules\PetroPD\Entities\PumpOperatorAssignment;
use Modules\PetroPD\Entities\PumpOperatorCommission;
use Modules\PetroPD\Entities\PumpOperatorMeterSale;
use Modules\PetroPD\Entities\PumpOperatorOtherSale;
use Modules\PetroPD\Entities\PumpOperatorPayment;
use Modules\PetroPD\Entities\Settlement;
use Modules\PetroPD\Entities\SettlementCardPayment;
use Modules\PetroPD\Entities\SettlementCashDeposit;
use Modules\PetroPD\Entities\SettlementCashPayment;
use Modules\PetroPD\Entities\SettlementChequePayment;
use Modules\PetroPD\Entities\SettlementCreditSalePayment;
use Modules\PetroPD\Entities\SettlementCustomerLoan;
use Modules\PetroPD\Entities\SettlementDrawingPayment;
use Modules\PetroPD\Entities\SettlementEditHistory;
use Modules\PetroPD\Entities\SettlementExcessPayment;
use Modules\PetroPD\Entities\SettlementExpensePayment;
use Modules\PetroPD\Entities\SettlementLoanPayment;
use Modules\PetroPD\Entities\SettlementShortagePayment;
use Modules\PetroPD\Entities\TankSellLine;
use Modules\PetroPD\Entities\TanksTransactionDetail;
use Modules\PetroPD\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Modules\Superadmin\Entities\Subscription;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;
use Modules\PetroPD\Entities\PumpOperatorMeterSaleDetail;
use Modules\PetroPD\Services\PetroPdClosedShiftQuery;
use Modules\PetroPD\Services\PetroPdSmsNotificationService;

/**
 * Dropdown and lookup endpoints used by the settlement screens.
 *
 * MA-002: split out of PetroPDSettlementController, which was 15,639 lines in
 * a single file - the largest controller in the application after core's
 * ReportController.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged. Routes still point at
 *   PetroPDSettlementController, action() targets still resolve, and $this->
 *   calls between these 111 methods still work. Splitting into separate
 *   controller classes would mean rewriting routes and every action()
 *   reference - a behavioural change dressed up as tidying.
 *
 *   So this is a purely physical split: same class at runtime, smaller files.
 *
 * Method bodies are byte-identical to the original. Nothing was rewritten
 * while moving.
 *
 * Methods here: getPumpDetails, getPumpDetailsPerShift, getPumps, getBalanceStock, getStoresById, getProductsByStoreId, getBalanceStockById, checkPreviousPumpSettlementPD, getSettlementPDShiftIds
 */
trait ProvidesPdLookups
{
    public function getPumpDetails($pump_id, $shift_id = null)
    {

        $pump = Pump::where("id", $pump_id)->first();

        $last_meter_reading = $pump->last_meter_reading ?? 0;

        $last_meter_sale = MeterSale::where("pump_id", $pump_id)

            ->orderBy("id", "desc")

            ->first();

        if (! empty($last_meter_sale)) {

            $last_meter_reading = ! empty($last_meter_sale->meter_reset_value)

                ? $last_meter_sale->meter_reset_value

                : $last_meter_sale->closing_meter;
        }

        // Settlement PD allows meter sales for open pumps (no "close pump first" requirement).
        $is_open = 0;

        $ass = PumpOperatorAssignment::where("pump_id", $pump_id)

            ->where("shift_id", $shift_id)

            ->where("closed_in_settlement", 0)

            ->orderBy("id", "desc")

            ->first();

        $po_closing = 0;

        $day_entry = null;

        if (! empty($ass)) {

            $po_closing = $ass->closing_meter;

            $day_entry = PumperDayEntry::where("pump_id", $pump_id)

                ->where("pumper_assignment_id", $ass->id)

                ->where("closed_in_settlement", 0)

                ->first();
        } else {
            if (! empty($last_meter_sale)) {
                $po_closing = 0;
            }
        }

        $fuel_tank = FuelTank::where("id", $pump->fuel_tank_id)->first();

        $product = Variation::leftjoin(

            "products",

            "variations.product_id",

            "products.id"

        )

            ->leftjoin(

                "variation_location_details",

                "variations.id",

                "variation_location_details.variation_id"

            )

            ->where("products.id", $fuel_tank->product_id)

            ->select(

                "sku",

                "variations.sell_price_inc_tax as default_sell_price",

                "products.name",

                "products.id",

                "variation_location_details.qty_available"

            )

            ->first();

        $business_id = request()

            ->session()

            ->get("business.id");

        $business = Business::where("id", $business_id)->first();

        $currency_precision = $business->currency_precision;

        $product->default_sell_price = number_format(

            $product->default_sell_price,

            $currency_precision,

            ".",

            ""

        );

        $current_balance = $this->transactionUtil->getTankBalanceById(

            $pump->fuel_tank_id

        );

        return [

            "colsing_value"    => number_format($last_meter_reading, 3, ".", ""),

            "tank_remaing_qty" => $current_balance,

            "product"          => $product,

            "pump_name"        => $pump->pump_name,

            "product_id"       => $product->id,

            "pump_id"          => $pump->id,

            "bulk_sale_meter"  => $pump->bulk_sale_meter,

            "po_closing"       => number_format(

                $last_meter_reading > $po_closing ? 0 : $po_closing,

                3,

                ".",

                ""

            ),

            "po_testing"       => number_format(

                $last_meter_reading >= $po_closing

                    ? 0

                    : (! empty($day_entry)

                        ? $day_entry->testing_ltr

                        : 0),

                3,

                ".",

                ""

            ),

            "assignment_id"    => ! empty($ass) ? $ass->id : 0,

            "pumper_entry_id"  => ! empty($day_entry) ? $day_entry->id : 0,

            "is_open"          => $is_open,

        ];
    }

    public function getPumpDetailsPerShift($pump_id, $shift_id)
    {

        $pump = Pump::where("id", $pump_id)->first();

        $last_meter_reading = $pump->last_meter_reading ?? 0;

        // Check if there's an existing meter sale for this pump in this shift
        // and use its closing meter (new_meter in details) as the starting meter
        $last_meter_sale = PumpOperatorMeterSale::where('shift_id', $shift_id)
            ->whereHas('details', function ($query) use ($pump_id) {
                $query->where('pump_id', $pump_id);
            })
            ->orderBy('id', 'desc')
            ->first();

        $has_existing_meter_sale = false;
        if (! empty($last_meter_sale)) {
            $has_existing_meter_sale = true;
            if (! empty($last_meter_sale->meter_reset_value)) {
                $last_meter_reading = $last_meter_sale->meter_reset_value;
            } else {
                // Get closing meter from the detail record for this pump
                $last_detail = $last_meter_sale->details()->where('pump_id', $pump_id)->first();
                if ($last_detail) {
                    $last_meter_reading = $last_detail->new_meter;
                }
            }
        }

        // Settlement PD allows meter sales for open pumps (no "close pump first" requirement).
        $is_open = 0;

        $ass = PumpOperatorAssignment::where("pump_id", $pump_id)

            ->where("closed_in_settlement", 0)

            ->where("shift_id", $shift_id)

            ->orderBy("id", "desc")

            ->first();

        $po_closing = 0;

        $day_entry = null;

        if (! empty($ass)) {

            // Only use assignment's starting_meter if no meter sale exists yet for this pump
            if (! $has_existing_meter_sale) {
                $last_meter_reading = $ass->starting_meter;
            }

            $po_closing = $ass->closing_meter;

            $day_entry = PumperDayEntry::where("pump_id", $pump_id)

                ->where("pumper_assignment_id", $ass->id)

                ->where("closed_in_settlement", 0)

                ->first();
        }

        $fuel_tank = FuelTank::where("id", $pump->fuel_tank_id)->first();

        $product = Variation::leftjoin(

            "products",

            "variations.product_id",

            "products.id"

        )

            ->leftjoin(

                "variation_location_details",

                "variations.id",

                "variation_location_details.variation_id"

            )

            ->where("products.id", $fuel_tank->product_id)

            ->select(

                "sku",

                "variations.sell_price_inc_tax as default_sell_price",

                "products.name",

                "products.id",

                "variation_location_details.qty_available"

            )

            ->first();

        $business_id = request()

            ->session()

            ->get("business.id");

        $business = Business::where("id", $business_id)->first();

        $currency_precision = $business->currency_precision;

        $product->default_sell_price = number_format(

            $product->default_sell_price,

            $currency_precision,

            ".",

            ""

        );

        $current_balance = $this->transactionUtil->getTankBalanceById(

            $pump->fuel_tank_id

        );

        return [

            "colsing_value"    => number_format($last_meter_reading, 3, ".", ""),

            "tank_remaing_qty" => $current_balance,

            "product"          => $product,

            "pump_name"        => $pump->pump_name,

            "product_id"       => $product->id,

            "pump_id"          => $pump->id,

            "bulk_sale_meter"  => $pump->bulk_sale_meter,

            "po_closing"       => number_format(

                $last_meter_reading >= $po_closing ? 0 : $po_closing,

                3,

                ".",

                ""

            ),

            "po_testing"       => number_format(

                $last_meter_reading >= $po_closing

                    ? 0

                    : (! empty($day_entry)

                        ? $day_entry->testing_ltr

                        : 0),

                3,

                ".",

                ""

            ),

            "assignment_id"    => ! empty($ass) ? $ass->id : 0,

            "pumper_entry_id"  => ! empty($day_entry) ? $day_entry->id : 0,

            "is_open"          => $is_open,

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
            $business_id = $request->session()->get("business.id");

            // Ensure shift_id is a single value
            $shift_id = $request->input('shift_number');
            if (is_array($shift_id)) {
                $shift_id = $shift_id[0];
            }

            if ((string) $shift_id === '0' || empty($shift_id)) {
                $output = [
                    "success" => true,
                    "pumps"   => $this->getAvailableDirectSettlementPumps(
                        (int) $business_id,
                        ! empty($request->location_id) ? (int) $request->location_id : null
                    ),
                ];

                return $output;
            }

            /*
             |------------------------------------------------------------------
             | Every pump assigned to this shift - not merely the unlinked ones.
             |------------------------------------------------------------------
             |
             | This previously carried ->whereNull("settlement_id"), which hid any
             | pump whose assignment already pointed at a settlement. An operator
             | with two pumps on a closed shift was offered only one, with no
             | message and no way to settle the other.
             |
             | A shift's pumps are decided by the SHIFT. Whether some settlement
             | row references an assignment - including a settlement that was
             | started and abandoned - says nothing about which pumps the operator
             | actually worked.
             |
             | Guarding against settling a shift twice is a real need, but it
             | belongs at the settlement level: one shift, one settlement. Removing
             | rows from a dropdown never prevented a duplicate; it only made the
             | failure silent.
             |
             | NOTE: CreatesPdSettlements still uses whereNull("settlement_id")
             | when a settlement is SAVED, to attach assignments not yet linked.
             | That is correct and is untouched. This is the READ only.
             */
            $assigned_pumps = PumpOperatorAssignment::where("business_id", $business_id)
                ->where("pump_operator_id", $id)
                ->where("shift_id", $shift_id) // filter by shift
                // ->whereDate("date_and_time", date("Y-m-d"))
                ->distinct()
                ->pluck("pump_id");

            // dd(  $assigned_pumps );

            if ($assigned_pumps->isNotEmpty()) {
                $pumps_query = Pump::where("business_id", $business_id);
                if (Schema::hasColumn('pumps', 'is_other_sales_pump')) {
                    $pumps_query->where('is_other_sales_pump', 0);
                }
                $pumps = $pumps_query->whereIn("id", $assigned_pumps)
                    ->pluck("pump_name", "id");
            } else {
                // Do not fall back to all pumps. Settlement PD should only show
                // pumps assigned to the selected operator for the selected shift.
                $pumps = collect();
            }

            $output = [
                "success" => true,
                "pumps"   => $pumps,
            ];
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() .
                " Line: " . $e->getLine() .
                " Message: " . $e->getMessage());

            $output = [
                "success" => false,
                "msg"     => __("messages.something_went_wrong"),
            ];
        }

        return $output;
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

                "variations",

                "products.id",

                "variations.product_id"

            )

                ->leftjoin(

                    "variation_location_details",

                    "variations.id",

                    "variation_location_details.variation_id"

                )

                ->where("products.id", $id)

                ->select(

                    "qty_available",

                    "sell_price_inc_tax",

                    "products.name",

                    "sku"

                )

                ->first();

            $output = [

                "success"       => true,

                "balance_stock" => $product->qty_available,

                "price"         => $product->sell_price_inc_tax,

                "product_name"  => $product->name,

                "code"          => $product->sku,

                "msg"           => __("petropd::lang.success"),

            ];
        } catch (\Exception $e) {

            \Log::emergency(

                "File: " .

                    $e->getFile() .

                    "Line: " .

                    $e->getLine() .

                    "Message: " .

                    $e->getMessage()

            );

            $output = [

                "success" => false,

                "msg"     => __("messages.something_went_wrong"),

            ];
        }

        return $output;
    }

    /**







     * save meter sale to db







     * @return Response







     */

    public function getStoresById(Request $request)
    {

        $business_id = $request->session()->get("user.business_id");

        $account_type = null;

        $stores = '<option value="">Please Select</option>';

        $stores = Store::where("business_id", $business_id);

        if ($request->location_id) {

            $stores = $stores->where("location_id", $request->location_id);
        }

        $stores = $stores->pluck("name", "id");

        return $this->transactionUtil->createDropdownHtml(

            $stores,

            "Please Select"

        );
    }

    public function getProductsByStoreId(Request $request)
    {

        $business_id = $request->session()->get("user.business_id");

        $location_id = $request->location_id;

        $store_id = $request->store_id;

        $tab = $request->tab ?? 0;

        // dump($tab);exit;

        $fuel_category_id = Category::where("business_id", $business_id)

            ->where("name", "Fuel")

            ->first();

        if ($settlement) {
            // Ensure cash deposits, cash payments, card payments and credit sales are loaded correctly

            // Cash deposits fallback
            if ($settlement->cash_deposits->isEmpty()) {
                $cash_deposits = \Modules\PetroPD\Entities\SettlementCashDeposit::where('settlement_no', $settlement->settlement_no)
                    ->orWhere('settlement_no', $settlement->id)
                    ->get();
                $settlement->setRelation('cash_deposits', $cash_deposits);
            }

            // Cash payments fallback - use ID (integer) to match relationship
            if ($settlement->cash_payments->isEmpty()) {
                $cash_payments = SettlementCashPayment::whereIn('settlement_no', array_values(array_filter([(string) $settlement->id, (string) $settlement->settlement_no], static fn ($k) => $k !== '')))
                    ->get();
                $settlement->setRelation('cash_payments', $cash_payments);
            }

            // Card payments fallback
            if ($settlement->card_payments->isEmpty()) {
                // CRITICAL FIX: Filter by shift when loading fallback card payments
                $card_payments_query = SettlementCardPayment::where(function ($q) use ($settlement) {
                    $q->where('settlement_card_payments.settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_card_payments.settlement_no', $settlement->id);
                });

                // Filter by shift if work_shift is available
                if (! empty($settlement->work_shift)) {
                    $work_shifts = is_array($settlement->work_shift) ? $settlement->work_shift : json_decode($settlement->work_shift, true);
                    if (is_array($work_shifts) && ! empty($work_shifts)) {
                        $shift_ids = array_filter(array_map('intval', $work_shifts));
                        if (! empty($shift_ids)) {
                            /*
                             * IS1984 #4 (10 Aug 2026): third copy of the card shift
                             * filter - see the full note in CreatesPdSettlements.
                             * The old LEFT JOIN + whereIn on
                             * pump_operator_payments.shift_id dropped any card
                             * payment that could not be walked back to a pump
                             * payment, because NULL IN (...) is not true. Kept
                             * identical to the other two copies so the Cards figure
                             * cannot differ depending on which path loaded the
                             * settlement.
                             */
                            $card_payments_query->where(function ($q) use ($shift_ids) {
                                $q->whereExists(function ($sub) use ($shift_ids) {
                                    $sub->select(DB::raw(1))
                                        ->from('pump_operator_payments')
                                        ->whereColumn('pump_operator_payments.id', 'settlement_card_payments.pump_payment_id')
                                        ->whereIn('pump_operator_payments.shift_id', $shift_ids);
                                })
                                    ->orWhereExists(function ($sub) use ($shift_ids) {
                                        $sub->select(DB::raw(1))
                                            ->from('daily_cards')
                                            ->join('pump_operator_payments', function ($join) {
                                                $join->on('pump_operator_payments.pump_operator_id', '=', 'daily_cards.pump_operator_id')
                                                    ->whereRaw('pump_operator_payments.collection_form_no COLLATE utf8mb4_unicode_ci = daily_cards.collection_no COLLATE utf8mb4_unicode_ci')
                                                    ->where('pump_operator_payments.payment_type', 'card')
                                                    ->whereColumn('pump_operator_payments.payment_amount', 'daily_cards.amount');
                                            })
                                            ->whereColumn('daily_cards.id', 'settlement_card_payments.daily_card_id')
                                            ->whereIn('pump_operator_payments.shift_id', $shift_ids);
                                    })
                                    ->orWhere(function ($orQ) {
                                        $orQ->whereNull('settlement_card_payments.daily_card_id')
                                            ->whereNull('settlement_card_payments.pump_payment_id');
                                    });
                            });
                        }
                    }
                }

                $card_payments = $card_payments_query->get();
                $settlement->setRelation('card_payments', $card_payments);
            }

            // Credit sale payments fallback
            if ($settlement->credit_sale_payments->isEmpty()) {
                $credit_sales = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                    ->where(function ($q) use ($settlement) {
                        $q->where('settlement_no', $settlement->settlement_no)
                            ->orWhere('settlement_no', $settlement->id);
                    })
                    ->get();
                $settlement->setRelation('credit_sale_payments', $credit_sales);
            }
        }

        $fuel_category_id = ! empty($fuel_category_id)

            ? $fuel_category_id->id

            : null;

        if ($store_id) {

            return $this->transactionUtil->getProductsByStoreId(

                $business_id,

                $location_id,

                $store_id,

                $tab,

                $fuel_category_id,

                "petro_settlements"

            );
        } else {

            $products = [];

            return $this->transactionUtil->createDropdownHtml(

                $products,

                "No Item Found"

            );
        }
    }

    public function getBalanceStockById(Request $request, $id)
    {

        try {

            $product = Product::join(

                "variations",

                "products.id",

                "=",

                "variations.product_id"

            )

                ->leftJoin("variation_location_details", function ($join) use (

                    $id,

                    $request

                ) {

                    $join

                        ->on(

                            "variations.id",

                            "=",

                            "variation_location_details.variation_id"

                        )

                        ->where(

                            "variation_location_details.product_id",

                            "=",

                            $id

                        )

                        ->where(

                            "variation_location_details.location_id",

                            "=",

                            $request->location_id

                        );
                })

                ->leftJoin("variation_store_details", function ($join) use (

                    $request

                ) {

                    $join

                        ->on(

                            "variations.id",

                            "=",

                            "variation_store_details.variation_id"

                        )

                        ->where(

                            "variation_store_details.store_id",

                            "=",

                            $request->store_id

                        );
                })

                ->where("products.id", $id)

                ->select(

                    DB::raw(

                        "COALESCE(variation_store_details.qty_available, 0) as qty_available"

                    ),

                    DB::raw(

                        "COALESCE(sell_price_inc_tax, 0) as sell_price_inc_tax"

                    ),

                    "products.name",

                    "products.sku"

                )

                ->first();

            $output = [

                "success"       => true,

                "balance_stock" => $product->qty_available,

                "price"         => $product->sell_price_inc_tax,

                "product_name"  => $product->name,

                "code"          => $product->sku,

                "msg"           => __("petropd::lang.success"),

            ];
        } catch (\Exception $e) {

            \Log::emergency(

                "File: " .

                    $e->getFile() .

                    " Line: " .

                    $e->getLine() .

                    " Message: " .

                    $e->getMessage()

            );

            $output = [

                "success" => false,

                "msg"     => __("messages.something_went_wrong"),

            ];
        }

        return $output;
    }

    public function checkPreviousPumpSettlementPD(Request $request)
    {

        try {
            $shift_id = request()->shift_id;
            $pump_operator_id = request()->pump_operator_id;

            if (! $shift_id) {
                return response()->json([
                    "status" => false,
                    "msg"    => "Shift ID is required.",
                ], 400);
            }

            $business_id = (int) $request->session()->get('user.business_id');

            $assignmentQuery = PumpOperatorAssignment::where("shift_id", $shift_id);
            if (! empty($pump_operator_id)) {
                $assignmentQuery->where('pump_operator_id', $pump_operator_id);
            }
            $currentAssignment = $assignmentQuery->first();

            if (! $currentAssignment) {
                return response()->json([
                    "status" => false,
                    "msg"    => "Shift not found.",
                ], 404);
            }

            if (! empty($currentAssignment->settlement_id)) {
                $linkedSettlement = \Modules\PetroPD\Entities\Settlement::find($currentAssignment->settlement_id);
                if ($linkedSettlement && $linkedSettlement->status == 1) {
                    return response()->json([
                        "status" => true,
                        "msg"    => "OK",
                    ], 200);
                }
            }

            if (! PetroPdClosedShiftQuery::isNextAllowedShift($business_id, (int) $shift_id, ! empty($pump_operator_id) ? (int) $pump_operator_id : null)) {
                $oldest = PetroPdClosedShiftQuery::oldestPendingForOperator(
                    $business_id,
                    (int) ($pump_operator_id ?? $currentAssignment->pump_operator_id)
                );

                if (! $oldest) {
                    return response()->json([
                        "status" => false,
                        "msg"    => __("petropd::lang.no_pending_pd_shift_for_operator"),
                    ], 200);
                }

                return response()->json([
                    "status" => false,
                    "msg"    => __(
                        "petropd::lang.settle_oldest_shift_first_pd",
                        ["shift" => $oldest->shift_number]
                    ),
                ], 200);
            }

            return response()->json([
                "status" => true,
                "msg"    => "OK",
            ], 200);
        } catch (\Exception $e) {
            \Log::error("checkPreviousPumpSettlement error: " . $e->getMessage());

            return response()->json([
                "success" => false,
                "msg"     => __("messages.something_went_wrong"),
            ], 500);
        }
    }

    private function getSettlementPDShiftIds(?Settlement $settlement): array
    {
        if (empty($settlement)) {
            return [];
        }

        $shift_ids = [];

        $work_shifts = $settlement->work_shift;
        if (! empty($work_shifts)) {
            if (is_string($work_shifts)) {
                $decoded     = json_decode($work_shifts, true);
                $work_shifts = is_array($decoded) ? $decoded : explode(',', $work_shifts);
            }

            if (is_array($work_shifts)) {
                $shift_ids = array_filter(array_map('intval', $work_shifts));
            }
        }

        /*
         |----------------------------------------------------------------------
         | work_shift holds shift IDs. Do not also treat them as shift NUMBERS.
         |----------------------------------------------------------------------
         |
         | This block looked up assignments whose shift_NUMBER matched the values
         | in work_shift, then MERGED those ids in alongside the originals.
         |
         | work_shift holds shift IDs, so that lookup finds a completely different
         | shift that merely happens to have a matching number - and merging it in
         | means the settlement reports another shift's payments as its own.
         |
         | Measured on PDST182:
         |
         |     cash    647,360.00 shown  vs  251,600.00 correct
         |     cards   299,776.00 shown  vs  299,776.00 correct
         |     credit  257,312.62 shown  vs   22,920.00 correct
         |     total 1,204,441.32 shown  vs  574,288.70 correct
         |
         | Cards matched because the extra shift had no card payments; cash and
         | credit did not. 574,288.70 is the figure the Add Payment page reports
         | once it uses work_shift alone.
         |
         | The number lookup is kept ONLY as a fallback for settlements whose
         | work_shift genuinely holds numbers rather than ids - it is used when
         | none of the values resolve to a real shift, and never merged on top.
         */
        if (! empty($shift_ids)) {
            $valid_shift_ids = PumpOperatorAssignment::where('business_id', $settlement->business_id)
                ->whereIn('shift_id', $shift_ids)
                ->whereNotNull('shift_id')
                ->pluck('shift_id')
                ->unique()
                ->values()
                ->toArray();

            if (! empty($valid_shift_ids)) {
                // work_shift holds real shift ids - use them, and nothing else.
                $shift_ids = array_values(array_unique(array_map('intval', $valid_shift_ids)));
            } else {
                // Nothing resolved as an id, so treat the values as shift numbers.
                $shift_ids_from_numbers = PumpOperatorAssignment::where('business_id', $settlement->business_id)
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->whereIn('shift_number', $shift_ids)
                    ->whereNotNull('shift_id')
                    ->pluck('shift_id')
                    ->toArray();

                if (! empty($shift_ids_from_numbers)) {
                    $shift_ids = array_values(array_unique(array_map('intval', $shift_ids_from_numbers)));
                }
            }
        }

        if (empty($shift_ids)) {
            $shift_ids = PumpOperatorAssignment::where('settlement_id', $settlement->id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->whereNotNull('shift_id')
                ->pluck('shift_id')
                ->unique()
                ->values()
                ->toArray();
        }

        return $shift_ids;
    }
}

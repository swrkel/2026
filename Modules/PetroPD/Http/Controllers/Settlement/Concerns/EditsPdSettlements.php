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
 * Editing, updating and deleting a settlement.
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
 * Methods here: edit, update, canEditSettlement, destroy, unlinkSettlementPayments, deletePreviouseTransactions, updateCreditSales, adjustDiscounts, adjustMeterSalesDates
 */
trait EditsPdSettlements
{
    public function edit($id)
    {

        $business_id = request()

            ->session()

            ->get("business.id");

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        $payment_types = $this->productUtil->payment_types(

            $default_location,

            false,

            false,

            false,

            false,

            "is_sale_enabled"

        );

        $customers = Contact::customersDropdown(

            $business_id,

            false,

            true,

            "customer"

        );

        $pump_operators = PumpOperator::where(

            "business_id",

            $business_id

        )->pluck("name", "id");

        $pump_nos = Pump::where("business_id", $business_id)->pluck(

            "pump_name",

            "id"

        );

        $business_details = \App\Business::find($business_id);

        $currency_precision = ! empty($business_details->currency_precision)

            ? $business_details->currency_precision

            : 2;

        $meeter_precision = 3;

        $items = [];

        $active_settlement = Settlement::where("id", $id)

            ->select("settlements.*")

            ->with([

                "meter_sales",
                "meter_sales_pd.details",

                "other_sales",

                "other_incomes",

                "customer_payments",

                "cash_payments",

                "card_payments",

                "cheque_payments",

                "credit_sale_payments",

                "expense_payments",

                "excess_payments",

                "shortage_payments",

                "loan_payments",

                "drawings_payments",

                "customer_loans",

            ])

            ->first();

        if (empty($active_settlement)) {
            abort(404);
        }

        $this->assertPetroPdSettlementRolePermission($active_settlement, 'edit');

        $this->repairMeterSalePdSettlementNo($active_settlement);

        $pump_operator_id = $active_settlement->pump_operator_id;
        $pump_operator_name = PumpOperator::find($pump_operator_id, ['name'])?->name;

        // if (! empty($active_settlement)) {

        //     $active_settlement->setRelation('cash_payments', \Modules\PetroPD\Entities\SettlementCashPayment::where('settlement_no', $active_settlement->settlement_no)->get());

        //     $active_settlement->setRelation('card_payments', \Modules\PetroPD\Entities\SettlementCardPayment::where('settlement_no', $active_settlement->settlement_no)->get());

        //     $active_settlement->setRelation('cheque_payments', \Modules\PetroPD\Entities\SettlementChequePayment::where('settlement_no', $active_settlement->settlement_no)->get());

        //     $active_settlement->setRelation('credit_sale_payments', \Modules\PetroPD\Entities\SettlementCreditSalePayment::where('settlement_no', $active_settlement->settlement_no)->with('product')->get());

        //     $active_settlement->setRelation('expense_payments', \Modules\PetroPD\Entities\SettlementExpensePayment::where('settlement_no', $active_settlement->settlement_no)->get());

        //     $active_settlement->setRelation('excess_payments', \Modules\PetroPD\Entities\SettlementExcessPayment::where('settlement_no', $active_settlement->settlement_no)->get());

        //     $active_settlement->setRelation('shortage_payments', \Modules\PetroPD\Entities\SettlementShortagePayment::where('settlement_no', $active_settlement->settlement_no)->get());

        //     $active_settlement->setRelation('loan_payments', \Modules\PetroPD\Entities\SettlementLoanPayment::where('settlement_no', $active_settlement->settlement_no)->get());

        //     $active_settlement->setRelation('drawings_payments', \Modules\PetroPD\Entities\SettlementDrawingPayment::where('settlement_no', $active_settlement->settlement_no)->get());

        //     $active_settlement->setRelation('cash_deposits', \Modules\PetroPD\Entities\SettlementCashDeposit::where('settlement_no', $active_settlement->settlement_no)->get());

        //     $active_settlement->setRelation('customer_loans', \Modules\PetroPD\Entities\SettlementCustomerLoan::where('settlement_no', $active_settlement->settlement_no)->get());

        //     \Log::info('Settlement PD Edit: Payment Relations Reloaded', [

        //         'settlement_id'              => $active_settlement->id,

        //         'settlement_no_string'       => $active_settlement->settlement_no,

        //         'credit_sale_payments_count' => $active_settlement->credit_sale_payments->count(),

        //         'credit_sale_payments_sum'   => $active_settlement->credit_sale_payments->sum('amount'),

        //     ]);

        // }

        if (!empty($active_settlement)) {
            $this->reloadSettlementPayments($active_settlement);

            \Log::info('Settlement PD Edit: Payment Relations Reloaded (Merged)', [
                'settlement_id' => $active_settlement->id,
                'settlement_no' => $active_settlement->settlement_no,
                'cash'          => $active_settlement->cash_payments->count(),
                'card'          => $active_settlement->card_payments->count(),
                'cheque'        => $active_settlement->cheque_payments->count(),
                'credit_sales'  => $active_settlement->credit_sale_payments->count(),
            ]);
        }

        $settlement_no = $active_settlement->settlement_no;

        $shiftAssignments = PumpOperatorAssignment::leftJoin('petro_shifts as ps', 'pump_operator_assignments.shift_id', '=', 'ps.id')
            ->where(

                "pump_operator_assignments.settlement_id",

                $active_settlement->id

            )
            ->where("pump_operator_assignments.pump_operator_id", $active_settlement->pump_operator_id)

            ->groupBy("pump_operator_assignments.shift_number", "pump_operator_assignments.shift_id", "ps.work_shift_id")

            ->select(
                "pump_operator_assignments.shift_number",
                "pump_operator_assignments.shift_id",
                "ps.work_shift_id"
            )

            ->get();

        if ($shiftAssignments->isEmpty()) {
            $fallbackShiftIds = $this->getSettlementPDShiftIds($active_settlement);

            if (! empty($fallbackShiftIds)) {
                $shiftAssignments = PumpOperatorAssignment::leftJoin('petro_shifts as ps', 'pump_operator_assignments.shift_id', '=', 'ps.id')
                    ->where('pump_operator_assignments.business_id', $business_id)
                    ->where('pump_operator_assignments.pump_operator_id', $active_settlement->pump_operator_id)
                    ->whereIn('pump_operator_assignments.shift_id', $fallbackShiftIds)
                    ->groupBy('pump_operator_assignments.shift_number', 'pump_operator_assignments.shift_id', 'ps.work_shift_id')
                    ->select(
                        'pump_operator_assignments.shift_number',
                        'pump_operator_assignments.shift_id',
                        'ps.work_shift_id'
                    )
                    ->get();
            }
        }

        $shift_number = $shiftAssignments->toArray();
        $shift_id = $shiftAssignments->pluck('shift_id')->filter()->first();
        $shift_numbers = $shiftAssignments
            ->filter(fn ($assignment) => ! empty($assignment->shift_id))
            ->mapWithKeys(function ($assignment) {
                return [
                    $assignment->shift_id => [
                        'shift_number' => $assignment->shift_number,
                        'work_shift_id' => $assignment->work_shift_id,
                        'pump_operator_id' => $assignment->pump_operator_id,
                    ],
                ];
            })
            ->toArray();

        $show_shift_no = '';
        if (! empty($shift_number)) {
            $shiftNumberLabels = collect($shift_number)
                ->pluck('shift_number')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
            if (! empty($shiftNumberLabels)) {
                $show_shift_no = implode(', ', $shiftNumberLabels);
            }
        }
        if (empty($show_shift_no) && ! empty($active_settlement->work_shift)) {
            $work_shifts = is_array($active_settlement->work_shift)
                ? $active_settlement->work_shift
                : json_decode($active_settlement->work_shift, true);
            if (! is_array($work_shifts)) {
                $work_shifts = explode(',', $active_settlement->work_shift);
            }
            $work_shifts = array_filter(array_map('intval', $work_shifts));
            if (! empty($work_shifts)) {
                $shiftNumberLabels = PumpOperatorAssignment::whereIn('shift_id', $work_shifts)
                    ->where('pump_operator_id', $active_settlement->pump_operator_id)
                    ->distinct()
                    ->pluck('shift_number')
                    ->toArray();
                if (! empty($shiftNumberLabels)) {
                    $show_shift_no = implode(', ', $shiftNumberLabels);
                } else {
                    $show_shift_no = implode(', ', $work_shifts);
                }
            }
        }

        $other_sale_final_total = 0;

        $pump_other_sale_final_total = 0;

        $userOtherDetails = [];

        foreach ($active_settlement->other_sales as $ot_item) {

            $product = \App\Product::find($ot_item->product_id);

            $discount_amount = $ot_item->discount_amount ?? 0;

            $withDiscount = ($ot_item->sub_total ?? 0) - $discount_amount;

            $pump_other_sale_final_total += $withDiscount;

            $userOtherDetails[] = [

                "id"            => $ot_item->id,

                "sku"           => ! empty($product) ? $product->sku : "",

                "name"          => ! empty($product) ? $product->name : "",

                "balance_stock" => number_format(

                    $ot_item->balance_stock,

                    4,

                    ".",

                    ","

                ),

                "price"         => number_format($ot_item->price, $currency_precision),

                "qty"           => number_format($ot_item->qty, 4, ".", ","),

                "discount_type" => $ot_item->discount_type,

                "discount"      => number_format(

                    $ot_item->discount,

                    $currency_precision

                ),

                "sub_total"     => number_format(

                    $ot_item->sub_total,

                    $currency_precision

                ),

                "with_discount" => number_format(

                    $withDiscount,

                    $currency_precision

                ),

                "user_check"    => 1,

            ];
        }

        $shiftIds = array_column($shift_number, "shift_id");

        // Edit view must also show closing meter sales linked only by shift/operator until finalization attaches settlement_no.
        $this->hydrateSettlementPdMeterSalesForDisplay($active_settlement, $shiftIds);

        $query = PumpOperatorOtherSale::join(

            "products",

            "products.id",

            "=",

            "pump_operator_other_sales.product_id"

        )
            ->leftJoin("variations", "products.id", "variations.product_id")

            ->leftJoin(

                "variation_location_details",

                "variations.id",

                "variation_location_details.variation_id"

            )

            ->whereIn("pump_operator_other_sales.shift_id", $shiftIds)

            ->join("pump_operator_assignments", function ($join) {

                $join

                    ->on(

                        "pump_operator_assignments.shift_id",

                        "=",

                        "pump_operator_other_sales.shift_id"

                    )

                    ->where(

                        "pump_operator_assignments.status",

                        "close"

                    )->whereRaw('pump_operator_assignments.id = (



                         SELECT MAX(poa.id)

                         FROM pump_operator_assignments poa

                         WHERE poa.shift_id = pump_operator_other_sales.shift_id AND poa.status = "close"

                     )');
            })

            ->select(

                "pump_operator_other_sales.*",

                "products.name as product_name",

                "products.sku as product_sku",

                "pump_operator_assignments.shift_number",

                "qty_available"

            )

            ->groupBy("pump_operator_other_sales.id");

        $pumperOthersaleDetails = [];

        $pumpSales = $query->get();

        foreach ($pumpSales as $pumpSale) {

            $discount_amount = $pumpSale->discount ?? 0;

            $withDiscount = ($pumpSale->sub_total ?? 0) - $discount_amount;

            $pump_other_sale_final_total += $withDiscount;

            $pumperOthersaleDetails[] = [

                "sku"           => $pumpSale->product_sku,

                "name"          => $pumpSale->product_name,

                "balance_stock" => number_format(

                    $pumpSale->qty_available,

                    4,

                    ".",

                    ","

                ),

                "price"         => number_format($pumpSale->price, $currency_precision),

                "qty"           => number_format($pumpSale->qty, 4, ".", ","),

                "discount_type" => $pumpSale->discount_type,

                "discount"      => number_format(

                    $pumpSale->discount,

                    $currency_precision

                ),

                "sub_total"     => number_format(

                    $pumpSale->sub_total,

                    $currency_precision

                ),

                "with_discount" => number_format(

                    $withDiscount,

                    $currency_precision

                ),

                "user_check"    => 0,

            ];
        }

        $combinedOtherSales = array_merge(

            $userOtherDetails,

            $pumperOthersaleDetails

        );

        $final_other_sale_total =

            $other_sale_final_total + $pump_other_sale_final_total;

        $has_reviewed = $this->transactionUtil->hasReviewed(

            $active_settlement->transaction_date

        );

        if (! empty($has_reviewed)) {

            $output = [

                "success" => 0,

                "msg"     => __("lang_v1.review_first"),

            ];

            return redirect()

                ->back()

                ->with(["status" => $output]);
        }

        $reviewed = $this->transactionUtil->get_review(

            $active_settlement->transaction_date,

            $active_settlement->transaction_date

        );

        if (! empty($reviewed)) {

            $output = [

                "success" => 0,

                "msg"     =>

                "You can't edit a settlement for an already reviewed date",

            ];

            return redirect()

                ->back()

                ->with(["status" => $output]);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        if (! empty($active_settlement)) {

            $already_pumps = MeterSale::where(

                "settlement_no",

                $active_settlement->id

            )

                ->pluck("pump_id")

                ->toArray();

            if ($active_settlement->meter_sales->count()) {

                $already_pumps = array_diff($already_pumps, [

                    $active_settlement->meter_sales->toArray()[0]["pump_id"],

                ]);

                $already_pumps = array_values($already_pumps);
            }

            $pump_nos = collect();
        } else {

            $pump_nos = collect();
        }

        $stores = Store::forDropdown($business_id, 0, 1, "sell");

        $fuel_category_id = Category::where("business_id", $business_id)

            ->where("name", "Fuel")

            ->first();

        $fuel_category_id = ! empty($fuel_category_id)

            ? $fuel_category_id->id

            : null;

        $items = $this->transactionUtil->getProductDropDownArray(

            $business_id,

            $fuel_category_id,

            "petro_settlements"

        );

        $payment_meter_sale_total = $this->getSettlementPDMeterSaleTotal(
            (int) $business_id,
            $active_settlement
        );
        $display_meter_sales = $this->getSettlementPdDisplayMeterSales(
            (int) $business_id,
            $active_settlement,
            $shiftIds
        );
        $has_saved_meter_sales = $display_meter_sales->isNotEmpty();

        $payment_other_sale_total = ! empty($active_settlement->other_sales)

            ? $active_settlement->other_sales->sum("sub_total")

            : 0.0;

        $payment_other_income_total = ! empty($active_settlement->other_incomes)

            ? $active_settlement->other_incomes->sum("sub_total")

            : 0.0;

        $payment_customer_payment_total = ! empty($active_settlement->customer_payments)

            ? $active_settlement->customer_payments->sum("sub_total")

            : 0.0;

        $payment_other_sale_discount = ! empty($active_settlement->other_sales)

            ? $active_settlement->other_sales->sum("sub_total")

            : 0.0;

        $payment_other_sale_total -= $payment_other_sale_discount;

        $work_shifts = WorkShift::where("business_id", $business_id)->pluck(

            "shift_name",

            "id"

        );

        $bulk_tanks = FuelTank::where("business_id", $business_id)

            ->where("bulk_tank", 1)

            ->pluck("fuel_tank_number", "id");

        $services = Product::where("business_id", $business_id)

            ->forModule("petro_settlements")

            ->where("enable_stock", 0)

            ->pluck("name", "id");

        $discount_types = ["fixed" => "Fixed", "percentage" => "Percentage"];

        $can_edit_details = $this->canEditSettlement($id);



        // IS1776: This controller is registered only under PetroPD routes, therefore
        // its edit screen must remain fully module-owned. The former source-based fallback
        // loaded the legacy Petro view whenever the query string was missing, which caused
        // the PetroPD filters and full Meter Sales layout to disappear on normal Edit.
        $view = 'petropd::pd_settlement.edit';
        $wrok_shifts = $work_shifts;

        return view($view)->with(

            compact(

                "business_locations",

                "payment_types",

                "services",

                "customers",

                "pump_operators",
                "pump_operator_name",
                "pump_operator_id",

                "work_shifts",
                "wrok_shifts",

                "pump_nos",

                "items",

                "settlement_no",

                "default_location",

                "active_settlement",

                "stores",

                "payment_meter_sale_total",

                "payment_other_sale_total",

                "payment_other_income_total",

                "payment_customer_payment_total",

                "bulk_tanks",

                "discount_types",

                "can_edit_details",

                "shift_number",
                "shift_numbers",
                "shift_id",
                "show_shift_no",
                "display_meter_sales",
                "has_saved_meter_sales",

                "combinedOtherSales",

                "pump_other_sale_final_total"

            )

        );
    }

    public function update(Request $request, $id)
    {

        try {

            $input = $request->except("_token", "_method", "source", "direct_shift_number", "direct_shift_operator_id", "shift_number");
            $business_id = $request->session()->get("business.id");

            $settlement    = null;
            $changedFields = '';

            if ($id != "0") {

                $settlement = Settlement::find($id);

                if (empty($settlement)) {
                    return response()->json([
                        'success' => false,
                        'msg'     => __('messages.something_went_wrong'),
                    ]);
                }

                $this->assertPetroPdSettlementRolePermission($settlement, 'edit');

                $currentWorkShift = $settlement->work_shift;

                $currentTransactionDate = $settlement->transaction_date;

                $note = $settlement->note;

                $operator = $settlement->pump_operator_id;

                $location = $settlement->location_id;

                $input["work_shift"] = ! empty($request->work_shift)
                    ? json_encode($request->work_shift)
                    : json_encode([]);

                $input["transaction_date"] = \Carbon::parse(

                    $request->transaction_date

                )->format("Y-m-d");

                $business_id = request()

                    ->session()

                    ->get("business.id");

                $latest_date =

                    DayEnd::where("business_id", $business_id)

                    ->get()

                    ->last()->day_end_date ?? null;

                if (

                    ! empty($latest_date) &&

                    strtotime($latest_date) >=

                    strtotime($input["transaction_date"])

                ) {

                    return [

                        "success" => false,

                        "msg"     => __("petropd::lang.date_greater_than_day_end"),

                    ];
                }

                $input["note"] = $request->note;

                $input["pump_operator_id"] = $request->pump_operator_id;

                $current_shift_ids = PumpOperatorAssignment::where('settlement_id', $settlement->id)
                    ->pluck('shift_id')
                    ->toArray();

                $operator_changed = ($request->pump_operator_id != $operator);
                $shift_changed = false;

                if ($request->has('shift_number') && !empty($request->shift_number)) {
                    $new_shift_id = (int)$request->shift_number;
                    if (!in_array($new_shift_id, $current_shift_ids)) {
                        $shift_changed = true;
                    }
                } else {
                    $new_shift_id = count($current_shift_ids) > 0 ? $current_shift_ids[0] : null;
                }

                /*
                 |------------------------------------------------------------------
                 | A DATE change alone must never unlink the shift or its payments.
                 |------------------------------------------------------------------
                 |
                 | Reported: moving a PD settlement's date back to the day the shift
                 | was actually opened made all the entered data disappear.
                 |
                 | Cause: changing the date reloads the shift dropdown for the new
                 | date. The reloaded list frequently selects a different shift id -
                 | or none - and that value is posted here as shift_number. The test
                 | above then reads it as a genuine shift change, and the block below
                 | unlinks the payments and detaches every PumpOperatorAssignment
                 | from the settlement. The rows still exist, but nothing is attached
                 | to the settlement any more, so the screen comes back empty.
                 |
                 | The guard below requires the browser to say explicitly that the
                 | user changed the shift (shift_change_confirmed), OR the operator
                 | to have genuinely changed. A date-only update now leaves the
                 | existing links exactly as they are, which is what lets an operator
                 | finish a closed-but-pending shift by selecting its original date.
                 |
                 | Deliberately conservative for a live system: when the intent is
                 | not explicit, nothing is unlinked. The worst case is that a real
                 | shift change needs the shift re-selected once, which is
                 | recoverable - whereas unlinking loses the day's work.
                 */
                $shift_change_is_explicit = $request->boolean('shift_change_confirmed')
                    || $request->has('shift_changed_by_user');

                $date_only_change = ! $operator_changed
                    && ! $shift_change_is_explicit;

                if ($date_only_change) {
                    $shift_changed = false;
                }

                if ($operator_changed || $shift_changed) {
                    $this->unlinkSettlementPayments($settlement, $business_id);

                    // Unlink previous assignments
                    PumpOperatorAssignment::where('settlement_id', $settlement->id)
                        ->update([
                            'settlement_id' => null,
                            'closed_in_settlement' => 0
                        ]);

                    // Link new assignment
                    if (!empty($new_shift_id)) {
                        PumpOperatorAssignment::whereNull('settlement_id')
                            ->where('shift_id', $new_shift_id)
                            ->update([
                                'settlement_id' => $settlement->id,
                                'closed_in_settlement' => 1
                            ]);

                        // Link new payments
                        $addPaymentController = app(\Modules\PetroPD\Http\Controllers\AddPaymentController::class);
                        $addPaymentController->addDailyCards($settlement->id, $request->pump_operator_id, $business_id, $new_shift_id);
                        $addPaymentController->addDailyCheques($settlement->id, $request->pump_operator_id, $business_id, [$new_shift_id]);
                        $addPaymentController->addDailyShortageExcess($settlement->id, $request->pump_operator_id, $business_id, [$new_shift_id]);
                    }
                }

                Settlement::where("id", $id)->update($input);

                $changedFields = "";

                //Comment for rajib Dev

                //  if ($input["work_shift"] !== $currentWorkShift) {

                //      $changedFields = "Update Work shift";

                //  }

                if ($input["transaction_date"] !== $currentTransactionDate) {

                    $changedFields = "Update Transaction Date";

                    // Update all related transactions and account_transactions when settlement date changes
                    try {
                        $this->updateSettlementRelatedTransactions($settlement, $input["transaction_date"]);
                    } catch (\Exception $e) {
                        Log::error('Failed to update related transactions for settlement', [
                            'settlement_id' => $settlement->id,
                            'error'         => $e->getMessage(),
                        ]);
                    }
                }

                if ($input["note"] !== $note) {

                    $changedFields = "Update Note";
                }

                if ($request->pump_operator_id != $operator) {

                    $changedFields = "Update Pump Operator";
                }

                if ($request->location_id != $location) {

                    $changedFields = "Update Location";
                }
            } else {

                $changedFields = "Update Shift No.";
            }

            $assigned_pumps = PumpOperatorAssignment::where(
                "pump_operator_assignments.pump_operator_id",
                $request->pump_operator_id
            )
                ->leftJoin(
                    "settlements",
                    "pump_operator_assignments.settlement_id",
                    "=",
                    "settlements.id"
                )
                ->leftJoin(
                    "petro_shifts as ps",
                    "pump_operator_assignments.shift_id",
                    "=",
                    "ps.id"
                )
                ->where(function ($q) use ($id) {
                    // Match the create-page rules: allow open shifts, or closed-but-unsettled shifts.
                    // OR is currently assigned to the settlement being edited
                    $q->where(function ($q2) {
                        $q2->where("ps.status", 0)
                            ->whereNull("ps.closed_time");
                    })->orWhere(function ($q2) {
                        $q2->where("pump_operator_assignments.status", "close")
                            ->where("pump_operator_assignments.closed_in_settlement", 0);
                    });
                    if ($id != '0') {
                        $q->orWhere("pump_operator_assignments.settlement_id", $id);
                    }
                })
                ->where(function ($q) use ($id) {
                    // Not yet closed in settlement (either null or 0)
                    // OR is currently assigned to the settlement being edited
                    $q->where("pump_operator_assignments.closed_in_settlement", 0)
                        ->orWhereNull("pump_operator_assignments.closed_in_settlement");
                    if ($id != '0') {
                        $q->orWhere("pump_operator_assignments.settlement_id", $id);
                    }
                })
                ->where(function ($q) use ($id) {
                    // Not yet assigned to a settlement (either null or the settlement doesn't exist)
                    // OR is currently assigned to the settlement being edited
                    $q->whereNull("pump_operator_assignments.settlement_id")
                        ->orWhereNull("settlements.id");
                    if ($id != '0') {
                        $q->orWhere("pump_operator_assignments.settlement_id", $id);
                    }
                })
                ->select("pump_operator_assignments.*", "ps.status as shift_status")
                ->groupBy("pump_operator_assignments.shift_id", "pump_operator_assignments.shift_number")
                ->orderBy('pump_operator_assignments.shift_id', 'asc')
                ->get();

            $optionHtml        = '';
            $success           = true; // Changed to true by default to allow settlement without shifts
            $defaultShiftId    = null;
            $oldestShiftNumber = null;
            $hasShifts         = $assigned_pumps->isNotEmpty();

            // Log for debugging
            \Log::info('Settlement PD: Loading shifts for pump operator', [
                'pump_operator_id'     => $request->pump_operator_id,
                'assigned_pumps_count' => $assigned_pumps->count(),
                'has_shifts'           => $hasShifts,
                'shift_ids'            => $assigned_pumps->pluck('shift_id')->toArray(),
                'shift_numbers'        => $assigned_pumps->pluck('shift_number')->toArray(),
            ]);

            // dd($assigned_pumps);
            if ($hasShifts) {
                // If there are assigned pumps, handle their shifts
                foreach ($assigned_pumps as $index => $item) {
                    $shift = PetroShift::find($item->shift_id);

                    if (! $shift) {
                        continue; // skip invalid references
                    }

                    // Allow both open and closed shifts for settlement
                    $success = true;

                    $work_shifts_array = [];
                    if (!empty($settlement) && !empty($settlement->work_shift)) {
                        if (is_array($settlement->work_shift)) {
                            $work_shifts_array = $settlement->work_shift;
                        } else {
                            $decoded = json_decode($settlement->work_shift, true);
                            $work_shifts_array = is_array($decoded) ? $decoded : [$settlement->work_shift];
                        }
                    }

                    if (
                        ! empty($settlement)
                        && $settlement->pump_operator_id == $request->pump_operator_id
                        && (
                            $settlement->work_shift == $item->shift_number
                            || in_array($item->shift_number, $work_shifts_array)
                            || in_array((string)$item->shift_id, $work_shifts_array)
                            || in_array((string)$item->shift_number, $work_shifts_array)
                        )
                    ) {
                        $optionHtml .= '<option value="' . $item["shift_id"] . '" selected data-work-shift="' . ($shift->work_shift_id ?? '') . '" data-pump-operator="' . ($item["pump_operator_id"] ?? '') . '">'
                            . $item["shift_number"] . '</option>';
                    } else {
                        $optionHtml .= '<option value="' . $item["shift_id"] . '" data-work-shift="' . ($shift->work_shift_id ?? '') . '" data-pump-operator="' . ($item["pump_operator_id"] ?? '') . '">'
                            . $item["shift_number"] . '</option>';
                    }

                    // Set default shift
                    if ($defaultShiftId === null) {
                        $defaultShiftId = $item["shift_id"];
                    }
                }
            } else {
                // No assigned shifts → create empty option but allow settlement to proceed
                $directShiftLabel = $this->getNextDirectSettlementShiftLabel(
                    (int) $business_id,
                    ! empty($request->direct_shift_number) ? (string) $request->direct_shift_number : null,
                    ! empty($request->direct_shift_operator_id) ? (int) $request->direct_shift_operator_id : null,
                    ! empty($request->pump_operator_id) ? (int) $request->pump_operator_id : null
                );
                $optionHtml = '<option value="0" selected data-work-shift="" data-pump-operator="' . e($request->pump_operator_id) . '" data-direct-shift="1">'
                    . e($directShiftLabel) . '</option>';
                $success = true;
                $defaultShiftId = 0;

                if (! empty($settlement)) {
                    $settlement->work_shift = json_encode([$directShiftLabel]);
                    $settlement->save();
                }
            }

            // Auto-select default shift if applicable
            if ($defaultShiftId !== null && empty($settlement) && ! request()->has('multiple')) {
                $optionHtml = str_replace(
                    'value="' . $defaultShiftId . '"',
                    'value="' . $defaultShiftId . '" selected',
                    $optionHtml
                );
            }

            $pump_nos = collect();
            if ($hasShifts && ! empty($defaultShiftId)) {
                $assigned_pump_ids = PumpOperatorAssignment::where('business_id', $business_id)
                    ->where('pump_operator_id', $request->pump_operator_id)
                    ->where('shift_id', $defaultShiftId)
                    ->whereNull('settlement_id')
                    ->distinct()
                    ->pluck('pump_id');

                if ($assigned_pump_ids->isNotEmpty()) {
                    $pump_query = Pump::where('business_id', $business_id)
                        ->whereIn('id', $assigned_pump_ids);

                    if (Schema::hasColumn('pumps', 'is_other_sales_pump')) {
                        $pump_query->where('is_other_sales_pump', 0);
                    }

                    $pump_nos = $pump_query->pluck('pump_name', 'id');
                }
            } elseif (! $hasShifts) {
                $pump_nos = $this->getAvailableDirectSettlementPumps(
                    (int) $business_id,
                    ! empty($request->location_id) ? (int) $request->location_id : null
                );
            }

            // SMS for edits is sent from the settlement-finalize path (edit_settlements template, auto_send_sms in Petro SMS settings).

            $output = [
                "success"    => $success,
                "msg"        => __($hasShifts ? "Settlement updated successfully." : "Direct settlement shift number generated."),
                "optionHtml" => $optionHtml,
                "pump_nos"   => $pump_nos,
            ];

            //     foreach ($assigned_pumps as $item) {

            //         $shift = PetroShift::find($item->shift_id);

            //                 if (!empty($shift) && $shift->status == 0) {

            //                     $hasOpenShift = true;   // at least one open shift

            //                 }

            //         $previousUnsettled = PumpOperatorAssignment::where("pump_id", $item->pump_id)

            //         ->where("id", "<", $item->id)

            //         ->where(function ($query) {

            //             $query->whereNull("settlement_id")

            //             ->where("closed_in_settlement", false);

            //         })

            //         ->orderBy("id", "desc")

            //         ->first();

            //         \Log::info('Shift status:', [$shift]);

            //  \Log::info('Settled number:', [$previousUnsettled]);

            //  \Log::info('Item status :', [$item["shift_id"]]);

            //   \Log::info('Shift :', [$shift]);

            //         if (!empty($shift) && $shift->status == 2 && $item["status"] == "close") {

            //             $disabled = $firstOption ? "" : "";

            //             $optionHtml .='<option value="' .$item["shift_id"] .'" ' .$disabled .">" .$item["shift_number"] ."</option>";

            //             $firstOption = false;

            //             $success = true;

            //         }

            //         else {

            //            if ($hasOpenShift) {

            //             $optionHtml .=

            //                 '<option value="' .

            //                 $item["shift_id"] .

            //                 '" disabled>' .

            //                 $item["shift_number"] .

            //                 " (Not Closed)</option>";

            //             $changedFields = "Operator shift not closed.";

            //         }

            //         else

            //         {

            //             $changedFields = 'Operator shift not closed.';

            //             $success = false;

            //         }

            //     }

            //     }

            //     $output = [

            //         "success" => $success,

            //         // Assuming $changedFields is an array, join it into a string if necessary

            //         "msg" => __($changedFields),

            //         "optionHtml" => $optionHtml,

            //     ];

        } catch (\Exception $e) {
            Log::emergency(

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

        return response()->json($output);
    }

    public function canEditSettlement($id)
    {

        $settlement = Settlement::findOrFail($id);

        $transaction = Transaction::where(

            "invoice_no",

            $settlement->settlement_no

        )

            ->where("type", "sell")

            ->first();

        // see the customer payments marked as paid

        // see the loan to customer already paid for by the customer

        $paid_customer_loan = Transaction::where(

            "invoice_no",

            $settlement->settlement_no

        )

            ->where("sub_type", "customer_loan")

            ->whereIn("payment_status", ["partial", "paid"])

            ->count();

        // see the cheques already deposited

        $deposited_cheques = 0;
        if (! empty($transaction)) {

            $deposited_cheques = TransactionPayment::where(

                "transaction_id",

                $transaction->id

            )

                ->where("method", "cheque")

                ->where("is_deposited", 1)

                ->count();
        }

        $can_edit = 1;

        $reasons = "";

        if ($paid_customer_loan > 0 || $deposited_cheques > 0) {

            $can_edit = 0;

            $reasons .= "<ol>";

            if ($paid_customer_loan > 0) {

                $reasons .=

                    "<li>" . __("petropd::lang.paid_customer_loan") . "</li>";
            }

            if ($deposited_cheques > 0) {

                $reasons .=

                    "<li>" . __("petropd::lang.deposited_cheque") . "</li>";
            }

            $reasons .= "</ol>";
        }

        return [$can_edit, $reasons];
    }

    /**



     * Update the specified resource in storage.

     * @param  Request $request

     * @return Response

     */

    public function destroy($id)
    {

        try {

            DB::beginTransaction();

            $settlement = Settlement::findOrFail($id);

            $this->assertPetroPdSettlementRolePermission($settlement, 'delete');

            // Activity log for delete
            $activity_del               = new Activity();
            $activity_del->log_name     = "Settlement PD";
            $activity_del->description  = "delete";
            $activity_del->subject_id   = $settlement->id;
            $activity_del->subject_type = "App\Settlement";
            $activity_del->causer_id    = auth()->user()->id;
            $activity_del->causer_type  = "App\User";
            $activity_del->properties   = json_encode(["attributes" => $settlement->toArray()]);
            $activity_del->created_at   = date("Y-m-d H:i");
            $activity_del->updated_at   = date("Y-m-d H:i");
            $activity_del->save();

            // SMS notification for delete (reuse edit_settlements template)
            $del_data = [
                "settlement_no"    => $settlement->settlement_no,
                "editted_date"     => $this->transactionUtil->format_date(date("Y-m-d")),
                "user_editted"     => auth()->user()->username,
                "original_details" => "Settlement deleted",
                "editted_details"  => "",
            ];
            if (Str::startsWith((string) $settlement->settlement_no, 'PDST')) {
                $this->notificationUtil->sendPetroNotification("edit_settlements", $del_data);
            }

            $this->deletePreviouseTransactions($settlement->id, true);

            $settlement->delete();

            DB::commit();

            $output = [

                "success" => true,

                "msg"     => __("petropd::lang.settlement_delete_success"),

            ];
        } catch (\Exception $e) {

            Log::emergency(

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







     * get details for pump id







     * @return Response







     */

    public function unlinkSettlementPayments($settlement, $business_id)
    {
        $settlement_id = $settlement->id;

        // 1. Reset daily_cards
        \Modules\PetroPD\Entities\DailyCard::where('settlement_no', $settlement_id)
            ->update([
                'used_status' => 0,
                'settlement_no' => null
            ]);

        // 2. Reset daily_cheque_payments
        \Modules\PetroPD\Entities\DailyChequePayment::where('settlement_no', $settlement_id)
            ->update([
                'settlement_no' => null
            ]);

        // 3. Reset pump_operator_payments
        \Modules\PetroPD\Entities\PumpOperatorPayment::where('settlement_no', $settlement_id)
            ->update([
                'is_used' => 0,
                'parent_id' => null,
                'settlement_no' => null
            ]);

        // 4. Delete the linked records
        $reconciler = app(\Modules\PetroPD\Services\SettlementPaymentReconciler::class);
        foreach ([
            'settlement_card_payments',
            'settlement_cheque_payments'
        ] as $table) {
            $reconciler->wipeAllForSettlement($business_id, (string) $settlement_id, $table);
        }

        \Modules\PetroPD\Entities\SettlementShortagePayment::where('settlement_no', $settlement_id)->delete();
        \Modules\PetroPD\Entities\SettlementExcessPayment::where('settlement_no', $settlement_id)->delete();
    }

    /**

     * Remove the specified resource from storage.

     * @return Response

     */

    public function deletePreviouseTransactions(

        $settlement_id,

        $is_destory = false,

        $no_change = false

    ) {

        $business_id = request()

            ->session()

            ->get("business.id");

        $settlement = Settlement::find($settlement_id);

        $all_trasactions = Transaction::where(

            "invoice_no",

            $settlement->settlement_no

        )

            ->where("is_settlement", 1)

            ->where("business_id", $business_id)

            ->with(["sell_lines"])

            ->withTrashed()

            ->get();

        foreach ($all_trasactions as $transaction) {

            if (! empty($no_change) && $transaction->sub_type == "credit_sale") {

                // for credit sales and no change edit type; skip deleting the existing credit sales

                continue;
            }

            if (! empty($transaction)) {

                $deleted_sell_lines = $transaction->sell_lines;

                $deleted_sell_lines_ids = $deleted_sell_lines

                    ->pluck("id")

                    ->toArray();

                if ($transaction->sub_type == "credit_sale") {

                    $this->transactionUtil->deleteSellLinesSettlement(

                        $deleted_sell_lines_ids,

                        $transaction->location_id,

                        false

                    );
                } else {

                    $this->transactionUtil->deleteSellLinesSettlement(

                        $deleted_sell_lines_ids,

                        $transaction->location_id

                    );
                }

                $transaction->status = "draft";

                $business = [

                    "id"                => $business_id,

                    "accounting_method" => request()

                        ->session()

                        ->get("business.accounting_method"),

                    "location_id"       => $transaction->location_id,
                    "enable_product_expiry" => 0,
                    "on_product_expiry"     => "keep_selling",
                    "stop_selling_before"   => 0,

                ];

                if ($transaction->sub_type != "credit_sale") {

                    $this->transactionUtil->adjustMappingPurchaseSell(

                        "final",

                        $transaction,

                        $business,

                        $deleted_sell_lines_ids

                    );
                }

                //Delete Cash register transactions

                $transaction->cash_register_payments()->delete();
            }

            $tank_sell_lines = TankSellLine::where(

                "transaction_id",

                $transaction->id

            )->get();

            foreach ($tank_sell_lines as $tank_sell_line) {

                FuelTank::where("id", $tank_sell_line->tank_id)->increment(

                    "current_balance",

                    $tank_sell_line->quantity

                );
            }

            TankSellLine::where(

                "transaction_id",

                $transaction->id

            )->forceDelete();

            AccountTransaction::where(

                "transaction_id",

                $transaction->id

            )->forceDelete();

            ContactLedger::where(

                "transaction_id",

                $transaction->id

            )->forceDelete();

            ContactLedger::where('transaction_id', null)
                ->where('note', 'like', '%' . $settlement->settlement_no . '%')
                ->forceDelete();

            TransactionPayment::where(

                "transaction_id",

                $transaction->id

            )->forceDelete();

            Transaction::where("id", $transaction->id)->forceDelete();
        }

        $settlement->total_amount = 0;

        $settlement->save();

        if ($is_destory) {

            \Modules\PetroPD\Entities\PumpOperatorAssignment::where('settlement_id', $settlement->id)
                ->update([
                    'settlement_id' => null,
                    'closed_in_settlement' => 0
                ]);

            $meter_sales = MeterSale::where(

                "settlement_no",

                $settlement->id

            )->get();

            foreach ($meter_sales as $meter_sale) {

                Pump::where("id", $meter_sale->pump_id)->update([

                    "last_meter_reading" => $meter_sale->starting_meter,

                ]);

                $meter_sale->delete();
            }

            OtherSale::where("settlement_no", $settlement->id)->delete();

            OtherIncome::where("settlement_no", $settlement->id)->delete();

            CustomerPayment::where("settlement_no", $settlement->id)->delete();

            $reconciler = app(\Modules\PetroPD\Services\SettlementPaymentReconciler::class);
            foreach ([
                'settlement_card_payments',
                'settlement_cash_payments',
                'settlement_cheque_payments',
                'settlement_credit_sale_payments',
            ] as $table) {
                $reconciler->wipeAllForSettlement(
                    $business_id,
                    (string) $settlement->id,
                    $table
                );
            }

            SettlementCashDeposit::where(

                "settlement_no",

                $settlement->id

            )->delete();

            SettlementExpensePayment::where(

                "settlement_no",

                $settlement->id

            )->delete();

            SettlementExcessPayment::where(

                "settlement_no",

                $settlement->id

            )->delete();

            SettlementShortagePayment::where(

                "settlement_no",

                $settlement->id

            )->delete();

        }
    }

    public function updateCreditSales()
    {

        try {

            DB::beginTransaction();

            $trans = SettlementCreditSalePayment::where('settlement_credit_sale_payments.business_id', request()->session()->get('user.business_id'))
                ->join(

                "transactions",

                "transactions.id",

                "settlement_credit_sale_payments.transaction_id"

            )

                ->where("transactions.final_total", 0)

                ->get();

            foreach ($trans as $one) {

                $final_total = $one->amount - $one->total_discount;

                Transaction::where("id", $one->transaction_id)->update([

                    "final_total"      => $final_total,

                    "total_before_tax" => $final_total,

                ]);
            }

            $output = "Successfull";

            DB::commit();
        } catch (\Exception $e) {

            DB::rollback();

            logger($e);

            $output = "Failed";
        }

        dd($output);
    }

    /**







     * Show the form for creating a new resource.







     * @return Response







     */

    public function adjustDiscounts()
    {

        ini_set("max_execution_time", 0);
        $meter_sales = MeterSale::all();

        $other_sales = OtherSale::all();

        foreach ($meter_sales as $sale) {

            if (empty($sale->discount) || empty($sale->discount_type)) {

                MeterSale::where("id", $sale->id)->update([

                    "discount_amount" => $sale->sub_total,

                ]);
            } else {

                $discount = 0;

                if ($sale->discount_type == "fixed") {

                    $discount = $sale->discount;
                }

                if ($sale->discount_type == "percentage") {

                    $discount = ($sale->discount * $sale->sub_total) / 100;
                }

                MeterSale::where("id", $sale->id)->update([

                    "discount_amount" => $sale->sub_total - $discount,

                ]);
            }
        }

        foreach ($other_sales as $sale) {

            if (empty($sale->discount) || empty($sale->discount_type)) {

                OtherSale::where("id", $sale->id)->update([

                    "discount_amount" => 0,

                ]);
            } else {

                $discount = 0;

                if ($sale->discount_type == "fixed") {

                    $discount = $sale->discount;
                }

                if ($sale->discount_type == "percentage") {

                    $discount = ($sale->discount * $sale->sub_total) / 100;
                }

                OtherSale::where("id", $sale->id)->update([

                    "discount_amount" => $discount,

                ]);
            }
        }
    }

    public function adjustMeterSalesDates()
    {

        ini_set("max_execution_time", 0);

        $query = TankSellLine::leftjoin(

            "transactions",

            "transactions.id",

            "tank_sell_lines.transaction_id"

        )

            ->leftjoin(

                "settlements",

                "settlements.settlement_no",

                "transactions.invoice_no"

            )

            ->leftjoin("fuel_tanks", "fuel_tanks.id", "tank_sell_lines.tank_id")

            ->leftjoin("products", "products.id", "tank_sell_lines.product_id")

            ->whereDate("settlements.created_at", date("Y-m-d"))

            ->select([

                "products.name as product_name",

                "fuel_tanks.fuel_tank_number",

                "tank_sell_lines.*",

                "settlements.transaction_date",

                "settlements.settlement_no",

            ])

            ->orderBy("settlements.transaction_date", "DESC")

            ->get();

        foreach ($query as $one) {

            $transaction_date = $one->transaction_date;

            $created_at = $one->created_at;

            if (

                date("Y-m-d", strtotime($transaction_date)) !=

                date("Y-m-d", strtotime($created_at))

            ) {

                $new_date =

                    date("Y-m-d", strtotime($transaction_date)) .

                    " " .

                    date("H:i:s", strtotime($created_at));

                TankSellLine::where("id", $one->id)->update([

                    "created_at" => $new_date,

                ]);
            }
        }
    }
}

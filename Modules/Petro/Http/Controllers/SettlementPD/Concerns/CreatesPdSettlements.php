<?php

namespace Modules\Petro\Http\Controllers\SettlementPD\Concerns;

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
use Modules\Petro\Entities\PumpOperatorMeterSale;
use Modules\Petro\Entities\PumpOperatorOtherSale;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCardPayment;
use Modules\Petro\Entities\SettlementCashDeposit;
use Modules\Petro\Entities\SettlementCashPayment;
use Modules\Petro\Entities\SettlementChequePayment;
use Modules\Petro\Entities\SettlementCreditSalePayment;
use Modules\Petro\Entities\SettlementCustomerLoan;
use Modules\Petro\Entities\SettlementDrawingPayment;
use Modules\Petro\Entities\SettlementEditHistory;
use Modules\Petro\Entities\SettlementExcessPayment;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\Petro\Entities\SettlementLoanPayment;
use Modules\Petro\Entities\SettlementShortagePayment;
use Modules\Petro\Entities\TankSellLine;
use Modules\Petro\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Modules\Superadmin\Entities\Subscription;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;
use Modules\Petro\Entities\PumpOperatorMeterSaleDetail;
use Modules\PetroPD\Services\PetroPdClosedShiftQuery;

/**
 * Creating a settlement. store() is the large one - see the note in this file.
 *
 * MA-002: split out of SettlementPDController, which was 13,540 lines in a
 * single file.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged. Routes still point at
 *   SettlementPDController, action() targets still resolve, and the $this->
 *   calls between these 85 methods still work. Separate controller classes
 *   would mean rewriting routes and every action() reference - a behavioural
 *   change dressed up as tidying, and with 85 methods the odds of missing one
 *   are high.
 *
 *   So this is a purely physical split: same class at runtime, smaller files.
 *
 * A NOTE ON store()
 *   store() is 3,140 lines on its own. Breaking it up means understanding the
 *   order in which it posts transactions, account transactions, ledger rows
 *   and stock - a refactor with real risk to money, not file tidying. It
 *   belongs in its own piece of work with a before/after comparison on a real
 *   settlement. Grouping it here at least means nobody scrolls past it to
 *   reach anything else.
 *
 * Method bodies are byte-identical to the original. Nothing was rewritten
 * while moving.
 *
 * Methods here: create, store, createSettlementIfNotExist
 */
trait CreatesPdSettlements
{
    public function create()
    {

        $business_id = request()

            ->session()

            ->get("user.business_id");

        if (

            ! $this->moduleUtil->hasThePermissionInSubscription(

                $business_id,

                "enable_petro_module"

            )

        ) {

            abort(403, "Unauthorized Access");
        }

        $requestedSettlementId = ! empty(request()->view_settlement_id)
            ? (int) request()->view_settlement_id
            : (! empty(request()->settlement_id) ? (int) request()->settlement_id : null);

        // Check if pumper dashboard is enabled and operator has open shifts
        $is_pumper_dashboard_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');

        if ($is_pumper_dashboard_enabled && ! empty(request()->pump_operator)) {
            $pump_operator_id = request()->pump_operator;

            // Check if operator has any open shifts (status = 0 or 1 in petro_shifts)
            $open_shift_count = PumpOperatorAssignment::join('petro_shifts', 'pump_operator_assignments.shift_id', '=', 'petro_shifts.id')
                ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
                ->whereIn('petro_shifts.status', [0, 1]) // 0 = open, 1 = open/pending
                ->count();

            if ($open_shift_count > 0) {
                $output = [
                    'success' => false,
                    'msg'     => 'The shift is not yet closed, please close the shift and continue',
                ];
                return redirect()->back()->with('status', $output);
            }
        }

        $shiftQuery = PumpOperatorAssignment::query()
            ->where('pump_operator_assignments.business_id', $business_id)
            ->leftJoin('petro_shifts as ps', 'pump_operator_assignments.shift_id', '=', 'ps.id')
            ->where('pump_operator_assignments.status', 'close')
            ->whereNull("pump_operator_assignments.settlement_id")
            ->where("pump_operator_assignments.closed_in_settlement", 0)
            ->whereNotNull('pump_operator_assignments.close_date_and_time')
            ->where(function ($q) {
                $q->where('pump_operator_assignments.is_manually_closed', 1)
                    ->orWhere('ps.status', 2)
                    ->orWhereNotNull('ps.closed_time');
            });

        // If form should show shifts only for a selected operator (optional)
        if (! empty(request()->pump_operator)) {
            $shiftQuery->where('pump_operator_assignments.pump_operator_id', request()->pump_operator);
        }

        $shift_numbers = $shiftQuery->select(
            'pump_operator_assignments.shift_number',
            'pump_operator_assignments.shift_id',
            'pump_operator_assignments.pump_operator_id',
            'ps.work_shift_id'
        )
            ->groupBy('pump_operator_assignments.shift_id')
            ->orderBy('pump_operator_assignments.shift_id', 'asc')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->shift_id => [
                    'shift_number' => $item->shift_number,
                    'work_shift_id' => $item->work_shift_id,
                    'pump_operator_id' => $item->pump_operator_id,
                ]];
            })
            ->toArray();

        $initial_shift_numbers = $shift_numbers;

        // $open_shift = PumpOperatorAssignment::where('business_id', $business_id)
        //     ->where(function ($q) {
        //         $q->whereNull('close_date_and_time')
        //         ->orWhere('status', '!=', 'close');
        //     })
        //     ->orderByRaw('CAST(shift_number AS UNSIGNED)')
        //     ->first();

        // if ($open_shift) {
        //     return redirect()->back()->with('status', [
        //         'success' => false,
        //         'msg' => "Shift No {$open_shift->shift_number} is not yet closed in the Pumper Dashboard. Please complete it first",
        //     ]);
        // }

        // $active_shift = PumpOperatorAssignment::with(['pumpOperator', 'shift'])
        //     ->where('business_id', $business_id)
        //     ->where('status', 'close')
        //     ->whereNotNull('close_date_and_time')
        //     ->orderByDesc('close_date_and_time')
        //     ->first();


        // $shift_number = optional($active_shift)->shift_number;
        // $shift_id     = optional($active_shift)->shift_id;
        // $pump_operator_id = optional($active_shift)->pump_operator_id;
        // $pump_operator_name = optional($active_shift->pumpOperator)->name;

        // $settleable_shift_id = PumpOperatorAssignment::where('business_id', $business_id)
        //     ->where('status', 'close')
        //     ->whereNotNull('close_date_and_time')
        //     ->groupBy('shift_id')
        //     ->orderByRaw('MAX(close_date_and_time) DESC')
        //     ->value('shift_id');
        $settleable_shift_id = PumpOperatorAssignment::where('pump_operator_assignments.business_id', $business_id)
            ->leftJoin('petro_shifts as ps', 'pump_operator_assignments.shift_id', '=', 'ps.id')
            ->where('pump_operator_assignments.status', 'close')
            ->whereNull("pump_operator_assignments.settlement_id")
            ->where("pump_operator_assignments.closed_in_settlement", 0)
            ->whereNotNull('pump_operator_assignments.close_date_and_time')
            ->where(function ($q) {
                $q->where('pump_operator_assignments.is_manually_closed', 1)
                    ->orWhere('ps.status', 2)
                    ->orWhereNotNull('ps.closed_time');
            })
            ->groupBy('pump_operator_assignments.shift_id')
            ->orderBy('pump_operator_assignments.shift_id', 'asc')
            ->value('pump_operator_assignments.shift_id');

        $shift_assignments = PumpOperatorAssignment::with('pumpOperator')
            ->where('business_id', $business_id)
             ->whereNull("settlement_id")
            ->where("closed_in_settlement",0)
            ->where('shift_id', $settleable_shift_id)
            ->where('status', 'close')
            ->get();

        $firstAssignment = $shift_assignments->first();

        $shift_id = $settleable_shift_id;
        $shift_number = optional($firstAssignment)->shift_number;

        // $pump_operator_name = $shift_assignments->count() > 1
        // ? 'Multiple Operators'
        // : optional($firstAssignment?->pumpOperator)->name;
        $pump_operator_name = optional($firstAssignment?->pumpOperator)->name;   //sapna 09-02-2026
        $pump_operator_id = optional($firstAssignment?->pumpOperator)->id;






        $next_shift_number = $shift_number
            ? ((int) $shift_number)
            : null;



        $reviewed = $this->transactionUtil->get_review(

            date("Y-m-d"),

            date("Y-m-d")

        );

        if (! empty($reviewed)) {

            $output = [

                "success" => 0,

                "msg"     =>

                "You can't add a settlement for an already reviewed date",

            ];

            return redirect()

                ->back()

                ->with(["status" => $output]);
        }

        $business_id = request()

            ->session()

            ->get("business.id");

        $business = Business::where("id", $business_id)->first();

        $pos_settings = json_decode($business->pos_settings, true);

        $check_qty = ! empty($pos_settings["allow_overselling"]) ? false : true;

        $cash_denoms = ! empty($pos_settings["cash_denominations"])

            ? explode(",", $pos_settings["cash_denominations"])

            : [];

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

        $customers = Contact::customersDropdown($business_id, false);

        $pump_operators = PumpOperator::where(

            "business_id",

            $business_id

        )->pluck("name", "id");

        $items = [];

        $ref_no_prefixes = request()

            ->session()

            ->get("business.ref_no_prefixes");

        $ref_no_starting_number = request()

            ->session()

            ->get("business.ref_no_starting_number");

        $prefix = ! empty($ref_no_prefixes["settlement"])

            ? $ref_no_prefixes["settlement"]

            : "ST";

        $starting_no = ! empty($ref_no_starting_number["settlement_pd"])

            ? (int) $ref_no_starting_number["settlement_pd"]

            : 1;

        $count = Settlement::where("business_id", $business_id)
            ->where("settlement_no", "LIKE", $prefix . "%")
            ->where("settlement_no", "NOT LIKE", "SET-SW%")
            ->where("settlement_no", "NOT LIKE", "PDST%")
            ->orderBy("id", "DESC")
            ->first();

        if (! empty($count)) {

            $count = $this->extractLastInteger($count->settlement_no);
        } else {

            $count = 0;
        }

        $nextNumber = 1 + $count;
        while (Settlement::where('business_id', $business_id)->where('settlement_no', $prefix . $nextNumber)->exists()) {
            $nextNumber++;
        }
        $settlement_no = $prefix . $nextNumber;

        $currency_precision = ! empty($business->currency_precision)

            ? $business->currency_precision

            : 2;

        $meeter_precision = 3;

        $active_settlement = Settlement::where("status", 1)

            ->where("business_id", $business_id);

        $this->applyPdSettlementScope($active_settlement, $business_id);

        if (! empty($requestedSettlementId)) {
            $active_settlement->where('id', $requestedSettlementId);
        }

        $active_settlement = $active_settlement

            ->select("settlements.*")

            ->with([

                "meter_sales",

                "meter_sales_pd",

                "other_sales",

                "other_incomes",

                "customer_payments",

            ])

            ->first();

        $is_finishing_existing_settlement = ! empty($requestedSettlementId) && ! empty($active_settlement);
        $can_resume_active_settlement = false;

        $other_sale_final_total = 0.0;

        $pump_other_sale_final_total = 0.0;

        $combinedOtherSales = [];

        if ($active_settlement) {
            $draft_resume_context = $this->getValidPdDraftResumeContext($active_settlement, (int) $business_id);

            if ($draft_resume_context['valid']) {
                $can_resume_active_settlement = true;
                $active_settlement->pump_operator_id = $draft_resume_context['pump_operator_id'];
                $shift_id = $draft_resume_context['shift_id'];
                $shift_number = $draft_resume_context['shift_numbers'];
                $shift_numbers = $draft_resume_context['shift_numbers'];
            } else {
                Log::warning('Ignoring inconsistent PD draft on create', [
                    'settlement_id' => $active_settlement->id,
                    'settlement_no' => $active_settlement->settlement_no,
                    'reason' => $draft_resume_context['reason'],
                ]);

                $active_settlement = null;
                $shift_id = null;
                $shift_number = null;
                $shift_numbers = $initial_shift_numbers;
                $pump_operator_id = null;
                $pump_operator_name = null;
            }

            if ($can_resume_active_settlement) {
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

                    "price"         => number_format(

                        $ot_item->price,

                        $currency_precision

                    ),

                    // Derive qty from sub_total/price to preserve fractional values even if stored qty was rounded
                    "qty"           => number_format(
                        (! empty($ot_item->price) && $ot_item->price != 0)
                            ? (($ot_item->sub_total ?? 0) / $ot_item->price)
                            : $ot_item->qty,
                        4,
                        ".",
                        ","
                    ),

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

            // $shiftIds = array_column($shift_number, "shift_id");
            $shiftIds = [];

            if ($shift_id) {
                $shiftIds = [$shift_id];
            }

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

                    "price"         => number_format(

                        $pumpSale->price,

                        $currency_precision

                    ),

                    // Preserve fractional quantities: derive from sub_total/price when present
                    "qty"           => number_format(
                        (! empty($pumpSale->price) && $pumpSale->price != 0)
                            ? ($pumpSale->sub_total / $pumpSale->price)
                            : $pumpSale->qty,
                        4,
                        ".",
                        ","
                    ),

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

                    "user_check"    => 0, // 0 for pump operator entry

                ];
            }

                $combinedOtherSales = array_merge(

                    $userOtherDetails,

                    $pumperOthersaleDetails

                );

                $final_other_sale_total =

                    $other_sale_final_total + $pump_other_sale_final_total;
            }
        }

        if (! $can_resume_active_settlement) {
            $shift_id = $settleable_shift_id;
            $shift_number = optional($firstAssignment)->shift_number;
            $pump_operator_id = optional($firstAssignment?->pumpOperator)->id;
            $pump_operator_name = optional($firstAssignment?->pumpOperator)->name;
            $shift_numbers = $initial_shift_numbers;
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        if (! empty($active_settlement)) {

            // $already_pumps = PumpOperatorMeterSale::where(

            //     "settlement_no",

            //     $active_settlement->id

            // )

            //     ->pluck("pump_id")

            //     ->toArray();
                $already_pumps = PumpOperatorMeterSale::where(function ($q) use ($active_settlement) {
                        $q->where('settlement_no', $active_settlement->settlement_no)
                          ->orWhere('settlement_no', $active_settlement->id);
                    })
    ->with('details:id,pump_id')
    ->get()
    ->pluck('details.pump_id')
    ->filter()
    ->unique()
    ->values()
    ->toArray();

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

        $services = Product::where("business_id", $business_id)

            ->forModule("petro_settlements")

            ->where("enable_stock", 0)

            ->pluck("name", "id");

        $subscription = Subscription::active_subscription($business_id);

        if (is_null($subscription)) {

            $show_shift_no = false;
        } else {

            if ($subscription->customer_credit_notification_type == []) {

                $show_shift_no = false;
            } else {

                $firstDecode = json_decode(

                    $subscription->customer_credit_notification_type,

                    true

                );

                if (is_string($firstDecode)) {

                    $decodedData = json_decode($firstDecode, true);

                    $show_shift_no = in_array("pumper_dashboard", $decodedData)

                        ? true

                        : false;
                } else {

                    $show_shift_no = false;
                }
            }
        }

        $pump_operator_id = ! empty($active_settlement)
            ? (int) $active_settlement->pump_operator_id
            : (! empty(request()->pump_operator)
                ? (int) request()->pump_operator
                : (! empty($pump_operator_id) ? (int) $pump_operator_id : null));
        $payment_meter_sale_total = (! empty($shift_id) && ! empty($pump_operator_id))
            ? $this->getMeterSaleTotalByShift(
                (int) $business_id,
                $shift_id,
                $pump_operator_id
            )
            : 0.0;

        $payment_other_sale_total = ! empty($active_settlement) && ! empty($active_settlement->other_sales)

            ? $active_settlement->other_sales->sum("sub_total")

            : 0.0;

        $payment_other_sale_discount = ! empty($active_settlement) && ! empty($active_settlement->other_sales)

            ? $active_settlement->other_sales->sum("sub_total")

            : 0.0;

        $payment_other_sale_total -= $payment_other_sale_discount;

        $payment_other_income_total = ! empty($active_settlement) && ! empty($active_settlement->other_incomes)

            ? $active_settlement->other_incomes->sum("sub_total")

            : 0.0;

        $payment_customer_payment_total = ! empty($active_settlement) && ! empty($active_settlement->customer_payments)

            ? $active_settlement->customer_payments->sum("sub_total")

            : 0.0;

        $work_shifts = WorkShift::where("business_id", $business_id)->pluck(

            "shift_name",

            "id"

        );

        $bulk_tanks = FuelTank::where("business_id", $business_id)

            ->where("bulk_tank", 1)

            ->pluck("fuel_tank_number", "id");

        $select_pump_operator_in_settlement = $this->moduleUtil->hasThePermissionInSubscription(

            $business_id,

            "select_pump_operator_in_settlement"

        );

        $message = $this->transactionUtil->getGeneralMessage(

            "general_message_pump_management_checkbox"

        );

        $closed_shift = PumpOperatorAssignment::where('business_id', $business_id)
            ->whereNotNull('close_date_and_time')
            ->latest('id')
            ->first();

        $meter_sales = [];

        if ($closed_shift && ! empty($shift_id) && ! empty($pump_operator_id)) {
         //   dd($settlement_no);
            // $pump_ids = $shift_assignments->pluck('pump_id')->filter()->unique();
            if (empty($active_settlement)) {
                // Only assign meters that were added via Settlement PD, not from Pumper Dashboard / Payments page
                PumpOperatorMeterSale::where('business_id', $business_id)
                    ->where('shift_id', $shift_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->when(true, function ($query) {
                        $this->onlyClosedPumpMeterSales($query);
                    })
                    ->whereNull('p_o_payment_id')
                    ->where(function ($q) {
                        $q->whereNull('settlement_no')
                            ->orWhere('settlement_no', '');
                    })
                    ->update([
                        'settlement_no' => $settlement_no
                    ]);
            }
            // Only show meters added via Settlement PD; exclude those from Pumper Dashboard / Payments page
            $meter_sales = PumpOperatorMeterSale::with([
                'details',
                'details.pump',
            ])
                ->where('business_id', $business_id)
                ->where('shift_id', $shift_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->when(true, function ($query) {
                    $this->onlyClosedPumpMeterSales($query);
                })
                ->whereNull('p_o_payment_id')
                ->get();

            \Modules\Petro\Support\PetroDebug::info('Meter sales loaded in Settlement PD create', ['count' => $meter_sales->count()]);


            $pump_nos = Pump::whereIn(
                'id',
                PumpOperatorAssignment::where('shift_id', $shift_id)
                    ->where('business_id', $business_id)
                    ->when(! empty($pump_operator_id), function ($query) use ($pump_operator_id) {
                        $query->where('pump_operator_id', $pump_operator_id);
                    })
                    ->pluck('pump_id')
            )->pluck('pump_name', 'id');
        }

        // If there is no active settlement, load other sales for the autoloaded closed shift
        if (empty($combinedOtherSales) && ! empty($shift_id)) {
            $otherQuery = PumpOperatorOtherSale::join(
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
                ->where('pump_operator_other_sales.shift_id', $shift_id)
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
            $pumpOtherRows = $otherQuery->get();

            foreach ($pumpOtherRows as $pumpSale) {
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
                    "price"         => number_format(
                        $pumpSale->price,
                        $currency_precision
                    ),
                    "qty"           => number_format(
                        (! empty($pumpSale->price) && $pumpSale->price != 0)
                            ? ($pumpSale->sub_total / $pumpSale->price)
                            : $pumpSale->qty,
                        4,
                        ".",
                        ","
                    ),
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

            $combinedOtherSales = array_merge($combinedOtherSales, $pumperOthersaleDetails);
        }

        \Modules\Petro\Support\PetroDebug::info('combined other sales: ', $combinedOtherSales);

        $discount_types = ["fixed" => "Fixed", "percentage" => "Percentage"];

        // Fetch settlement credit sale payments for the Credit Sales tab
        $settlement_credit_sale_payments = collect();
        if (! empty($active_settlement)) {
            $settlement_credit_sale_payments = SettlementCreditSalePayment::where('settlement_credit_sale_payments.business_id', $active_settlement->business_id)
                ->leftJoin(
                'contacts',
                'settlement_credit_sale_payments.customer_id',
                '=',
                'contacts.id'
            )
                ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
                ->where(function ($q) use ($active_settlement) {
                    $q->where('settlement_credit_sale_payments.settlement_no', $active_settlement->settlement_no)
                        ->orWhere('settlement_credit_sale_payments.settlement_no', $active_settlement->id);
                })
                ->select(
                    'settlement_credit_sale_payments.*',
                    'contacts.name as customer_name',
                    'products.name as product_name'
                )
                ->get();
        }

        $can_edit_details = [1, ""];
        if (! empty($active_settlement) && ! empty($active_settlement->id)) {
            $can_edit_details = $this->canEditSettlement($active_settlement->id);
        }

        return view("petro::settlement_pd.edit")->with(

            compact(

                "select_pump_operator_in_settlement",

                "message",

                "shift_numbers",

                "business_locations",

                "payment_types",

                "customers",

                "pump_operators",

                "work_shifts",

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

                "services",

                "discount_types",

                "cash_denoms",

                "check_qty",

                "payment_other_sale_discount",

                "show_shift_no",

                "combinedOtherSales",

                "pump_other_sale_final_total",

                "settlement_credit_sale_payments",

                "can_edit_details",

                "next_shift_number",

                "pump_operator_name",
                "meter_sales",
                "pump_operator_id",
                "is_finishing_existing_settlement",
                "shift_id"

            )

        );
    }

    /**

     * Store a newly created resource in storage.

     * @param  Request $request

     * @return Response

     */

    // change and set by @zeeshan ali

    public function store(Request $request, ContactController $contactController)
    {
        try {

            $denom_qty     = $request->denom_qty;
            $denom_value   = $request->denom_value;
            $denom_enabled = $request->denom_enabled;
            $denom_data    = [];

            $business_id = request()->session()->get("user.business_id");

            $settings = \Modules\Petro\Entities\PumpOperator::where('business_id', $business_id)->whereNotNull('dashboard_settings')->first();
            $dashboard_settings = (! is_null($settings)) ? json_decode($settings->dashboard_settings, true) : [];
            $update_ledger = ($dashboard_settings['real_time_update_customer_ledger'] ?? 'no') === 'yes';
            $update_account = ($dashboard_settings['real_time_update_account_books'] ?? 'no') === 'yes';
            $pumper_ledger_update = ($dashboard_settings['pumper_ledger_update'] ?? 'no') === 'yes';

            if ($denom_enabled > 0) {
                $i = 0;
                foreach ($denom_qty as $one) {
                    $denom_data[] = [
                        "value" => $denom_value[$i],
                        "qty"   => $denom_qty[$i],
                    ];
                    $i++;
                }
            }

            $settlement_no = $request->settlement_no;
            $no_change     = $request->no_change;
            $business_id   = $request->session()->get("business.id");

            // Try to find settlement by settlement_no first
            $settlement = Settlement::where("settlement_no", $request->settlement_no)
                ->where("business_id", $business_id)
                ->first();

            // If not found, try to find by ID (in case settlement_no is actually an ID)
            if (empty($settlement) && is_numeric($request->settlement_no)) {
                $settlement = Settlement::where("id", $request->settlement_no)
                    ->where("business_id", $business_id)
                    ->first();
            }

            // If no active settlement found, log and return a friendly validation error
            if (empty($settlement)) {
                \Log::error('Settlement PD store: Settlement not found for finalize', [
                    'settlement_no'           => $request->settlement_no,
                    'business_id'             => $business_id,
                    'url'                     => $request->fullUrl(),
                    'input'                   => $request->all(),
                    'all_settlements_with_no' => Settlement::where('business_id', $business_id)->pluck('settlement_no', 'id')->toArray(),
                ]);

                return response()->json([
                    "success" => 0,
                    "msg"     => __("petro::lang.settlement_not_found_for_finalize") ?: "Unable to finalize: settlement not found or already closed. Please ensure you have created the settlement before saving Payment to Finalize.",
                ]);
            }

            $edit = Settlement::where("id", $settlement->id)
                ->where("business_id", $business_id)
                ->where("status", 0)
                ->first();

            $pump_operator_total_other_sale = 0;
            $pump_operator_other_sales      = [];

            if ($request->shift_ids) {
                $shift_ids = is_array($request->shift_ids)
                    ? $request->shift_ids
                    : explode(",", $request->shift_ids);

                $pump_operator_total_other_sale = PumpOperatorOtherSale::join(
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
                    ->whereIn("pump_operator_other_sales.shift_id", $shift_ids);

                $pump_operator_total_other_sale = $pump_operator_total_other_sale->join(
                    "pump_operator_assignments",
                    function ($join) {
                        $join
                            ->on(
                                "pump_operator_assignments.shift_id",
                                "=",
                                "pump_operator_other_sales.shift_id"
                            )
                            ->where("pump_operator_assignments.status", "close")
                            ->whereRaw(
                                'pump_operator_assignments.id = (
                                    SELECT MAX(poa.id)
                                    FROM pump_operator_assignments poa
                                    WHERE poa.shift_id = pump_operator_other_sales.shift_id
                                    AND poa.status = "close"
                                )'
                            );
                    }
                );

                $pump_operator_other_sales = $pump_operator_total_other_sale
                    ->select("pump_operator_other_sales.*")
                    ->get();

                // Net after discount (must match other_sales and sell lines — was gross sub_total only).
                $pump_operator_total_other_sale = $pump_operator_other_sales->sum(function ($row) {
                    $sub = (float) ($row->sub_total ?? 0);
                    if (empty($row->discount_type)) {
                        return $sub;
                    }
                    if ($row->discount_type === "percentage") {
                        return max(0, $sub - ($sub * (float) ($row->discount ?? 0) / 100));
                    }
                    $off = (float) ($row->discount ?? 0);
                    if ($off <= 0) {
                        $off = (float) ($row->discount_amount ?? 0);
                    }

                    return max(0, $sub - $off);
                });
            } else {
                $shift_ids = [];
            }
            if (empty($shift_ids)) {
                $shift_ids = PumpOperatorMeterSale::where('business_id', $business_id)
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->when(true, function ($query) {
                        $this->onlyClosedPumpMeterSales($query);
                    })
                    ->where(function ($query) use ($settlement) {
                        $query->where('settlement_no', $settlement->settlement_no)
                            ->orWhere('settlement_no', (string) $settlement->id);
                    })
                    ->pluck('shift_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            }
            if (empty($shift_ids)) {
                $shift_ids = PumpOperatorAssignment::where('business_id', $business_id)
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->where('settlement_id', $settlement->id)
                    ->pluck('shift_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            }
            if (empty($pump_operator_other_sales) && ! empty($shift_ids)) {
                $pump_operator_other_sales = $this->getPumpOperatorOtherSalesForFinalization($business_id, $shift_ids);
                $pump_operator_total_other_sale = $pump_operator_other_sales->sum(function ($row) {
                    $sub = (float) ($row->sub_total ?? 0);
                    if (empty($row->discount_type)) {
                        return $sub;
                    }
                    if ($row->discount_type === "percentage") {
                        return max(0, $sub - ($sub * (float) ($row->discount ?? 0) / 100));
                    }
                    $off = (float) ($row->discount ?? 0);
                    if ($off <= 0) {
                        $off = (float) ($row->discount_amount ?? 0);
                    }

                    return max(0, $sub - $off);
                });
            }

            $pd_meter_sales_for_transaction = $this->getPdMeterSalesForFinalization($settlement, $shift_ids);
            $pd_meter_sales_total = $pd_meter_sales_for_transaction->sum(function ($sale) {
                return (float) ($sale->amount ?? $sale->balance ?? 0);
            });
            $regular_meter_sales_for_transaction = $this->getRegularMeterSalesForFinalization(
                $settlement,
                $pd_meter_sales_for_transaction
            );

            // Adding daily collection to cash payments
            // Modified by Engr. Alex -- task 7889: use post-discount amounts
            // meter_sales.discount_amount = net after discount; other_sales.discount_amount = discount value
            $settlement_total =
                $regular_meter_sales_for_transaction->sum("discount_amount") +
                $pd_meter_sales_total +
                ($settlement->other_sales->sum("sub_total") - $settlement->other_sales->sum("discount_amount")) +
                $settlement->other_incomes->sum("sub_total") +
                $settlement->customer_payments->sum("sub_total") +
                $pump_operator_total_other_sale;

            // Get daily collections
            // Include both unlinked records AND records already linked to this settlement
            $shift_ids_for_collections = $shift_ids;
            if (empty($shift_ids_for_collections) && ! empty($settlement->work_shift)) {
                $decoded_work_shift = is_array($settlement->work_shift)
                    ? $settlement->work_shift
                    : json_decode($settlement->work_shift, true);
                $shift_ids_for_collections = is_array($decoded_work_shift)
                    ? $decoded_work_shift
                    : explode(",", $settlement->work_shift);
            }
            $shift_ids_for_collections = array_filter(array_map('intval', (array) $shift_ids_for_collections));

            if (empty($shift_ids_for_collections)) {
                \Log::warning('Settlement PD: Skipping daily collection updates due to missing shift_ids', [
                    'settlement_id'    => $settlement->id,
                    'settlement_no'    => $settlement->settlement_no,
                    'pump_operator_id' => $settlement->pump_operator_id,
                ]);
                $daily_collections = collect();
            } else {
                $daily_collections = DailyCollection::leftJoin(
                    "business_locations",
                    "daily_collections.location_id",
                    "business_locations.id"
                )
                    ->leftJoin("pump_operators", "daily_collections.pump_operator_id", "pump_operators.id")
                    ->leftJoin("users", "daily_collections.created_by", "users.id")
                    ->leftJoin("settlements", "daily_collections.settlement_id", "settlements.id")
                    ->where("daily_collections.business_id", $business_id)
                    ->where("daily_collections.type", "daily_collection")
                    ->where("daily_collections.pump_operator_id", $settlement->pump_operator_id)
                    ->where(function ($q) use ($settlement) {
                        // Include unlinked records OR records already linked to this settlement
                        $q->where(function ($subQ) {
                            $subQ->whereNull("daily_collections.settlement_id")
                                ->whereNull("daily_collections.added_to_account");
                        })
                            ->orWhere("daily_collections.settlement_id", $settlement->id);
                    })
                    ->whereIn("daily_collections.shift_id", $shift_ids_for_collections)
                    ->select([
                        "daily_collections.*",
                        "business_locations.name as location_name",
                        "pump_operators.name as pump_operator_name",
                        "settlements.id as settlements_id",
                        "users.username as user",
                    ])
                    ->orderBy("daily_collections.id")
                    ->get();
            }

            \Modules\Petro\Support\PetroDebug::info('Settlement PD DailyCollections Query', [
                'settlement_id'           => $settlement->id,
                'settlement_no'           => $settlement->settlement_no,
                'pump_operator_id'        => $settlement->pump_operator_id,
                'shift_ids'               => $shift_ids,
                'daily_collections_found' => $daily_collections->count(),
            ]);

            // Check if there are actual changes being made (payments, collections, etc.)
            // Only block if settlement is already finalized AND no_change is set AND there are no pending changes
            $has_pending_changes = false;

            // Check if there are unlinked daily collections that need to be processed
            $unlinked_collections = $daily_collections->whereNull('settlement_id')->count();
            if ($unlinked_collections > 0) {
                $has_pending_changes = true;
            }

            // Check if there are payments that need to be processed
            $has_cash_payments = SettlementCashPayment::where('settlement_no', $settlement->id)->exists();
            $has_credit_sales  = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->id)
                        ->orWhere('settlement_no', $settlement->settlement_no)
                        ->orWhereNull('settlement_no');
                })
                ->exists();

            if ($has_cash_payments || $has_credit_sales) {
                $has_pending_changes = true;
            }

            // Only block if there are truly no changes AND no_change is explicitly set
            // If no_change is empty, allow the save to proceed (it might be a final save with payments)
            if ($settlement->is_edit == 0 && $settlement->status == 0 && ! empty($no_change) && ! $has_pending_changes) {
                return [
                    "success" => 0,
                    "msg"     => __("petro::lang.no_change_performed"),
                ];
            }

            DB::beginTransaction();

            if (! empty($edit)) {
                $this->deletePreviouseTransactions($settlement->id, false, $no_change);
            }

            $business_locations = BusinessLocation::forDropdown($business_id);
            $default_location   = current(array_keys($business_locations->toArray()));

            // CRITICAL: Don't filter settlement by shift_ids in the reload - this causes the settlement to disappear
            // if there are no matching pump_operator_assignments for those shift_ids
            // The shift filtering should only apply to payments, not to the settlement itself
            // Use a simple reload without joins to avoid any filtering issues
            $settlement = Settlement::where("id", $settlement->id)
                ->where("business_id", $business_id)
                ->with([
                    "meter_sales",
                     "meter_sales_pd",
                     "meter_sales_pd.details",
                    "other_sales",
                    "other_incomes",
                    "customer_payments",
                    "cash_payments",
                    "cash_deposits",
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

            // If the reload with joins/shift filters dropped the settlement, fail gracefully
            if (empty($settlement)) {
                \Log::error('Settlement PD store: Settlement disappeared after reload with shift filters', [
                    'original_settlement_no' => $request->settlement_no,
                    'business_id'            => $business_id,
                    'shift_ids'              => $shift_ids,
                    'url'                    => $request->fullUrl(),
                    'input'                  => $request->all(),
                ]);

                return [
                    "success" => 0,
                    "msg"     => __("petro::lang.settlement_not_found_for_finalize") ?: "Unable to finalize: settlement not found with the selected shift(s). Please ensure the shift filter matches this settlement and try again.",
                ];
            }

            // CRITICAL: DO NOT filter meter_sales and credit_sale_payments in the store method
            // The store method should process ALL entries that were added to the settlement
            // Filtering should only happen in show/print methods for display purposes

            // Repair PumpOperatorMeterSale records that have integer ID instead of string settlement_no
            if (! empty($settlement)) {
                $this->repairMeterSalePdSettlementNo($settlement);
            }

            $pd_meter_sales_for_transaction = $this->getPdMeterSalesForFinalization($settlement, $shift_ids);
            $pd_meter_sales_total = $pd_meter_sales_for_transaction->sum(function ($sale) {
                return (float) ($sale->amount ?? $sale->balance ?? 0);
            });
            $regular_meter_sales_for_transaction = $this->getRegularMeterSalesForFinalization(
                $settlement,
                $pd_meter_sales_for_transaction
            );

            // Fallback to load payments by both settlement ID (integer) and settlement_no (string)
            // This ensures payments saved with settlement->id are found during finalization
            if (! empty($settlement)) {
                // Reload credit sales if empty
                if ($settlement->credit_sale_payments->isEmpty()) {
                    $credit_sales = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                        ->where('pump_operator_id', $settlement->pump_operator_id)
                        ->where(function ($q) use ($settlement) {
                            $q->where('settlement_no', $settlement->settlement_no)
                                ->orWhere('settlement_no', $settlement->id);
                        })
                        ->with('product')
                        ->get();
                    $settlement->setRelation('credit_sale_payments', $credit_sales);
                }

                // Reload card payments if empty
                if ($settlement->card_payments->isEmpty()) {
                    $card_payments_query = SettlementCardPayment::where(function ($q) use ($settlement) {
                        $q->where('settlement_card_payments.settlement_no', $settlement->settlement_no)
                            ->orWhere('settlement_card_payments.settlement_no', $settlement->id);
                    });

                    // CRITICAL FIX: Filter card payments by shift_id to prevent Shift 5 payments appearing in Shift 3 settlement
                    // When user selects Shift 5 → goes to Payment to Finalize → Back → selects Shift 3 → Finalize
                    // We must ONLY load card payments for the CURRENT shift selection (Shift 3), not old Shift 5 entries
                    if (! empty($shift_ids) && count($shift_ids) > 0) {
                        $card_payments_query->leftJoin('daily_cards', 'settlement_card_payments.daily_card_id', '=', 'daily_cards.id')
                            ->leftJoin('pump_operator_payments', function ($join) {
                                $join->on('pump_operator_payments.pump_operator_id', '=', 'daily_cards.pump_operator_id')
                                    ->whereRaw('pump_operator_payments.collection_form_no COLLATE utf8mb4_unicode_ci = daily_cards.collection_no COLLATE utf8mb4_unicode_ci')
                                    ->where('pump_operator_payments.payment_type', 'card')
                                    ->whereColumn('pump_operator_payments.payment_amount', 'daily_cards.amount');
                            })
                            ->where(function ($subQ) use ($shift_ids) {
                                $subQ->whereIn('pump_operator_payments.shift_id', $shift_ids);
                            })
                            ->select('settlement_card_payments.*'); // Only select settlement_card_payments columns
                    }

                    $card_payments = $card_payments_query->get();
                    $settlement->setRelation('card_payments', $card_payments);
                }

                // Reload cash payments if empty
                if ($settlement->cash_payments->isEmpty()) {
                    $cash_payments = SettlementCashPayment::where(function ($q) use ($settlement) {
                        $q->where('settlement_no', $settlement->settlement_no)
                            ->orWhere('settlement_no', $settlement->id);
                    })->get();
                    $settlement->setRelation('cash_payments', $cash_payments);
                }

                // Reload loan payments if empty
                if ($settlement->loan_payments->isEmpty()) {
                    $loan_payments = SettlementLoanPayment::where(function ($q) use ($settlement) {
                        $q->where('settlement_no', $settlement->settlement_no)
                            ->orWhere('settlement_no', $settlement->id);
                    })->get();
                    $settlement->setRelation('loan_payments', $loan_payments);
                }
            }

            // dd('out', $daily_collections);
            $outstanding_payment = $settlement_total;

            // Create SettlementCashPayment records from DailyCollection (similar to direct settlement)
            // This ensures cash entries from daily collection are also converted to cash payments
            $daily_collection_cash_count = 0;
            foreach ($daily_collections as $row) {
                $amount = floatval($row->current_amount);
                $alloc  = min($amount, $outstanding_payment);

                // Try to link DailyCollection to a PumpOperatorPayment to avoid duplicate cash payments
                $linked_pump_payment = null;
                if (! empty($row->collection_form_no)) {
                    $linked_pump_payment = \Modules\Petro\Entities\PumpOperatorPayment::where('business_id', $business_id)
                        ->where('pump_operator_id', $settlement->pump_operator_id)
                        ->where('payment_type', 'cash')
                        ->where('collection_form_no', $row->collection_form_no)
                        ->where(function ($q) {
                            $q->whereNull('is_used')->orWhere('is_used', 0);
                        })
                        ->first();
                }

                // Check if SettlementCashPayment already exists for this daily collection
                // Check by amount + settlement_no to avoid duplicates
                // Note: DailyCollection doesn't have a direct link to SettlementCashPayment via customer_payment_id
                $existing_cash_payment = SettlementCashPayment::where('business_id', $business_id)
                    ->where('settlement_no', $settlement->id)
                    ->where('amount', $alloc)
                    ->first();

                if ($linked_pump_payment) {
                    $existing_by_pump_payment = SettlementCashPayment::where('business_id', $business_id)
                        ->where('settlement_no', $settlement->id)
                        ->where('customer_payment_id', $linked_pump_payment->id)
                        ->first();
                    if ($existing_by_pump_payment) {
                        $existing_cash_payment = $existing_by_pump_payment;
                    }
                }

                if (! $existing_cash_payment && $alloc > 0) {
                    // Get default customer (Walk-In Customer)
                    $walkin_customer = Contact::where('name', 'Walk-In Customer')
                        ->where('business_id', $business_id)
                        ->first();
                    $default_customer_id = $walkin_customer ? $walkin_customer->id : null;

                    // If no walk-in customer, get first customer from dropdown
                    if (! $default_customer_id) {
                        $customers           = Contact::customersDropdown($business_id, false, true, 'customer');
                        $default_customer_id = array_key_first($customers->toArray());
                    }

                    $settlement_cash_payment = app(\Modules\Petro\Services\SettlementPaymentReconciler::class)->upsertOne(
                        $business_id,
                        (string) $settlement->id,
                        'settlement_cash_payments',
                        [
                            'amount'              => $alloc,
                            'customer_id'         => $default_customer_id,
                            'customer_payment_id' => ! empty($linked_pump_payment) ? $linked_pump_payment->id : null,
                            'pump_payment_id'     => ! empty($linked_pump_payment) ? $linked_pump_payment->id : null,
                            'note'                => 'Daily Collection',
                        ]
                    );

                    $daily_collection_cash_count++;

                    if (! empty($linked_pump_payment)) {
                        $linked_pump_payment->is_used       = 1;
                        $linked_pump_payment->parent_id     = $settlement_cash_payment->id;
                        $linked_pump_payment->settlement_no = $settlement->id;
                        $linked_pump_payment->save();
                    }
                } elseif (! empty($linked_pump_payment) && ! empty($existing_cash_payment) && empty($existing_cash_payment->customer_payment_id)) {
                    $existing_cash_payment = app(\Modules\Petro\Services\SettlementPaymentEditService::class)
                        ->editCashPayment($business_id, $existing_cash_payment->id, [
                            'customer_payment_id' => $linked_pump_payment->id,
                        ]);
                    $linked_pump_payment->is_used       = 1;
                    $linked_pump_payment->parent_id     = $existing_cash_payment->id;
                    $linked_pump_payment->settlement_no = $settlement->id;
                    $linked_pump_payment->save();
                }

                $row->update([
                    'settlement_id'      => $settlement->id,
                    'settlement_date'    => $settlement->finish_date ?? date('Y-m-d'),
                    'balance_collection' => $alloc,
                    'added_to_account'   => 1,
                ]);
            }

            \Modules\Petro\Support\PetroDebug::info('Settlement PD DailyCollection Cash Payments Created', [
                'settlement_id'                 => $settlement->id,
                'settlement_no'                 => $settlement->settlement_no,
                'daily_collections_count'       => $daily_collections->count(),
                'created_from_daily_collection' => $daily_collection_cash_count,
            ]);

            $business      = Business::where("id", $settlement->business_id)->first();
            $pump_operator = PumpOperator::where("id", $settlement->pump_operator_id)->first();

            // Include both direct meter_sales and PD meter_sales in totals
            // Modified by Engr. Alex -- task 7889: use post-discount amounts
            // meter_sales.discount_amount = net after discount; other_sales.discount_amount = discount value
            $total_sales_amount =
                $regular_meter_sales_for_transaction->sum('discount_amount') +
                $pd_meter_sales_total +
                ($settlement->other_sales->sum("sub_total") - $settlement->other_sales->sum("discount_amount")) +
                $pump_operator_total_other_sale;

            // Store gross pre-discount total for reference in transactions.discount_amount
            $total_sales_discount_amount =
                $regular_meter_sales_for_transaction->sum('sub_total') +
                $pd_meter_sales_total +
                $settlement->other_sales->sum("sub_total");

            // $pump_ids = $settlement->meter_sales_pd->pluck("pump_id")->unique()->toArray();

$pump_ids = $pd_meter_sales_for_transaction
    ->pluck('details')     // get details collections
    ->flatten()            // flatten nested collections
    ->pluck('pump_id')     // now pluck pump_id
    ->filter()             // remove nulls
    ->unique()
    ->values()
    ->toArray();
   // dd($pump_ids);
            $pumps = Pump::whereIn("id", $pump_ids)
                ->select("pump_name")
                ->pluck("pump_name")
                ->toArray() ?? [];

            $subscription           = Subscription::active_subscription($business_id);
            $monthly_max_sale_limit = $subscription->package->monthly_max_sale_limit;

            $startOfMonth = \Carbon::now()->startOfMonth()->toDateString();
            $endOfMonth   = \Carbon::now()->endOfMonth()->toDateString();

            $current_monthly_sale = DB::table("transactions")
                ->select(DB::raw("sum(final_total) as total"))
                ->where("business_id", $business_id)
                ->whereIn("type", ["sell", "property_sell"])
                ->whereBetween("transaction_date", [$startOfMonth, $endOfMonth])
                ->groupBy("business_id")
                ->first();

            $current_monthly_sale = is_null($current_monthly_sale)
                ? 0
                : (float) $current_monthly_sale->total;

            $current_monthly_sale += $total_sales_amount;

            if ($current_monthly_sale > $monthly_max_sale_limit) {
                return [
                    "success" => 0,
                    "msg"     => __("lang_v1.monthly_max_sale_limit_exceeded", [
                        "monthly_max_sale_limit" => $monthly_max_sale_limit,
                    ]),
                ];
            }

            $transaction = $this->createTransaction(
                $settlement,
                $total_sales_amount,
                null,
                $settlement->pump_operator_id,
                "sell",
                "settlement",
                $settlement_no,
                null,
                0,
                $total_sales_discount_amount
            );

            $sell_transaction = $transaction;
            $tax_amt          = 0;


            // Ensure regular meter sales also create sell lines/account entries
            foreach ($regular_meter_sales_for_transaction as $m_sale) {
                $fuel_tank_id = null;
                if (!empty($m_sale->pump_id)) {
                    $pump = Pump::find($m_sale->pump_id);
                    if (!empty($pump)) {
                        $fuel_tank_id = $pump->fuel_tank_id;
                    }
                }


                // meter_sale already contains product_id and qty fields
                $sell_line = $this->createSellTransactions(
                    $transaction,
                    $m_sale,
                    $business_id,
                    $default_location,
                    $fuel_tank_id,
                    null
                );
            }

            // Process pump_operator_meter_sales (meter_sales_pd) with their details
            // These are critical for displaying meter sales line items in account books
            foreach ($pd_meter_sales_for_transaction as $pd_sale) {
                // Process each detail record within the meter sale
                foreach ($pd_sale->details as $meter_sale_detail) {
                    $pump_obj = Pump::find($meter_sale_detail->pump_id);
                    $fuel_tank_id = ! empty($pump_obj) ? $pump_obj->fuel_tank_id : null;

                    // Mapping properties for createSellTransactions expected by other systems
                    $meter_sale_detail->product_id = ! empty($pd_sale->product_id)
                        ? $pd_sale->product_id
                        : (! empty($pump_obj) ? $pump_obj->product_id : null);
                    $meter_sale_detail->qty = $meter_sale_detail->sold_qty;
                    $meter_sale_detail->price = $meter_sale_detail->unit_price;
                    $meter_sale_detail->discount_type = $meter_sale_detail->discount_type ?? 'fixed';
                    $meter_sale_detail->discount = $meter_sale_detail->discount ?? 0;


                    // Create sell line transaction for each meter sale detail
                    // This ensures each detail appears as a line item in account books
                    $sell_line = $this->createSellTransactions(
                        $transaction,
                        $meter_sale_detail,
                        $business_id,
                        $default_location,
                        $fuel_tank_id,
                        null
                    );
                }

                // Update the pump_operator_meter_sale record with transaction link
                // This links the entire pump operator meter sale to the settlement transaction
                PumpOperatorMeterSale::where("id", $pd_sale->id)->update([
                    "transaction_id" => $transaction->id,
                ]);
            }

            // CRITICAL FIX: Also process pump_operator_meter_sales that may exist outside meter_sales_pd relationship
            // This ensures meter sales details appear in Account Books (Finished Goods, COGS, Sales Income)
            // Same logic as Other Sales to handle any additional pump_operator_meter_sales
            $additional_meter_sales = PumpOperatorMeterSale::where('business_id', $business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->whereNull('p_o_payment_id')
                ->when(true, function ($query) {
                    $this->onlyClosedPumpMeterSales($query);
                })
                ->whereNotIn('id', $pd_meter_sales_for_transaction->pluck('id')->filter()->all())
                ->when(! empty($shift_ids), function ($query) use ($shift_ids) {
                    $query->whereIn('shift_id', $shift_ids);
                })
                ->where(function ($q) use ($transaction) {
                    $q->whereNull('transaction_id')
                        ->orWhere('transaction_id', '!=', $transaction->id);
                })
                ->get();

            foreach ($additional_meter_sales as $pump_meter_sale) {
                // Check if this meter sale already has details processed
                if (!empty($pump_meter_sale->details) && $pump_meter_sale->details->count() > 0) {
                    // Process each detail within this meter sale
                    foreach ($pump_meter_sale->details as $meter_sale_detail) {
                        $pump_obj = Pump::find($meter_sale_detail->pump_id);
                        $fuel_tank_id = !empty($pump_obj) ? $pump_obj->fuel_tank_id : null;

                        // Mapping properties for createSellTransactions
                        $meter_sale_detail->product_id = ! empty($pump_meter_sale->product_id)
                            ? $pump_meter_sale->product_id
                            : (! empty($pump_obj) ? $pump_obj->product_id : null);
                        $meter_sale_detail->qty = $meter_sale_detail->sold_qty;
                        $meter_sale_detail->price = $meter_sale_detail->unit_price;
                        $meter_sale_detail->discount_type = $meter_sale_detail->discount_type ?? 'fixed';
                        $meter_sale_detail->discount = $meter_sale_detail->discount ?? 0;

                        // Create sell line for this detail
                        $sell_line = $this->createSellTransactions(
                            $transaction,
                            $meter_sale_detail,
                            $business_id,
                            $default_location,
                            $fuel_tank_id,
                            null
                        );
                    }

                    // Link the meter sale to the transaction
                    PumpOperatorMeterSale::where("id", $pump_meter_sale->id)->update([
                        "transaction_id" => $transaction->id,
                    ]);
                }
            }

            foreach ($settlement->other_sales as $other_sale) {
                $getOtherSale = OtherSale::where("id", $other_sale->id)->first();

                if ($getOtherSale->transaction_id == null || $getOtherSale->transaction_id != $transaction->id) {
                    $sell_line = $this->createSellTransactions(
                        $transaction,
                        $other_sale,
                        $business_id,
                        $default_location,
                        null,
                        true
                    );

                    OtherSale::where("id", $other_sale->id)->update([
                        "transaction_id" => $transaction->id,
                    ]);
                }
            }

            foreach ($pump_operator_other_sales as $pump_operator_other_sales_item) {
                if (
                    $pump_operator_other_sales_item->transaction_id == null ||
                    $pump_operator_other_sales_item->transaction_id != $transaction->id
                ) {
                    $sell_line = $this->createSellTransactions(
                        $transaction,
                        $pump_operator_other_sales_item,
                        $business_id,
                        $default_location,
                        null,
                        "pump_operator_other_sale"
                    );

                    PumpOperatorOtherSale::where("id", $pump_operator_other_sales_item->id)
                        ->update(["transaction_id" => $transaction->id]);
                }
            }

            foreach ($settlement->other_incomes as $other_income) {
                $sell_line = $this->createSellTransactions(
                    $transaction,
                    $other_income,
                    $business_id,
                    $default_location,
                    null,
                    null
                );

                OtherIncome::where("id", $other_income->id)->update([
                    "transaction_id" => $transaction->id,
                ]);
            }

            /* map purchase sell lines */
            $sell_lines_before = \App\TransactionSellLine::where('transaction_id', $transaction->id)->get();

            $this->createStockAccountTransactions($transaction); // @eng 11/2 1700

            $acct_txns_after = \App\AccountTransaction::where('transaction_id', $transaction->id)->get();

            $this->mapSellPurchaseLines(
                $business_id,
                $transaction,
                $settlement
            );

            $account_id = $this->transactionUtil->account_exist_return_id("Accounts Receivable");

            // Auto-create SettlementCashPayment records from pumper dashboard cash payments
            // that haven't been linked to a SettlementCashPayment yet
            $work_shifts = ! empty($shift_ids) ? $shift_ids : $settlement->work_shift;
            if (is_string($work_shifts)) {
                $decoded_work_shifts = json_decode($work_shifts, true);
                $work_shifts         = is_array($decoded_work_shifts) ? $decoded_work_shifts : explode(',', $work_shifts);
            }
            if (! is_array($work_shifts)) {
                $work_shifts = [];
            }
            $work_shifts = array_filter(array_map('intval', $work_shifts));

            // Get default customer (Walk-In Customer)
            $walkin_customer = Contact::where('name', 'Walk-In Customer')
                ->where('business_id', $business_id)
                ->first();
            $default_customer_id = $walkin_customer ? $walkin_customer->id : null;

            // Get cash payments from pumper dashboard
            // Check both by shift_id (if work_shifts exist) AND by settlement_no (if already linked)
            $unused_cash_payments_query = \Modules\Petro\Entities\PumpOperatorPayment::where('business_id', $business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where('payment_type', 'cash')
                ->where(function ($q) {
                    $q->whereNull('is_used')->orWhere('is_used', 0);
                });

            // If work_shifts exist, filter by shift_id; otherwise, check by settlement_no
            if (! empty($work_shifts)) {
                $unused_cash_payments_query->where(function ($q) use ($work_shifts, $settlement) {
                    $q->whereIn('shift_id', $work_shifts)
                        ->orWhere('settlement_no', $settlement->id);
                });
            } else {
                // If work_shifts is empty, check for payments already linked to this settlement
                $unused_cash_payments_query->where(function ($q) use ($settlement) {
                    $q->whereNull('settlement_no')
                        ->orWhere('settlement_no', $settlement->id);
                });
            }

            $unused_cash_payments = $unused_cash_payments_query->get();

            \Modules\Petro\Support\PetroDebug::info('Settlement PD PumpOperatorPayment Cash Lookup', [
                'settlement_id'              => $settlement->id,
                'settlement_no'              => $settlement->settlement_no,
                'pump_operator_id'           => $settlement->pump_operator_id,
                'work_shifts'                => $work_shifts,
                'shift_ids'                  => $shift_ids ?? [],
                'work_shifts_count'          => count($work_shifts),
                'unused_cash_payments_found' => $unused_cash_payments->count(),
            ]);

            // Create SettlementCashPayment records for each unused cash payment
            $created_count = 0;
            foreach ($unused_cash_payments as $pump_payment) {
                // Check if SettlementCashPayment already exists for this pump_payment
                // Check with both settlement_no formats (string and integer)
                // Primary check: customer_payment_id (links to PumpOperatorPayment ID)
                // Check if SettlementCashPayment already exists for this PumpOperatorPayment
                // Use customer_payment_id (links to PumpOperatorPayment ID)
                $existing = SettlementCashPayment::where('business_id', $business_id)
                    ->where('settlement_no', $settlement->id) // Use ID (integer) to match relationship
                    ->where('customer_payment_id', $pump_payment->id)
                    ->first();

                if (! $existing) {
                    $existing_daily_collection = SettlementCashPayment::where('business_id', $business_id)
                        ->where('settlement_no', $settlement->id)
                        ->whereNull('customer_payment_id')
                        ->where('amount', $pump_payment->payment_amount)
                        ->where(function ($q) {
                            $q->whereNull('note')
                                ->orWhere('note', 'LIKE', '%Daily Collection%');
                        })
                        ->first();

                    if ($existing_daily_collection) {
                        $existing_daily_collection->customer_payment_id = $pump_payment->id;
                        $existing_daily_collection->save();
                        $pump_payment->is_used       = 1;
                        $pump_payment->parent_id     = $existing_daily_collection->id;
                        $pump_payment->settlement_no = $settlement->id;
                        $pump_payment->save();
                        continue;
                    }

                    // Use settlement ID (integer) to match the relationship definition
                    // The relationship expects: SettlementCashPayment.settlement_no = Settlement.id
                    $settlement_cash_payment = app(\Modules\Petro\Services\SettlementPaymentReconciler::class)->upsertOne(
                        $business_id,
                        (string) $settlement->id,
                        'settlement_cash_payments',
                        [
                            'amount'              => $pump_payment->payment_amount,
                            'customer_id'         => $default_customer_id,
                            'customer_payment_id' => $pump_payment->id,
                            'pump_payment_id'     => $pump_payment->id,
                            'note'                => $pump_payment->note ?? '',
                        ]
                    );

                    $created_count++;

                    // Mark pump payment as used
                    $pump_payment->is_used       = 1;
                    $pump_payment->parent_id     = $settlement_cash_payment->id;
                    $pump_payment->settlement_no = $settlement->id;
                    $pump_payment->save();
                }
            }

            \Modules\Petro\Support\PetroDebug::info('Settlement PD Auto-created Cash Payments', [
                'settlement_id'              => $settlement->id,
                'settlement_no'              => $settlement->settlement_no,
                'unused_cash_payments_found' => $unused_cash_payments->count(),
                'created_count'              => $created_count,
            ]);

            // Reload cash_payments to include the newly created ones
            if ($created_count > 0) {
                $settlement->load('cash_payments');
            }

            // Ensure cash_payments are loaded - reload with both settlement_no formats to catch any manually added ones
            // This MUST happen AFTER creating SettlementCashPayment from DailyCollection and PumpOperatorPayment
            $all_cash_payments = SettlementCashPayment::where(function ($q) use ($settlement) {
                $q->where('settlement_no', $settlement->id)             // Primary: match by ID (integer)
                    ->orWhere('settlement_no', $settlement->settlement_no); // Fallback: match by string
            })
                ->where('business_id', $business_id)
                ->get();

            \Modules\Petro\Support\PetroDebug::info('Settlement PD Cash Payments Reload', [
                'settlement_id'         => $settlement->id,
                'settlement_no'         => $settlement->settlement_no,
                'found_count'           => $all_cash_payments->count(),
                'cash_payments_details' => $all_cash_payments->map(function ($cp) {
                    return ['id' => $cp->id, 'amount' => $cp->amount, 'settlement_no' => $cp->settlement_no, 'settlement_no_type' => gettype($cp->settlement_no)];
                })->toArray(),
            ]);

            // Always set the relation, even if empty, to ensure we have the latest data
            $settlement->setRelation('cash_payments', $all_cash_payments);

            $this->createSettlementCardPaymentsFromPumpPayments($settlement, $business_id, $work_shifts);

            $cash_note = "";

            // Log cash payments count for debugging
            \Modules\Petro\Support\PetroDebug::info('Settlement PD Cash Payments Processing', [
                'settlement_no'       => $settlement_no,
                'settlement_id'       => $settlement->id,
                'cash_payments_count' => $settlement->cash_payments->count(),
                'cash_payments'       => $settlement->cash_payments->map(function ($cp) {
                    return ['id' => $cp->id, 'amount' => $cp->amount, 'settlement_no' => $cp->settlement_no];
                })->toArray(),
            ]);

            foreach ($settlement->cash_payments as $cash_payment) {
                $i = 0;

                // Get cash account and operation date
                $cash_account_id = $this->transactionUtil->account_exist_return_id("Cash");
                $operation_date  = \Carbon::parse($settlement->transaction_date)->format('Y-m-d');

                // IDEMPOTENCY TOKEN: Structured reference with full context
                // Format: [SETL:{settlement_no}|BIZ:{business_id}|ACCT:{account_id}|CP:{cash_payment_id}]
                $idempotency_token = '[SETL:' . $settlement_no . '|BIZ:' . $business_id . '|ACCT:' . $cash_account_id . '|CP:' . $cash_payment->id . ']';

                // CRITICAL: Check for existing AccountTransaction with this idempotency token BEFORE creating any new records
                // Check by multiple criteria to ensure we catch duplicates
                $existing_entry = AccountTransaction::where('business_id', $business_id)
                    ->where('account_id', $cash_account_id)
                    ->where('type', 'debit')
                    ->where(function ($q) {
                        $q->where('sub_type', 'cash_payment')
                            ->orWhere('sub_type', 'settlement_cash_payment');
                    })
                    ->where(function ($q) use ($idempotency_token, $settlement_no, $cash_payment) {
                        // Check by idempotency token in note
                        $q->where('note', 'LIKE', '%' . $idempotency_token . '%')
                            // Also check by settlement_no and cash_payment_id in note (fallback)
                            ->orWhere(function ($q2) use ($settlement_no, $cash_payment) {
                                $q2->where('note', 'LIKE', '%Settlement No: ' . $settlement_no . '%')
                                    ->where('note', 'LIKE', '%CP:' . $cash_payment->id . '%');
                            });
                    })
                    ->first();

                if ($existing_entry) {
                    // EDIT CASE: If amount changed, update existing entry instead of creating duplicate
                    if (abs($existing_entry->amount - $cash_payment->amount) > 0.001) {
                        $existing_entry->update([
                            'amount'         => $cash_payment->amount,
                            'operation_date' => $operation_date,
                        ]);
                        
                        // Also update linked ContactLedger
                        \App\ContactLedger::where('transaction_id', $existing_entry->transaction_id)
                            ->where('sub_type', 'cash_payment')
                            ->update([
                                'amount' => $cash_payment->amount,
                                'operation_date' => $operation_date,
                            ]);
                        \Modules\Petro\Support\PetroDebug::info('Settlement PD: Updated existing cash entry (amount changed)', [
                            'settlement_no'     => $settlement_no,
                            'cash_payment_id'   => $cash_payment->id,
                            'old_amount'        => $existing_entry->amount,
                            'new_amount'        => $cash_payment->amount,
                            'idempotency_token' => $idempotency_token,
                        ]);
                    } else {
                        \Modules\Petro\Support\PetroDebug::info('Settlement PD: Idempotency guard - skipping duplicate cash entry', [
                            'settlement_no'     => $settlement_no,
                            'cash_payment_id'   => $cash_payment->id,
                            'amount'            => $cash_payment->amount,
                            'idempotency_token' => $idempotency_token,
                            'existing_entry_id' => $existing_entry->id,
                        ]);
                    }
                    continue; // Skip new creation - entry already exists or was updated
                }

                $cash_transaction = $this->createTransaction(
                    $settlement,
                    $cash_payment->amount,
                    $cash_payment->customer_id,
                    null,
                    "settlement",
                    "cash_payment",
                    $settlement_no
                );

                // Create Transaction Payment for cash
                // Use settlement's transaction_date directly to ensure correct date

                $cash_transaction_payment = TransactionPayment::create([
                    'transaction_id' => $cash_transaction->id,
                    'business_id'    => $business_id,
                    'amount'         => $cash_payment->amount,
                    'method'         => 'cash',
                    'paid_on'        => $operation_date,
                    'created_by'     => $cash_transaction->created_by,
                    'paid_in_type'   => 'settlement',
                    'payment_for'    => $cash_transaction->contact_id,
                ]);

                // DOUBLE CHECK: After creating TransactionPayment, check again by transaction_payment_id
                // This catches cases where the same TransactionPayment was used
                $existing_by_tp = AccountTransaction::where('business_id', $business_id)
                    ->where('account_id', $cash_account_id)
                    ->where('transaction_payment_id', $cash_transaction_payment->id)
                    ->where('type', 'debit')
                    ->where(function ($q) {
                        $q->where('sub_type', 'cash_payment')
                            ->orWhere('sub_type', 'settlement_cash_payment');
                    })
                    ->first();

                if ($existing_by_tp) {
                    \Modules\Petro\Support\PetroDebug::info('Settlement PD: Duplicate detected by transaction_payment_id - skipping', [
                        'settlement_no'          => $settlement_no,
                        'cash_payment_id'        => $cash_payment->id,
                        'transaction_payment_id' => $cash_transaction_payment->id,
                        'existing_entry_id'      => $existing_by_tp->id,
                    ]);
                    continue; // Skip - duplicate detected
                }

                // Create Account Transaction for Cash Account (Debit - Money In)

                \Modules\Petro\Support\PetroDebug::info('Settlement PD Cash Account Lookup', [
                    'settlement_no'         => $settlement_no,
                    'cash_payment_id'       => $cash_payment->id,
                    'cash_payment_amount'   => $cash_payment->amount,
                    'cash_account_id_found' => $cash_account_id,
                    'business_id'           => $business_id,
                    'operation_date'        => $operation_date,
                ]);

                if (empty($cash_account_id)) {
                    // Log warning if cash account not found
                    Log::warning('Cash account not found for Settlement PD cash payment', [
                        'settlement_no'   => $settlement_no,
                        'cash_payment_id' => $cash_payment->id,
                        'amount'          => $cash_payment->amount,
                        'business_id'     => $business_id,
                    ]);
                } else {
                    // Build note with idempotency token included
                    $account_note = 'Settlement No: ' . $settlement_no . ' | Cash Payment | ' . $idempotency_token;
                    if (! empty($cash_payment->note)) {
                        $account_note .= ' | ' . $cash_payment->note;
                    }

                    $account_transaction_data = [
                        'amount'                 => $cash_payment->amount,
                        'account_id'             => $cash_account_id,
                        'business_id'            => $business_id,
                        'type'                   => 'debit',
                        'sub_type'               => 'cash_payment',
                        'operation_date'         => $operation_date,
                        'created_by'             => $cash_transaction->created_by,
                        'transaction_id'         => $cash_transaction->id,
                        'transaction_payment_id' => $cash_transaction_payment->id,
                        'note'                   => $account_note,
                        'contact_id'             => $cash_transaction->contact_id,
                    ];

                    \Modules\Petro\Support\PetroDebug::info('Creating Account Transaction for Cash Payment', $account_transaction_data);

                    $is_rt = ! empty($cash_payment->pump_payment_id);
                    $created_at = null;
                    if (! ($is_rt && $update_account)) {
                        $created_at = AccountTransaction::createAccountTransaction($account_transaction_data);
                    }

                    // Create ledger entry for customer
                    if (! (($is_rt && $update_ledger) || ($is_rt && $pumper_ledger_update))) {
                        $ledger_data = $account_transaction_data;
                        $ledger_data['type'] = 'credit'; // Customer is credited
                        \App\ContactLedger::createContactLedger($ledger_data);
                    }

                    \Modules\Petro\Support\PetroDebug::info('Account Transaction and Ledger Created', [
                        'account_transaction_id' => $created_at->id ?? 'unknown',
                        'account_id'             => $cash_account_id,
                        'amount'                 => $cash_payment->amount,
                        'idempotency_token'      => $idempotency_token,
                    ]);
                }

                $cash_note .= ! empty($cash_payment->note)
                    ? "Note " . $i++ . ": " . $cash_payment->note . "\n"
                    : "";
            }

            foreach ($settlement->customer_loans as $customer_loan) {
                $customer_loan_transaction = $this->createTransaction(
                    $settlement,
                    $customer_loan->amount,
                    $customer_loan->customer_id,
                    null,
                    "settlement",
                    "customer_loan",
                    $settlement_no,
                    null,
                    0,
                    0.0,
                    $customer_loan->note
                );

                $type       = "debit";
                $account_id = $this->transactionUtil->account_exist_return_id("Accounts Receivable");

                $this->createAccountTransaction(
                    $customer_loan_transaction,
                    $type,
                    $account_id,
                    $customer_loan_transaction->id,
                    "null",
                    null,
                    $customer_loan->amount,
                    false,
                    $customer_loan->note
                );

                $type       = "credit";
                $account_id = $this->transactionUtil->account_exist_return_id("Cash");

                $this->createAccountTransaction(
                    $customer_loan_transaction,
                    $type,
                    $account_id,
                    $customer_loan_transaction->id,
                    "null",
                    null,
                    $customer_loan->amount,
                    false,
                    $customer_loan->note
                );
            }

            $loan_note = "";

            foreach ($settlement->loan_payments as $loan_payment) {
                $i = 0;

                // this transaction will use in report to show amounts
                $loan_transaction_payment = $this->createTransaction(
                    $settlement,
                    $loan_payment->amount,
                    null,
                    null,
                    "settlement",
                    "loan_payment",
                    $settlement_no
                );

                $loan_note .= ! empty($loan_payment->note)
                    ? "Note " . $i++ . ": " . $loan_payment->note . "\n"
                    : "";

                $type       = "debit";
                $account_id = $this->transactionUtil->account_exist_return_id("Cash");

                $this->createAccountTransaction(
                    $loan_transaction_payment,
                    $type,
                    $account_id,
                    $loan_transaction_payment->id,
                    "null",
                    null,
                    $loan_payment->amount,
                    false,
                    $loan_payment->note
                );

                $type       = "credit";
                $account_id = $this->transactionUtil->account_exist_return_id("Cash");

                $this->createAccountTransaction(
                    $loan_transaction_payment,
                    $type,
                    $account_id,
                    $loan_transaction_payment->id,
                    "null",
                    null,
                    $loan_payment->amount,
                    false,
                    $loan_payment->note
                );

                $type       = "debit";
                $account_id = $loan_payment->loan_account;

                $this->createAccountTransaction(
                    $loan_transaction_payment,
                    $type,
                    $account_id,
                    $loan_transaction_payment->id,
                    "null",
                    null,
                    $loan_payment->amount,
                    false,
                    $loan_payment->note
                );
            }

            $drawing_note = "";

            foreach ($settlement->drawings_payments as $drawing_payment) {
                $i = 0;

                // this transaction will use in report to show amounts
                $drawing_transaction_payment = $this->createTransaction(
                    $settlement,
                    $drawing_payment->amount,
                    null,
                    null,
                    "settlement",
                    "drawing_payment",
                    $settlement_no
                );

                $drawing_note .= ! empty($drawing_payment->note)
                    ? "Note " . $i++ . ": " . $drawing_payment->note . "\n"
                    : "";

                $type       = "debit";
                $account_id = $this->transactionUtil->account_exist_return_id("Cash");

                $this->createAccountTransaction(
                    $drawing_transaction_payment,
                    $type,
                    $account_id,
                    $drawing_transaction_payment->id,
                    "null",
                    null,
                    $drawing_payment->amount,
                    false,
                    $drawing_payment->note
                );

                $type       = "credit";
                $account_id = $this->transactionUtil->account_exist_return_id("Cash");

                $this->createAccountTransaction(
                    $drawing_transaction_payment,
                    $type,
                    $account_id,
                    $drawing_transaction_payment->id,
                    "null",
                    null,
                    $drawing_payment->amount,
                    false,
                    $drawing_payment->note
                );

                $type       = "debit";
                $account_id = $drawing_payment->loan_account;

                $this->createAccountTransaction(
                    $drawing_transaction_payment,
                    $type,
                    $account_id,
                    $drawing_transaction_payment->id,
                    "null",
                    null,
                    $drawing_payment->amount,
                    false,
                    $drawing_payment->note
                );
            }

            foreach ($settlement->cash_deposits as $cash_payment) {
                $i = 0;

                // this transaction will use in report to show amounts
                $cash_deposit = $this->createTransaction(
                    $settlement,
                    $cash_payment->amount,
                    null,
                    null,
                    "settlement",
                    "cash_deposit",
                    $settlement_no,
                    $cash_payment->id
                );

                $type       = "debit";
                $account_id = $this->transactionUtil->account_exist_return_id("Cash");

                $this->createAccountTransaction(
                    $cash_deposit,
                    $type,
                    $account_id,
                    $cash_deposit->id,
                    "null",
                    null,
                    $cash_payment->amount,
                    false,
                    null
                );

                $this->createAccountTransaction(
                    $cash_deposit,
                    "credit",
                    $account_id,
                    $cash_deposit->id,
                    "null",
                    null,
                    $cash_payment->amount,
                    false,
                    null
                );

                $type       = "debit";
                $account_id = $cash_payment->bank_id;

                $this->createAccountTransaction(
                    $cash_deposit,
                    $type,
                    $account_id,
                    $cash_deposit->id,
                    "null",
                    null,
                    $cash_payment->amount,
                    false,
                    null
                );

                $depositBank   = Account::find($cash_payment->bank_id);
                $depositBankNm = $depositBank ? $depositBank->name : "";
                $depositParts  = array_filter([
                    "Cash deposit to bank",
                    $depositBankNm ? "(" . $depositBankNm . ")" : null,
                    "Settlement: " . $settlement_no,
                    ! empty($cash_payment->account_no) ? "Receipt: " . $cash_payment->account_no : null,
                ]);
                ContactLedger::createContactLedger([
                    "business_id"    => $cash_deposit->business_id,
                    "contact_id"     => $cash_deposit->contact_id,
                    "amount"         => $cash_payment->amount,
                    "type"           => "credit",
                    "sub_type"       => "cash_deposit",
                    "operation_date" => $cash_deposit->transaction_date,
                    "created_by"     => $cash_deposit->created_by,
                    "transaction_id" => $cash_deposit->id,
                    "note"           => implode(" ", $depositParts),
                ]);

                $sms_settings = empty($business->sms_settings)
                    ? $this->businessUtil->defaultSmsSettings()
                    : $business->sms_settings;

                $msg_template = NotificationTemplate::where("business_id", $business_id)
                    ->where("template_for", "cash_deposit")
                    ->first();

                if (! empty($msg_template)) {
                    $msg       = $msg_template->sms_body;
                    $account   = Account::find($cash_payment->bank_id);
                    $bank_name = ! empty($account) ? $account->name : "";

                    $msg = str_replace("{account}", $cash_payment->account_no, $msg);
                    $msg = str_replace("{amount}", $this->transactionUtil->num_f($cash_payment->amount), $msg);
                    $msg = str_replace(
                        "{time}",
                        $this->transactionUtil->format_date($cash_payment->time_deposited, true),
                        $msg
                    );
                    $msg = str_replace("{bank}", $bank_name, $msg);

                    $phones = [];
                    if (! empty($business->sms_settings)) {
                        $phones = explode(
                            ",",
                            str_replace(" ", "", $business->sms_settings["msg_phone_nos"])
                        );
                    }

                    foreach ($phones as $phone) {
                        $data = [
                            "sms_settings"  => $sms_settings,
                            "mobile_number" => $phone,
                            "sms_body"      => $msg,
                        ];

                        $response = $this->transactionUtil->sendSms($data);
                    }
                }
            }

            $cash_transaction_payment = null;

            if ($settlement->cash_payments->sum("amount") > 0) {
                $cash_transaction_payment = $this->createTansactionPayment(
                    $transaction,
                    "cash",
                    $settlement->cash_payments->sum("amount")
                );
            }

            foreach ($this->uniqueSettlementCardPaymentsForAccounting($settlement->card_payments) as $card_payment) {
                // Check if account transaction already exists for this SettlementCardPayment
                // This prevents duplicates when payments are added via modal and then settlement is saved
                $card_account_id = ! empty($card_payment->card_type)
                    ? $card_payment->card_type
                    : $this->transactionUtil->account_exist_return_id("Cards (Credit Debit) Account");
                $operation_date = \Carbon::parse($settlement->transaction_date)->format('Y-m-d');

                if (! empty($card_payment->customer_payment_id)) {
                    $existing_by_payment_id = AccountTransaction::where('business_id', $business_id)
                        ->where('account_id', $card_account_id)
                        ->where('type', 'debit')
                        ->where('transaction_payment_id', $card_payment->customer_payment_id)
                        ->first();

                    if ($existing_by_payment_id) {
                        \Modules\Petro\Support\PetroDebug::info('Settlement PD: Skipping duplicate account transaction (customer_payment_id already linked)', [
                            'settlement_no'                   => $settlement_no,
                            'card_payment_id'                 => $card_payment->id,
                            'transaction_payment_id'          => $card_payment->customer_payment_id,
                            'existing_account_transaction_id' => $existing_by_payment_id->id,
                        ]);
                        continue;
                    }
                }

                // Check if account transaction already exists (check for both sub_types)
                $existing_card_account_transaction = AccountTransaction::where('business_id', $business_id)
                    ->where('account_id', $card_account_id)
                    ->where('type', 'debit')
                    ->where(function ($q) {
                        // Check for both sub_types (card_payment from store, settlement_card_payment from saveCardPayment)
                        $q->where('sub_type', 'card_payment')
                            ->orWhere('sub_type', 'settlement_card_payment');
                    })
                    ->where('amount', $card_payment->amount)
                    ->where('operation_date', $operation_date)
                    ->where(function ($q) use ($settlement_no, $card_payment) {
                        $q->whereRaw('note LIKE ?', ['%Settlement No: ' . $settlement_no . '%'])
                            ->where(function ($subQ) use ($card_payment) {
                                if (! empty($card_payment->customer_payment_id)) {
                                    $subQ->whereRaw('note LIKE ?', ['%Card Payment%'])
                                        ->orWhereRaw('note LIKE ?', ['%settlement%']);
                                }
                                if (! empty($card_payment->slip_no)) {
                                    $subQ->orWhereRaw('note LIKE ?', ['%Slip No: ' . $card_payment->slip_no . '%']);
                                }
                            });
                    })
                    ->first();

                if ($existing_card_account_transaction) {
                    \Modules\Petro\Support\PetroDebug::info('Settlement PD: Skipping duplicate account transaction for SettlementCardPayment', [
                        'settlement_no'                   => $settlement_no,
                        'card_payment_id'                 => $card_payment->id,
                        'existing_account_transaction_id' => $existing_card_account_transaction->id,
                        'amount'                          => $card_payment->amount,
                    ]);
                    continue; // Skip creating duplicate transaction
                }

                // this transaction will use in report to show amounts
                $card_transaction = $this->createTransaction(
                    $settlement,
                    $card_payment->amount,
                    $card_payment->customer_id,
                    null,
                    "settlement",
                    "card_payment",
                    $settlement_no
                );

                $transaction_payment = $this->createTansactionPayment(
                    $transaction,
                    "card",
                    $card_payment->amount,
                    $card_payment->card_number,
                    $card_payment->card_type,
                    null,
                    null,
                    null,
                    0,
                    $card_transaction->contact_id
                );

                SettlementCardPayment::where("id", $card_payment->id)->update([
                    "customer_payment_id" => $transaction_payment->id,
                ]);

                $type = "debit";

                // Build descriptive note for card account book
                $card_note = $card_payment->note;
                if (empty($card_note)) {
                    $note_parts = ['Settlement No: ' . $settlement_no, 'Card Payment'];
                    if (! empty($card_payment->customer_id)) {
                        $customer = Contact::find($card_payment->customer_id);
                        if ($customer) {
                            $note_parts[] = 'Customer: ' . $customer->name;
                        }
                    }
                    if (! empty($card_payment->slip_no)) {
                        $note_parts[] = 'Slip No: ' . $card_payment->slip_no;
                    }
                    $card_note = implode(' | ', $note_parts);
                }

                $is_rt = ! empty($card_payment->pump_payment_id);
                $this->createAccountTransaction(
                    $card_transaction, // Use specific card transaction
                    $type,
                    $card_account_id,
                    $transaction_payment->id,
                    null, // Use transaction sub_type ('card_payment')
                    $card_payment->customer_id,
                    $card_payment->amount,
                    false,
                    $card_note,
                    $card_payment->slip_no,
                    $is_rt && $update_account,
                    ($is_rt && $update_ledger) || ($is_rt && $pumper_ledger_update)
                );
            }

            $this->ensureSettlementCardAccounting($settlement, $business_id);

            foreach ($settlement->cheque_payments as $cheque_payment) {
                // this transaction will use in report to show amounts
                $cheque_transaction = $this->createTransaction(
                    $settlement,
                    $cheque_payment->amount,
                    $cheque_payment->customer_id,
                    null,
                    "settlement",
                    "cheque_payment",
                    $settlement_no
                );

                $transaction_payment = $this->createTansactionPayment(
                    $transaction,
                    "cheque",
                    $cheque_payment->amount,
                    null,
                    null,
                    $cheque_payment->cheque_number,
                    $cheque_payment->bank_name,
                    $cheque_payment->cheque_date,
                    $cheque_payment->post_dated_cheque,
                    $cheque_transaction->contact_id
                );

                $contact                                   = Contact::where("id", $cheque_payment->customer_id)->first();
                $cheque_transaction->contact               = $contact;
                $cheque_transaction->single_payment_amount = $this->transactionUtil->num_uf(
                    $cheque_payment->amount
                );
                $cheque_transaction->payment_ref_number = "";

                $this->notificationUtil->autoSendNotification(
                    $business_id,
                    "payment_received",
                    $cheque_transaction,
                    $cheque_transaction->contact,
                    true
                );

                SettlementChequePayment::where("id", $cheque_payment->id)->update([
                    "customer_payment_id" => $transaction_payment->id,
                ]);

                $account_id = $this->transactionUtil->account_exist_return_id("Cheques in Hand");
                $type       = "debit";

                $is_rt = ! empty($cheque_payment->pump_payment_id);
                $this->createAccountTransaction(
                    $cheque_transaction, // Use specific cheque transaction
                    $type,
                    $account_id,
                    $transaction_payment->id,
                    null, // Use transaction sub_type ('cheque_payment')
                    $cheque_payment->customer_id,
                    $cheque_payment->amount,
                    false,
                    $cheque_payment->note,
                    null,
                    $is_rt && $update_account,
                    ($is_rt && $update_ledger) || ($is_rt && $pumper_ledger_update)
                );
            }

            if (empty($no_change)) {
                if (! empty($settlement->credit_sale_payments)) {
                    $deduped_credit_sales = $settlement->credit_sale_payments
                        ->unique(function ($item) {
                            if (! empty($item->collection_form_no)) {
                                return 'cf-' . $item->collection_form_no;
                            }
                            if (! empty($item->daily_voucher_id)) {
                                return 'dv-' . $item->daily_voucher_id . '-' . ($item->product_id ?? '0');
                            }
                            $order_number = ! empty($item->order_number) ? $item->order_number : '0';
                            return 'manual-' . $order_number . '-' . ($item->customer_id ?? '0') . '-' . ($item->product_id ?? '0') . '-' . ($item->order_date ?? '');
                        })
                        ->values();
                    $settlement->setRelation('credit_sale_payments', $deduped_credit_sales);
                }

                foreach ($settlement->credit_sale_payments as $credit_sale_payment) {
                    $transaction = $this->createCreditSellTransactions(
                        $settlement,
                        $credit_sale_payment,
                        $default_location
                    );

                    $credit_sale_payment = app(\Modules\Petro\Services\SettlementPaymentEditService::class)
                        ->editCreditSale($business_id, $credit_sale_payment->id, [
                            "transaction_id" => $transaction->id,
                        ]);

                    $account_id = $this->transactionUtil->account_exist_return_id("Accounts Receivable");
                    $type       = "debit";

                    // Build descriptive note for account receivable book
                    $credit_note = $credit_sale_payment->note;
                    if (empty($credit_note)) {
                        $note_parts = ['Settlement No: ' . $settlement_no, 'Credit Sale'];
                        if (! empty($credit_sale_payment->customer_id)) {
                            $customer = Contact::find($credit_sale_payment->customer_id);
                            if ($customer) {
                                $note_parts[] = 'Customer: ' . $customer->name;
                            }
                        }
                        if (! empty($credit_sale_payment->order_number)) {
                            $note_parts[] = 'Order No: ' . $credit_sale_payment->order_number;
                        }
                        $credit_note = implode(' | ', $note_parts);
                    }

                    // Pass customer_id explicitly to ensure ContactLedger entry is created with correct contact_id
                    // Pass the actual amount (after discount) to ensure each credit sale shows separately
                    $credit_sale_amount = $credit_sale_payment->amount - $credit_sale_payment->total_discount;

                    $is_rt = ! empty($credit_sale_payment->pump_payment_id);
                    $this->createAccountTransaction(
                        $transaction,
                        $type,
                        $account_id,
                        null,
                        "ledger_show",
                        $credit_sale_payment->customer_id, // Explicitly pass customer_id
                        $credit_sale_amount,               // Pass the actual amount instead of 0
                        true,
                        $credit_note,
                        null,
                        $is_rt && $update_account,
                        ($is_rt && $update_ledger) || ($is_rt && $pumper_ledger_update)
                    );

                    if ($credit_sale_payment->is_from_pumper == 0) {
                        // store the customer reference
                        if (! empty($credit_sale_payment->customer_reference)) {
                            $customer       = Contact::findOrFail($credit_sale_payment->customer_id);
                            $name           = $customer->name;
                            $barcode_string = $name . "." . $credit_sale_payment->customer_reference;

                            $qr  = new DNS2D();
                            $qr  = $qr->getBarcodePNG($barcode_string, "QRCODE");
                            $src = "data:image/png;base64," . $qr;

                            $ref_data = [
                                "business_id" => $credit_sale_payment->business_id,
                                "date"        => date("Y-m-d", strtotime($credit_sale_payment->order_date)),
                                "contact_id"  => $credit_sale_payment->customer_id,
                                "reference"   => $credit_sale_payment->customer_reference,
                                "barcode_src" => $src,
                            ];

                            CustomerReference::updateOrCreate(
                                [
                                    "business_id" => $credit_sale_payment->business_id,
                                    "contact_id"  => $credit_sale_payment->customer_id,
                                    "reference"   => $credit_sale_payment->customer_reference,
                                ],
                                $ref_data
                            );
                        }

                        $cheque_pmt = SettlementChequePayment::where("customer_id", $credit_sale_payment->customer_id)
                            ->where("settlement_no", $credit_sale_payment->settlement_no)
                            ->sum("amount");

                        $cash_pmt = SettlementCardPayment::where("customer_id", $credit_sale_payment->customer_id)
                            ->where("settlement_no", $credit_sale_payment->settlement_no)
                            ->sum("amount");

                        $card_pmt = SettlementCashPayment::where("customer_id", $credit_sale_payment->customer_id)
                            ->where("settlement_no", $credit_sale_payment->settlement_no)
                            ->sum("amount");

                        $total_paid = $cheque_pmt + $cash_pmt + $card_pmt;

                        $business_id  = request()->session()->get("user.business_id");
                        $business     = Business::where("id", $business_id)->first();
                        $sms_settings = empty($business->sms_settings)
                            ? $this->businessUtil->defaultSmsSettings()
                            : $business->sms_settings;

                        $contact      = Contact::where("id", $credit_sale_payment->customer_id)->first();
                        $msg_template = NotificationTemplate::where("business_id", $business_id)
                            ->where("template_for", "credit_sale")
                            ->first();

                        $final_total = $credit_sale_payment->amount - $credit_sale_payment->total_discount;
                        $product     = Product::findOrFail($credit_sale_payment->product_id);

                        $product_msg = PHP_EOL .
                            "Product Sold: " . ucfirst($product->name) . PHP_EOL .
                            "Quantity: " . $this->productUtil->num_f($credit_sale_payment->qty);

                        if (! empty($msg_template) && $contact->credit_notification == "settlement") {
                            $msg = $msg_template->sms_body;

                            $msg = str_replace("{business_name}", $business->name, $msg);
                            $msg = str_replace("{total_amount}", $this->productUtil->num_f($final_total), $msg);
                            $msg = str_replace("{contact_name}", $contact->name, $msg);
                            $msg = str_replace("{invoice_number}", $settlement->settlement_no, $msg);
                            $msg = str_replace("{transaction_date}", $settlement->transaction_date, $msg);
                            $msg = str_replace("{paid_amount}", $this->productUtil->num_f($total_paid), $msg);
                            $msg = str_replace("{due_amount}", $this->productUtil->num_f($final_total - $total_paid), $msg);
                            $msg = str_replace(
                                "{cumulative_due_amount}",
                                $this->productUtil->num_f(
                                    strval($contactController->get_due_bal($credit_sale_payment->customer_id, false))
                                ),
                                $msg
                            );
                            $msg = str_replace("{customer_reference}", $credit_sale_payment->customer_reference, $msg);
                            $msg = str_replace("{vehicle_no}", $credit_sale_payment->customer_reference, $msg);

                            $msg .= $product_msg;

                            if (! empty($business->sms_settings)) {
                                $phones = explode(
                                    ",",
                                    str_replace(" ", "", $business->sms_settings["msg_phone_nos"])
                                );
                            }

                            $data = [
                                "sms_settings"  => $sms_settings,
                                "mobile_number" => $contact->mobile,
                                "sms_body"      => $msg,
                            ];

                            $response = $this->businessUtil->sendSms($data);

                            $data["mobile_number"] = $contact->alternate_number;
                            $response              = $this->businessUtil->sendSms($data, $contact, "credit_sale");
                        }
                    } else {
                        $credit_sale_payment = app(\Modules\Petro\Services\SettlementPaymentEditService::class)
                            ->editCreditSale($business_id, $credit_sale_payment->id, [
                                'is_from_pumper' => 0,
                                'is_committed' => 1,
                            ]);
                    }
                }
            }

            $total_shortage = $pump_operator->short_amount; // get previous amount

            foreach ($settlement->shortage_payments as $shortage_payment) {
                if (!empty($shortage_payment->transaction_id)) {
                    continue;
                }

                $transaction = $this->createTransaction(
                    $settlement,
                    $shortage_payment->amount,
                    null,
                    $settlement->pump_operator_id,
                    "settlement",
                    "shortage",
                    $settlement_no
                );

                SettlementShortagePayment::where("id", $shortage_payment->id)
                    ->update(["transaction_id" => $transaction->id]);

                $account_id = $this->transactionUtil->account_exist_return_id("Accounts Receivable");
                $type       = "debit";

                $this->createAccountTransaction(
                    $transaction,
                    $type,
                    $account_id,
                    null,
                    "ledger_show",
                    null,
                    0,
                    false,
                    $shortage_payment->note
                );

                $total_shortage += $shortage_payment->amount;
            }

            $total_excess = $pump_operator->excess_amount; // get previous amount

            foreach ($settlement->excess_payments as $excess_payment) {
                if (!empty($excess_payment->transaction_id)) {
                    continue;
                }

                $transaction = $this->createTransaction(
                    $settlement,
                    $excess_payment->amount,
                    null,
                    $settlement->pump_operator_id,
                    "settlement",
                    "excess",
                    $settlement_no
                );

                SettlementExcessPayment::where("id", $excess_payment->id)
                    ->update(["transaction_id" => $transaction->id]);

                $account_id = $this->transactionUtil->account_exist_return_id("Accounts Payable");
                $type       = "credit";

                $this->createAccountTransaction(
                    $transaction,
                    $type,
                    $account_id,
                    null,
                    "ledger_show",
                    null,
                    0,
                    false,
                    $excess_payment->note
                );

                $total_excess += $excess_payment->amount;
            }

            $pump_operator->short_amount  = $total_shortage;
            $pump_operator->excess_amount = $total_excess;
            $pump_operator->settlement_no = $settlement->settlement_no;
            $pump_operator->save();

            foreach ($settlement->expense_payments as $expense_payment) {
                $transaction = $this->createTransaction(
                    $settlement,
                    $expense_payment->amount,
                    null,
                    $settlement->pump_operator_id,
                    "settlement",
                    "expense",
                    $settlement_no
                );

                $transaction->expense_category_id = $expense_payment->category_id;
                $transaction->ref_no              = "Settlement No: " . $settlement->settlement_no;
                $transaction->expense_account     = $expense_payment->account_id;
                $transaction->save();

                SettlementExpensePayment::where("id", $expense_payment->id)
                    ->update(["transaction_id" => $transaction->id]);

                $is_pd_cheque_expense = ! empty($expense_payment->pd_cheque);
                $transaction_payment = $this->createTansactionPayment(
                    $transaction,
                    $is_pd_cheque_expense ? "cheque" : "cash",
                    $expense_payment->amount,
                    null,
                    null,
                    $expense_payment->cheque_number ?? null,
                    $expense_payment->bank_name ?? null,
                    $expense_payment->cheque_date ?? null,
                    $is_pd_cheque_expense ? 1 : 0
                );

                $account_id = $expense_payment->account_id;
                $type       = "debit";

                $this->createAccountTransaction($transaction, $type, $account_id, $transaction_payment->id);

                if ($is_pd_cheque_expense) {
                    Account::crearePostdatedChequesAccount($business_id, auth()->id());
                    $issued_pd_account_id = $this->transactionUtil->account_exist_return_id("Issued Post Dated Cheques");
                    $expense_category = \App\ExpenseCategory::find($expense_payment->category_id);
                    $bank_name = $expense_payment->bank_name;

                    if (empty($bank_name) && ! empty($expense_payment->bank_account_id)) {
                        $bank_name = optional(Account::find($expense_payment->bank_account_id))->name;
                    }

                    AccountTransaction::createAccountTransaction([
                        "amount"                 => abs($transaction->final_total),
                        "account_id"             => $issued_pd_account_id,
                        "contact_id"             => $transaction->contact_id,
                        "type"                   => "credit",
                        "operation_date"         => date('Y-m-d H:i:s'),
                        "created_by"             => $transaction->created_by,
                        "transaction_id"         => $transaction->id,
                        "transaction_payment_id" => $transaction_payment->id,
                        "note"                   => trim(collect([
                            optional($expense_category)->name,
                            "Post dated Cheque Issued from Bank " . ($bank_name ?: "N/A"),
                        ])->filter()->implode("\n")),
                        "cheque_number"          => $expense_payment->cheque_number ?? null,
                        "cheque_date"            => $expense_payment->cheque_date ?? null,
                        "post_dated_cheque"      => 1,
                    ]);
                } else {
                    $account_id = $this->transactionUtil->account_exist_return_id("Cash");
                    $type       = "credit";

                    $this->createAccountTransaction($transaction, $type, $account_id, $transaction_payment->id);
                }
            }

            if (
                $settlement->expense_payments->sum("amount") > 0 &&
                $settlement->cash_payments->sum("amount") == 0
            ) {
                // Cash payment + expense payment // doc 3075 - POS Settlement Expense amount in cash account – 5 Nov 2020
                $account_id = $this->transactionUtil->account_exist_return_id("Cash");

                $expense_transaction_data = [
                    "amount"                 => $settlement->expense_payments->sum("amount"),
                    "account_id"             => $account_id,
                    "contact_id"             => $sell_transaction->contact_id,
                    "type"                   => "debit",
                    "sub_type"               => null,
                    "operation_date"         => date('Y-m-d H:i:s'),
                    "created_by"             => $sell_transaction->created_by,
                    "transaction_id"         => $sell_transaction->id,
                    "transaction_payment_id" => ! empty($cash_transaction_payment)
                        ? $cash_transaction_payment->id
                        : null,
                    "note"                   => (! empty($cash_note) ? $cash_note . "\n" : "") . "Settlement No: " . $settlement->settlement_no,
                ];

                AccountTransaction::createAccountTransaction($expense_transaction_data);
            }

            foreach ($settlement->customer_payments as $customer_payments) {
                $account_id = $this->transactionUtil->account_exist_return_id("Accounts Receivable");

                $ob_transaction_data = [
                    "amount"                 => $customer_payments->amount,
                    "post_dated_cheque"      => $customer_payments->post_dated_cheque,
                    "account_id"             => $account_id,
                    "type"                   => "credit", // changed from debit to credit
                    "sub_type"               => "deposit",
                    "operation_date"         => date('Y-m-d H:i:s'),
                    "created_by"             => auth()->user()->id,
                    "transaction_id"         => $sell_transaction->id,
                    "transaction_payment_id" => null,
                ];

                AccountTransaction::createAccountTransaction($ob_transaction_data);
            }

            // This is only to show in print page customer payments which entered in customer payments tab
            $customer_payments_tab = CustomerPayment::leftJoin(
                "contacts",
                "customer_payments.customer_id",
                "contacts.id"
            )
                ->where("customer_payments.settlement_no", $settlement->id)
                ->where("customer_payments.business_id", $business_id)
                ->select(
                    "customer_payments.*",
                    "contacts.name as customer_name"
                )
                ->get();

            // Calculate settlement_total from sales (this is the amount to be paid)
            // This matches Direct Settlement's calculation pattern
            $payment_meter_sale_total = $this->getSettlementPDMeterSaleTotal($business_id, $settlement);

            // Modified by Engr. Alex -- task 7889: use post-discount amounts so
            // settlements.total_amount and P&L reflect the amount after discount
            $settlement_total =
                $settlement->meter_sales->sum("discount_amount") +
                ($settlement->other_sales->sum("sub_total") - $settlement->other_sales->sum("discount_amount")) +
                $settlement->other_incomes->sum("sub_total") +
                $settlement->customer_payments->sum("sub_total") +
                $pump_operator_total_other_sale +
                $payment_meter_sale_total;

            // Set total_amount immediately (matching Direct Settlement pattern)
            $settlement->total_amount = $settlement_total;
            // NOTE: status = 0 will be set RIGHT BEFORE commit to ensure it's persisted
            // This matches Settlement SW pattern and prevents rollback issues

            $settlement->cash_denomination = ($denom_enabled > 0) ? json_encode($denom_data) : null;

            // CRITICAL: Set work_shift to the shift_ids being settled
            // This is needed for filtering in show/print methods
            if (! empty($shift_ids)) {
                $settlement->work_shift = is_array($shift_ids) ? $shift_ids : explode(",", $shift_ids);
            } else {
                $settlement->work_shift = [];
            }

            // IMPORTANT: Save settlement (without status) to ensure settlement_no is final before updating credit sales
            // Status will be set to 0 right before commit
            $settlement->save();

            \Modules\Petro\Support\PetroDebug::info('Settlement PD: Settlement Saved (before finalization)', [
                'settlement_id'    => $settlement->id,
                'settlement_no'    => $settlement->settlement_no,
                'current_status'   => $settlement->status,
                'pump_operator_id' => $settlement->pump_operator_id,
                'work_shift'       => $settlement->work_shift,
                'shift_ids'        => $shift_ids ?? [],
            ]);

            // Log credit sales linked to this settlement
            $credit_sales_after_save = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                ->with('product')
                ->get();

            \Modules\Petro\Support\PetroDebug::info('Settlement PD: Credit Sales After Save', [
                'settlement_id'        => $settlement->id,
                'settlement_no'        => $settlement->settlement_no,
                'credit_sales_count'   => $credit_sales_after_save->count(),
                'credit_sales_total'   => $credit_sales_after_save->sum('amount'),
                'credit_sales_details' => $credit_sales_after_save->map(function ($cs) {
                    return [
                        'id'                 => $cs->id,
                        'order_number'       => $cs->order_number,
                        'amount'             => $cs->amount,
                        'collection_form_no' => $cs->collection_form_no,
                    ];
                })->toArray(),
            ]);

            // Get Daily Collection from business_id and pump_operator_id where settlement_id is null
            if (empty($shift_ids_for_collections)) {
                $daily_collections = collect();
            } else {
                $daily_collections = DailyCollection::leftJoin(
                    "business_locations",
                    "daily_collections.location_id",
                    "business_locations.id"
                )
                    ->leftJoin(
                        "pump_operators",
                        "daily_collections.pump_operator_id",
                        "pump_operators.id"
                    )
                    ->leftJoin(
                        "pump_operator_assignments",
                        "pump_operator_assignments.pump_operator_id",
                        "pump_operators.id"
                    )
                    ->leftJoin("users", "daily_collections.created_by", "users.id")
                    ->leftJoin(
                        "settlements",
                        "daily_collections.settlement_id",
                        "settlements.id"
                    )
                    ->where("daily_collections.business_id", $business_id)
                    ->where("daily_collections.pump_operator_id", $settlement->pump_operator_id)
                    ->where("daily_collections.type", "daily_collection")
                    ->whereNull("daily_collections.settlement_id")
                    ->whereNull("daily_collections.added_to_account")
                    ->whereIn("daily_collections.shift_id", $shift_ids_for_collections)
                    ->select([
                        "daily_collections.*",
                        "business_locations.name as location_name",
                        "pump_operators.name as pump_operator_name",
                        "settlements.id as settlements_id",
                        "users.username as user",
                    ])
                    ->orderBy("daily_collections.id")
                    ->get();
            }

            $outstanding_payment = $settlement_total;

            if ($daily_collections->isNotEmpty() && $outstanding_payment > 0) {
                $sids = $shift_ids_for_collections;

                foreach ($daily_collections as $dc) {
                    if ($outstanding_payment <= 0) {
                        break;
                    }

                    if (! is_object($dc) || ! isset($dc->current_amount)) {
                        continue;
                    }

                    $row = DailyCollection::where('id', $dc->id)
                        ->where('business_id', $business_id)
                        ->where("type", "daily_collection")
                        ->where('pump_operator_id', $settlement->pump_operator_id)
                        ->whereNull('settlement_id')
                        ->whereIn('shift_id', $sids)
                        ->lockForUpdate()
                        ->first();

                    if (! $row) {
                        continue;
                    }

                    $amount = floatval($row->current_amount);
                    $alloc  = min($amount, $outstanding_payment);

                    // Check if this DailyCollection is already linked to a PumpOperatorPayment
                    // that has a SettlementCashPayment (to prevent duplication)
                    $pump_payment = null;
                    if (! empty($row->collection_form_no)) {
                        $pump_payment = \Modules\Petro\Entities\PumpOperatorPayment::where('business_id', $business_id)
                            ->where('pump_operator_id', $settlement->pump_operator_id)
                            ->where('collection_form_no', $row->collection_form_no)
                            ->where('payment_type', 'cash')
                            ->first();
                    }

                    // If linked to PumpOperatorPayment, check if SettlementCashPayment already exists
                    $existing_cash_payment = null;
                    if ($pump_payment) {
                        $existing_cash_payment = SettlementCashPayment::where('business_id', $business_id)
                            ->where(function ($q) use ($settlement) {
                                $q->where('settlement_no', $settlement->id)
                                    ->orWhere('settlement_no', $settlement->settlement_no);
                            })
                            ->where('customer_payment_id', $pump_payment->id)
                            ->first();
                    } else {
                        // If not linked to PumpOperatorPayment, check by amount and settlement
                        // to prevent duplicate entries from DailyCollection
                        // Check if SettlementCashPayment already exists for this DailyCollection
                        // Use daily_collection_id if available, otherwise check by amount
                        $existing_cash_payment = SettlementCashPayment::where('business_id', $business_id)
                            ->where('settlement_no', $settlement->id) // Use ID (integer) to match relationship
                            ->where(function ($q) use ($row) {
                                $q->where('daily_collection_id', $row->id) // Primary check: daily_collection_id
                                    ->orWhere(function ($q2) use ($row) {
                                        // Fallback: check by amount if daily_collection_id not set
                                        $q2->where('amount', $row->current_amount)
                                            ->whereNull('customer_payment_id')
                                            ->whereNull('daily_collection_id');
                                    });
                            })
                            ->first();
                    }

                    // Only create SettlementCashPayment if it doesn't already exist
                    if (! $existing_cash_payment) {
                        // create a settlement cash payment record (so UI/process shows this payment)
                        // Check if SettlementCashPayment already exists for this DailyCollection to prevent duplicates
                        $existing_cash_payment = SettlementCashPayment::where('business_id', $business_id)
                            ->where('settlement_no', $settlement->id)
                            ->where('daily_collection_id', $row->id)
                            ->first();

                        if (! $existing_cash_payment) {
                            // Also check if there's a SettlementCashPayment with same amount and customer_payment_id
                            // This prevents duplicates when the same payment is added both from pumper dashboard and settlement modal
                            if ($pump_payment) {
                                $existing_by_pump_payment = SettlementCashPayment::where('business_id', $business_id)
                                    ->where('settlement_no', $settlement->id)
                                    ->where('customer_payment_id', $pump_payment->id)
                                    ->first();

                                if ($existing_by_pump_payment) {
                                    \Modules\Petro\Support\PetroDebug::info('Settlement PD: Skipping duplicate SettlementCashPayment from DailyCollection', [
                                        'settlement_id'                       => $settlement->id,
                                        'daily_collection_id'                 => $row->id,
                                        'pump_payment_id'                     => $pump_payment->id,
                                        'existing_settlement_cash_payment_id' => $existing_by_pump_payment->id,
                                        'amount'                              => $alloc,
                                    ]);
                                    // Update daily_collection but don't create duplicate SettlementCashPayment
                                } else {
                                    $customers   = Contact::customersDropdown($business_id, false, true, "customer");
                                    $customer_id = null;
                                    if ($customers instanceof \Illuminate\Support\Collection) {
                                        $custArr     = $customers->toArray();
                                        $customer_id = array_key_first($custArr);
                                    } elseif (is_array($customers)) {
                                        $customer_id = array_key_first($customers);
                                    }

                                    app(\Modules\Petro\Services\SettlementPaymentReconciler::class)->upsertOne(
                                        $business_id,
                                        (string) $settlement->id,
                                        'settlement_cash_payments',
                                        [
                                            "amount"              => $alloc,
                                            "customer_id"         => $customer_id,
                                            "customer_payment_id" => $pump_payment->id,
                                            "pump_payment_id"     => $pump_payment->id,
                                            "daily_collection_id" => $row->id,
                                        ]
                                    );
                                }
                            } else {
                                // No pump_payment link, create SettlementCashPayment from DailyCollection
                                $customers   = Contact::customersDropdown($business_id, false, true, "customer");
                                $customer_id = null;
                                if ($customers instanceof \Illuminate\Support\Collection) {
                                    $custArr     = $customers->toArray();
                                    $customer_id = array_key_first($custArr);
                                } elseif (is_array($customers)) {
                                    $customer_id = array_key_first($customers);
                                }

                                // DAY1-ORPHAN: no pump_payment link from DailyCollection — orphan-path insert via Reconciler.
                                app(\Modules\Petro\Services\SettlementPaymentReconciler::class)->upsertOne(
                                    $business_id,
                                    (string) $settlement->id,
                                    'settlement_cash_payments',
                                    [
                                        "amount"              => $alloc,
                                        "customer_id"         => $customer_id,
                                        "customer_payment_id" => null,
                                        "pump_payment_id"     => null,
                                        "daily_collection_id" => $row->id,
                                    ]
                                );
                            }
                        } else {
                            \Modules\Petro\Support\PetroDebug::info('Settlement PD: SettlementCashPayment already exists for DailyCollection', [
                                'settlement_id'                       => $settlement->id,
                                'daily_collection_id'                 => $row->id,
                                'existing_settlement_cash_payment_id' => $existing_cash_payment->id,
                            ]);
                        }
                    }
                    $row->update([
                        'settlement_id'      => $settlement->id,
                        'settlement_date'    => $settlement->finish_date ?? date('Y-m-d'),
                        'balance_collection' => $alloc,
                        'added_to_account'   => 1,
                    ]);

                    $outstanding_payment -= $alloc;
                }
            }

            // create VAT entries
            $this->transactionUtil->calculateAndUpdateVAT($sell_transaction);

            PumperDayEntry::where("settlement_no", $settlement_no)
                ->update([
                    "settlement_no"        => $request->settlement_no,
                    "settlement_added_by"  => auth()->user()->id,
                    "closed_in_settlement" => 1,
                ]);

            // Get shift_ids for filtering (to prevent updating records from other shifts)
            $shift_ids_for_filter = [];
            if ($request->shift_ids) {
                $shift_ids_for_filter = is_array($request->shift_ids)
                    ? $request->shift_ids
                    : explode(",", $request->shift_ids);
                $shift_ids_for_filter = array_map('intval', $shift_ids_for_filter);
            }

            // Forever-fix (2026-05-13): link pump_operator_assignments to the new settlement.
            //
            // Background: the SettlementPDController previously updated pumper_day_entries
            // with the new settlement_no / closed_in_settlement=1 (above), but never
            // propagated the same linkage to pump_operator_assignments. Result: after
            // finalize, every assignment for this settlement still had settlement_id=NULL
            // and closed_in_settlement=0. The edit page's primary lookup at line ~7538
            // (`WHERE assignments.settlement_id = $settlement->id`) returned empty, forcing
            // it onto the fragile work_shift→shift_number→shift_id fallback. The fallback
            // could miss meter sales whose shift_id wasn't reachable via shift_number, so
            // the Meter Sale tab on the edit page rendered empty. See the regression test:
            // tests/Feature/Petro/SettlementEditTest.php::finalized_pd_edit_meter_total_uses_shift_number_to_find_real_shift_id
            //
            // Linking by (business_id, pump_operator_id, shift_id) is stable and unique:
            // each assignment row is for exactly one operator on one shift on one pump.
            if (! empty($shift_ids_for_filter)) {
                $assignment_links = PumpOperatorAssignment::where('business_id', $business_id)
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->whereIn('shift_id', $shift_ids_for_filter)
                    ->update([
                        'settlement_id'        => $settlement->id,
                        'closed_in_settlement' => 1,
                    ]);

                \Modules\Petro\Support\PetroDebug::info('Settlement PD: linked pump_operator_assignments to settlement', [
                    'settlement_id'        => $settlement->id,
                    'settlement_no'        => $settlement->settlement_no,
                    'pump_operator_id'     => $settlement->pump_operator_id,
                    'shift_ids_for_filter' => $shift_ids_for_filter,
                    'rows_updated'         => $assignment_links,
                ]);
            }

            // Link credit sales from pumper dashboard that don't have settlement_no yet
            // Match by pump_operator_id and shift_id (from DailyVoucher)
            \Modules\Petro\Support\PetroDebug::info('Settlement PD: Linking Credit Sales from Pumper Dashboard', [
                'settlement_id'        => $settlement->id,
                'settlement_no'        => $settlement->settlement_no,
                'pump_operator_id'     => $settlement->pump_operator_id,
                'shift_ids_for_filter' => $shift_ids_for_filter,
            ]);

            if (! empty($shift_ids_for_filter)) {
                // Get DailyVoucher IDs for the shifts being settled
                $daily_voucher_ids_for_shifts = DailyVoucher::where('operator_id', $settlement->pump_operator_id)
                    ->whereIn('shift_id', $shift_ids_for_filter)
                    ->whereNull('settlement_no')
                    ->pluck('id')
                    ->toArray();

                \Modules\Petro\Support\PetroDebug::info('Settlement PD: DailyVoucher IDs for Shifts', [
                    'daily_voucher_ids_for_shifts' => $daily_voucher_ids_for_shifts,
                    'count'                        => count($daily_voucher_ids_for_shifts),
                ]);

                // Link SettlementCreditSalePayment records that match these DailyVouchers
                if (! empty($daily_voucher_ids_for_shifts)) {
                    // Ensure ALL daily vouchers for these shifts are marked as completed
                    // (even when voucher_order_number is empty or '0')
                    $daily_vouchers_updated = DailyVoucher::whereIn('id', $daily_voucher_ids_for_shifts)
                        ->whereNull('settlement_no')
                        ->update(['settlement_no' => $settlement->settlement_no]);

                    \Modules\Petro\Support\PetroDebug::info('Settlement PD: DailyVouchers Updated for Shifts', [
                        'updated_count'                      => $daily_vouchers_updated,
                        'settlement_no'                      => $settlement->settlement_no,
                        'daily_voucher_ids_for_shifts_count' => count($daily_voucher_ids_for_shifts),
                    ]);

                    $credit_sales_before_link = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                        ->where('pump_operator_id', $settlement->pump_operator_id)
                        ->whereIn('daily_voucher_id', $daily_voucher_ids_for_shifts)
                        ->where(function ($q) {
                            $q->whereNull('settlement_no')
                                ->orWhere('settlement_no', '');
                        })
                        ->get(['id', 'order_number', 'customer_id', 'settlement_no', 'daily_voucher_id']);

                    \Modules\Petro\Support\PetroDebug::info('Settlement PD: Credit Sales Found for Linking (by daily_voucher_id)', [
                        'count'        => $credit_sales_before_link->count(),
                        'credit_sales' => $credit_sales_before_link->toArray(),
                    ]);

                    $linked_count = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                        ->where('pump_operator_id', $settlement->pump_operator_id)
                        ->whereIn('daily_voucher_id', $daily_voucher_ids_for_shifts)
                        ->where(function ($q) {
                            $q->whereNull('settlement_no')
                                ->orWhere('settlement_no', '');
                        })
                        ->update(['settlement_no' => $settlement->settlement_no]);

                    \Modules\Petro\Support\PetroDebug::info('Settlement PD: Credit Sales Linked (by daily_voucher_id)', [
                        'linked_count'  => $linked_count,
                        'settlement_no' => $settlement->settlement_no,
                    ]);
                }

                // Also link by matching order_number and customer_id with DailyVouchers
                $daily_vouchers_for_shifts = DailyVoucher::where('operator_id', $settlement->pump_operator_id)
                    ->whereIn('shift_id', $shift_ids_for_filter)
                    ->whereNull('settlement_no')
                    ->get(['id', 'voucher_order_number', 'customer_id']);

                \Modules\Petro\Support\PetroDebug::info('Settlement PD: DailyVouchers for Matching by order_number', [
                    'count'          => $daily_vouchers_for_shifts->count(),
                    'daily_vouchers' => $daily_vouchers_for_shifts->toArray(),
                ]);

                foreach ($daily_vouchers_for_shifts as $dv) {
                    if (! empty($dv->voucher_order_number)) {
                        $credit_sales_found = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                            ->where('pump_operator_id', $settlement->pump_operator_id)
                            ->where('order_number', $dv->voucher_order_number)
                            ->where('customer_id', $dv->customer_id)
                            ->where(function ($q) {
                                $q->whereNull('settlement_no')
                                    ->orWhere('settlement_no', '');
                            })
                            ->get(['id', 'order_number', 'customer_id', 'settlement_no']);

                        \Modules\Petro\Support\PetroDebug::info('Settlement PD: Credit Sales Found for Linking (by order_number)', [
                            'order_number' => $dv->voucher_order_number,
                            'customer_id'  => $dv->customer_id,
                            'count'        => $credit_sales_found->count(),
                            'credit_sales' => $credit_sales_found->toArray(),
                        ]);

                        $linked_count = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                            ->where('pump_operator_id', $settlement->pump_operator_id)
                            ->where('order_number', $dv->voucher_order_number)
                            ->where('customer_id', $dv->customer_id)
                            ->where(function ($q) {
                                $q->whereNull('settlement_no')
                                    ->orWhere('settlement_no', '');
                            })
                            ->update(['settlement_no' => $settlement->settlement_no]);

                        \Modules\Petro\Support\PetroDebug::info('Settlement PD: Credit Sales Linked (by order_number)', [
                            'linked_count'  => $linked_count,
                            'order_number'  => $dv->voucher_order_number,
                            'settlement_no' => $settlement->settlement_no,
                        ]);
                    }
                }
            } else {
                \Log::warning('Settlement PD: No shift_ids_for_filter - Skipping Credit Sales Linking', [
                    'settlement_id' => $settlement->id,
                    'settlement_no' => $settlement->settlement_no,
                ]);
            }

            // Update DailyVoucher settlement_no for all credit sales linked to this settlement
            // First, update SettlementCreditSalePayment records to use settlement_no string (not ID)
            // IMPORTANT: Wrap the orWhere in a closure to prevent matching all records
            // Credit sales relationship expects settlement_no (string), not id (integer)

            // Log before update
            \Modules\Petro\Support\PetroDebug::info('Settlement PD: Updating Credit Sales Settlement No', [
                'settlement_id'    => $settlement->id,
                'settlement_no'    => $settlement->settlement_no,
                'pump_operator_id' => $settlement->pump_operator_id,
            ]);

            // Count credit sales before update
            $credit_sales_before = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->id)
                        ->orWhere('settlement_no', $settlement->settlement_no)
                        ->orWhereNull('settlement_no')
                        ->orWhere('settlement_no', '');
                })
                ->count();

            \Modules\Petro\Support\PetroDebug::info('Settlement PD: Credit Sales Count Before Update', [
                'count'         => $credit_sales_before,
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
            ]);

            // Update credit sales for this pump operator to use the final settlement_no
            // CRITICAL: Only update credit sales that:
            // 1. Are already linked to THIS settlement (by ID or settlement_no)
            // 2. Are unsettled AND belong to the current shift(s) being settled
            // DO NOT update credit sales that belong to OTHER saved settlements or OTHER shifts
            // This prevents modifying credit sales from previously saved settlements
            $updated_count = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where(function ($q) use ($settlement, $shift_ids_for_filter) {
                    // Credit sales already linked to this settlement (by ID or settlement_no)
                    $q->where(function ($subQ) use ($settlement) {
                        $subQ->where('settlement_no', $settlement->id)
                            ->orWhere('settlement_no', $settlement->settlement_no);
                    });

                    // OR unsettled credit sales that belong to the current shift(s)
                    if (! empty($shift_ids_for_filter)) {
                        $q->orWhere(function ($subQ) use ($shift_ids_for_filter) {
                            // Unsettled credit sales (null or empty settlement_no)
                            $subQ->where(function ($unsettledQ) {
                                $unsettledQ->whereNull('settlement_no')
                                    ->orWhere('settlement_no', '');
                            })
                                // AND belong to the current shift(s) via DailyVoucher
                                ->whereExists(function ($existsQ) use ($shift_ids_for_filter) {
                                    $existsQ->select(DB::raw(1))
                                        ->from('daily_vouchers')
                                        ->whereColumn('daily_vouchers.id', 'settlement_credit_sale_payments.daily_voucher_id')
                                        ->whereIn('daily_vouchers.shift_id', $shift_ids_for_filter);
                                });
                        })
                            // OR unsettled credit sales that belong to the current shift(s) via PumpOperatorPayment
                            ->orWhere(function ($subQ) use ($shift_ids_for_filter) {
                                // Unsettled credit sales (null or empty settlement_no)
                                $subQ->where(function ($unsettledQ) {
                                    $unsettledQ->whereNull('settlement_no')
                                        ->orWhere('settlement_no', '');
                                })
                                    // AND belong to the current shift(s) via PumpOperatorPayment
                                    ->whereExists(function ($existsQ) use ($shift_ids_for_filter) {
                                        $existsQ->select(DB::raw(1))
                                            ->from('pump_operator_payments')
                                            ->whereColumn('pump_operator_payments.pump_operator_id', 'settlement_credit_sale_payments.pump_operator_id')
                                            ->whereRaw('pump_operator_payments.collection_form_no COLLATE utf8mb4_unicode_ci = settlement_credit_sale_payments.collection_form_no COLLATE utf8mb4_unicode_ci')
                                            ->where('pump_operator_payments.payment_type', 'credit')
                                            ->whereIn('pump_operator_payments.shift_id', $shift_ids_for_filter);
                                    });
                            });
                    } else {
                        // If no shift_ids, only update already linked credit sales (don't link new ones)
                        // This prevents linking credit sales from unknown shifts
                    }
                })
                ->update(['settlement_no' => $settlement->settlement_no]);

            // Also update credit sales that are linked via DailyVouchers to the shifts being settled
            // This catches credit sales that might have been linked during meter sale addition
            // but need to be updated to the final settlement_no
            $additional_updated = 0;
            if (! empty($shift_ids_for_filter)) {
                // Get all DailyVouchers for these shifts that are now linked to this settlement
                $daily_voucher_ids_for_settlement = DailyVoucher::where('operator_id', $settlement->pump_operator_id)
                    ->whereIn('shift_id', $shift_ids_for_filter)
                    ->where('settlement_no', $settlement->settlement_no)
                    ->pluck('id')
                    ->toArray();

                if (! empty($daily_voucher_ids_for_settlement)) {
                    // Update credit sales linked to these DailyVouchers to use final settlement_no
                    // CRITICAL: Only update if settlement_no is null, empty, or matches this settlement
                    // DO NOT update credit sales that belong to OTHER saved settlements
                    $additional_updated = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                        ->where('pump_operator_id', $settlement->pump_operator_id)
                        ->whereIn('daily_voucher_id', $daily_voucher_ids_for_settlement)
                        ->where(function ($q) use ($settlement) {
                            // Only update if unsettled or already linked to this settlement
                            $q->whereNull('settlement_no')
                                ->orWhere('settlement_no', '')
                                ->orWhere('settlement_no', $settlement->id)
                                ->orWhere('settlement_no', $settlement->settlement_no);
                        })
                        ->update(['settlement_no' => $settlement->settlement_no]);
                }
            }

            $total_updated = $updated_count + $additional_updated;

            \Modules\Petro\Support\PetroDebug::info('Settlement PD: Credit Sales Update Result', [
                'updated_count'      => $updated_count,
                'additional_updated' => $additional_updated ?? 0,
                'total_updated'      => $total_updated ?? $updated_count,
                'settlement_id'      => $settlement->id,
                'settlement_no'      => $settlement->settlement_no,
            ]);

            // Verify after update
            $credit_sales_after = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where('settlement_no', $settlement->settlement_no)
                ->get(['id', 'order_number', 'customer_id', 'settlement_no', 'daily_voucher_id']);

            \Modules\Petro\Support\PetroDebug::info('Settlement PD: Credit Sales After Update', [
                'count'         => $credit_sales_after->count(),
                'credit_sales'  => $credit_sales_after->toArray(),
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
            ]);

            // Update DailyVoucher records - get all credit sales linked to this settlement
            // Credit sales relationship uses settlement_no (string), so query by settlement_no only
            $credit_sale_payments = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                ->where('settlement_no', $settlement->settlement_no)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->get();

            if (! empty($credit_sale_payments)) {
                // Collect all daily_voucher_ids from credit sales
                // Use values() to ensure we get a simple array, not associative
                $daily_voucher_ids = $credit_sale_payments->pluck('daily_voucher_id')->filter()->unique()->values()->toArray();

                // Update DailyVouchers by daily_voucher_id (most direct link)
                // IMPORTANT: Update ALL vouchers linked to these credit sales, even if they already have a settlement_no
                // This ensures that if vouchers were linked during meter sale addition, they get updated to the final settlement_no
                if (! empty($daily_voucher_ids)) {
                    $voucher_query = DailyVoucher::whereIn('id', $daily_voucher_ids)
                        ->where('operator_id', $settlement->pump_operator_id);
                    if (! empty($shift_ids_for_filter) && \Modules\Petro\Support\SchemaCapabilityCache::hasColumn('daily_vouchers', 'shift_id')) {
                        $voucher_query->whereIn('shift_id', $shift_ids_for_filter);
                    }
                    $voucher_query->update(['settlement_no' => $settlement->settlement_no]);
                }

                // Also update DailyVouchers that match the credit sale order_number and customer
                // This handles cases where daily_voucher_id might be null or missing
                // IMPORTANT: Filter by shift_id to prevent updating records from other shifts
                foreach ($credit_sale_payments as $credit_sale) {
                    if (! empty($credit_sale->order_number)) {
                        // IMPORTANT: Update vouchers even if they already have a settlement_no
                        // This ensures vouchers linked during meter sale addition get updated to final settlement_no
                        $voucher_update_query = DailyVoucher::where('daily_vouchers_no', $credit_sale->order_number)
                            ->where('customer_id', $credit_sale->customer_id)
                            ->where('operator_id', $settlement->pump_operator_id);

                        // CRITICAL: Filter by shift_id to only update vouchers from the current shift being settled
                        // This prevents updating records from other shifts (e.g., old unsettled shifts)
                        if (! empty($shift_ids_for_filter)) {
                            $voucher_update_query->whereIn('shift_id', $shift_ids_for_filter);
                        }

                        $vouchers_found = $voucher_update_query->get(['id', 'daily_vouchers_no', 'settlement_no', 'shift_id']);
                        \Modules\Petro\Support\PetroDebug::info('Settlement PD: DailyVouchers Found by order_number', [
                            'count'        => $vouchers_found->count(),
                            'vouchers'     => $vouchers_found->toArray(),
                            'order_number' => $credit_sale->order_number,
                        ]);

                        // Update ALL matching vouchers to use the final settlement_no
                        // This ensures vouchers linked during meter sale addition get updated to final settlement_no
                        $vouchers_updated = $voucher_update_query->update(['settlement_no' => $settlement->settlement_no]);

                        \Modules\Petro\Support\PetroDebug::info('Settlement PD: DailyVouchers Updated by order_number', [
                            'updated_count' => $vouchers_updated,
                            'order_number'  => $credit_sale->order_number,
                        ]);
                    }
                }
            }

            // Final safety net: update all DailyVouchers and credit sales in this settlement scope
            $strict_shift_ids = $shift_ids_for_filter;
            if (empty($strict_shift_ids) && ! empty($settlement->work_shift)) {
                $strict_shift_ids = is_array($settlement->work_shift)
                    ? $settlement->work_shift
                    : explode(',', $settlement->work_shift);
                $strict_shift_ids = array_filter(array_map('intval', $strict_shift_ids));
            }

            $strict_voucher_query = DailyVoucher::where('business_id', $settlement->business_id)
                ->where('location_id', $settlement->location_id)
                ->where('operator_id', $settlement->pump_operator_id)
                ->whereDate('transaction_date', $settlement->transaction_date)
                ->where(function ($q) use ($settlement) {
                    $q->whereNull('settlement_no')
                        ->orWhere('settlement_no', '')
                        ->orWhere('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', (string) $settlement->id);
                });

            if (! empty($strict_shift_ids) && \Modules\Petro\Support\SchemaCapabilityCache::hasColumn('daily_vouchers', 'shift_id')) {
                $strict_voucher_query->whereIn('shift_id', $strict_shift_ids);
            }

            $strict_voucher_ids = $strict_voucher_query->pluck('id')->toArray();

            if (! empty($strict_voucher_ids)) {
                $vouchers_updated = DailyVoucher::whereIn('id', $strict_voucher_ids)
                    ->update([
                        'settlement_no' => $settlement->settlement_no,
                        'status'        => 1,
                    ]);

                $credit_sales_updated = SettlementCreditSalePayment::whereIn('daily_voucher_id', $strict_voucher_ids)
                    ->where('business_id', $settlement->business_id)
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->where(function ($q) use ($settlement) {
                        $q->whereNull('settlement_no')
                            ->orWhere('settlement_no', '')
                            ->orWhere('settlement_no', $settlement->settlement_no)
                            ->orWhere('settlement_no', $settlement->id);
                    })
                    ->update(['settlement_no' => $settlement->settlement_no]);

                \Modules\Petro\Support\PetroDebug::info('Settlement PD: Strict scope credit sale update', [
                    'daily_vouchers_updated' => $vouchers_updated,
                    'credit_sales_updated'   => $credit_sales_updated,
                    'strict_shift_ids'       => $strict_shift_ids,
                    'settlement_no'          => $settlement->settlement_no,
                ]);
            } else {
                \Modules\Petro\Support\PetroDebug::info('Settlement PD: No DailyVouchers matched strict scope for settlement update', [
                    'strict_shift_ids' => $strict_shift_ids,
                    'settlement_no'    => $settlement->settlement_no,
                ]);
            }

            // Update PumpOperatorAssignment for the given pumps and shifts
            $shift_ids = $request->shift_ids; // e.g. [5]

            if (! empty($shift_ids)) {
                $shift_ids = (array) $shift_ids; // force into array

                PumpOperatorAssignment::whereNull("settlement_id")
                    ->whereIn("shift_id", $shift_ids)
                    ->update([
                        "settlement_id"        => $settlement->id,
                        "closed_in_settlement" => 1,
                    ]);
            } else {
                if (!empty($pump_ids)) {
                    PumpOperatorAssignment::whereNull("settlement_id")
                        ->whereIn("pump_id", $pump_ids)
                        ->update([
                            "settlement_id"        => $settlement->id,
                            "closed_in_settlement" => 1,
                        ]);
                }
            }

            // CRITICAL: Mark settlement as finalized RIGHT BEFORE commit (matching Settlement SW pattern)
            // This ensures status = 0 is set at the very end, just before commit
            // If we set it earlier and there's an error, the transaction rollback would lose the status
            $settlement->update([
                'status'      => 0, // set status to non-active (finalized)
                'is_edit'     => 0,
                'finish_date' => date("Y-m-d"),
            ]);

            \Modules\Petro\Support\PetroDebug::info('Settlement PD: Settlement Finalized (status = 0) Right Before Commit', [
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'status'        => $settlement->status,
                'finish_date'   => $settlement->finish_date,
            ]);


            // Do not commit here. The finalize process still prepares print data,
            // sends notifications/history, and returns the response below.
            // The single DB::commit() must remain at the end of this try block.

            // CRITICAL: Reload settlement before preparing response data
            $settlement->refresh();

            // CRITICAL: Reload all relationships needed for the print view
            // refresh() clears all relations, so we must reload them explicitly
            $settlement->load([
                "meter_sales",
                "meter_sales_pd.details",
                "other_sales",
                "other_incomes",
                "customer_payments",
                "cash_payments",
                "cash_payments.customer",
                "cash_deposits",
                "card_payments",
                "cheque_payments",
                "credit_sale_payments",
                "expense_payments",
                "excess_payments",
                "shortage_payments",
                "loan_payments",
                "drawings_payments",
                "customer_loans",
            ]);

            $this->ensureSettlementCardAccounting($settlement, $business_id);
            $settlement->load("card_payments");

            \Modules\Petro\Support\PetroDebug::info('Settlement PD: Settlement Status After Commit', [
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'status'        => $settlement->status,
                'status_type'   => gettype($settlement->status),
            ]);

            try {
            } catch (\Exception $e) {
                \Log::warning('Failed to log settlement cash_payments: ' . $e->getMessage());
            }

            $sms_data = [
                "settlement_id"      => $settlement->id,
                "settlement_no"      => $settlement->settlement_no,
                "settlement_date"    => $this->transactionUtil->format_date($settlement->transaction_date),
                "pump_operator_name" => $pump_operator->name,
                "settlement_pumps"   => implode(",", $pumps),
                "total_sale_amount"  => $this->transactionUtil->num_f($total_sales_amount),
                "total_cash"         => $this->transactionUtil->num_f($settlement->cash_payments->sum("amount")),
                "total_cards"        => $this->transactionUtil->num_f($settlement->card_payments->sum("amount")),
                "total_credit_sales" => $this->transactionUtil->num_f($settlement->credit_sale_payments->sum("amount")),
                "total_short"        => $this->transactionUtil->num_f($settlement->shortage_payments->sum("amount")),
                "total_loans"        => $this->transactionUtil->num_f($settlement->customer_loans->sum("amount")),
                "total_cheques"      => $this->transactionUtil->num_f($settlement->cheque_payments->sum("amount")),
                "cash_deposit"       => $this->transactionUtil->num_f($settlement->cash_deposits->sum("amount")),
                "total_expenses"     => $this->transactionUtil->num_f($settlement->expense_payments->sum("amount")),
                "total_excess"       => $this->transactionUtil->num_f($settlement->excess_payments->sum("amount")),
                "loan_payments"      => $this->transactionUtil->num_f($settlement->loan_payments->sum("amount")),
                "owners_drawings"    => $this->transactionUtil->num_f($settlement->drawings_payments->sum("amount")),
                "editted_by"         => auth()->user()->username,
            ];

            if (! empty($edit)) {
                $original_details = SettlementEditHistory::where("settlement_id", $settlement->id)->first();
                $o_details        = "";
                $n_details        = "";
                $is_changed       = false;
                $changed_msg      = "";

                if (! empty($original_details)) {
                    // Check each field for changes and append to message if changed
                    $fields_to_check = [
                        "settlement_date",
                        "pump_operator_name",
                        "settlement_pumps",
                        "total_sale_amount",
                        "total_cash",
                        "total_cards",
                        "total_credit_sales",
                        "total_short",
                        "total_loans",
                        "total_cheques",
                    ];

                    foreach ($fields_to_check as $field) {
                        if ($original_details->$field != $sms_data[$field]) {
                            $is_changed   = true;
                            $changed_msg .= __("petro::lang.$field") .
                                __("petro::lang.changed_from") .
                                $original_details->$field .
                                __("petro::lang.to") .
                                $sms_data[$field] . PHP_EOL;

                            $o_details .= __("petro::lang.$field") . ": " . $original_details->$field . PHP_EOL;
                            $n_details .= __("petro::lang.$field") . ": " . $sms_data[$field] . PHP_EOL;
                        }
                    }

                    if (! empty($is_changed) && ! empty($changed_msg)) {
                        $activity               = new Activity();
                        $activity->log_name     = "Settlement PD";
                        $activity->description  = "update";
                        $activity->subject_id   = $settlement->id;
                        $activity->subject_type = "App\Settlement";
                        $activity->causer_id    = auth()->user()->id;
                        $activity->causer_type  = "App\User";
                        $activity->properties   = $changed_msg;
                        $activity->created_at   = date("Y-m-d H:i");
                        $activity->updated_at   = date("Y-m-d H:i");
                        $activity->save();
                    }
                }

                $data = [
                    "settlement_no"    => $settlement->settlement_no,
                    "editted_date"     => $this->transactionUtil->format_date(date("Y-m-d")),
                    "user_editted"     => auth()->user()->username,
                    "original_details" => $o_details,
                    "editted_details"  => $n_details,
                ];

                if (Str::startsWith((string) $settlement->settlement_no, 'PDST')) {
                    $this->notificationUtil->sendPetroNotification(
                        "edit_settlements",
                        $data
                    );
                }
            } else {
                if (Str::startsWith((string) $settlement->settlement_no, 'PDST')) {
                    $this->notificationUtil->sendPetroNotification(
                        "settlements",
                        $sms_data
                    );
                }
            }

            SettlementEditHistory::updateOrCreate(
                ["settlement_id" => $settlement->id],
                $sms_data
            );

            // $total_daily_collection = floatval(
            //     DailyCollection::where("pump_operator_id", $settlement->pump_operator_id)
            //         ->where("business_id", $business_id)
            //         ->where("settlement_id", $settlement->id)
            //         ->sum("current_amount")
            // );

            // Calculate final_cash_amount from SettlementCashPayment records (most accurate)
            // Calculate it here before it's used for total_daily_collection
            $cash_payments_for_calc = SettlementCashPayment::where('settlement_no', $settlement->id)
                ->where('business_id', $business_id)
                ->get();
            $cash_payments_total_for_calc = $cash_payments_for_calc->sum('amount');

            // IMPORTANT: Do NOT use daily_collections as fallback if SettlementCashPayment records exist
            // because SettlementCashPayment records are created FROM DailyCollection entries,
            // which would cause double counting. Only use daily_collections if no SettlementCashPayment exists.
            $final_cash_amount = $cash_payments_total_for_calc;

            // Only fallback to daily_collections if there are NO SettlementCashPayment records
            // (for backward compatibility with old settlements that might not have SettlementCashPayment records)
            if ($cash_payments_total_for_calc == 0) {
                $daily_collections_total = DB::table('daily_collections')
                    ->where("settlement_id", $settlement->id)
                    ->where('type', 'daily_collection')
                    ->selectRaw('COALESCE(SUM(current_amount + COALESCE(balance_collection,0)), 0) as total')
                    ->value('total') ?? 0;
                $final_cash_amount = $daily_collections_total;
            }

            // For total_daily_collection, use the same value
            $total_daily_collection  = floatval($final_cash_amount);

            $msg_template_wahtsapp = PetroWhatsAppTemplate::where("business_id", $business_id)
                ->where("auto_send_sms", 1)
                ->first();

            $business_locations = BusinessLocation::where("business_id", $business_id)
                ->pluck("name")
                ->first();

            $business_details = Business::find($business_id);

            $subscription = Subscription::active_subscription($business_id);

            $whatsapp_phone_no = 0;
            if (! empty($subscription)) {
                $pacakge_details   = $subscription->package_details;
                $whatsapp_phone_no = $pacakge_details["whatsapp_phone_no"];
            }

            // Commented WhatsApp sending logic
            /*
            if (!empty($whatsapp_phone_no) && !empty($msg_template_wahtsapp)) {
                $msg = $sms_data;

                $phones = [];
                if (!empty($business->sms_settings)) {
                    $phones = explode(',', str_replace(' ', '', $business->sms_settings['msg_phone_nos']));
                }

                $clean_phone = $whatsapp_phone_no;
                $text = "";
                foreach ($msg as $key => $value) {
                    $text .= "$key: $value\n";
                }

                $whatsapp_url = app(\App\Services\Messaging\GlobalWhatsAppService::class)->clickToChatUrl(
                    $clean_phone,
                    $text,
                    ['page_title' => 'Settlement Notification', 'page_no' => 1]
                );

                return redirect()->away($whatsapp_url);
            }
            */

            //$shift_ids = $request->shift_ids;

            // Reload cash payments - use ID (integer) to match cash_payments relationship
            $cash_payments_query = SettlementCashPayment::where('settlement_no', $settlement->id)
                ->where('business_id', $business_id);

            if (! empty($shift_ids_for_filter)) {
                $cash_payments_query->where(function ($q) use ($shift_ids_for_filter) {
                    $q->whereExists(function ($existsQ) use ($shift_ids_for_filter) {
                        $existsQ->select(DB::raw(1))
                            ->from('pump_operator_payments')
                            ->where(function ($subQ) {
                                $subQ->whereColumn('pump_operator_payments.id', 'settlement_cash_payments.customer_payment_id')
                                     ->orWhereColumn('pump_operator_payments.id', 'settlement_cash_payments.pump_payment_id');
                            })
                            ->whereIn('pump_operator_payments.shift_id', $shift_ids_for_filter);
                    })
                    ->orWhere(function ($orQ) {
                        $orQ->whereNull('settlement_cash_payments.customer_payment_id')
                            ->whereNull('settlement_cash_payments.pump_payment_id');
                    });
                });
            }

            $cash_payments = $cash_payments_query->with('customer')->get();
            $settlement->setRelation('cash_payments', $cash_payments);

            // Calculate final_cash_amount from SettlementCashPayment records (most accurate)
            $cash_payments_total = $settlement->cash_payments->sum('amount');

            \Modules\Petro\Support\PetroDebug::info('Settlement PD Store: Cash Payments Calculation', [
                'settlement_id'       => $settlement->id,
                'settlement_no'       => $settlement->settlement_no,
                'cash_payments_count' => $settlement->cash_payments->count(),
                'cash_payments_total' => $cash_payments_total,
            ]);

            // IMPORTANT: Do NOT use daily_collections as fallback if SettlementCashPayment records exist
            // because SettlementCashPayment records are created FROM DailyCollection entries,
            // which would cause double counting. Only use daily_collections if no SettlementCashPayment exists.
            $final_cash_amount = $cash_payments_total;

            // Only fallback to daily_collections if there are NO SettlementCashPayment records
            // (for backward compatibility with old settlements that might not have SettlementCashPayment records)
            if ($cash_payments_total == 0) {
                $daily_collections_total = DB::table('daily_collections')
                    ->where("settlement_id", $settlement->id)
                    ->where('type', 'daily_collection')
                    ->selectRaw('COALESCE(SUM(current_amount + COALESCE(balance_collection,0)), 0) as total')
                    ->value('total') ?? 0;
                $final_cash_amount = $daily_collections_total;

                \Modules\Petro\Support\PetroDebug::info('Settlement PD Store: Using DailyCollections Fallback', [
                    'daily_collections_total' => $daily_collections_total,
                    'final_cash_amount'       => $final_cash_amount,
                ]);
            }

            // Credit sale payments for the print preview - filtered to the settled shift(s) only.
            // Uses the same logic as print() to ensure the popup only shows the relevant credit sales.
            $credit_sale_payments_for_print = $this->getFilteredCreditSalePayments(
                $settlement,
                array_values(array_filter(array_map('intval', $shift_ids_for_filter)))
            );
            $settlement->setRelation('credit_sale_payments', $credit_sale_payments_for_print);

            \Modules\Petro\Support\PetroDebug::info('Settlement PD Store: Credit Sales Loaded for Print', [
                'settlement_id'              => $settlement->id,
                'settlement_no'             => $settlement->settlement_no,
                'credit_sale_payments_count' => $credit_sale_payments_for_print->count(),
                'credit_sales'               => $credit_sale_payments_for_print->pluck('id', 'collection_form_no')->toArray(),
            ]);

            // Final verification: Check credit sales one more time before returning
            $final_credit_sales_check = SettlementCreditSalePayment::where('pump_operator_id', $settlement->pump_operator_id)
                ->where('settlement_no', $settlement->settlement_no)
                ->get(['id', 'order_number', 'customer_id', 'settlement_no', 'daily_voucher_id']);

            \Modules\Petro\Support\PetroDebug::info('Settlement PD Store: Final Credit Sales Verification', [
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'final_count'   => $final_credit_sales_check->count(),
                'credit_sales'  => $final_credit_sales_check->toArray(),
            ]);

            // Also verify DailyVouchers are updated
            $final_daily_vouchers_check = DailyVoucher::where('operator_id', $settlement->pump_operator_id)
                ->where('settlement_no', $settlement->settlement_no)
                ->when(! empty($shift_ids_for_filter), function ($q) use ($shift_ids_for_filter) {
                    $q->whereIn('shift_id', $shift_ids_for_filter);
                })
                ->get(['id', 'daily_vouchers_no', 'settlement_no', 'shift_id']);

            \Modules\Petro\Support\PetroDebug::info('Settlement PD Store: Final DailyVouchers Verification', [
                'settlement_id'  => $settlement->id,
                'settlement_no'  => $settlement->settlement_no,
                'final_count'    => $final_daily_vouchers_check->count(),
                'daily_vouchers' => $final_daily_vouchers_check->toArray(),
            ]);

            $shift_ids = array_values(array_filter(array_map('intval', $strict_shift_ids)));
            $this->hydrateSettlementPdMeterSalesForDisplay($settlement, $shift_ids);

            // Commit the transaction before returning the view
            DB::commit();

            // Return JSON response for AJAX requests, view for regular requests
            if ($request->ajax() || $request->wantsJson()) {
                $redirect_url = $request->input('source') === 'petro_pd'
                    ? route('petropd.pd-settlement')
                    : action("\Modules\Petro\Http\Controllers\SettlementPDController@create");

                return response()->json([
                    "success"       => 1,
                    "msg"           => __("petro::lang.settlement_saved_successfully") ?: "Settlement saved successfully",
                    "settlement_id" => $settlement->id,
                    "settlement_no" => $settlement->settlement_no,
                    "redirect_url"  => $redirect_url,
                    "html"          => view("petro::settlement_pd.print")->with(
                        compact(
                            "settlement",
                            "business",
                            "pump_operator",
                            "customer_payments_tab",
                            "total_daily_collection",
                            "final_cash_amount",
                            "shift_ids"
                        )
                    )->render(),
                ]);
            }

            return view("petro::settlement_pd.print")->with(
                compact(
                    "settlement",
                    "business",
                    "pump_operator",
                    "customer_payments_tab",
                    "total_daily_collection",
                    "final_cash_amount",
                    "shift_ids"
                )
            );
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Settlement PD finalize failed', [
                'settlement_no' => $request->settlement_no ?? null,
                'business_id'   => $request->session()->get('business.id') ?? $request->session()->get('user.business_id'),
                'user_id'       => auth()->id(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'message'       => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);

            $output = [
                "success" => 0,
                "msg"     => ! empty($e->getMessage()) ? $e->getMessage() : __("messages.something_went_wrong"),
            ];

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($output);
            }

            return $output;
        }
    }

    public function createSettlementIfNotExist(Request $request)
    {

        $business_id = $request->session()->get("business.id");
        $business_id = ! empty($business_id) ? $business_id : $request->session()->get("user.business_id");
        $pump_operator_id = ! empty($request->pump_operator_id) ? $request->pump_operator_id : (! empty($request->operator_id) ? $request->operator_id : null);
        $location_id = $this->resolvePetroPdSettlementLocationId(
            (int) $business_id,
            $request->location_id,
            $pump_operator_id
        );
        if (! empty($location_id)) {
            $request->merge(['location_id' => $location_id]);
        }
        $settlement_no = trim((string) ($request->settlement_no ?? ''));
        $isPetroPdRequest = $this->isPetroPdModuleRequest($request);
        $transaction_date = \Carbon::parse(
            $request->transaction_date
        )->format("Y-m-d");

        // Permanent rule: only one pending Petro PD Settlement (PDST...) is allowed.
        // If a pending PDST settlement exists, reuse it instead of creating PDST+1.
        $active_settlement_id = (int) $request->input('active_settlement_id', 0);
        if (empty($active_settlement_id) && empty($settlement_no)) {
            $pending_pd_settlement = Settlement::where('business_id', $business_id)
                ->where('status', 1)
                ->where(function ($query) use ($business_id) {
                    foreach ($this->getPetroPdModuleSettlementPrefixes($business_id) as $prefix) {
                        $query->orWhere('settlement_no', 'LIKE', $prefix . '%');
                    }
                })
                ->orderByDesc('id')
                ->first();

            if (! empty($pending_pd_settlement)) {
                if (empty($pending_pd_settlement->location_id) && ! empty($location_id)) {
                    $pending_pd_settlement->location_id = $location_id;
                    $pending_pd_settlement->save();
                }
                $request->merge(['settlement_no' => $pending_pd_settlement->settlement_no]);
                return $pending_pd_settlement;
            }
        }

        // When editing an existing settlement (finalized or draft), reuse it
        // regardless of status so we don't spawn a new "Pending" settlement.
        if ($active_settlement_id > 0 && ! empty($pump_operator_id)) {
            $active_settlement = Settlement::where('id', $active_settlement_id)
                ->where('business_id', $business_id)
                ->first();

            if (! empty($active_settlement) && (int) $active_settlement->pump_operator_id === (int) $pump_operator_id) {
                if (empty($active_settlement->location_id) && ! empty($location_id)) {
                    $active_settlement->location_id = $location_id;
                    $active_settlement->save();
                }
                $request->merge(['settlement_no' => $active_settlement->settlement_no]);
                return $active_settlement;
            }
        }

        if ($isPetroPdRequest && ! $this->isPetroPdModuleSettlementNo($settlement_no, $business_id)) {
            $settlement_no = '';
        }

        if (empty($settlement_no)) {
            // S 268 FIX (2026-05-31): For PetroPD, never reuse a draft just because the
            // same operator/date/location already has status = 1. The draft must match the
            // selected shift. Otherwise Shift 1 and Shift 2 can get the same Settlement No.
            $requested_shift_ids = [];

            if (! empty($request->shift_ids)) {
                $requested_shift_ids = is_array($request->shift_ids)
                    ? $request->shift_ids
                    : explode(',', (string) $request->shift_ids);
            } elseif (! empty($request->shift_id)) {
                $requested_shift_ids = [$request->shift_id];
            } elseif (! empty($request->work_shift)) {
                $requested_shift_ids = is_array($request->work_shift)
                    ? $request->work_shift
                    : explode(',', (string) $request->work_shift);
            }

            $requested_shift_ids = array_values(array_unique(array_filter(array_map('intval', $requested_shift_ids))));

            $existing_draft = null;

            if (! $isPetroPdRequest || ! empty($requested_shift_ids) || ! empty($request->direct_shift_number)) {
                $existing_draft_query = Settlement::where("business_id", $business_id)
                    ->when(! empty($pump_operator_id), function ($query) use ($pump_operator_id) {
                        $query->where("pump_operator_id", $pump_operator_id);
                    })
                    ->when(! empty($location_id), function ($query) use ($location_id) {
                        $query->where("location_id", $location_id);
                    })
                    ->where("status", 1)
                    ->whereDate("transaction_date", $transaction_date);

                if ($isPetroPdRequest) {
                    $this->applyPetroPdModuleSettlementScope($existing_draft_query, $business_id, 'settlement_no');

                    if (! empty($requested_shift_ids)) {
                        $existing_draft_query->where(function ($query) use ($requested_shift_ids) {
                            foreach ($requested_shift_ids as $shift_id) {
                                $query->orWhere('work_shift', 'LIKE', '%"' . $shift_id . '"%')
                                    ->orWhere('work_shift', 'LIKE', '%[' . $shift_id . ']%')
                                    ->orWhere('work_shift', 'LIKE', '%,' . $shift_id . ',%')
                                    ->orWhere('work_shift', 'LIKE', $shift_id);
                            }
                        });
                    }
                } else {
                    $this->applyPdSettlementScope($existing_draft_query, $business_id);
                }

                $existing_draft = $existing_draft_query
                    ->orderByDesc("id")
                    ->first();
            }

            if (! empty($existing_draft) && ! empty($existing_draft->settlement_no)) {
                $settlement_no = $existing_draft->settlement_no;
            } else {
                $business = Business::find($business_id);
                $ref_no_prefixes = $business->ref_no_prefixes ?? [];
                $prefix = $isPetroPdRequest
                    ? $this->getPrimaryPetroPdModuleSettlementPrefix($business_id)
                    : (! empty($ref_no_prefixes["settlement"]) ? $ref_no_prefixes["settlement"] : "ST");

                $count = Settlement::where("business_id", $business_id)
                    ->where("settlement_no", "LIKE", $prefix . "%");

                if ($isPetroPdRequest) {
                    $this->applyPetroPdModuleSettlementScope($count, $business_id, 'settlement_no');
                } else {
                    $count->where("settlement_no", "NOT LIKE", "SET-SW%");
                    $this->excludePetroPdModuleSettlements($count, $business_id, 'settlement_no');
                }

                // Use the highest numeric suffix for the selected prefix, not the latest row id.
                // This keeps PDST and ST sequences independent and prevents skipped/reset numbers.
                $count = $count
                    ->pluck("settlement_no")
                    ->map(function ($settlementNo) {
                        return $this->extractLastInteger($settlementNo);
                    })
                    ->max();

                $nextNumber = ((int) $count) + 1;
                while (Settlement::where('business_id', $business_id)->where('settlement_no', $prefix . $nextNumber)->exists()) {
                    $nextNumber++;
                }
                $settlement_no = $prefix . $nextNumber;
            }

            $request->merge(["settlement_no" => $settlement_no]);
        }

        // Final safety guard: PD Settlement must never save an ST number.
        // If a stale browser/request sends ST, replace it with the next PDST before insert.
        if (! $this->isPetroPdModuleSettlementNo($settlement_no, $business_id)) {
            $prefix = $this->getPrimaryPetroPdModuleSettlementPrefix($business_id);
            $maxNumber = Settlement::where("business_id", $business_id)
                ->where(function ($query) use ($business_id) {
                    foreach ($this->getPetroPdModuleSettlementPrefixes($business_id) as $prefixOption) {
                        $query->orWhere("settlement_no", "LIKE", $prefixOption . "%");
                    }
                })
                ->pluck("settlement_no")
                ->map(function ($settlementNo) {
                    return $this->extractLastInteger($settlementNo);
                })
                ->max();
            $nextNumber = ((int) $maxNumber) + 1;
            while (Settlement::where('business_id', $business_id)->where('settlement_no', $prefix . $nextNumber)->exists()) {
                $nextNumber++;
            }
            $settlement_no = $prefix . $nextNumber;
            $request->merge(["settlement_no" => $settlement_no]);
        }

        $settlement_data = [

            "settlement_no"    => $settlement_no,

            "business_id"      => $business_id,

            "transaction_date" => $transaction_date,

            "location_id"      => $location_id,

            "pump_operator_id" => $pump_operator_id,

            "work_shift"       => ! empty($request->direct_shift_number)
                ? [$request->direct_shift_number]
                : (! empty($request->work_shift)
                    ? $request->work_shift
                    : []),

            "note"             => $request->note,

            "status"           => 1,

        ];

        $latest_date =

            DayEnd::where("business_id", $business_id)

            ->get()

            ->last()->day_end_date ?? null;

        if (

            ! empty($latest_date) &&

            strtotime($latest_date) >=

            strtotime($settlement_data["transaction_date"])

        ) {

            return 406;
        }

        $settlement_exist = Settlement::where(

            "settlement_no",

            $settlement_no

        )

            ->where("business_id", $business_id)
            ->when(! empty($pump_operator_id), function ($query) use ($pump_operator_id) {
                $query->where("pump_operator_id", $pump_operator_id);
            })

            ->first();

        if (empty($settlement_exist)) {

            $settlement_exist = Settlement::create($settlement_data);
        } elseif (empty($settlement_exist->location_id) && ! empty($location_id)) {
            $settlement_exist->location_id = $location_id;
            $settlement_exist->save();
        }

        return $settlement_exist;
    }

    /**







     * print resources







     * @param settlement_id







     * @return Response







     */
}

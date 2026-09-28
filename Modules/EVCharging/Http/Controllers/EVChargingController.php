<?php

namespace Modules\EVCharging\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\Product;
use App\PumperLoginAttempt;
use App\Settlement;
use App\SettlementCreditSalePayment;
use App\Store;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Essentials\Entities\WorkShift;
use Modules\Petro\Entities\FuelTank;
use Modules\Petro\Entities\PetroShift;
use Modules\Petro\Entities\Pump;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\PumpOperatorAssignment;
use Modules\Petro\Entities\PumpOperatorMeterSale;
use Modules\Petro\Entities\PumpOperatorOtherSale;
use Modules\Superadmin\Entities\Subscription;
use Yajra\DataTables\Facades\DataTables;

class EVChargingController extends Controller
{
    protected $productUtil;

    protected $moduleUtil;

    protected $transactionUtil;

    protected $commonUtil;

    protected $notificationUtil;

    private $barcode_types;

    public function __construct(

        Util $commonUtil,

        ProductUtil $productUtil,

        ModuleUtil $moduleUtil,

        TransactionUtil $transactionUtil,

        BusinessUtil $businessUtil,

        NotificationUtil $notificationUtil

    ) {

        $this->commonUtil = $commonUtil;

        $this->productUtil = $productUtil;

        $this->moduleUtil = $moduleUtil;

        $this->transactionUtil = $transactionUtil;

        $this->businessUtil = $businessUtil;

        $this->notificationUtil = $notificationUtil;
    }

    public function evChargingSettlement()
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
            ->where('ps.status', 0) // open shift
            ->whereNull('ps.closed_time')
            ->whereNull('pump_operator_assignments.settlement_id'); // not yet assigned to a settlement

        // If form should show shifts only for a selected operator (optional)
        if (! empty(request()->pump_operator)) {
            $shiftQuery->where('pump_operator_assignments.pump_operator_id', request()->pump_operator);
        }

        $shift_numbers = $shiftQuery->select('pump_operator_assignments.shift_number', 'pump_operator_assignments.shift_id')
            ->groupBy('pump_operator_assignments.shift_id')
            ->orderBy(DB::raw('CAST(pump_operator_assignments.shift_number AS UNSIGNED)'))
            ->pluck('shift_number', 'shift_id')
            ->toArray();

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
        $settleable_shift_id = PumpOperatorAssignment::where('business_id', $business_id)
            ->where('status', 'close')
            ->whereNull("settlement_id")
            ->where("closed_in_settlement", 0)
            ->whereNotNull('close_date_and_time')
            ->groupBy('shift_id')
            ->orderBy('shift_id', 'asc')
            ->value('shift_id');

        $shift_assignments = PumpOperatorAssignment::with('pumpOperator')
            ->where('business_id', $business_id)
            ->whereNull("settlement_id")
            ->where("closed_in_settlement", 0)
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


        // Log::info('pump operator iddddddd: ' . $pump_operator_id);
        Log::info('shift numberrrrrrrrrrr: ' . $shift_number);
        Log::info('pump operator nameeeeeeeeeee: ' . $pump_operator_name);

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

        $prefix = ! empty($ref_no_prefixes["settlement_pd"])

            ? $ref_no_prefixes["settlement_pd"]

            : "STPD";

        $starting_no = ! empty($ref_no_starting_number["settlement_pd"])

            ? (int) $ref_no_starting_number["settlement_pd"]

            : 1;

        $count = Settlement::where("business_id", $business_id)

            ->orderBy("id", "DESC")

            ->first();

        if (! empty($count)) {

            $count = $this->extractLastInteger($count->settlement_no);
        } else {

            $count = 0;
        }

        $settlement_no = $prefix . (1 + $count);

        $currency_precision = ! empty($business->currency_precision)

            ? $business->currency_precision

            : 2;

        $meeter_precision = 3;

        $active_settlement = Settlement::where("status", 1)

            ->where("settlement_no", "NOT LIKE", "SET-SW%")

            ->where("business_id", $business_id)

            ->select("settlements.*")

            ->with([

                "meter_sales",

                "other_sales",

                "other_incomes",

                "customer_payments",

            ])

            ->first();

        $other_sale_final_total = 0.0;

        $pump_other_sale_final_total = 0.0;

        $combinedOtherSales = [];

        if ($active_settlement) {

            $shift_number = PumpOperatorAssignment::where(

                "pump_operator_assignments.pump_operator_id",

                $active_settlement->pump_operator_id

            )

                ->leftJoin(

                    "settlements",

                    "pump_operator_assignments.settlement_id",

                    "=",

                    "settlements.id"

                )

                ->where(function ($query) {

                    $query

                        ->where("settlements.status", 1)

                        ->orWhereNull(

                            "pump_operator_assignments.settlement_id"

                        );
                })

                ->select("pump_operator_assignments.shift_number")

                ->groupBy("pump_operator_assignments.shift_number")

                ->get()

                ->toarray();

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

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        if (! empty($active_settlement)) {

            // $already_pumps = PumpOperatorMeterSale::where(

            //     "settlement_no",

            //     $active_settlement->id

            // )

            //     ->pluck("pump_id")

            //     ->toArray();
            $already_pumps = PumpOperatorMeterSale::where('settlement_no', $active_settlement->id)
                ->with('details:id,pump_id')
                ->get()
                ->pluck('details.pump_id')
                ->filter()        // removes nulls
                ->unique()        // avoids duplicates
                ->values()
                ->toArray();

            $pump_nos = Pump::where("business_id", $business_id)

                ->whereNotIn("id", $already_pumps)

                ->pluck("pump_name", "id");
        } else {

            $pump_nos = Pump::where("business_id", $business_id)->pluck(

                "pump_name",

                "id"

            );
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

        $payment_meter_sale_total = $this->getMeterSaleTotalByShift(
            (int) $business_id,
            $shift_id
        );

        $payment_other_sale_total = ! empty($active_settlement->other_sales)

            ? $active_settlement->other_sales->sum("sub_total")

            : 0.0;

        $payment_other_sale_discount = ! empty($active_settlement->other_sales)

            ? $active_settlement->other_sales->sum("sub_total")

            : 0.0;

        $payment_other_sale_total -= $payment_other_sale_discount;

        $payment_other_income_total = ! empty($active_settlement->other_incomes)

            ? $active_settlement->other_incomes->sum("sub_total")

            : 0.0;

        $payment_customer_payment_total = ! empty($active_settlement->customer_payments)

            ? $active_settlement->customer_payments->sum("sub_total")

            : 0.0;

        $wrok_shifts = WorkShift::where("business_id", $business_id)->pluck(

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

        if ($closed_shift) {
            //   dd($settlement_no);
            // $pump_ids = $shift_assignments->pluck('pump_id')->filter()->unique();
            if (empty($active_settlement)) {
                PumpOperatorMeterSale::where('business_id', $business_id)
                    ->where('shift_id', $shift_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->where(function ($q) {
                        $q->whereNull('settlement_no')
                            ->orWhere('settlement_no', '');
                    })
                    ->update([
                        'settlement_no' => $settlement_no
                    ]);
            }
            $meter_sales = PumpOperatorMeterSale::with([
                'details',
                'details.pump',
            ])
                ->where('business_id', $business_id)
                ->where('shift_id', $shift_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->get();

            Log::info('meter sales in create method of Settlement PD:', $meter_sales->toArray());


            $pump_nos = Pump::whereIn(
                'id',
                PumpOperatorAssignment::where('shift_id', $shift_id)
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

        Log::info('combined other sales: ', $combinedOtherSales);

        $discount_types = ["fixed" => "Fixed", "percentage" => "Percentage"];

        // Fetch settlement credit sale payments for the Credit Sales tab
        $settlement_credit_sale_payments = collect();
        if (! empty($active_settlement)) {
            $settlement_credit_sale_payments = SettlementCreditSalePayment::leftJoin(
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

        return view('evcharging::ev_charging_settlement.create')->with(

            compact(

                "select_pump_operator_in_settlement",

                "message",

                "shift_numbers",

                "business_locations",

                "payment_types",

                "customers",

                "pump_operators",

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
                "shift_id"

            )

        );
    }

    private function getMeterSaleTotalByShift(int $business_id, ?int $shift_id): float
    {
        if (empty($shift_id)) return 0.0;

        return (float) PumpOperatorMeterSale::where('business_id', $business_id)
            ->where('shift_id', $shift_id)
            ->sum('balance');
    }

    public function evChargingOperators()
    {

        $business_id = Auth::user()->business_id;
        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_module')) {
            abort(403, 'Unauthorized Access');
        }

        if (request()->ajax()) {

            $business_id = Auth::user()->business_id;
            if (request()->ajax()) {
                $query = PumpOperator::withoutGlobalScope('active')
                ->leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')
                    ->leftjoin('settlements', 'pump_operators.id', 'settlements.pump_operator_id')
                    ->where('pump_operators.business_id', $business_id)
                    ->select([
                        'pump_operators.*',
                        'settlements.settlement_no as st_no',
                        'pump_operators.id as pump_operator_id',
                        'business_locations.name as location_name',
                    ])->groupBy('pump_operators.id');

                if (! empty(request()->location_id)) {
                    $query->where('pump_operators.location_id', request()->location_id);
                }
                if (! empty(request()->pump_operator)) {
                    $query->where('pump_operators.id', request()->pump_operator);
                }
                if (! empty(request()->settlement_no)) {
                    $query->where('settlements.settlement_no', request()->settlement_no);
                }
                if (! empty(request()->status)) {
                    if (request()->status == 'active') {
                        $query->where('pump_operators.active', 1);
                    } else {
                        $query->where('pump_operators.active', 0);
                    }
                }
                if (! empty(request()->type)) {
                }

                $start_date       = request()->start_date;
                $end_date         = request()->end_date;
                
                // FIX: Provide default date range if not provided (today)
                if (empty($start_date)) {
                    $start_date = now()->format('Y-m-d');
                }
                if (empty($end_date)) {
                    $end_date = now()->format('Y-m-d');
                }
                
                $business_details = Business::find($business_id);
                $period_balances  = $this->getPeriodBalancesForRange($business_id, $start_date, $end_date, [
                    'location_id' => request()->location_id,
                ]);
                
                // DEBUG: Log period balances to verify data
                Log::info('Pump Operator List - Date Range: ' . $start_date . ' to ' . $end_date);
                Log::info('Pump Operator List - Period Balances Count: ' . count($period_balances));
                Log::info('Pump Operator List - Period Balances: ', $period_balances);

                $fuel_tanks = Datatables::of($query)
                    ->addColumn(
                        'action',
                        function ($row) {
                            $business_id           = session()->get('user.business_id');
                            $pay_excess_commission = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pay_excess_commission');
                            $recover_shortage      = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'recover_shortage');
                            $pump_operator_ledger  = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_ledger');

                            $html = '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-left" role="menu">

                            <li><a href="' . action('\Modules\Petro\Http\Controllers\PumpOperatorController@show', [$row->id]) . '"><i class="fa fa-eye" aria-hidden="true"></i>' . __("messages.view") . '</a></li>
                            <li><a href="' . action('\Modules\Petro\Http\Controllers\PumpOperatorController@edit', [$row->id]) . '" class="edit_contact_button"><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';
                            if (auth()->user()->can('pum_operator.active_inactive')) {
                                $html .= '<li class="divider"></li>';
                                if (! $row->active) {
                                    $html .= '<li><a href="' . action('\Modules\Petro\Http\Controllers\PumpOperatorController@toggleActivate', [$row->id]) . '" class="toggle_active_button"><i class="fa fa-check"></i> ' . __("lang_v1.activate") . '</a></li>';
                                } else {
                                    $html .= '<li><a href="' . action('\Modules\Petro\Http\Controllers\PumpOperatorController@toggleActivate', [$row->id]) . '" class="toggle_active_button"><i class="fa fa-times"></i> ' . __("lang_v1.deactivate") . '</a></li>';
                                }
                            }

                            $html .= '<li class="divider"></li>';
                            if ($pay_excess_commission) {
                                $html .= '<li><a href="' . action('\Modules\Petro\Http\Controllers\ExcessComissionController@create', ['pump_operator_id' => $row->id]) . '" class="edit_contact_button"> ' . __("petro::lang.pay_excess_and_commission") . '</a></li>';
                            }
                            if ($recover_shortage) {
                                $html .= '<li><a href="' . action('\Modules\Petro\Http\Controllers\RecoverShortageController@create', ['pump_operator_id' => $row->id]) . '" class="edit_contact_button"> ' . __("petro::lang.recover_shortages") . '</a></li>';
                            }
                            $html .= '<li class="divider"></li>
                            <li>
                                <a href="' . action('\Modules\Petro\Http\Controllers\PumpOperatorController@show', [$row->id]) . "?view=contact_info" . '">
                                    <i class="fa fa-user" aria-hidden="true"></i>
                                    ' . __("contact.contact_info", ["contact" => __("contact.contact")]) . '
                                </a>
                            </li>
                            ';

                            if ($pump_operator_ledger) {
                                $html .= '<li>
                                    <a href="' . action('\Modules\Petro\Http\Controllers\PumpOperatorController@show', [$row->id]) . "?view=ledger" . '">
                                        <i class="fa fa-anchor" aria-hidden="true"></i>
                                        ' . __("lang_v1.ledger") . '
                                    </a>
                                </li>';
                            }

                            $html .= '<li>
                                    <a href="' . action('\Modules\Petro\Http\Controllers\PumpOperatorController@listCommission', [$row->id]) . '">
                                        <i class="fa fa-anchor" aria-hidden="true"></i>
                                        ' . __("petro::lang.list_commission") . '
                                    </a>
                                </li>';

                            $html .= '<li>
                                <a href="' . action('\Modules\Petro\Http\Controllers\PumpOperatorController@show', [$row->id]) . "?view=documents_and_notes" . '">
                                    <i class="fa fa-paperclip" aria-hidden="true"></i>
                                     ' . __("lang_v1.documents_and_notes") . '
                                </a>
                            </li>

                        </ul></div>';

                            return $html;
                        }
                    )
                   ->editColumn('name', function ($row) {
                        $html = $row->name;

                        // show default badge
                        if ($row->is_default == 1) {
                            $html .= " <span class='badge bg-danger'>Default</span>";
                        }

                        // show deactivated badge
                        if ($row->active == 0) {
                            $html .= " <span class='badge bg-secondary'>Deactivated</span>";
                        }

                        return $html;
                    })

                    ->addColumn(
                        'pump_no',
                        ''
                    )
                    ->addColumn(
                        'settlement_no',
                        ''
                    )
                    ->addColumn(
                        'sold_fuel_qty',
                        function ($row) use ($business_details, $start_date, $end_date) {
                            $qty = PumpOperator::leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')
                                ->leftjoin('transactions', 'pump_operators.id', 'transactions.pump_operator_id')
                                ->leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                                ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
                                ->leftjoin('categories', 'products.category_id', 'categories.id')
                                ->where('transactions.type', 'sell')
                                ->where('categories.name', 'Fuel')
                                ->where('transactions.transaction_date', '>=', $start_date)
                                ->where('transactions.transaction_date', '<=', $end_date)
                                ->where('pump_operators.id', $row->pump_operator_id)
                                ->select([
                                    DB::raw('SUM(transaction_sell_lines.quantity) as sold_fuel_qty'),
                                ])
                                ->groupBy('pump_operators.id')->first();

                            if (empty($qty->sold_fuel_qty)) {
                                return $this->productUtil->num_f(0, false, $business_details, true);
                            }
                            return '<span class="sold_fuel_qty" data-orig-value="' . $qty->sold_fuel_qty . '" data-currency_symbol = true>' . $this->productUtil->num_f($qty->sold_fuel_qty, false, $business_details, true) . '</span>';
                        }
                    )
                    ->addColumn(
                        'sale_amount_fuel',
                        function ($row) use ($business_details, $start_date, $end_date) {
                            $amount = PumpOperator::leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')
                                ->leftjoin('transactions', 'pump_operators.id', 'transactions.pump_operator_id')
                                ->leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                                ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
                                ->leftjoin('categories', 'products.category_id', 'categories.id')
                                ->where('transactions.type', 'sell')
                                ->where('categories.name', 'Fuel')
                                ->where('transactions.transaction_date', '>=', $start_date)
                                ->where('transactions.transaction_date', '<=', $end_date)
                                ->where('pump_operators.id', $row->pump_operator_id)
                                ->select([
                                    'pump_operators.*',
                                    'business_locations.name as location_name',
                                    DB::raw('SUM(transaction_sell_lines.quantity * unit_price) as sale_amount_fuel'),
                                ])->first();
                            return '<span class="display_currency sale_amount_fuel" data-orig-value="' . $amount->sale_amount_fuel . '" data-currency_symbol = true>' . $this->productUtil->num_f($amount->sale_amount_fuel, false, $business_details, false) . '</span>';
                        }
                    )
                    ->addColumn(
                        'current_balance',
                        function ($row) {
                            //$balance_due = $this->getLedgerDetailsForDateRange($row->pump_operator_id, $start_date,$end_date)['balance_due'];
                            $balance_due = $this->transactionUtil->getPumpOperatorBalance($row->pump_operator_id);
                            return '<span class="display_currency current_balance" data-orig-value="' . $balance_due . '" data-currency_symbol = true>' . $this->productUtil->num_f($balance_due, false) . '</span>';
                        }
                    )
                    ->addColumn('balance_for_period', function ($row) use ($period_balances, $business_details) {
                        $summary = $period_balances[$row->pump_operator_id] ?? [
                            'balance_for_period' => 0,
                        ];
                        $balance_for_period = $summary['balance_for_period'] ?? 0;
                        return '<span class="display_currency text-right balance_for_period" style="display:block" data-orig-value="' . $balance_for_period . '" data-currency_symbol = true>' . $this->productUtil->num_f($balance_for_period, false, $business_details, true) . '</span>';
                    })

                ->editColumn(
                    'excess_amount',
                    function ($row) use ($period_balances, $business_details) {
                        $summary = $period_balances[$row->pump_operator_id] ?? [];
                        $total_excess = $summary['total_credit_for_period'] ?? 0;
                        return  '<span class="display_currency excess_amount" data-orig-value="' .  $total_excess . '" data-currency_symbol = true>' . $this->productUtil->num_f($total_excess, false, $business_details, true) . '</span>';
                    }
                )
                ->editColumn(
                    'short_amount',
                    function ($row) use ($period_balances, $business_details) {
                        $summary = $period_balances[$row->pump_operator_id] ?? [];
                        $total_shortage = $summary['total_debit_for_period'] ?? 0;
                        return  '<span class="display_currency short_amount" data-orig-value="' . $total_shortage . '" data-currency_symbol = true>' . $this->productUtil->num_f($total_shortage, false, $business_details, true) . '</span>';
                    }
                )
                    ->editColumn(
                        'commission_type',
                        function ($row) {
                            return ucfirst($row->commission_type);
                        }
                    )
                    ->editColumn(
                        'commission_rate',
                        function ($row) use ($business_details) {
                            return '<span class="display_currency commission_ap" data-orig-value="' . $row->commission_ap . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->commission_ap, false, $business_details, false) . '</span>';
                        }
                    )
                    ->addColumn(
                        'commission_amount',
                        function ($row) use ($business_details, $start_date, $end_date) {
                            $amount = $this->transactionUtil->getPumpOperatorCommission($row->pump_operator_id, $start_date, $end_date);
                            return '<span class="display_currency commission_amount" data-orig-value="' . $amount . '" data-currency_symbol = true>' . $this->productUtil->num_f($amount, false, $business_details, true) . '</span>';
                        }
                    )

                    ->removeColumn('id');

                return $fuel_tanks->rawColumns(['name', 'action', 'sold_fuel_qty', 'sale_amount_fuel', 'excess_amount', 'short_amount', 'commission_rate', 'commission_amount', 'current_balance', 'balance_for_period'])
                    ->make(true);
            }
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $pump_operators     = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');

        // dd($pump_operators);

        $pumps = Pump::where('pumps.business_id', $business_id)
            ->select('pumps.*')
            ->orderBy('pumps.id')
            ->get();

        foreach ($pumps as $pump) {

            $po_assign = PumpOperatorAssignment::leftjoin('pump_operators', 'pump_operators.id', 'pump_operator_assignments.pump_operator_id')
                ->where('pump_operator_assignments.business_id', $business_id)
                ->where('pump_operator_assignments.pump_id', $pump->id)
                ->where('pump_operator_assignments.status', 'open')
                ->select(
                    'pump_operator_assignments.id as assignment_id',
                    'pump_operator_assignments.pump_operator_id',
                    'pump_operator_assignments.shift_number',
                    'pump_operator_assignments.shift_id',
                    'pump_operator_assignments.is_confirmed',
                    'pump_operator_assignments.status as assignment_status',
                    'pump_operators.name as pumper_name'
                )
                ->first();
            if (! empty($po_assign)) {
                $pump->pumper_name       = $po_assign->pumper_name;
                $pump->pump_operator_id  = $po_assign->pump_operator_id;
                $pump->shift_number      = $po_assign->shift_number;
                $pump->shift_id          = $po_assign->shift_id;
                $pump->is_confirmed      = $po_assign->is_confirmed;
                $pump->assignment_id     = $po_assign->assignment_id;
                $pump->assignment_status = $po_assign->assignment_status;
            }
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $default_location   = current(array_keys($business_locations->toArray()));
        $payment_types      = $this->productUtil->payment_types($default_location);
        $tanks              = FuelTank::where('business_id', $business_id)->pluck('fuel_tank_number', 'id');
        $products           = Product::leftjoin('categories', 'products.category_id', 'categories.id')->where('products.business_id', $business_id)->where('categories.name', 'Fuel')->pluck('products.name', 'products.id');
        $settlement_nos     = [];

        $shifts = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')->where('petro_shifts.business_id', $business_id)->select('pump_operators.name', 'petro_shifts.*')->orderBy('id', 'DESC');

        $shifts = $shifts->get();

        $message = $this->transactionUtil->getGeneralMessage('general_message_pump_management_checkbox');

        $pumperLoginAttempts = PumperLoginAttempt::where('business_id', $business_id)
            ->where('status', "Blocked")
            ->get();
        $customers = Contact::customersDropdown($business_id, false, true, 'customer');
    
        return view('evcharging::ev_charging_operators.index')
        ->with(compact(
            'business_locations',
            'pump_operators',
            'settlement_nos',
            'message',
            'payment_types',
            'pumps',
            'tanks',
            'products',
            'shifts',
            'customers',
            'pumperLoginAttempts',
            'default_location'
        ));;
    }

    public function extractLastInteger($text)
    {

        if (preg_match('/\d+$/', $text, $matches)) {

            return intval($matches[0]);
        } else {

            return 0;
        }
    }
}

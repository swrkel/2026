<?php

namespace Modules\PetroGeneral\Http\Controllers\Settlement\Concerns;

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
use Modules\PetroGeneral\Entities\CustomerPayment;
use Modules\PetroGeneral\Entities\CustomerBillVatPrefix;
use Modules\PetroGeneral\Entities\DailyCard;
use Modules\PetroGeneral\Entities\DailyCollection;
use Modules\PetroGeneral\Entities\DailyVoucher;
use Modules\PetroGeneral\Entities\DayEnd;
use Modules\PetroGeneral\Entities\FuelTank;
use Modules\PetroGeneral\Entities\MeterSale;
use Modules\PetroGeneral\Entities\OtherIncome;
use Modules\PetroGeneral\Entities\OtherSale;
use Modules\PetroGeneral\Entities\PetroShift;
use Modules\PetroGeneral\Entities\PetroWhatsAppTemplate;
use Modules\PetroGeneral\Entities\Pump;
use Modules\PetroGeneral\Entities\PumperDayEntry;
use Modules\PetroGeneral\Entities\PumpOperator;
use Modules\PetroGeneral\Entities\PumpOperatorAssignment;
use Modules\PetroGeneral\Entities\PumpOperatorCommission;
use Modules\PetroGeneral\Entities\PumpOperatorPayment;
use Modules\PetroGeneral\Entities\PumpOperatorOtherSale;
use Modules\PetroGeneral\Entities\Settlement;
use Modules\PetroGeneral\Entities\SettlementCardPayment;
use Modules\PetroGeneral\Entities\SettlementCashDeposit;
use Modules\PetroGeneral\Entities\SettlementCashPayment;
use Modules\PetroGeneral\Entities\SettlementChequePayment;
use Modules\PetroGeneral\Entities\SettlementCreditSalePayment;
use Modules\PetroGeneral\Entities\SettlementEditHistory;
use Modules\PetroGeneral\Entities\SettlementExcessPayment;
use Modules\PetroGeneral\Entities\PumpOperatorMeterSale;
use Modules\PetroGeneral\Entities\SettlementExpensePayment;
use Modules\PetroGeneral\Entities\SettlementShortagePayment;
use Modules\PetroGeneral\Entities\SettlementLoanPayment;
use Modules\PetroGeneral\Entities\SettlementDrawingPayment;
use Modules\PetroGeneral\Entities\SettlementCustomerLoan;
use Modules\PetroGeneral\Entities\TankSellLine;
use Modules\Superadmin\Entities\Subscription;
use Modules\PetroGeneral\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

/**
 * Creating a settlement. store() is the large one - see the note in this file.
 *
 * MA-002: split out of PetroGeneral's SettlementController, which was 11,479
 * lines in a single file.
 *
 * The grouping follows the one used for the PD settlement controllers, since
 * these files share most of their method names - but it was rebuilt against
 * THIS file, because the modules have genuinely diverged in content.
 *
 * Traits, not separate controllers: routes, action() targets and the $this->
 * calls between these methods all resolve exactly as before. Method bodies
 * are byte-identical to the original.
 *
 * Methods here: create, store, createSettlementIfNotExist
 */
trait CreatesPdSettlements
{
    public function create()
    {

        $business_id = request()

            ->session()

            ->get('user.business_id');

        if (

            ! $this->moduleUtil->hasThePermissionInSubscription(

                $business_id,

                'petro_general'

            )

        ) {

            abort(403, 'Unauthorized Access');

        }

        // Keep empty by default; shift options are loaded for selected pump operator only.
        $shift_numbers = [];

        $reviewed = $this->transactionUtil->get_review(

            date('Y-m-d'),

            date('Y-m-d')

        );

        if (! empty($reviewed)) {

            $output = [

                'success' => 0,

                'msg' => "You can't add a settlement for an already reviewed date",

            ];

            return redirect()

                ->back()

                ->with(['status' => $output]);

        }

        $business_id = request()

            ->session()

            ->get('business.id');

        $business = Business::where('id', $business_id)->first();

        $pos_settings = json_decode($business->pos_settings ?? '{}', true);

        $check_qty = ! empty($pos_settings['allow_overselling']) ? false : true;

        $cash_denoms = ! empty($pos_settings['cash_denominations'])

            ? explode(',', $pos_settings['cash_denominations'])

            : [];

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        $payment_types = $this->productUtil->payment_types(

            $default_location,

            false,

            false,

            false,

            false,

            'is_sale_enabled'

        );

        $customers = Contact::customersDropdown($business_id, false);

        $pending_pump_operator_ids = $this->getDirectSettlementHiddenPendingPumpOperatorIds($business_id);

        $pump_operators = PumpOperator::where(

            'business_id',

            $business_id

        )
            ->when(!empty($pending_pump_operator_ids), function ($query) use ($pending_pump_operator_ids) {
                $query->whereNotIn('id', $pending_pump_operator_ids);
            })
            ->pluck('name', 'id');

        $settlement_credit_sale_payments = collect();

        $items = [];

        $ref_no_prefixes = request()

            ->session()

            ->get('business.ref_no_prefixes');

        $ref_no_starting_number = request()

            ->session()

            ->get('business.ref_no_starting_number');

        $prefix = ! empty($ref_no_prefixes['settlement'])

            ? $ref_no_prefixes['settlement']

            : '';

        $starting_no = ! empty($ref_no_starting_number['settlement'])

            ? (int) $ref_no_starting_number['settlement']

            : 1;

        $settlement_no = $this->getNextDirectSettlementNo($business_id);

        $currency_precision = ! empty($business->currency_precision)

            ? $business->currency_precision

            : 2;

        $meeter_precision = 3;

        $view_settlement_id = (int) request()->query('view_settlement_id', 0);

        $active_settlement = null;

        if ($view_settlement_id > 0) {
            $active_settlement = Settlement::where('business_id', $business_id)
                ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                ->where('settlement_no', 'NOT LIKE', 'PDST%')
                ->where('id', $view_settlement_id)
                ->select('settlements.*')
                ->with([
                    'meter_sales',
                    'other_sales',
                    'other_incomes',
                    'customer_payments',
                ])
                ->first();
        }

        $default_pump_operator_id = null;

        $other_sale_final_total = 0.0;

        $pump_other_sale_final_total = 0.0;

        $combinedOtherSales = [];

        if ($active_settlement) {

            if ((int) $active_settlement->status === 0) {
                $shift_number = PumpOperatorAssignment::where('settlement_id', $active_settlement->id)
                    ->where('pump_operator_id', $active_settlement->pump_operator_id)
                    ->select('pump_operator_assignments.shift_number', 'pump_operator_assignments.shift_id')
                    ->groupBy('pump_operator_assignments.shift_number')
                    ->get()
                    ->toarray();
            } else {
                $shift_number = PumpOperatorAssignment::where(

                    'pump_operator_assignments.pump_operator_id',

                    $active_settlement->pump_operator_id

                )

                    ->leftJoin(

                        'settlements',

                        'pump_operator_assignments.settlement_id',

                        '=',

                        'settlements.id'

                    )

                    ->where(function ($query) {

                        $query

                            ->where('settlements.status', 1)

                            ->orWhereNull(

                                'pump_operator_assignments.settlement_id'

                            );

                    })

                    ->select('pump_operator_assignments.shift_number', 'pump_operator_assignments.shift_id')

                    ->groupBy('pump_operator_assignments.shift_number')

                    ->get()

                    ->toarray();
            }

            $shift_numbers = collect($shift_number ?? [])
                ->mapWithKeys(function ($item) {
                    return [$item['shift_id'] => $item['shift_number']];
                })
                ->toArray();

            if (empty($shift_numbers) && ! empty($active_settlement->work_shift)) {
                $decodedDirectShift = is_array($active_settlement->work_shift)
                    ? $active_settlement->work_shift
                    : json_decode($active_settlement->work_shift, true);
                $directShiftLabel = is_array($decodedDirectShift)
                    ? collect($decodedDirectShift)->first()
                    : $active_settlement->work_shift;

                if (is_string($directShiftLabel) && Str::startsWith($directShiftLabel, $this->getDirectSettlementShiftPrefix($business_id))) {
                    $shift_numbers = [0 => $directShiftLabel];
                }
            }

            $userOtherDetails = [];

            foreach ($active_settlement->other_sales as $ot_item) {

                $product = \App\Product::find($ot_item->product_id);

                $discount_amount = $ot_item->discount_amount ?? 0;

                $withDiscount = ($ot_item->sub_total ?? 0) - $discount_amount;

                $other_sale_final_total += $withDiscount;

                // Prepare formatted array for user-entered sales

                $userOtherDetails[] = [

                    'id' => $ot_item->id,

                    'sku' => ! empty($product) ? $product->sku : '',

                    'name' => ! empty($product) ? $product->name : '',

                    'balance_stock' => number_format(

                        $ot_item->balance_stock,

                        4,

                        '.',

                        ','

                    ),

                    'price' => number_format(

                        $ot_item->price,

                        $currency_precision

                    ),

                    'qty' => number_format($ot_item->qty, 4, '.', ','),

                    'discount_type' => $ot_item->discount_type,

                    'discount' => number_format(

                        $ot_item->discount,

                        $currency_precision

                    ),

                    'sub_total' => number_format(

                        $ot_item->sub_total,

                        $currency_precision

                    ),

                    'with_discount' => number_format(

                        $withDiscount,

                        $currency_precision

                    ),

                    'user_check' => 1, // 1 for user entry

                ];

            }

            // ✅ Prepare shift_ids

            $shiftIds = array_column($shift_number, 'shift_id');

            // ✅ Pump operator other sale query

            $query = PumpOperatorOtherSale::join(

                'products',

                'products.id',

                '=',

                'pump_operator_other_sales.product_id'

            )

                ->leftJoin('variations', 'products.id', 'variations.product_id')

                ->leftJoin(

                    'variation_location_details',

                    'variations.id',

                    'variation_location_details.variation_id'

                )

                ->whereIn('pump_operator_other_sales.shift_id', $shiftIds)

                ->leftJoin('pump_operator_assignments', function ($join) {

                    $join

                        ->on(

                            'pump_operator_assignments.shift_id',

                            '=',

                            'pump_operator_other_sales.shift_id'

                        )

                        ->whereRaw('pump_operator_assignments.id = (



                             SELECT MAX(poa.id)



                             FROM pump_operator_assignments poa



                             WHERE poa.shift_id = pump_operator_other_sales.shift_id



                         )');

                })

                ->select(

                    'pump_operator_other_sales.*',

                    'products.name as product_name',

                    'products.sku as product_sku',

                    'pump_operator_assignments.shift_number',

                    'qty_available'

                )

                ->groupBy('pump_operator_other_sales.id');

            // ✅ Pump operator data processing

            $pumperOthersaleDetails = [];

            $pumpSales = $query->get();

            foreach ($pumpSales as $pumpSale) {

                $discount_amount = $pumpSale->discount ?? 0;

                $withDiscount = ($pumpSale->sub_total ?? 0) - $discount_amount;

                $pump_other_sale_final_total += $withDiscount;

                // Prepare formatted array for pump-operator-entered sales

                $pumperOthersaleDetails[] = [

                    'sku' => $pumpSale->product_sku,

                    'name' => $pumpSale->product_name,

                    'balance_stock' => number_format(

                        $pumpSale->qty_available,

                        4,

                        '.',

                        ','

                    ),

                    'price' => number_format(

                        $pumpSale->price,

                        $currency_precision

                    ),

                    'qty' => number_format(
                        ! empty($pumpSale->qty)
                            ? $pumpSale->qty
                            : ((float) ($pumpSale->price ?? 0) != 0.0 ? ($pumpSale->sub_total / $pumpSale->price) : 0),
                        4,
                        '.',
                        ','
                    ),

                    'discount_type' => $pumpSale->discount_type,

                    'discount' => number_format(

                        $pumpSale->discount,

                        $currency_precision

                    ),

                    'sub_total' => number_format(

                        $pumpSale->sub_total,

                        $currency_precision

                    ),

                    'with_discount' => number_format(

                        $withDiscount,

                        $currency_precision

                    ),

                    'user_check' => 0, // 0 for pump operator entry

                ];

            }

            // ✅ Merge both user and pump operator details

            $combinedOtherSales = array_merge(
                $userOtherDetails,
                $pumperOthersaleDetails
            );

            // ✅ Final Total of both

            $final_other_sale_total =

                $other_sale_final_total + $pump_other_sale_final_total;

        }

        // $combinedOtherSales = []; $pump_other_sale_final_total = 0;

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        if (! empty($active_settlement)) {

            $already_pumps = MeterSale::where(

                'settlement_no',

                $active_settlement->id

            )

                ->pluck('pump_id')

                ->toArray();

            // Keep Pump No scoped by selected operator/shift via AJAX on create page.
            // Avoid preloading broad pump lists at render time.
            $pump_nos = collect();

        } else {
            // Start empty; populated after operator + shift selection.
            $pump_nos = collect();
        }

        // other_sale tab

        $stores = Store::forDropdown($business_id, 0, 1, 'sell');

        $fuel_category_id = Category::where('business_id', $business_id)

            ->where('name', 'Fuel')

            ->first();

        $fuel_category_id = ! empty($fuel_category_id)

            ? $fuel_category_id->id

            : null;

        $items = $this->transactionUtil->getProductDropDownArray(

            $business_id,

            $fuel_category_id,

            'petro_settlements'

        );

        // other income tab

        $services = Product::where('business_id', $business_id)

            ->forModule('petro_settlements')

            ->where('enable_stock', 0)

            ->pluck('name', 'id');

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

                    $show_shift_no = in_array('pumper_dashboard', $decodedData)

                        ? true

                        : false;

                } else {

                    $show_shift_no = false;

                }

            }

        }

        $payment_meter_sale_total = $active_settlement

            ? $active_settlement->meter_sales->sum('discount_amount')

            : 0.0;

        $payment_other_sale_total = $active_settlement

            ? $active_settlement->other_sales->sum('sub_total')

            : 0.0;

        $payment_other_sale_discount = $active_settlement

            ? $active_settlement->other_sales->sum('sub_total')

            : 0.0;

        $payment_other_sale_total -= $payment_other_sale_discount;

        $payment_other_income_total = $active_settlement

            ? $active_settlement->other_incomes->sum('sub_total')

            : 0.0;

        $payment_customer_payment_total = $active_settlement

            ? $active_settlement->customer_payments->sum('sub_total')

            : 0.0;

        $wrok_shifts = WorkShift::where('business_id', $business_id)->pluck(

            'shift_name',

            'id'

        );

        $bulk_tanks = FuelTank::where('business_id', $business_id)

            ->where('bulk_tank', 1)

            ->pluck('fuel_tank_number', 'id');

        $select_pump_operator_in_settlement = $this->moduleUtil->hasThePermissionInSubscription(

            $business_id,

            'select_pump_operator_in_settlement'

        );

        $message = $this->transactionUtil->getGeneralMessage(

            'general_message_pump_management_checkbox'

        );

        $discount_types = ['fixed' => 'Fixed', 'percentage' => 'Percentage'];

        $show_mechanical_meter_too = $this->shouldShowMechanicalMeterToo($business_id);

        // dd($active_settlement,123);

        // dd($settlement_credit_sale_payments);

        $response = response()->view('petrogeneral::settlement.create', compact(

                'select_pump_operator_in_settlement',

                'message',

                'shift_numbers',

                'business_locations',

                'payment_types',

                'customers',

                'pump_operators',

                'default_pump_operator_id',

                'wrok_shifts',

                'pump_nos',

                'items',

                'settlement_no',

                'default_location',

                'active_settlement',

                'stores',

                'payment_meter_sale_total',

                'payment_other_sale_total',

                'payment_other_income_total',

                'payment_customer_payment_total',

                'bulk_tanks',

                'services',

                'discount_types',

                'cash_denoms',

                'check_qty',

                'payment_other_sale_discount',

                'show_shift_no',

                'combinedOtherSales',

                'other_sale_final_total',

                'pump_other_sale_final_total',
                'settlement_credit_sale_payments',
                'show_mechanical_meter_too'

            ));

        // Prevent caching so returning users always get fresh settlement data
        return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');

    }

    /**
     * Store a newly created resource in storage.







     * @return Response
     */

    public function store(Request $request, ContactController $contactController)
    {

        Log::info('SettlementController@store called', ['request' => $request->all()]);

        try {

            $denom_qty = $request->denom_qty;
            $denom_value = $request->denom_value;
            $denom_enabled = $request->denom_enabled;
            $denom_data = [];

            $business_id = request()->session()->get('user.business_id');

            $settings = \Modules\PetroGeneral\Entities\PumpOperator::where('business_id', $business_id)->whereNotNull('dashboard_settings')->first();
            $dashboard_settings = (! is_null($settings)) ? json_decode($settings->dashboard_settings, true) : [];
            $update_ledger = ($dashboard_settings['real_time_update_customer_ledger'] ?? 'no') === 'yes';
            $update_account = ($dashboard_settings['real_time_update_account_books'] ?? 'no') === 'yes';
            $pumper_ledger_update = ($dashboard_settings['pumper_ledger_update'] ?? 'no') === 'yes';

            if ($denom_enabled > 0) {
                $i = 0;
                foreach ($denom_qty as $one) {
                    $denom_data[] = [
                        'value' => $denom_value[$i],
                        'qty' => $denom_qty[$i],
                    ];
                    $i++;
                }
            }

            $settlement_no = $request->settlement_no;
            $no_change = $request->no_change;
            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)
                ->where('business_id', $business_id)
                ->first();

            if (empty($settlement)) {
                return [
                    'success' => 0,
                    'msg' => __('petrogeneral::lang.settlement_not_found_for_finalize') ?: 'Unable to finalize: settlement not found. Please refresh and try again.',
                ];
            }

            if ($settlement) {
                $this->attachUnsettledRteMeterSalesToSettlement($settlement);
            }

            $edit = Settlement::where('id', $settlement->id)
                ->where('business_id', $business_id)
                ->where('status', 0)
                ->first();

            $pump_operator_total_other_sale = 0;
            $pump_operator_other_sales = [];

            if ($request->shift_ids) {
                $shift_ids = is_array($request->shift_ids)
                    ? $request->shift_ids
                    : explode(',', $request->shift_ids);

                $shift_ids = array_values(array_filter(array_map('intval', $shift_ids), function ($shift_id) {
                    return $shift_id > 0;
                }));

                $pump_operator_total_other_sale = PumpOperatorOtherSale::join(
                    'products',
                    'products.id',
                    '=',
                    'pump_operator_other_sales.product_id'
                )
                    ->leftJoin('variations', 'products.id', 'variations.product_id')
                    ->leftJoin(
                        'variation_location_details',
                        'variations.id',
                        'variation_location_details.variation_id'
                    )
                    ->whereIn('pump_operator_other_sales.shift_id', $shift_ids);

                $pump_operator_total_other_sale = $pump_operator_total_other_sale->leftJoin(
                    'pump_operator_assignments',
                    function ($join) {
                        $join
                            ->on(
                                'pump_operator_assignments.shift_id',
                                '=',
                                'pump_operator_other_sales.shift_id'
                            )
                            ->whereRaw(
                                'pump_operator_assignments.id = (
                                    SELECT MAX(poa.id)
                                    FROM pump_operator_assignments poa
                                    WHERE poa.shift_id = pump_operator_other_sales.shift_id
                                )'
                            );
                    }
                );

                $pump_operator_other_sales = $pump_operator_total_other_sale
                    ->select('pump_operator_other_sales.*')
                    ->get();

                // Net after discount (must match other_sales and sell lines — was gross sub_total only).
                $pump_operator_total_other_sale = $pump_operator_other_sales->sum(function ($row) {
                    $sub = (float) ($row->sub_total ?? 0);
                    if (empty($row->discount_type)) {
                        return $sub;
                    }
                    if ($row->discount_type === 'percentage') {
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

            // Adding daily collection to cash payments
            $settlement_total =
                $settlement->meter_sales->sum('sub_total') +
                $settlement->other_sales->sum('sub_total') +
                $settlement->other_incomes->sum('sub_total') +
                $settlement->customer_payments->sum('sub_total') +
                $pump_operator_total_other_sale;

            // Get daily collections
            $daily_collections = DailyCollection::leftJoin(
                'business_locations',
                'daily_collections.location_id',
                'business_locations.id'
            )
                ->leftJoin('pump_operators', 'daily_collections.pump_operator_id', 'pump_operators.id')
                ->leftJoin('users', 'daily_collections.created_by', 'users.id')
                ->leftJoin('settlements', 'daily_collections.settlement_id', 'settlements.id')
                ->where('daily_collections.business_id', $business_id)
                ->where('daily_collections.pump_operator_id', $settlement->pump_operator_id)
                ->whereIn('daily_collections.type', ['daily_collection', 'daily_collection_sw'])
                ->whereNull('daily_collections.settlement_id')
                ->whereNull('daily_collections.added_to_account')
                ->whereIn('daily_collections.shift_id', $shift_ids)
                ->select([
                    'daily_collections.*',
                    'business_locations.name as location_name',
                    'pump_operators.name as pump_operator_name',
                    'settlements.id as settlements_id',
                    'users.username as user',
                ])
                ->orderBy('daily_collections.id')
                ->get();

            $outstanding_payment = $settlement_total;

            foreach ($daily_collections as $daily_collections) {
                if ($outstanding_payment >= 0) {
                    $customers = Contact::customersDropdown($business_id, false, true, 'customer');

                    // DAY1-ORPHAN: DailyCollection-sourced row, no pump_payment_id linkage in this flow.
                    // Goes through Reconciler so Lock 2 lets it through; orphan path always inserts,
                    // so we keep the legacy customer_id+amount existence check to avoid re-insert on retry.
                    $data = [
                        'amount' => floatval($daily_collections->current_amount),
                        'customer_id' => array_key_first($customers->toArray()),
                        'pump_payment_id' => null,
                    ];
                    $existing_payment = SettlementCashPayment::where('settlement_no', $settlement->id)
                        ->where('business_id', $business_id)
                        ->where('customer_id', $data['customer_id'])
                        ->where('amount', $data['amount'])
                        ->first();
                    if (!$existing_payment) {
                        app(\Modules\PetroGeneral\Services\SettlementPaymentReconciler::class)
                            ->upsertOne($business_id, (string) $settlement->id, 'settlement_cash_payments', $data);
                    }
                }
            }

            // Align with SettlementPDController: reject only when the client explicitly sends
            // `no_change` and there is truly nothing to finalize. The create flow does not
            // post `no_change`; the old `empty($no_change)` check blocked every finalize.
            $has_pending_changes = false;
            if ($daily_collections->whereNull('settlement_id')->count() > 0) {
                $has_pending_changes = true;
            }
            if (SettlementCashPayment::where('settlement_no', $settlement->id)->exists()) {
                $has_pending_changes = true;
            }
            if (SettlementCardPayment::where('settlement_no', $settlement->id)->exists()) {
                $has_pending_changes = true;
            }
            if (SettlementChequePayment::where('settlement_no', $settlement->id)->exists()) {
                $has_pending_changes = true;
            }
            if (SettlementCashDeposit::where('settlement_no', $settlement->id)->exists()) {
                $has_pending_changes = true;
            }
            if (SettlementExcessPayment::where('settlement_no', $settlement->id)->exists()) {
                $has_pending_changes = true;
            }
            if (SettlementShortagePayment::where('settlement_no', $settlement->id)->exists()) {
                $has_pending_changes = true;
            }
            if (SettlementExpensePayment::where('settlement_no', $settlement->id)->exists()) {
                $has_pending_changes = true;
            }
            if (SettlementLoanPayment::where('settlement_no', $settlement->id)->exists()) {
                $has_pending_changes = true;
            }
            if (SettlementDrawingPayment::where('settlement_no', $settlement->id)->exists()) {
                $has_pending_changes = true;
            }
            if (SettlementCustomerLoan::where('settlement_no', $settlement->id)->exists()) {
                $has_pending_changes = true;
            }
            if (CustomerPayment::where('settlement_no', $settlement->id)->exists()) {
                $has_pending_changes = true;
            }
            $has_credit_sales = SettlementCreditSalePayment::where('pump_operator_id', $settlement->pump_operator_id)
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->id)
                        ->orWhere('settlement_no', $settlement->settlement_no)
                        ->orWhereNull('settlement_no');
                })
                ->exists();
            if ($has_credit_sales) {
                $has_pending_changes = true;
            }
            if ($settlement->meter_sales()->exists()
                || $settlement->other_sales()->exists()
                || $settlement->other_incomes()->exists()) {
                $has_pending_changes = true;
            }

            if ($settlement->is_edit == 0 && $settlement->status == 0 && ! empty($no_change) && ! $has_pending_changes) {
                return [
                    'success' => 0,
                    'msg' => __('petrogeneral::lang.no_change_performed'),
                ];
            }

            DB::beginTransaction();

            if (! empty($edit)) {
                $this->deletePreviouseTransactions($settlement->id, false, $no_change);
            }

            $business_locations = BusinessLocation::forDropdown($business_id);
            $default_location = current(array_keys($business_locations->toArray()));

            $settlement = Settlement::where('settlements.id', $settlement->id)
                ->where('settlements.business_id', $business_id)
                ->leftJoin('pump_operators', 'settlements.pump_operator_id', 'pump_operators.id')
                ->leftJoin(
                    'pump_operator_assignments',
                    'pump_operator_assignments.pump_operator_id',
                    'pump_operators.id'
                )
                ->when(! empty($shift_ids), function ($query) use ($shift_ids) {
                    return $query->whereIn('pump_operator_assignments.shift_id', $shift_ids);
                })
                ->with([
                    'meter_sales',
                    'other_sales',
                    'other_incomes',
                    'customer_payments',
                    'cash_payments',
                    'cash_deposits',
                    'card_payments',
                    'cheque_payments',
                    'credit_sale_payments',
                    'expense_payments',
                    'excess_payments',
                    'shortage_payments',
                    'loan_payments',
                    'drawings_payments',
                    'customer_loans',
                ])
                ->select('settlements.*', 'pump_operators.name as pump_operator_name')
                ->first();

            if (empty($settlement)) {
                DB::rollBack();

                return [
                    'success' => 0,
                    'msg' => __('petrogeneral::lang.settlement_not_found_for_finalize') ?: 'Unable to finalize: settlement not found for the selected shift. Please refresh and try again.',
                ];
            }
            // dd('out', $daily_collections);

            $business = Business::where('id', $business_id)->first();
            $pump_operator = PumpOperator::where('id', $settlement->pump_operator_id)->first();

            if (empty($business) || empty($pump_operator)) {
                DB::rollBack();

                return [
                    'success' => 0,
                    'msg' => 'Unable to finalize: settlement business or pump operator details are missing.',
                ];
            }

            $this->syncRealTimePaymentsToSettlement($settlement, $shift_ids, $business_id);

            $settlement->load([
                'card_payments',
                'credit_sale_payments',
                'cheque_payments',
                'cash_payments',
            ]);

            // Modified by Engr. Alex -- task 7889
            // meter_sales.discount_amount = net amount after discount (sub_total - discount)
            // other_sales.discount_amount = the actual discount value (sub_total - discount_amount = net)
            $total_sales_amount =
                $settlement->meter_sales->sum('discount_amount') +
                ($settlement->other_sales->sum('sub_total') - $settlement->other_sales->sum('discount_amount')) +
                $pump_operator_total_other_sale;

            // Store gross pre-discount total for reference in transactions.discount_amount
            $total_sales_discount_amount =
                $settlement->meter_sales->sum('sub_total') +
                $settlement->other_sales->sum('sub_total');

            $pump_ids = $settlement->meter_sales->pluck('pump_id')->unique()->toArray();

            $pumps = Pump::whereIn('id', $pump_ids)
                ->select('pump_name')
                ->pluck('pump_name')
                ->toArray() ?? [];

            $subscription = Subscription::active_subscription($business_id);
            $monthly_max_sale_limit = 0;
            if (! empty($subscription) && ! empty($subscription->package)) {
                $monthly_max_sale_limit = (float) ($subscription->package->monthly_max_sale_limit ?? 0);
            }

            $startOfMonth = \Carbon::now()->startOfMonth()->toDateString();
            $endOfMonth = \Carbon::now()->endOfMonth()->toDateString();

            $current_monthly_sale = DB::table('transactions')
                ->select(DB::raw('sum(final_total) as total'))
                ->where('business_id', $business_id)
                ->whereIn('type', ['sell', 'property_sell'])
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->groupBy('business_id')
                ->first();

            $current_monthly_sale = is_null($current_monthly_sale)
                ? 0
                : (float) $current_monthly_sale->total;

            $current_monthly_sale += $total_sales_amount;

            if ($monthly_max_sale_limit > 0 && $current_monthly_sale > $monthly_max_sale_limit) {
                DB::rollBack();

                return [
                    'success' => 0,
                    'msg' => __('lang_v1.monthly_max_sale_limit_exceeded', [
                        'monthly_max_sale_limit' => $monthly_max_sale_limit,
                    ]),
                ];
            }

            $transaction = $this->createTransaction(
                $settlement,
                $total_sales_amount,
                null,
                $settlement->pump_operator_id,
                'sell',
                'settlement',
                $settlement_no,
                null,
                0,
                $total_sales_discount_amount
            );

            $sell_transaction = $transaction;
            $tax_amt = 0;

            foreach ($settlement->meter_sales as $meter_sale) {
                $pump = Pump::where('id', $meter_sale->pump_id)->first();
                $fuel_tank_id = ! empty($pump) ? $pump->fuel_tank_id : null;

                // Modified by Engr. Alex -- task 7889: if product_id is null on the meter sale
                // (e.g. saved before frontend fix), resolve it from the pump's fuel tank
                if (empty($meter_sale->product_id) && !empty($fuel_tank_id)) {
                    $fuel_tank = \Modules\PetroGeneral\Entities\FuelTank::find($fuel_tank_id);
                    if ($fuel_tank && !empty($fuel_tank->product_id)) {
                        $meter_sale->product_id = $fuel_tank->product_id;
                    }
                }

                \Log::info("meter-sale: $meter_sale");

                $sell_line = $this->createSellTransactions(
                    $transaction,
                    $meter_sale,
                    $business_id,
                    $default_location,
                    $fuel_tank_id,
                    null
                );

                MeterSale::where('id', $meter_sale->id)->update([
                    'transaction_id' => $transaction->id,
                ]);

                \Log::info('meter-sale: ');
            }

            foreach ($settlement->other_sales as $other_sale) {
                $getOtherSale = OtherSale::where('id', $other_sale->id)->first();

                if (empty($getOtherSale) || $getOtherSale->transaction_id == null || $getOtherSale->transaction_id != $transaction->id) {
                    \Log::info("other-sale: $other_sale");

                    $sell_line = $this->createSellTransactions(
                        $transaction,
                        $other_sale,
                        $business_id,
                        $default_location,
                        null,
                        true
                    );

                    OtherSale::where('id', $other_sale->id)->update([
                        'transaction_id' => $transaction->id,
                    ]);

                    \Log::info('Other-sale: ');
                }
            }

            foreach ($pump_operator_other_sales as $pump_operator_other_sales_item) {
                \Log::info("operator_other_sales: $pump_operator_other_sales_item");

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
                        'pump_operator_other_sale'
                    );

                    PumpOperatorOtherSale::where('id', $pump_operator_other_sales_item->id)
                        ->update(['transaction_id' => $transaction->id]);

                    \Log::info('operator_other_sales: ');
                }
            }

            foreach ($settlement->other_incomes as $other_income) {
                \Log::info("Other-income: $other_income");

                $sell_line = $this->createSellTransactions(
                    $transaction,
                    $other_income,
                    $business_id,
                    $default_location,
                    null,
                    null
                );

                OtherIncome::where('id', $other_income->id)->update([
                    'transaction_id' => $transaction->id,
                ]);

                \Log::info('Other-income: ');
            }

            /* map purchase sell lines */
            $this->createStockAccountTransactions($transaction); // @eng 11/2 1700

            $this->mapSellPurchaseLines(
                $business_id,
                $transaction,
                $settlement
            );

            $account_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');

            $cash_note = '';

            foreach ($settlement->cash_payments as $cash_payment) {
                $i = 0;

                $cash_transaction_payment = $this->createTransaction(
                    $settlement,
                    $cash_payment->amount,
                    $cash_payment->customer_id,
                    null,
                    'settlement',
                    'cash_payment',
                    $settlement_no
                );

                $cash_note .= ! empty($cash_payment->note)
                    ? 'Note '.$i++.': '.$cash_payment->note."\n"
                    : '';
                
                // Create account transaction (and ledger entry) for cash payment
                // payment_for: required for customer ledger (ContactUtil::getCustomerLedger $pmts)
                $transaction_payment = $this->createTansactionPayment(
                    $transaction,
                    'cash',
                    $cash_payment->amount,
                    null,
                    null,
                    null,
                    null,
                    null,
                    0,
                    $cash_transaction_payment->contact_id
                );
                
                // use a dedicated variable for cash account so we don't overwrite $account_id
                $cash_account_id = $this->transactionUtil->account_exist_return_id('Cash');
                $type = 'debit';
                
                // include settlement reference in note for traceability
                $entry_note = $cash_payment->note;
                if (!empty($entry_note)) {
                    $entry_note .= "\n";
                }
                $entry_note .= 'Settlement No: '.$settlement_no;

                $is_rt = ! empty($cash_payment->pump_payment_id);
                $this->createAccountTransaction(
                    $cash_transaction_payment,
                    $type,
                    $cash_account_id,
                    $transaction_payment->id,
                    null, // Use transaction sub_type ('cash_payment')
                    $cash_payment->customer_id,
                    $cash_payment->amount,
                    false,
                    $entry_note,
                    null,
                    $is_rt && $update_account,
                    ($is_rt && $update_ledger) || ($is_rt && $pumper_ledger_update)
                );
            }

            foreach ($settlement->customer_loans as $customer_loan) {
                $customer_loan_transaction = $this->createTransaction(
                    $settlement,
                    $customer_loan->amount,
                    $customer_loan->customer_id,
                    null,
                    'settlement',
                    'customer_loan',
                    $settlement_no,
                    null,
                    0,
                    0.0,
                    $customer_loan->note
                );

                $type = 'debit';
                $account_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');

                $this->createAccountTransaction(
                    $customer_loan_transaction,
                    $type,
                    $account_id,
                    $customer_loan_transaction->id,
                    'null',
                    null,
                    $customer_loan->amount,
                    false,
                    $customer_loan->note
                );

                $type = 'credit';
                $account_id = $this->transactionUtil->account_exist_return_id('Cash');

                $this->createAccountTransaction(
                    $customer_loan_transaction,
                    $type,
                    $account_id,
                    $customer_loan_transaction->id,
                    'null',
                    null,
                    $customer_loan->amount,
                    false,
                    $customer_loan->note
                );
            }

            $loan_note = '';

            foreach ($settlement->loan_payments as $loan_payment) {
                $i = 0;

                // this transaction will use in report to show amounts
                $loan_transaction_payment = $this->createTransaction(
                    $settlement,
                    $loan_payment->amount,
                    null,
                    null,
                    'settlement',
                    'loan_payment',
                    $settlement_no
                );

                $loan_note .= ! empty($loan_payment->note)
                    ? 'Note '.$i++.': '.$loan_payment->note."\n"
                    : '';

                $type = 'debit';
                $account_id = $this->transactionUtil->account_exist_return_id('Cash');

                $this->createAccountTransaction(
                    $loan_transaction_payment,
                    $type,
                    $account_id,
                    $loan_transaction_payment->id,
                    'null',
                    null,
                    $loan_payment->amount,
                    false,
                    $loan_payment->note
                );

                $type = 'credit';
                $account_id = $this->transactionUtil->account_exist_return_id('Cash');

                $this->createAccountTransaction(
                    $loan_transaction_payment,
                    $type,
                    $account_id,
                    $loan_transaction_payment->id,
                    'null',
                    null,
                    $loan_payment->amount,
                    false,
                    $loan_payment->note
                );

                $type = 'debit';
                $account_id = $loan_payment->loan_account;

                $this->createAccountTransaction(
                    $loan_transaction_payment,
                    $type,
                    $account_id,
                    $loan_transaction_payment->id,
                    'null',
                    null,
                    $loan_payment->amount,
                    false,
                    $loan_payment->note
                );
            }

            $drawing_note = '';

            foreach ($settlement->drawings_payments as $drawing_payment) {
                $i = 0;

                // this transaction will use in report to show amounts
                $drawing_transaction_payment = $this->createTransaction(
                    $settlement,
                    $drawing_payment->amount,
                    null,
                    null,
                    'settlement',
                    'drawing_payment',
                    $settlement_no
                );

                $drawing_note .= ! empty($drawing_payment->note)
                    ? 'Note '.$i++.': '.$drawing_payment->note."\n"
                    : '';

                $type = 'debit';
                $account_id = $this->transactionUtil->account_exist_return_id('Cash');

                $this->createAccountTransaction(
                    $drawing_transaction_payment,
                    $type,
                    $account_id,
                    $drawing_transaction_payment->id,
                    'null',
                    null,
                    $drawing_payment->amount,
                    false,
                    $drawing_payment->note
                );

                $type = 'credit';
                $account_id = $this->transactionUtil->account_exist_return_id('Cash');

                $this->createAccountTransaction(
                    $drawing_transaction_payment,
                    $type,
                    $account_id,
                    $drawing_transaction_payment->id,
                    'null',
                    null,
                    $drawing_payment->amount,
                    false,
                    $drawing_payment->note
                );

                $type = 'debit';
                $account_id = $drawing_payment->loan_account;

                $this->createAccountTransaction(
                    $drawing_transaction_payment,
                    $type,
                    $account_id,
                    $drawing_transaction_payment->id,
                    'null',
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
                    'settlement',
                    'cash_deposit',
                    $settlement_no,
                    $cash_payment->id
                );

                $type = 'debit';
                $account_id = $this->transactionUtil->account_exist_return_id('Cash');

                $this->createAccountTransaction(
                    $cash_deposit,
                    $type,
                    $account_id,
                    $cash_deposit->id,
                    'null',
                    null,
                    $cash_payment->amount,
                    false,
                    null
                );

                $this->createAccountTransaction(
                    $cash_deposit,
                    'credit',
                    $account_id,
                    $cash_deposit->id,
                    'null',
                    null,
                    $cash_payment->amount,
                    false,
                    null
                );

                $type = 'debit';
                $account_id = $cash_payment->bank_id;

                $this->createAccountTransaction(
                    $cash_deposit,
                    $type,
                    $account_id,
                    $cash_deposit->id,
                    'null',
                    null,
                    $cash_payment->amount,
                    false,
                    null
                );

                // Customer ledger: cash deposits were only in account books; walk-in ledger needs a row.
                $depositBank = Account::find($cash_payment->bank_id);
                $depositBankName = $depositBank ? $depositBank->name : '';
                $depositNoteParts = array_filter([
                    'Cash deposit to bank',
                    $depositBankName ? '('.$depositBankName.')' : null,
                    'Settlement: '.$settlement_no,
                    ! empty($cash_payment->account_no) ? 'Receipt: '.$cash_payment->account_no : null,
                ]);
                ContactLedger::createContactLedger([
                    'business_id' => $cash_deposit->business_id,
                    'contact_id' => $cash_deposit->contact_id,
                    'amount' => $cash_payment->amount,
                    'type' => 'credit',
                    'sub_type' => 'cash_deposit',
                    'operation_date' => $cash_deposit->transaction_date,
                    'created_by' => $cash_deposit->created_by,
                    'transaction_id' => $cash_deposit->id,
                    'note' => implode(' ', $depositNoteParts),
                ]);

                $customer = \App\Contact::find($cash_deposit->contact_id);
                if (! empty($customer) && (int) $customer->is_default === 1) {
                    ContactLedger::createContactLedger([
                        'business_id' => $cash_deposit->business_id,
                        'contact_id' => $cash_deposit->contact_id,
                        'amount' => $cash_payment->amount,
                        'type' => 'debit',
                        'sub_type' => 'sell',
                        'operation_date' => $cash_deposit->transaction_date,
                        'created_by' => $cash_deposit->created_by,
                        'transaction_id' => $cash_deposit->id,
                        'note' => implode(' ', $depositNoteParts),
                    ]);
                }

                $sms_settings = empty($business->sms_settings)
                    ? $this->businessUtil->defaultSmsSettings()
                    : $business->sms_settings;

                $msg_template = NotificationTemplate::where('business_id', $business_id)
                    ->where('template_for', 'cash_deposit')
                    ->first();

                if (! empty($msg_template)) {
                    $msg = $msg_template->sms_body;
                    $account = Account::find($cash_payment->bank_id);
                    $bank_name = ! empty($account) ? $account->name : '';

                    $msg = str_replace('{account}', $cash_payment->account_no, $msg);
                    $msg = str_replace('{amount}', $this->transactionUtil->num_f($cash_payment->amount), $msg);
                    $msg = str_replace(
                        '{time}',
                        $this->transactionUtil->format_date($cash_payment->time_deposited, true),
                        $msg
                    );
                    $msg = str_replace('{bank}', $bank_name, $msg);

                    $phones = [];
                    if (! empty($business->sms_settings)) {
                        $phones = explode(
                            ',',
                            str_replace(' ', '', $business->sms_settings['msg_phone_nos'])
                        );
                    }

                    foreach ($phones as $phone) {
                        $data = [
                            'sms_settings' => $sms_settings,
                            'mobile_number' => $phone,
                            'sms_body' => $msg,
                        ];

                        $response = $this->transactionUtil->sendSms($data);
                    }
                }
            }

            $cash_transaction_payment = null;

            if ($settlement->cash_payments->sum('amount') > 0) {
                $cash_transaction_payment = $this->createTansactionPayment(
                    $transaction,
                    'cash',
                    $settlement->cash_payments->sum('amount')
                );
            }

            foreach ($settlement->card_payments as $card_payment) {
                // this transaction will use in report to show amounts
                $card_transaction = $this->createTransaction(
                    $settlement,
                    $card_payment->amount,
                    $card_payment->customer_id,
                    null,
                    'settlement',
                    'card_payment',
                    $settlement_no
                );

                $transaction_payment = $this->createTansactionPayment(
                    $transaction,
                    'card',
                    $card_payment->amount,
                    $card_payment->card_number,
                    $card_payment->card_type,
                    null,
                    null,
                    null,
                    0,
                    $card_transaction->contact_id
                );

                SettlementCardPayment::where('id', $card_payment->id)->update([
                    'customer_payment_id' => $transaction_payment->id,
                ]);

                if (! empty($card_payment->card_type)) {
                    $account_id = $card_payment->card_type;
                } else {
                    $account_id = $this->transactionUtil->account_exist_return_id(
                        'Cards (Credit Debit) Account'
                    );
                }

                $type = 'debit';

                // Use the individual card payment transaction instead of main settlement transaction
                $is_rt = ! empty($card_payment->pump_payment_id);
                $this->createAccountTransaction(
                    $card_transaction,
                    $type,
                    $account_id,
                    $transaction_payment->id,
                    null, // Use transaction sub_type ('card_payment')
                    $card_payment->customer_id,
                    $card_payment->amount,
                    false,
                    $card_payment->note,
                    $card_payment->slip_no,
                    $is_rt && $update_account,
                    ($is_rt && $update_ledger) || ($is_rt && $pumper_ledger_update)
                );
            }

            foreach ($settlement->cheque_payments as $cheque_payment) {
                // this transaction will use in report to show amounts
                $cheque_transaction = $this->createTransaction(
                    $settlement,
                    $cheque_payment->amount,
                    $cheque_payment->customer_id,
                    null,
                    'settlement',
                    'cheque_payment',
                    $settlement_no
                );

                $transaction_payment = $this->createTansactionPayment(
                    $transaction,
                    'cheque',
                    $cheque_payment->amount,
                    null,
                    null,
                    $cheque_payment->cheque_number,
                    $cheque_payment->bank_name,
                    $cheque_payment->cheque_date,
                    $cheque_payment->post_dated_cheque,
                    $cheque_transaction->contact_id
                );

                $contact = Contact::where('id', $cheque_payment->customer_id)->first();
                $cheque_transaction->contact = $contact;
                $cheque_transaction->single_payment_amount = $this->transactionUtil->num_uf(
                    $cheque_payment->amount
                );
                $cheque_transaction->payment_ref_number = '';

                $this->notificationUtil->autoSendNotification(
                    $business_id,
                    'payment_received',
                    $cheque_transaction,
                    $cheque_transaction->contact,
                    true
                );

                SettlementChequePayment::where('id', $cheque_payment->id)->update([
                    'customer_payment_id' => $transaction_payment->id,
                ]);

                $account_id = $this->transactionUtil->account_exist_return_id('Cheques in Hand');
                $type = 'debit';

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

            // On initial finalization: process ALL credit sales
            // On edit/no_change: only process NEW credit sales (those without transaction_id yet)
            $credit_sales_to_process = empty($no_change)
                ? $settlement->credit_sale_payments
                : $settlement->credit_sale_payments->filter(function ($p) { return empty($p->transaction_id); });

            foreach ($credit_sales_to_process as $credit_sale_payment) {
                    $transaction = $this->createCreditSellTransactions(
                        $settlement,
                        $credit_sale_payment,
                        $default_location
                    );

                    $credit_sale_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                        ->editCreditSale($business_id, $credit_sale_payment->id, [
                            'transaction_id' => $transaction->id,
                        ]);

                    $account_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
                    $type = 'debit';

                    $is_rt = ! empty($credit_sale_payment->pump_payment_id);
                    $this->createAccountTransaction(
                        $transaction,
                        $type,
                        $account_id,
                        null,
                        'ledger_show',
                        $credit_sale_payment->customer_id,
                        $credit_sale_payment->amount - $credit_sale_payment->total_discount,
                        true,
                        $credit_sale_payment->note,
                        null,
                        $is_rt && $update_account,
                        ($is_rt && $update_ledger) || ($is_rt && $pumper_ledger_update)
                    );

                    if ($credit_sale_payment->is_from_pumper == 0) {
                        // store the customer reference
                        if (! empty($credit_sale_payment->customer_reference)) {
                            $customer = Contact::findOrFail($credit_sale_payment->customer_id);
                            $name = $customer->name;
                            $barcode_string = $name.'.'.$credit_sale_payment->customer_reference;

                            $qr = new DNS2D;
                            $qr = $qr->getBarcodePNG($barcode_string, 'QRCODE');
                            $src = 'data:image/png;base64,'.$qr;

                            $ref_data = [
                                'business_id' => $credit_sale_payment->business_id,
                                'date' => date('Y-m-d', strtotime($credit_sale_payment->order_date)),
                                'contact_id' => $credit_sale_payment->customer_id,
                                'reference' => $credit_sale_payment->customer_reference,
                                'barcode_src' => $src,
                            ];

                            CustomerReference::updateOrCreate(
                                [
                                    'business_id' => $credit_sale_payment->business_id,
                                    'contact_id' => $credit_sale_payment->customer_id,
                                    'reference' => $credit_sale_payment->customer_reference,
                                ],
                                $ref_data
                            );
                        }

                        $cheque_pmt = SettlementChequePayment::where('customer_id', $credit_sale_payment->customer_id)
                            ->where('settlement_no', $credit_sale_payment->settlement_no)
                            ->sum('amount');

                        $cash_pmt = SettlementCardPayment::where('customer_id', $credit_sale_payment->customer_id)
                            ->where('settlement_no', $credit_sale_payment->settlement_no)
                            ->sum('amount');

                        $card_pmt = SettlementCashPayment::where('customer_id', $credit_sale_payment->customer_id)
                            ->where('settlement_no', $credit_sale_payment->settlement_no)
                            ->sum('amount');

                        $total_paid = $cheque_pmt + $cash_pmt + $card_pmt;

                        $business_id = request()->session()->get('user.business_id');
                        $business = Business::where('id', $business_id)->first();
                        $sms_settings = empty($business->sms_settings)
                            ? $this->businessUtil->defaultSmsSettings()
                            : $business->sms_settings;

                        $contact = Contact::where('id', $credit_sale_payment->customer_id)->first();
                        $msg_template = NotificationTemplate::where('business_id', $business_id)
                            ->where('template_for', 'credit_sale')
                            ->first();

                        $final_total = $credit_sale_payment->amount - $credit_sale_payment->total_discount;
                        $product = Product::findOrFail($credit_sale_payment->product_id);

                        $product_msg = PHP_EOL.
                            'Product Sold: '.ucfirst($product->name).PHP_EOL.
                            'Quantity: '.$this->productUtil->num_f($credit_sale_payment->qty);

                        if (! empty($msg_template) && ! empty($contact) && $contact->credit_notification == 'settlement') {
                            $msg = $msg_template->sms_body;

                            $msg = str_replace('{business_name}', $business->name, $msg);
                            $msg = str_replace('{total_amount}', $this->productUtil->num_f($final_total), $msg);
                            $msg = str_replace('{contact_name}', $contact->name, $msg);
                            $msg = str_replace('{invoice_number}', $settlement->settlement_no, $msg);
                            $msg = str_replace('{transaction_date}', $settlement->transaction_date, $msg);
                            $msg = str_replace('{paid_amount}', $this->productUtil->num_f($total_paid), $msg);
                            $msg = str_replace('{due_amount}', $this->productUtil->num_f($final_total - $total_paid), $msg);
                            $msg = str_replace(
                                '{cumulative_due_amount}',
                                $this->productUtil->num_f(
                                    strval($contactController->get_due_bal($credit_sale_payment->customer_id, false))
                                ),
                                $msg
                            );
                            $msg = str_replace('{customer_reference}', $credit_sale_payment->customer_reference, $msg);
                            $msg = str_replace('{vehicle_no}', $credit_sale_payment->customer_reference, $msg);

                            $msg .= $product_msg;

                            if (! empty($business->sms_settings)) {
                                $phones = explode(
                                    ',',
                                    str_replace(' ', '', $business->sms_settings['msg_phone_nos'])
                                );
                            }

                            $data = [
                                'sms_settings' => $sms_settings,
                                'mobile_number' => $contact->mobile,
                                'sms_body' => $msg,
                            ];

                            $response = $this->businessUtil->sendSms($data);

                            $data['mobile_number'] = $contact->alternate_number;
                            $response = $this->businessUtil->sendSms($data, $contact, 'credit_sale');
                        }
                    } else {
                        $credit_sale_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                            ->editCreditSale($business_id, $credit_sale_payment->id, [
                                'is_from_pumper' => 0,
                                'is_committed' => 1,
                            ]);
                    }
                }

            $total_shortage = $pump_operator->short_amount; // get previous amount

            foreach ($settlement->shortage_payments as $shortage_payment) {
                $transaction = $this->createTransaction(
                    $settlement,
                    $shortage_payment->amount,
                    null,
                    $settlement->pump_operator_id,
                    'settlement',
                    'shortage',
                    $settlement_no
                );

                SettlementShortagePayment::where('id', $shortage_payment->id)
                    ->update(['transaction_id' => $transaction->id]);

                $account_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
                $type = 'debit';

                $this->createAccountTransaction(
                    $transaction,
                    $type,
                    $account_id,
                    null,
                    'ledger_show',
                    null,
                    0,
                    false,
                    $shortage_payment->note
                );

                $total_shortage += $shortage_payment->amount;
            }

            $total_excess = $pump_operator->excess_amount; // get previous amount

            foreach ($settlement->excess_payments as $excess_payment) {
                $transaction = $this->createTransaction(
                    $settlement,
                    $excess_payment->amount,
                    null,
                    $settlement->pump_operator_id,
                    'settlement',
                    'excess',
                    $settlement_no
                );

                SettlementExcessPayment::where('id', $excess_payment->id)
                    ->update(['transaction_id' => $transaction->id]);

                $account_id = $this->transactionUtil->account_exist_return_id('Accounts Payable');
                $type = 'credit';

                $this->createAccountTransaction(
                    $transaction,
                    $type,
                    $account_id,
                    null,
                    'ledger_show',
                    null,
                    0,
                    false,
                    $excess_payment->note
                );

                $total_excess += $excess_payment->amount;
            }

            $pump_operator->short_amount = $total_shortage;
            $pump_operator->excess_amount = $total_excess;
            $pump_operator->settlement_no = $settlement->settlement_no;
            $pump_operator->save();

            foreach ($settlement->expense_payments as $expense_payment) {
                $transaction = $this->createTransaction(
                    $settlement,
                    $expense_payment->amount,
                    null,
                    $settlement->pump_operator_id,
                    'settlement',
                    'expense',
                    $settlement_no
                );

                $transaction->expense_category_id = $expense_payment->category_id;
                $transaction->ref_no = 'Settlement No: '.$settlement->settlement_no;
                $transaction->expense_account = $expense_payment->account_id;
                $transaction->save();

                SettlementExpensePayment::where('id', $expense_payment->id)
                    ->update(['transaction_id' => $transaction->id]);

                $transaction_payment = $this->createTansactionPayment($transaction, 'cash');

                $account_id = $expense_payment->account_id;
                $type = 'debit';

                $this->createAccountTransaction($transaction, $type, $account_id, $transaction_payment->id);

                $account_id = $this->transactionUtil->account_exist_return_id('Cash');
                $type = 'credit';

                $this->createAccountTransaction($transaction, $type, $account_id, $transaction_payment->id);
            }

            if ($settlement->expense_payments->sum('amount') > 0) {
                // doc 3075 - POS Settlement Expense amount in cash account – 5 Nov 2020
                // Only post expense portion; cash payments are already posted individually above
                $account_id = $this->transactionUtil->account_exist_return_id('Cash');

                $expense_transaction_data = [
                    'amount' => $settlement->expense_payments->sum('amount'),
                    'account_id' => $account_id,
                    'contact_id' => $sell_transaction->contact_id,
                    'type' => 'debit',
                    'sub_type' => null,
                    'operation_date' => date('Y-m-d H:i:s'),
                    'created_by' => $sell_transaction->created_by,
                    'transaction_id' => $sell_transaction->id,
                    'transaction_payment_id' => ! empty($cash_transaction_payment)
                        ? $cash_transaction_payment->id
                        : null,
                    'note' => $cash_note,
                ];

                AccountTransaction::createAccountTransaction($expense_transaction_data);
            }

            foreach ($settlement->customer_payments as $customer_payments) {
                $account_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');

                $ob_transaction_data = [
                    'amount' => $customer_payments->amount,
                    'post_dated_cheque' => $customer_payments->post_dated_cheque,
                    'account_id' => $account_id,
                    'type' => 'credit', // changed from debit to credit
                    'sub_type' => 'deposit',
                    'operation_date' => date('Y-m-d H:i:s'),
                    'created_by' => auth()->user()->id,
                    'transaction_id' => $sell_transaction->id,
                    'transaction_payment_id' => null,
                ];

                AccountTransaction::createAccountTransaction($ob_transaction_data);
            }

            // This is only to show in print page customer payments which entered in customer payments tab
            $customer_payments_tab = CustomerPayment::leftJoin(
                'contacts',
                'customer_payments.customer_id',
                'contacts.id'
            )
                ->where('customer_payments.settlement_no', $settlement->id)
                ->where('customer_payments.business_id', $business_id)
                ->select(
                    'customer_payments.*',
                    'contacts.name as customer_name'
                )
                ->get();

            $settlement_total =
                $settlement->meter_sales->sum('sub_total') +
                $settlement->other_sales->sum('sub_total') +
                $settlement->other_incomes->sum('sub_total') +
                $settlement->customer_payments->sum('sub_total') +
                $pump_operator_total_other_sale;

            // Direct Settlement business rule:
            // - Credit Sales are SALES (add to Total Amount)
            // - Customer payments are PAYMENTS (do not add to Total Amount)
            $credit_sales_net_for_total_amount = $settlement->credit_sale_payments->sum('amount') - $settlement->credit_sale_payments->sum('total_discount');
            // Modified by Engr. Alex -- task 7889: use post-discount amounts so
            // settlements.total_amount and P&L reflect the amount after discount
            $settlement_total_for_total_amount =
                $settlement->meter_sales->sum('discount_amount') +
                ($settlement->other_sales->sum('sub_total') - $settlement->other_sales->sum('discount_amount')) +
                $settlement->other_incomes->sum('sub_total') +
                $pump_operator_total_other_sale +
                $credit_sales_net_for_total_amount;

            $settlement->total_amount = $settlement_total_for_total_amount;
            $settlement->status = 0; // set status to non-active
            $settlement->is_edit = 0;

            $settlement->cash_denomination = ($denom_enabled > 0) ? json_encode($denom_data) : null;
            $settlement->finish_date = date('Y-m-d');
            $settlement->save();

            // Get Daily Collection from business_id and pump_operator_id where settlement_id is null
            $shift_ids_for_collections = $shift_ids;
            if (empty($shift_ids_for_collections) && !empty($settlement->work_shift)) {
                $decoded_work_shift = is_array($settlement->work_shift)
                    ? $settlement->work_shift
                    : json_decode($settlement->work_shift, true);
                $shift_ids_for_collections = is_array($decoded_work_shift)
                    ? $decoded_work_shift
                    : explode(",", $settlement->work_shift);
            }
            $shift_ids_for_collections = array_filter(array_map('intval', (array) $shift_ids_for_collections));

            if (empty($shift_ids_for_collections)) {
                \Log::warning('Settlement: Skipping daily collection updates due to missing shift_ids', [
                    'settlement_id' => $settlement->id,
                    'settlement_no' => $settlement->settlement_no,
                    'pump_operator_id' => $settlement->pump_operator_id,
                ]);
                $daily_collections = collect();
            } else {
                $daily_collections = DailyCollection::leftJoin(
                    'business_locations',
                    'daily_collections.location_id',
                    'business_locations.id'
                )
                    ->leftJoin(
                        'pump_operators',
                        'daily_collections.pump_operator_id',
                        'pump_operators.id'
                    )
                    ->leftJoin(
                        'pump_operator_assignments',
                        'pump_operator_assignments.pump_operator_id',
                        'pump_operators.id'
                    )
                    ->leftJoin('users', 'daily_collections.created_by', 'users.id')
                    ->leftJoin(
                        'settlements',
                        'daily_collections.settlement_id',
                        'settlements.id'
                    )
                    ->where('daily_collections.business_id', $business_id)
                    ->where('daily_collections.pump_operator_id', $settlement->pump_operator_id)
                    ->where('daily_collections.type', 'daily_collection')
                    ->whereNull('daily_collections.settlement_id')
                    ->whereNull('daily_collections.added_to_account')
                    ->whereIn('daily_collections.shift_id', $shift_ids_for_collections)
                    ->select([
                        'daily_collections.*',
                        'business_locations.name as location_name',
                        'pump_operators.name as pump_operator_name',
                        'settlements.id as settlements_id',
                        'users.username as user',
                    ])
                    ->orderBy('daily_collections.id')
                    ->get();
            }

            $outstanding_payment = $settlement_total;

            foreach ($daily_collections as $daily_collections) {
                if ($outstanding_payment >= 0) {
                    $outstanding_payment -= floatval($daily_collections->current_amount);

                    DB::update(
                        'update daily_collections set settlement_id = ?, settlement_date = ?, balance_collection = ? where business_id = ? and pump_operator_id = ? and id = ? and settlement_id is null',
                        [
                            $settlement->id,
                            $settlement->finish_date,
                            floatval($daily_collections->current_amount),
                            $business_id,
                            $settlement->pump_operator_id,
                            $daily_collections->id,
                        ]
                    );
                    // echo var_dump($outstanding_payment . "/nr");
                }
            }

            // create VAT entries
            $this->transactionUtil->calculateAndUpdateVAT($sell_transaction);

            PumperDayEntry::where('settlement_no', $settlement_no)
                ->update([
                    'settlement_no' => $request->settlement_no,
                    'settlement_added_by' => auth()->user()->id,
                    'closed_in_settlement' => 1,
                ]);

            // Update PumpOperatorAssignment - use pump_operator_id for reliable closure
            $shift_ids = is_array($request->shift_ids)
                ? $request->shift_ids
                : (! empty($request->shift_ids) ? explode(',', $request->shift_ids) : []);
            $shift_ids = array_values(array_filter(array_map('intval', (array) $shift_ids), function ($shift_id) {
                return $shift_id > 0;
            }));

            if (! empty($shift_ids)) {
                // Traditional settlement: close assignments for only the selected shifts
                PumpOperatorAssignment::where(function ($query) use ($settlement) {
                    $query->whereNull('settlement_id')
                        ->orWhere('settlement_id', $settlement->id);
                })
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->whereIn('shift_id', $shift_ids)
                    ->update([
                        'settlement_id' => $settlement->id,
                        'closed_in_settlement' => 1,
                        'status' => 'close',
                        'close_date_and_time' => \Carbon::now(),
                    ]);
            } else {
                // Direct settlement can use a generated label (option value 0), so close
                // the operator's open assignments for pumps actually included here.
                $settlement_pump_ids = $settlement->meter_sales()
                    ->pluck('pump_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $assignment_query = PumpOperatorAssignment::where(function ($query) use ($settlement) {
                    $query->whereNull('settlement_id')
                        ->orWhere('settlement_id', $settlement->id);
                })
                    ->where('pump_operator_id', $settlement->pump_operator_id);

                if (! empty($settlement_pump_ids)) {
                    $assignment_query->whereIn('pump_id', $settlement_pump_ids);
                }

                $assignment_query->update([
                    'settlement_id' => $settlement->id,
                    'closed_in_settlement' => 1,
                    'status' => 'close',
                    'close_date_and_time' => \Carbon::now(),
                ]);
            }

            DB::commit();

            $sms_data = [
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'settlement_date' => $this->transactionUtil->format_date($settlement->transaction_date),
                'pump_operator_name' => $pump_operator->name,
                'settlement_pumps' => implode(',', $pumps),
                'total_sale_amount' => $this->transactionUtil->num_f($total_sales_amount),
                'total_cash' => $this->transactionUtil->num_f($settlement->cash_payments->sum('amount')),
                'total_cards' => $this->transactionUtil->num_f($settlement->card_payments->sum('amount')),
                'total_credit_sales' => $this->transactionUtil->num_f($settlement->credit_sale_payments->sum('amount')),
                'total_short' => $this->transactionUtil->num_f($settlement->shortage_payments->sum('amount')),
                'total_loans' => $this->transactionUtil->num_f($settlement->customer_loans->sum('amount')),
                'total_cheques' => $this->transactionUtil->num_f($settlement->cheque_payments->sum('amount')),
                'cash_deposit' => $this->transactionUtil->num_f($settlement->cash_deposits->sum('amount')),
                'total_expenses' => $this->transactionUtil->num_f($settlement->expense_payments->sum('amount')),
                'total_excess' => $this->transactionUtil->num_f($settlement->excess_payments->sum('amount')),
                'loan_payments' => $this->transactionUtil->num_f($settlement->loan_payments->sum('amount')),
                'owners_drawings' => $this->transactionUtil->num_f($settlement->drawings_payments->sum('amount')),
                'mechanical_meter_difference' => $this->getMechanicalMeterDifferenceSummary($settlement),
                'editted_by' => auth()->user()->username,
            ];

            if (! empty($edit)) {
                $original_details = SettlementEditHistory::where('settlement_id', $settlement->id)->first();
                $o_details = '';
                $n_details = '';
                $is_changed = false;
                $changed_msg = '';

                if (! empty($original_details)) {
                    // Check each field for changes and append to message if changed
                    $fields_to_check = [
                        'settlement_date',
                        'pump_operator_name',
                        'settlement_pumps',
                        'total_sale_amount',
                        'total_cash',
                        'total_cards',
                        'total_credit_sales',
                        'total_short',
                        'total_loans',
                        'total_cheques',
                    ];

                    foreach ($fields_to_check as $field) {
                        if ($sms_data[$field] != $original_details->$field) {
                            $is_changed = true;
                            $changed_msg .= __("petrogeneral::lang.$field").
                                __('petrogeneral::lang.changed_from').
                                $original_details->$field.
                                __('petrogeneral::lang.to').
                                $sms_data[$field].PHP_EOL;

                            $o_details .= __("petrogeneral::lang.$field").': '.$original_details->$field.PHP_EOL;
                            $n_details .= __("petrogeneral::lang.$field").': '.$sms_data[$field].PHP_EOL;
                        }
                    }

                    if (! empty($is_changed) && ! empty($changed_msg)) {
                        $activity = new Activity;
                        $activity->log_name = 'Settlement';
                        $activity->description = 'update';
                        $activity->subject_id = $settlement->id;
                        $activity->subject_type = "App\Settlement";
                        $activity->causer_id = auth()->user()->id;
                        $activity->causer_type = "App\User";
                        $activity->properties = $changed_msg;
                        $activity->created_at = date('Y-m-d H:i');
                        $activity->updated_at = date('Y-m-d H:i');
                        $activity->save();
                    }
                }

                $data = [
                    'settlement_no' => $settlement->settlement_no,
                    'editted_date' => $this->transactionUtil->format_date(date('Y-m-d')),
                    'user_editted' => auth()->user()->username,
                    'original_details' => $o_details,
                    'editted_details' => $n_details,
                ];

                $this->notificationUtil->sendPetroNotification(
                    'edit_settlements',
                    $data
                );
            } else {
                $this->notificationUtil->sendPetroNotification(
                    'settlements',
                    $sms_data
                );
            }

            SettlementEditHistory::updateOrCreate(
                ['settlement_id' => $settlement->id],
                $sms_data
            );

            $total_daily_collection = floatval(
                DailyCollection::where('pump_operator_id', $settlement->pump_operator_id)
                    ->where('business_id', $business_id)
                    ->where('settlement_id', $settlement->id)
                    ->where('type', 'daily_collection')
                    ->sum('current_amount')
            );

            $msg_template_wahtsapp = PetroWhatsAppTemplate::where('business_id', $business_id)
                ->where('auto_send_sms', 1)
                ->first();

            $business_locations = BusinessLocation::where('business_id', $business_id)
                ->pluck('name')
                ->first();

            $business_details = Business::find($business_id);

            $subscription = Subscription::active_subscription($business_id);

            $whatsapp_phone_no = 0;
            if (! empty($subscription)) {
                $pacakge_details = $subscription->package_details;
                $whatsapp_phone_no = $pacakge_details['whatsapp_phone_no'];
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
                \Log::info("WhatsApp URL: $whatsapp_url");

                return redirect()->away($whatsapp_url);
            }
            */

            // $shift_ids = $request->shift_ids;
            $print_pump_operator_other_sales = $this->getPrintPumpOperatorOtherSales($settlement, (int) $business_id, $shift_ids);

            // Return JSON response for AJAX requests, view for regular requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    "success" => 1,
                    "msg" => __("petrogeneral::lang.settlement_saved_successfully") ?: "Settlement saved successfully",
                    "settlement_id" => $settlement->id,
                    "settlement_no" => $settlement->settlement_no,
                    "html" => view('petrogeneral::settlement.print')->with(
                        compact(
                            'settlement',
                            'business',
                            'pump_operator',
                            'customer_payments_tab',
                            'total_daily_collection',
                            'shift_ids',
                            'print_pump_operator_other_sales'
                        )
                    )->render()
                ]);
            }

            return view('petrogeneral::settlement.print')->with(
                compact(
                    'settlement',
                    'business',
                    'pump_operator',
                    'customer_payments_tab',
                    'total_daily_collection',
                    'shift_ids',
                    'print_pump_operator_other_sales'
                )
            );

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::emergency(
                'File: '.$e->getFile().
                ' Line: '.$e->getLine().
                ' Message: '.$e->getMessage()
            );

            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];
            
            // Return JSON for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($output);
            }
        }

        return $output;
    }

    public function createSettlementIfNotExist(Request $request)
    {

        $business_id = $request->session()->get('business.id');
        $pump_operator_id = !empty($request->pump_operator_id)
            ? $request->pump_operator_id
            : (!empty($request->operator_id) ? $request->operator_id : null);
        $active_settlement_id = (int) $request->input('active_settlement_id', 0);
        $settlement_no = trim((string) ($request->settlement_no ?? ''));
        $transaction_date = \Carbon::parse($request->transaction_date)->format('Y-m-d');
        $shift_ids = $this->extractRequestedSettlementShiftIds($request);

        // Identify if Petro PD request
        $isPetroPdRequest = false;
        $source = (string) $request->input('source', '');
        if (in_array($source, ['petro_pd', 'petropd'], true)) {
            $isPetroPdRequest = true;
        } else {
            $refererPath = parse_url((string) $request->headers->get('referer'), PHP_URL_PATH);
            if (is_string($refererPath) && \Illuminate\Support\Str::startsWith($refererPath, '/petropd')) {
                $isPetroPdRequest = true;
            }
        }

        // Identify Petro PD prefixes
        $petro_pd_prefixes = ['PDST'];
        $business = \App\Business::find($business_id);
        $ref_no_prefixes = $business->ref_no_prefixes ?? [];
        if (is_string($ref_no_prefixes)) {
            $ref_no_prefixes = json_decode($ref_no_prefixes, true) ?? [];
        }
        $pdPrefix = $ref_no_prefixes['settlement_pd'] ?? null;
        $petroPrefix = $ref_no_prefixes['settlement'] ?? 'ST';
        if (! empty($pdPrefix) && $pdPrefix !== $petroPrefix) {
            $petro_pd_prefixes[] = $pdPrefix;
        }
        $petro_pd_prefixes = array_values(array_unique(array_filter($petro_pd_prefixes)));

        // Validate prefix
        $is_valid_pd_settlement_no = false;
        if (! empty($settlement_no)) {
            foreach ($petro_pd_prefixes as $pref) {
                if (\Illuminate\Support\Str::startsWith($settlement_no, $pref)) {
                    $is_valid_pd_settlement_no = true;
                    break;
                }
            }
        }

        if ($isPetroPdRequest && ! $is_valid_pd_settlement_no) {
            $settlement_no = '';
            $request->merge(['settlement_no' => '']);
        }

        // Permanent rule: only one pending Direct Settlement (ST...) is allowed.
        // If a pending ST settlement exists, reuse it instead of creating ST+1.
        if (! $isPetroPdRequest && empty($active_settlement_id) && empty($settlement_no)) {
            $pending_direct_settlement = Settlement::where('business_id', $business_id)
                ->where('status', 1)
                ->where('settlement_no', 'LIKE', $this->getDirectSettlementPrefix($business_id) . '%')
                ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                ->where('settlement_no', 'NOT LIKE', 'PDST%')
                ->orderByDesc('id')
                ->first();

            if (! empty($pending_direct_settlement)) {
                $request->merge(['settlement_no' => $pending_direct_settlement->settlement_no]);
                return $pending_direct_settlement;
            }
        }

        if (! empty($request->input('is_edit')) && ! empty($settlement_no)) {
            $finalized_edit_settlement = Settlement::where('settlement_no', $settlement_no)
                ->where('business_id', $business_id)
                ->where('status', 0)
                ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                ->where('settlement_no', 'NOT LIKE', 'PDST%')
                ->first();

            if (! empty($finalized_edit_settlement)) {
                $request->merge(['settlement_no' => $finalized_edit_settlement->settlement_no]);
                return $finalized_edit_settlement;
            }
        }

        if ($active_settlement_id > 0 && ! empty($pump_operator_id)) {
            // Match the active settlement regardless of status so editing a
            // finalized settlement (status=0) reuses it instead of spawning a
            // new "Pending" draft each time a sub-item is saved.
            $active_settlement = Settlement::where('id', $active_settlement_id)
                ->where('business_id', $business_id)
                ->first();

            if (! empty($active_settlement)) {
                $activeSettlementIsDirectDraft = ! empty($this->normalizeDirectSettlementShiftLabel(
                    $active_settlement->work_shift,
                    (int) $business_id
                ));

                $selectedOperatorHasAssignedShifts = PumpOperatorAssignment::where('pump_operator_assignments.business_id', $business_id)
                    ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
                    ->where(function ($query) {
                        $query->where(function ($open_query) {
                            $open_query->where('pump_operator_assignments.status', 'open')
                                ->whereNull('pump_operator_assignments.close_date_and_time');
                        })
                            ->orWhere('pump_operator_assignments.status', 'close');
                    })
                    ->where(function ($query) {
                        $query->where('pump_operator_assignments.closed_in_settlement', 0)
                            ->orWhereNull('pump_operator_assignments.closed_in_settlement');
                    })
                    ->exists();

                if (
                    (int) $active_settlement->pump_operator_id === (int) $pump_operator_id
                    || ($activeSettlementIsDirectDraft && ! $selectedOperatorHasAssignedShifts)
                ) {
                    $active_is_pd = false;
                    foreach ($petro_pd_prefixes as $pref) {
                        if (\Illuminate\Support\Str::startsWith($active_settlement->settlement_no, $pref)) {
                            $active_is_pd = true;
                            break;
                        }
                    }
                    if ($isPetroPdRequest === $active_is_pd) {
                        if ((int) $active_settlement->pump_operator_id !== (int) $pump_operator_id) {
                            $active_settlement->pump_operator_id = $pump_operator_id;
                            $active_settlement->location_id = $request->location_id;
                            $active_settlement->transaction_date = $transaction_date;
                            $active_settlement->note = $request->note;
                            $active_settlement->save();
                        }

                        $request->merge(['settlement_no' => $active_settlement->settlement_no]);
                        return $active_settlement;
                    }
                }
            }
        }

        if (empty($settlement_no)) {
            $existing_draft_query = Settlement::where('business_id', $business_id)
                ->when(!empty($pump_operator_id), function ($query) use ($pump_operator_id) {
                    $query->where('pump_operator_id', $pump_operator_id);
                })
                ->when(!empty($request->location_id), function ($query) use ($request) {
                    $query->where('location_id', $request->location_id);
                })
                ->where('status', 1)
                ->whereDate('transaction_date', $transaction_date);

            if ($isPetroPdRequest) {
                $existing_draft_query->where(function ($q) use ($petro_pd_prefixes) {
                    foreach ($petro_pd_prefixes as $pref) {
                        $q->orWhere('settlement_no', 'LIKE', $pref . '%');
                    }
                });
            } else {
                $existing_draft_query->where('settlement_no', 'NOT LIKE', 'SET-SW%');
                foreach ($petro_pd_prefixes as $pref) {
                    $existing_draft_query->where('settlement_no', 'NOT LIKE', $pref . '%');
                }
            }

            $existing_draft = $existing_draft_query->orderByDesc('id')->first();

            if (
                !empty($existing_draft) &&
                !empty($existing_draft->settlement_no) &&
                $this->draftSettlementMatchesRequestedShifts($existing_draft, $shift_ids)
            ) {
                $settlement_no = $existing_draft->settlement_no;
            } else {
                $prefix = $isPetroPdRequest
                    ? ($petro_pd_prefixes[0] ?? 'PDST')
                    : (!empty($ref_no_prefixes['settlement']) ? $ref_no_prefixes['settlement'] : 'ST');

                $count_query = Settlement::where('business_id', $business_id);

                if ($isPetroPdRequest) {
                    $count_query->where(function ($q) use ($petro_pd_prefixes) {
                        foreach ($petro_pd_prefixes as $pref) {
                            $q->orWhere('settlement_no', 'LIKE', $pref . '%');
                        }
                    });
                } else {
                    $count_query->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                        ->where('settlement_no', 'NOT LIKE', 'PDST%');
                    foreach ($petro_pd_prefixes as $pref) {
                        $count_query->where('settlement_no', 'NOT LIKE', $pref . '%');
                    }
                }

                $count = $count_query->orderBy('id', 'DESC')->first();

                $count = !empty($count) ? (int) $this->extractLastInteger($count->settlement_no) : 0;
                $settlement_no = $prefix . (1 + $count);
            }

            $request->merge(['settlement_no' => $settlement_no]);
        }

        $direct_shift_number = null;
        if (! empty($request->direct_shift_number)) {
            $requested_direct_shift = $this->normalizeDirectSettlementShiftLabel(
                $request->direct_shift_number,
                (int) $business_id
            );
            $direct_shift_prefix = $this->getDirectSettlementShiftPrefix((int) $business_id);

            if (! empty($requested_direct_shift) && str_starts_with($requested_direct_shift, $direct_shift_prefix)) {
                $already_finalized = Settlement::where('business_id', $business_id)
                    ->where('status', 0)
                    ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                    ->where('settlement_no', 'NOT LIKE', 'PDST%')
                    ->where('work_shift', 'LIKE', '%' . $requested_direct_shift . '%')
                    ->exists();

                $direct_shift_number = $already_finalized
                    ? $this->getNextDirectSettlementShiftLabel((int) $business_id)
                    : $requested_direct_shift;
            }
        }

        $settlement_data = [

            'settlement_no' => $settlement_no,

            'business_id' => $business_id,

            'transaction_date' => $transaction_date,

            'location_id' => $request->location_id,

            'pump_operator_id' => $pump_operator_id,

            'work_shift' => ! empty($direct_shift_number)
                ? [$direct_shift_number]
                : (! empty($request->work_shift)
                    ? $request->work_shift
                    : []),

            'note' => $request->note,

            'status' => 1,

        ];

        $latest_date =

            DayEnd::where('business_id', $business_id)

                ->get()

                ->last()->day_end_date ?? null;

        if (

            ! empty($latest_date) &&

            strtotime($latest_date) >=

            strtotime($settlement_data['transaction_date'])

        ) {

            return 406;

        }

        // Reuse active drafts only. A stale browser tab can still submit a settlement number
        // that was already finalized, and new rows must not be attached to that settlement.
        $settlement_exist = Settlement::where(

            'settlement_no',

            $settlement_no

        )

            ->where('business_id', $business_id)
            ->where('status', 1)
            ->when(!empty($pump_operator_id), function ($query) use ($pump_operator_id) {
                $query->where('pump_operator_id', $pump_operator_id);
            })
            ->first();

        if (empty($settlement_exist)) {
            $finalized_settlement_exists = Settlement::where('settlement_no', $settlement_no)
                ->where('business_id', $business_id)
                ->where('status', 0)
                ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                ->where('settlement_no', 'NOT LIKE', 'PDST%')
                ->exists();

            if ($finalized_settlement_exists) {
                $business = Business::find($business_id);
                $ref_no_prefixes = $business->ref_no_prefixes ?? [];
                $prefix = $this->getDirectSettlementPrefix($business_id);

                $latest_settlement = Settlement::where('business_id', $business_id)
                    ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                    ->where('settlement_no', 'NOT LIKE', 'PDST%')
                    ->orderBy('id', 'DESC')
                    ->first();

                $settlement_no = $prefix . (1 + (! empty($latest_settlement) ? (int) $this->extractLastInteger($latest_settlement->settlement_no) : 0));
                $settlement_data['settlement_no'] = $settlement_no;
                $request->merge(['settlement_no' => $settlement_no]);
            }
        }

        if (empty($settlement_exist)) {

            // Create new settlement if none exists for this operator
            // This ensures each operator gets their own settlement, preventing ST7 from being reused
            $settlement_exist = Settlement::create($settlement_data);
            
            // Only link daily collections for the specific shift(s) being settled
            if (!empty($shift_ids)) {
                DailyCollection::where('business_id', $business_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->whereNull('settlement_id')
                    ->whereIn('shift_id', $shift_ids)
                    ->update(['settlement_id' => $settlement_exist->id]);
            }

            $daily_cards_query = DailyCard::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->whereNull('settlement_no');
            if (!empty($shift_ids) && Schema::hasColumn('daily_cards', 'shift_id')) {
                $daily_cards_query->whereIn('shift_id', $shift_ids);
            }
            $daily_cards_query->update(['settlement_no' => $settlement_exist->settlement_no]);

            $daily_vouchers_query = DailyVoucher::where('business_id', $business_id)
                ->where('operator_id', $pump_operator_id)
                ->whereNull('settlement_no');
            if (!empty($shift_ids) && Schema::hasColumn('daily_vouchers', 'shift_id')) {
                $daily_vouchers_query->whereIn('shift_id', $shift_ids);
            }
            $daily_vouchers_query->update(['settlement_no' => $settlement_exist->settlement_no]);
        }

        return $settlement_exist;

    }
}

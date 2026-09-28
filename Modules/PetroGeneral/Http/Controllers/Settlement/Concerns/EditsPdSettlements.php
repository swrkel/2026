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
 * Editing, updating and deleting a settlement.
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
 * Methods here: edit, update, canEditSettlement, destroy, deletePreviouseTransactions, updateCreditSales, adjustDiscounts, adjustMeterSalesDates
 */
trait EditsPdSettlements
{
    public function edit($id)
    {

        $business_id = request()

            ->session()

            ->get('business.id');

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

        $customers = Contact::customersDropdown(

            $business_id,

            false,

            true,

            'customer'

        );

        $pump_operators = PumpOperator::where(

            'business_id',

            $business_id

        )->pluck('name', 'id');

        $pump_nos = Pump::where('business_id', $business_id)->pluck(

            'pump_name',

            'id'

        );

        $business_details = \App\Business::find($business_id);

        $currency_precision = ! empty($business_details->currency_precision)

            ? $business_details->currency_precision

            : 2;

        $meeter_precision = 3;

        $items = [];

        // \DB::enableQueryLog(); // Start logging queries

        $active_settlement = Settlement::where('id', $id)

            ->select('settlements.*')

            ->with([

                'meter_sales',

                'other_sales',

                'other_incomes',

                'customer_payments',

                'cash_payments',

                'card_payments',

                'credit_sale_payments',

            ])

            ->first();

        $settlement_credit_sale_payments = SettlementCreditSalePayment::leftJoin(
            'contacts',
            'settlement_credit_sale_payments.customer_id',
            '=',
            'contacts.id'
        )
            ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
            ->where('settlement_credit_sale_payments.settlement_no', $active_settlement->id)
            ->select(
                'settlement_credit_sale_payments.*',
                'contacts.name as customer_name',
                'products.name as product_name'
            )
            ->get();

        // dd(\DB::getQueryLog()); // Dump the logged queries

        $settlement_no = $active_settlement->settlement_no;

        // $shift_number = PumpOperatorAssignment::where('shift_id', $active_settlement->id)->first();

        $shift_number = PumpOperatorAssignment::where('settlement_id', $active_settlement->id)
            ->where('pump_operator_id', $active_settlement->pump_operator_id)
            ->groupBy('shift_number')
            ->select('shift_number', 'shift_id')
            ->get()
            ->toArray();

        $other_sale_final_total = 0;

        $pump_other_sale_final_total = 0;

        $userOtherDetails = [];

        // dd($active_settlement->other_sales);

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

                'price' => number_format($ot_item->price, $currency_precision),

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

            ->join('pump_operator_assignments', function ($join) {

                $join

                    ->on(

                        'pump_operator_assignments.shift_id',

                        '=',

                        'pump_operator_other_sales.shift_id'

                    )

                    ->where(

                        'pump_operator_assignments.status',

                        'close'

                    )->whereRaw('pump_operator_assignments.id = (

                         SELECT MAX(poa.id)

                         FROM pump_operator_assignments poa

                         WHERE poa.shift_id = pump_operator_other_sales.shift_id AND poa.status = "close"

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

        // dd($userOtherDetails);

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

                'price' => number_format($pumpSale->price, $currency_precision),

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

        // dd($combinedOtherSales);

        // ✅ Final Total of both

        $final_other_sale_total =

            $other_sale_final_total + $pump_other_sale_final_total;

        $has_reviewed = $this->transactionUtil->hasReviewed(

            $active_settlement->transaction_date

        );

        if (! empty($has_reviewed)) {

            $output = [

                'success' => 0,

                'msg' => __('lang_v1.review_first'),

            ];

            return redirect()

                ->back()

                ->with(['status' => $output]);

        }

        $reviewed = $this->transactionUtil->get_review(

            $active_settlement->transaction_date,

            $active_settlement->transaction_date

        );

        if (! empty($reviewed)) {

            $output = [

                'success' => 0,

                'msg' => "You can't edit a settlement for an already reviewed date",

            ];

            return redirect()

                ->back()

                ->with(['status' => $output]);

        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        if (! empty($active_settlement)) {

            $already_pumps = MeterSale::where(

                'settlement_no',

                $active_settlement->id

            )

                ->pluck('pump_id')

                ->toArray();

            if ($active_settlement->meter_sales->count()) {

                $already_pumps = array_diff($already_pumps, [

                    $active_settlement->meter_sales->toArray()[0]['pump_id'],

                ]);

                $already_pumps = array_values($already_pumps);

            }

            $pump_nos = Pump::where('business_id', $business_id)

                ->whereNotIn('id', $already_pumps)

                ->pluck('pump_name', 'id');

        } else {

            $pump_nos = Pump::where('business_id', $business_id)->pluck(

                'pump_name',

                'id'

            );

        }

        // other_sale tab

        $stores = Store::forDropdown($business_id, 0, 1, 'sell');

        $fuel_category_id = Category::where('business_id', $business_id)

            ->where('name', 'Fuel')

            ->first();

        $fuel_category_id = ! empty($fuel_category_id)

            ? $fuel_category_id->id

            : null;

        // $items = Product::where('category_id', '!=', $fuel_category_id)->where('business_id', $business_id)->pluck('name', 'id');

        $items = $this->transactionUtil->getProductDropDownArray(

            $business_id,

            $fuel_category_id,

            'petro_settlements'

        );

        $payment_meter_sale_total = ! empty($active_settlement->meter_sales)

            ? $active_settlement->meter_sales->sum('discount_amount')

            : 0.0;

        $payment_other_sale_total = ! empty($active_settlement->other_sales)

            ? $active_settlement->other_sales->sum('sub_total')

            : 0.0;

        $payment_other_income_total = ! empty($active_settlement->other_incomes)

            ? $active_settlement->other_incomes->sum('sub_total')

            : 0.0;

        $payment_customer_payment_total = ! empty(

            $active_settlement->customer_payments

        )

            ? $active_settlement->customer_payments->sum('sub_total')

            : 0.0;

        $payment_other_sale_discount = ! empty($active_settlement->other_sales)

            ? $active_settlement->other_sales->sum('sub_total')

            : 0.0;

        $payment_other_sale_total -= $payment_other_sale_discount;

        $wrok_shifts = WorkShift::where('business_id', $business_id)->pluck(

            'shift_name',

            'id'

        );

        $bulk_tanks = FuelTank::where('business_id', $business_id)

            ->where('bulk_tank', 1)

            ->pluck('fuel_tank_number', 'id');

        $services = Product::where('business_id', $business_id)

            ->forModule('petro_settlements')

            ->where('enable_stock', 0)

            ->pluck('name', 'id');

        $discount_types = ['fixed' => 'Fixed', 'percentage' => 'Percentage'];

        $can_edit_details = $this->canEditSettlement($id);

        return view('petrogeneral::settlement.edit')->with(

            compact(

                'business_locations',

                'payment_types',

                'services',

                'customers',

                'pump_operators',

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

                'discount_types',

                'can_edit_details',

                'shift_number',

                'combinedOtherSales',

                'other_sale_final_total',

                'pump_other_sale_final_total',
                'settlement_credit_sale_payments'

            )

        );

    }

    public function update(Request $request, $id)
    {

        try {

            $input = $request->except('_token', '_method', 'direct_shift_number', 'direct_shift_operator_id');
            $settlement = null;
            $business_id = request()
                ->session()
                ->get('business.id');
            $requestedTransactionDate = \Carbon::parse($request->transaction_date)->format('Y-m-d');

            if ($id != '0') {

                $settlement = Settlement::find($id);

                if (
                    ! empty($settlement)
                    && (int) $settlement->pump_operator_id !== (int) $request->pump_operator_id
                ) {
                    $currentSettlementIsDirectDraft = ! empty($this->normalizeDirectSettlementShiftLabel($settlement->work_shift, (int) $business_id));

                    $selectedOperatorHasAssignedShifts = PumpOperatorAssignment::where('pump_operator_assignments.pump_operator_id', $request->pump_operator_id)
                        ->where('pump_operator_assignments.business_id', $business_id)
                        ->leftJoin('petro_shifts as ps', 'pump_operator_assignments.shift_id', '=', 'ps.id')
                        ->leftJoin('settlements', 'pump_operator_assignments.settlement_id', '=', 'settlements.id')
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
                        });

                    $this->excludePetroPdModuleAssignments($selectedOperatorHasAssignedShifts, $business_id);

                    $selectedOperatorHasAssignedShifts = $selectedOperatorHasAssignedShifts->exists();

                    if (! $currentSettlementIsDirectDraft || $selectedOperatorHasAssignedShifts) {
                        $id = '0';
                        $settlement = null;
                    }
                }
            }

            if ($id != '0') {

                $currentWorkShift = $settlement->work_shift;

                $currentTransactionDate = $settlement->transaction_date;

                $note = $settlement->note;

                $operator = $settlement->pump_operator_id;

                $location = $settlement->location_id;

                $requestedDirectShiftLabel = $this->normalizeDirectSettlementShiftLabel(
                    $request->input('direct_shift_number', ''),
                    (int) $business_id
                );
                $existingDirectShiftLabel = $this->normalizeDirectSettlementShiftLabel(
                    $currentWorkShift,
                    (int) $business_id
                );

                if (! empty($requestedDirectShiftLabel)) {
                    $input['work_shift'] = json_encode([$requestedDirectShiftLabel]);
                } elseif (! empty($request->work_shift)) {
                    $input['work_shift'] = json_encode($request->work_shift);
                } elseif (! empty($existingDirectShiftLabel)) {
                    $input['work_shift'] = json_encode([$existingDirectShiftLabel]);
                } else {
                    $input['work_shift'] = json_encode([]);
                }

                $input['transaction_date'] = \Carbon::parse(

                    $request->transaction_date

                )->format('Y-m-d');

                $latest_date =

                    DayEnd::where('business_id', $business_id)

                        ->get()

                        ->last()->day_end_date ?? null;

                if (

                    ! empty($latest_date) &&

                    strtotime($latest_date) >=

                    strtotime($input['transaction_date'])

                ) {

                    return [

                        'success' => false,

                        'msg' => __('petrogeneral::lang.date_greater_than_day_end'),

                    ];

                }

                $input['note'] = $request->note;

                $input['pump_operator_id'] = $request->pump_operator_id;

                Settlement::where('id', $id)->update($input);

                $settlementAfterUpdate = Settlement::find($id);
                if ($settlementAfterUpdate) {
                    $this->attachUnsettledRteMeterSalesToSettlement($settlementAfterUpdate);
                }

                $changedFields = '';

                // Comment for rajib Dev

                //  if ($input["work_shift"] !== $currentWorkShift) {

                //      $changedFields = "Update Work shift";

                //  }

                if ($input['transaction_date'] !== $currentTransactionDate) {

                    $changedFields = 'Update Transaction Date';

                    // Update all related transactions and account_transactions when settlement date changes
                    try {
                        $this->updateSettlementRelatedTransactions($settlement, $input['transaction_date']);
                    } catch (\Exception $e) {
                        Log::error('Failed to update related transactions for settlement', [
                            'settlement_id' => $settlement->id,
                            'error' => $e->getMessage()
                        ]);
                    }

                }

                if ($input['note'] !== $note) {

                    $changedFields = 'Update Note';

                }

                if ($request->pump_operator_id != $operator) {

                    $changedFields = 'Update Pump Operator';

                }

                if ($request->location_id != $location) {

                    $changedFields = 'Update Location';

                }

            } else {

                $changedFields = 'Update Shift No.';

            }

            $assigned_pumps = PumpOperatorAssignment::where('pump_operator_assignments.pump_operator_id', $request->pump_operator_id)
                ->leftJoin(
                    'settlements',
                    'pump_operator_assignments.settlement_id',
                    '=',
                    'settlements.id'
                )
                ->where(function ($query) {
                    $query->where('pump_operator_assignments.closed_in_settlement', 0)
                        ->orWhereNull('pump_operator_assignments.closed_in_settlement');
                })
                ->where(function ($query) {
                    $query->where(function ($open_query) {
                        $open_query->where('pump_operator_assignments.status', 'open')
                            ->whereNull('pump_operator_assignments.close_date_and_time');
                    })
                        ->orWhere('pump_operator_assignments.status', 'close');
                });

            $this->excludePetroPdModuleAssignments($assigned_pumps, $business_id);

            $assigned_pumps = $assigned_pumps
                ->select('pump_operator_assignments.*')
                ->groupBy('pump_operator_assignments.shift_number')
                ->orderByRaw("CASE WHEN pump_operator_assignments.status = 'close' THEN 0 ELSE 1 END")
                ->orderBy('pump_operator_assignments.id')
                ->get();

            $directShiftPrefixForLock = $this->getDirectSettlementShiftPrefix((int) $business_id);
            $lockedDirectShiftLabel = null;
            if (! empty($settlement) && (int) $settlement->status === 1) {
                $requestedDirectShift = $this->normalizeDirectSettlementShiftLabel($request->input('direct_shift_number', ''), (int) $business_id);
                if (! empty($requestedDirectShift)) {
                    $lockedDirectShiftLabel = $requestedDirectShift;
                } elseif (! empty($settlement->work_shift)) {
                    $lockedDirectShiftLabel = $this->normalizeDirectSettlementShiftLabel($settlement->work_shift, (int) $business_id);
                }

                if (! empty($lockedDirectShiftLabel)) {
                    $directShiftAlreadyFinalized = Settlement::where('business_id', $business_id)
                        ->where('id', '!=', $settlement->id)
                        ->where('status', 0)
                        ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                        ->where('settlement_no', 'NOT LIKE', 'PDST%')
                        ->where('work_shift', 'LIKE', '%' . $lockedDirectShiftLabel . '%')
                        ->exists();

                    if ($directShiftAlreadyFinalized) {
                        $lockedDirectShiftLabel = null;
                    }
                }
            }

            $optionHtml = '';
            $success = true; // Changed to true by default to allow settlement without shifts
            $defaultShiftId = null;
            // Direct Settlement must use its own DST shift sequence only.
            // Do not load PetroPD/Pumper Dashboard assignment shifts into Direct Settlement.
            $hasShifts = false;
            // dd($assigned_pumps);
            if ($hasShifts) {
                // If there are assigned pumps, handle their shifts
                $defaultShiftNumber = null;
                foreach ($assigned_pumps as $item) {
                    $shift = PetroShift::find($item->shift_id);

                    if (! $shift) {
                        continue; // skip invalid references
                    }

                    // Closed-but-unsettled shifts must remain selectable for final settlement.
                    $success = true;

                    // work_shift is stored as JSON (e.g. '["94","97"]'), decode before comparing
                    $settlementShifts = [];
                    if (! empty($settlement) && ! empty($settlement->work_shift)) {
                        $decoded = is_array($settlement->work_shift)
                            ? $settlement->work_shift
                            : json_decode($settlement->work_shift, true);
                        $settlementShifts = is_array($decoded) ? $decoded : [$settlement->work_shift];
                    }

                    $isSelected = ! empty($settlement)
                        && $settlement->pump_operator_id == $request->pump_operator_id
                        && in_array((string) $item->shift_number, array_map('strval', $settlementShifts));

                    if ($isSelected) {
                        $optionHtml .= '<option value="'.$item['shift_id'].'" selected>'
                            .$item['shift_number'].'</option>';
                    } else {
                        $optionHtml .= '<option value="'.$item['shift_id'].'">'
                            .$item['shift_number'].'</option>';
                    }

                    if ($defaultShiftId === null) {
                        $defaultShiftId = $item['shift_id'];
                        $defaultShiftNumber = $item['shift_number'];
                    }
                }

                if (empty($settlement) && ! empty($request->pump_operator_id)) {
                    $settlement = Settlement::where('business_id', $business_id)
                        ->where('status', 1)
                        ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                        ->where('settlement_no', 'NOT LIKE', 'PDST%')
                        ->where('pump_operator_id', $request->pump_operator_id)
                        ->when(! empty($request->location_id), function ($query) use ($request) {
                            $query->where('location_id', $request->location_id);
                        })
                        ->whereDate('transaction_date', $requestedTransactionDate)
                        ->orderByDesc('id')
                        ->first();
                }

                if (empty($settlement) && ! empty($defaultShiftNumber)) {
                    $preferredSettlementNo = $request->input('settlement_no');
                    $reusableDraft = $this->findReusableDirectDraftSettlement(
                        $business_id,
                        $preferredSettlementNo,
                        ! empty($request->location_id) ? (int) $request->location_id : null
                    );

                    if (! empty($reusableDraft)) {
                        $settlement = $reusableDraft;
                        $settlement->transaction_date = $requestedTransactionDate;
                        $settlement->location_id = $request->location_id;
                        $settlement->pump_operator_id = $request->pump_operator_id;
                        $settlement->work_shift = [$defaultShiftNumber];
                        $settlement->note = $request->note;
                        $settlement->save();
                    } else {
                        $nextSettlementNo = $this->getNextDirectSettlementNo($business_id, $preferredSettlementNo);

                        $settlement = Settlement::create([
                        'settlement_no' => $nextSettlementNo,
                        'business_id' => $business_id,
                        'transaction_date' => $requestedTransactionDate,
                        'location_id' => $request->location_id,
                        'pump_operator_id' => $request->pump_operator_id,
                        'work_shift' => [$defaultShiftNumber],
                        'note' => $request->note,
                            'status' => 1,
                        ]);
                    }
                }
            } else {
                // No assigned shifts → create empty option but allow settlement to proceed
                $directShiftPrefix = $this->getDirectSettlementShiftPrefix((int) $business_id);
                $existingDirectSettlement = null;

                $existingDirectShiftLabel = null;
                if (! empty($settlement)) {
                    $existingDirectShiftLabel = $this->normalizeDirectSettlementShiftLabel(
                        $settlement->work_shift,
                        (int) $business_id
                    );
                } else {
                    $existingDirectSettlement = Settlement::where('business_id', $business_id)
                        ->where('status', 1)
                        ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                        ->where('settlement_no', 'NOT LIKE', 'PDST%')
                        ->where('pump_operator_id', $request->pump_operator_id)
                        ->when(! empty($request->location_id), function ($query) use ($request) {
                            $query->where('location_id', $request->location_id);
                        })
                        ->whereDate('transaction_date', $requestedTransactionDate)
                        ->where('work_shift', 'LIKE', '%' . $directShiftPrefix . '%')
                        ->orderByDesc('id')
                        ->first();

                    if (empty($existingDirectSettlement)) {
                        $existingDirectSettlement = Settlement::where('business_id', $business_id)
                            ->where('status', 1)
                            ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                            ->where('settlement_no', 'NOT LIKE', 'PDST%')
                            ->when(! empty($request->location_id), function ($query) use ($request) {
                                $query->where('location_id', $request->location_id);
                            })
                            ->whereDate('transaction_date', $requestedTransactionDate)
                            ->where('work_shift', 'LIKE', '%' . $directShiftPrefix . '%')
                            ->orderByDesc('id')
                            ->first();
                    }
                }

                if (empty($existingDirectShiftLabel) && ! empty($existingDirectSettlement)) {
                    $existingDirectShiftLabel = $this->normalizeDirectSettlementShiftLabel(
                        $existingDirectSettlement->work_shift,
                        (int) $business_id
                    );
                }

                if (! empty($existingDirectShiftLabel)) {
                    $currentDirectSettlementId = ! empty($settlement)
                        ? $settlement->id
                        : (! empty($existingDirectSettlement) ? $existingDirectSettlement->id : 0);

                    $existingDirectShiftAlreadyFinalized = Settlement::where('business_id', $business_id)
                        ->when($currentDirectSettlementId > 0, function ($query) use ($currentDirectSettlementId) {
                            $query->where('id', '!=', $currentDirectSettlementId);
                        })
                        ->where('status', 0)
                        ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                        ->where('settlement_no', 'NOT LIKE', 'PDST%')
                        ->where('work_shift', 'LIKE', '%' . $existingDirectShiftLabel . '%')
                        ->exists();

                    if ($existingDirectShiftAlreadyFinalized) {
                        $existingDirectShiftLabel = null;
                    }
                }

                // Direct Settlement shift number must follow the Direct Settlement number.
                // Example: ST1 => DST1, ST2 => DST2. It must not come from PetroPD/Pumper shifts.
                $preferredSettlementNoForShift = (string) $request->input('settlement_no', '');
                if ($preferredSettlementNoForShift === '' && ! empty($settlement)) {
                    $preferredSettlementNoForShift = (string) $settlement->settlement_no;
                }
                if ($preferredSettlementNoForShift === '' && ! empty($existingDirectSettlement)) {
                    $preferredSettlementNoForShift = (string) $existingDirectSettlement->settlement_no;
                }

                $settlementSequenceForShift = $this->extractLastInteger($preferredSettlementNoForShift);
                $directShiftLabel = $settlementSequenceForShift > 0
                    ? $this->getDirectSettlementShiftPrefix((int) $business_id) . $settlementSequenceForShift
                    : $this->getNextDirectSettlementShiftLabel(
                        (int) $business_id,
                        ! empty($lockedDirectShiftLabel)
                            ? (string) $lockedDirectShiftLabel
                            : (! empty($existingDirectShiftLabel) ? (string) $existingDirectShiftLabel : null),
                        ! empty($lockedDirectShiftLabel)
                            ? (int) $request->pump_operator_id
                            : (! empty($settlement) ? (int) $request->pump_operator_id : (! empty($existingDirectSettlement) ? (int) $existingDirectSettlement->pump_operator_id : (! empty($request->direct_shift_operator_id) ? (int) $request->direct_shift_operator_id : null))),
                        ! empty($request->pump_operator_id) ? (int) $request->pump_operator_id : null
                    );

                $optionHtml = '<option value="0" selected data-direct-shift="1" data-pump-operator="'.e($request->pump_operator_id).'">'
                    .e($directShiftLabel).'</option>';
                $success = true;
                $defaultShiftId = 0;

                if (! empty($existingDirectSettlement)) {
                    $settlement = $existingDirectSettlement;
                    $settlement->pump_operator_id = $request->pump_operator_id;
                    $settlement->location_id = $request->location_id;
                    $settlement->transaction_date = $requestedTransactionDate;
                    $settlement->note = $request->note;
                    if (empty($existingDirectShiftLabel)) {
                        $settlement->work_shift = [$directShiftLabel];
                    }
                    $settlement->save();
                } elseif (! empty($settlement)) {
                    $settlement->work_shift = [$directShiftLabel];
                    $settlement->save();
                } else {
                    $preferredSettlementNo = $request->input('settlement_no');
                    $reusableDraft = $this->findReusableDirectDraftSettlement(
                        $business_id,
                        $preferredSettlementNo,
                        ! empty($request->location_id) ? (int) $request->location_id : null
                    );

                    if (! empty($reusableDraft)) {
                        $settlement = $reusableDraft;
                        $settlement->transaction_date = $requestedTransactionDate;
                        $settlement->location_id = $request->location_id;
                        $settlement->pump_operator_id = $request->pump_operator_id;
                        $settlement->work_shift = [$directShiftLabel];
                        $settlement->note = $request->note;
                        $settlement->save();
                    } else {
                        $nextSettlementNo = $this->getNextDirectSettlementNo($business_id, $preferredSettlementNo);

                        $settlement = Settlement::create([
                        'settlement_no' => $nextSettlementNo,
                        'business_id' => $business_id,
                        'transaction_date' => $requestedTransactionDate,
                        'location_id' => $request->location_id,
                        'pump_operator_id' => $request->pump_operator_id,
                        'work_shift' => [$directShiftLabel],
                        'note' => $request->note,
                            'status' => 1,
                        ]);
                    }
                }
            }

            // Auto-select default shift if applicable
            if ($defaultShiftId !== null && empty($settlement) && ! request()->has('multiple')) {
                $optionHtml = str_replace(
                    'value="'.$defaultShiftId.'"',
                    'value="'.$defaultShiftId.'" selected',
                    $optionHtml
                );
            }

            $output = [
                'success' => $success,
                'msg' => __('Settlement updated successfully.'),
                'optionHtml' => $optionHtml,
                'settlement_id' => ! empty($settlement) ? $settlement->id : null,
                'settlement_no' => ! empty($settlement) ? $settlement->settlement_no : null,
                'pump_nos' => ! $hasShifts
                    ? $this->getAvailableDirectSettlementPumps(
                        (int) $request->session()->get('business.id'),
                        ! empty($request->location_id) ? (int) $request->location_id : null
                    )
                    : null,
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

        return response()->json($output);

    }

    public function canEditSettlement($id)
    {

        $settlement = Settlement::findOrFail($id);

        $transaction = Transaction::where(

            'invoice_no',

            $settlement->settlement_no

        )

            ->where('type', 'sell')

            ->first();

        // see the customer payments marked as paid

        // see the loan to customer already paid for by the customer

        $paid_customer_loan = Transaction::where(

            'invoice_no',

            $settlement->settlement_no

        )

            ->where('sub_type', 'customer_loan')

            ->whereIn('payment_status', ['partial', 'paid'])

            ->count();

        // see the cheques already deposited

        if (! empty($transaction)) {

            $deposited_cheques = TransactionPayment::where(

                'transaction_id',

                $transaction->id

            )

                ->where('method', 'cheque')

                ->where('is_deposited', 1)

                ->count();

        }

        $can_edit = 1;

        $reasons = '';

        if ($paid_customer_loan > 0 || $deposited_cheques > 0) {

            $can_edit = 0;

            $reasons .= '<ol>';

            if ($paid_customer_loan > 0) {

                $reasons .=

                    '<li>'.__('petrogeneral::lang.paid_customer_loan').'</li>';

            }

            if ($deposited_cheques > 0) {

                $reasons .=

                    '<li>'.__('petrogeneral::lang.deposited_cheque').'</li>';

            }

            $reasons .= '</ol>';

        }

        return [$can_edit, $reasons];

    }

    /**
     * Update the specified resource in storage.

     *
     * @return Response
     */

    public function destroy($id)
    {

        try {

            DB::beginTransaction();

            $settlement = Settlement::findOrFail($id);

            $this->deletePreviouseTransactions($settlement->id, true);

            $settlement->delete();

            DB::commit();

            $output = [

                'success' => true,

                'msg' => __('petrogeneral::lang.settlement_delete_success'),

            ];

        } catch (\Exception $e) {

            Log::emergency(

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

        return response()->json($output);

    }

    public function deletePreviouseTransactions(

        $settlement_id,

        $is_destory = false,

        $no_change = false

    ) {

        $business_id = request()

            ->session()

            ->get('business.id');

        $settlement = Settlement::find($settlement_id);

        $all_trasactions = Transaction::where(

            'invoice_no',

            $settlement->settlement_no

        )

            ->where('is_settlement', 1)

            ->where('business_id', $business_id)

            ->with(['sell_lines'])

            ->withTrashed()

            ->get();

        foreach ($all_trasactions as $transaction) {

            if (! empty($no_change) && $transaction->sub_type == 'credit_sale') {

                // for credit sales and no change edit type; skip deleting the existing credit sales

                continue;

            }

            if (! empty($transaction)) {

                $deleted_sell_lines = $transaction->sell_lines;

                $deleted_sell_lines_ids = $deleted_sell_lines

                    ->pluck('id')

                    ->toArray();

                if ($transaction->sub_type == 'credit_sale') {

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

                $transaction->status = 'draft';

                $business = [

                    'id' => $business_id,

                    'accounting_method' => request()

                        ->session()

                        ->get('business.accounting_method'),

                    'location_id' => $transaction->location_id,

                ];

                if ($transaction->sub_type != 'credit_sale') {

                    $this->transactionUtil->adjustMappingPurchaseSell(

                        'final',

                        $transaction,

                        $business,

                        $deleted_sell_lines_ids

                    );

                }

                // Delete Cash register transactions

                $transaction->cash_register_payments()->delete();

            }

            $tank_sell_lines = TankSellLine::where(

                'transaction_id',

                $transaction->id

            )->get();

            foreach ($tank_sell_lines as $tank_sell_line) {

                FuelTank::where('id', $tank_sell_line->tank_id)->increment(

                    'current_balance',

                    $tank_sell_line->quantity

                );

            }

            TankSellLine::where(

                'transaction_id',

                $transaction->id

            )->forceDelete();

            AccountTransaction::where(

                'transaction_id',

                $transaction->id

            )->forceDelete();

            ContactLedger::where(
                'transaction_id',
                $transaction->id
            )->forceDelete();

            ContactLedger::where('transaction_id', null)
                ->where('note', 'like', '%' . $settlement->settlement_no . '%')
                ->forceDelete();

            TransactionPayment::where(

                'transaction_id',

                $transaction->id

            )->forceDelete();

            Transaction::where('id', $transaction->id)->forceDelete();

        }

        $settlement->total_amount = 0;

        $settlement->save();

        if ($is_destory) {

            $meter_sales = MeterSale::where(

                'settlement_no',

                $settlement->id

            )->get();

            foreach ($meter_sales as $meter_sale) {

                Pump::where('id', $meter_sale->pump_id)->update([

                    'last_meter_reading' => $meter_sale->starting_meter,

                ]);

                $meter_sale->delete();

            }

            OtherSale::where('settlement_no', $settlement->id)->delete();

            OtherIncome::where('settlement_no', $settlement->id)->delete();

            CustomerPayment::where('settlement_no', $settlement->id)->delete();

            SettlementCardPayment::where(

                'settlement_no',

                $settlement->id

            )->delete();

            SettlementCashPayment::where(

                'settlement_no',

                $settlement->id

            )->delete();

            SettlementCashDeposit::where(

                'settlement_no',

                $settlement->id

            )->delete();

            SettlementChequePayment::where(

                'settlement_no',

                $settlement->id

            )->delete();

            SettlementExpensePayment::where(

                'settlement_no',

                $settlement->id

            )->delete();

            SettlementExcessPayment::where(

                'settlement_no',

                $settlement->id

            )->delete();

            SettlementShortagePayment::where(

                'settlement_no',

                $settlement->id

            )->delete();

            SettlementCreditSalePayment::where(

                'settlement_no',

                $settlement->id

            )->delete();

        }

    }

    public function updateCreditSales()
    {

        try {

            DB::beginTransaction();

            $trans = SettlementCreditSalePayment::join(

                'transactions',

                'transactions.id',

                'settlement_credit_sale_payments.transaction_id'

            )

                ->where('transactions.final_total', 0)

                ->get();

            foreach ($trans as $one) {

                $final_total = $one->amount - $one->total_discount;

                Transaction::where('id', $one->transaction_id)->update([

                    'final_total' => $final_total,

                    'total_before_tax' => $final_total,

                ]);

            }

            $output = 'Successfull';

            DB::commit();

        } catch (\Exception $e) {

            DB::rollback();

            logger($e);

            $output = 'Failed';

        }

        dd($output);

    }

    /**
     * Show the form for creating a new resource.







     * @return Response
     */

    public function adjustDiscounts()
    {

        ini_set('max_execution_time', 0);

        $meter_sales = MeterSale::all();

        $other_sales = OtherSale::all();

        foreach ($meter_sales as $sale) {

            if (empty($sale->discount) || empty($sale->discount_type)) {

                MeterSale::where('id', $sale->id)->update([

                    'discount_amount' => $sale->sub_total,

                ]);

            } else {

                $discount = 0;

                if ($sale->discount_type == 'fixed') {

                    $discount = $sale->discount;

                }

                if ($sale->discount_type == 'percentage') {

                    $discount = ($sale->discount * $sale->sub_total) / 100;

                }

                MeterSale::where('id', $sale->id)->update([

                    'discount_amount' => $sale->sub_total - $discount,

                ]);

            }

        }

        foreach ($other_sales as $sale) {

            if (empty($sale->discount) || empty($sale->discount_type)) {

                OtherSale::where('id', $sale->id)->update([

                    'discount_amount' => 0,

                ]);

            } else {

                $discount = 0;

                if ($sale->discount_type == 'fixed') {

                    $discount = $sale->discount;

                }

                if ($sale->discount_type == 'percentage') {

                    $discount = ($sale->discount * $sale->sub_total) / 100;

                }

                OtherSale::where('id', $sale->id)->update([

                    'discount_amount' => $discount,

                ]);

            }

        }

    }

    public function adjustMeterSalesDates()
    {

        ini_set('max_execution_time', 0);

        $query = TankSellLine::leftjoin(

            'transactions',

            'transactions.id',

            'tank_sell_lines.transaction_id'

        )

            ->leftjoin(

                'settlements',

                'settlements.settlement_no',

                'transactions.invoice_no'

            )

            ->leftjoin('fuel_tanks', 'fuel_tanks.id', 'tank_sell_lines.tank_id')

            ->leftjoin('products', 'products.id', 'tank_sell_lines.product_id')

            ->whereDate('settlements.created_at', date('Y-m-d'))

            ->select([

                'products.name as product_name',

                'fuel_tanks.fuel_tank_number',

                'tank_sell_lines.*',

                'settlements.transaction_date',

                'settlements.settlement_no',

            ])

            ->orderBy('settlements.transaction_date', 'DESC')

            ->get();

        foreach ($query as $one) {

            $transaction_date = $one->transaction_date;

            $created_at = $one->created_at;

            if (

                date('Y-m-d', strtotime($transaction_date)) !=

                date('Y-m-d', strtotime($created_at))

            ) {

                $new_date =

                    date('Y-m-d', strtotime($transaction_date)).

                    ' '.

                    date('H:i:s', strtotime($created_at));

                TankSellLine::where('id', $one->id)->update([

                    'created_at' => $new_date,

                ]);

            }

        }

    }
}

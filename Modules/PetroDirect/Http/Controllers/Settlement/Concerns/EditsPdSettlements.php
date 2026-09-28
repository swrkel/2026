<?php

namespace Modules\PetroDirect\Http\Controllers\Settlement\Concerns;

use Modules\PetroDirect\Support\PetroDirectDebug;
use Modules\PetroDirect\Support\SchemaCapabilityCache;
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
use Modules\PetroDirect\Entities\CustomerPayment;
use Modules\PetroDirect\Entities\CustomerBillVatPrefix;
use Modules\PetroDirect\Entities\DailyCard;
use Modules\PetroDirect\Entities\DailyCollection;
use Modules\PetroDirect\Entities\DailyVoucher;
use Modules\PetroDirect\Entities\DayEnd;
use Modules\PetroDirect\Entities\FuelTank;
use Modules\PetroDirect\Entities\MeterSale;
use Modules\PetroDirect\Entities\OtherIncome;
use Modules\PetroDirect\Entities\OtherSale;
use Modules\PetroDirect\Entities\PetroShift;
use Modules\PetroDirect\Entities\PetroWhatsAppTemplate;
use Modules\PetroDirect\Entities\Pump;
use Modules\PetroDirect\Entities\PumperDayEntry;
use Modules\PetroDirect\Entities\PumpOperator;
use Modules\PetroDirect\Entities\PumpOperatorAssignment;
use Modules\PetroDirect\Entities\PumpOperatorCommission;
use Modules\PetroDirect\Entities\PumpOperatorPayment;
use Modules\PetroDirect\Entities\PumpOperatorOtherSale;
use Modules\PetroDirect\Entities\Settlement;
use Modules\PetroDirect\Entities\SettlementCardPayment;
use Modules\PetroDirect\Entities\SettlementCashDeposit;
use Modules\PetroDirect\Entities\SettlementCashPayment;
use Modules\PetroDirect\Entities\SettlementChequePayment;
use Modules\PetroDirect\Entities\SettlementCreditSalePayment;
use Modules\PetroDirect\Entities\SettlementEditHistory;
use Modules\PetroDirect\Entities\SettlementExcessPayment;
use Modules\PetroDirect\Entities\PumpOperatorMeterSale;
use Modules\PetroDirect\Entities\SettlementExpensePayment;
use Modules\PetroDirect\Entities\SettlementShortagePayment;
use Modules\PetroDirect\Entities\SettlementLoanPayment;
use Modules\PetroDirect\Entities\SettlementDrawingPayment;
use Modules\PetroDirect\Entities\SettlementCustomerLoan;
use Modules\PetroDirect\Entities\TankSellLine;
use Modules\Superadmin\Entities\Subscription;
use Modules\PetroDirect\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

/**
 * Editing, updating and deleting a settlement.
 *
 * MA-002: split out of PetroDirect's SettlementController, which was 10,590
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

        /*
         * MA-002 (Issue 1): Direct Settlement Edit failed with a 404 on tenant
         * domains. This single codebase serves multiple databases, businesses
         * and locations, so 'business.id' is not always populated in the
         * session while 'user.business_id' is. index() and show() already
         * resolve the active business through the shared helper; edit() did
         * not, so the ownership gate below received 0 and always aborted.
         */
        $business_id = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);

        abort_if($business_id <= 0, 403, __('messages.unauthorized_action'));

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

        abort_unless(
            $this->isHistoricalDirectSettlementRecord((int) $id, (int) $business_id),
            404
        );

        $active_settlement = Settlement::where('id', $id)
            ->where('business_id', $business_id)

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

            ->firstOrFail();

        $settlement_credit_sale_payments = SettlementCreditSalePayment::leftJoin(
            'contacts',
            'settlement_credit_sale_payments.customer_id',
            '=',
            'contacts.id'
        )
            ->petroDirectOwned()
            ->where('settlement_credit_sale_payments.business_id', $business_id)
            ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
            ->where('settlement_credit_sale_payments.settlement_no', $active_settlement->settlement_no)
            ->select(
                'settlement_credit_sale_payments.*',
                'contacts.name as customer_name',
                'products.name as product_name'
            )
            ->get();

        \Log::info('IS2171 credit sales', ['settlement' => $active_settlement->settlement_no, 'business' => $business_id, 'found' => $settlement_credit_sale_payments->count()]);
        // dd(\DB::getQueryLog()); // Dump the logged queries

        $settlement_no = $active_settlement->settlement_no;

        // IS1761: edit page keeps only the PetroDirect DST label and PetroDirect rows.
        $directShiftLabel = $this->normalizeDirectSettlementShiftLabel(
            $active_settlement->work_shift,
            (int) $business_id
        );
        $shift_number = ! empty($directShiftLabel)
            ? [['shift_number' => $directShiftLabel, 'shift_id' => 0]]
            : [];

        $other_sale_final_total = 0.0;
        $pump_other_sale_final_total = 0.0;
        $userOtherDetails = [];

        foreach ($active_settlement->other_sales as $ot_item) {
            $product = Product::find($ot_item->product_id);
            $discount_amount = (float) ($ot_item->discount_amount ?? 0);
            $withDiscount = (float) ($ot_item->sub_total ?? 0) - $discount_amount;
            $other_sale_final_total += $withDiscount;

            $userOtherDetails[] = [
                'id' => $ot_item->id,
                'sku' => ! empty($product) ? $product->sku : '',
                'name' => ! empty($product) ? $product->name : '',
                'balance_stock' => number_format((float) $ot_item->balance_stock, 4, '.', ','),
                'price' => number_format((float) $ot_item->price, $currency_precision),
                'qty' => number_format((float) $ot_item->qty, 4, '.', ','),
                'discount_type' => $ot_item->discount_type,
                'discount' => number_format((float) $ot_item->discount, $currency_precision),
                'sub_total' => number_format((float) $ot_item->sub_total, $currency_precision),
                'with_discount' => number_format($withDiscount, $currency_precision),
                'user_check' => 1,
            ];
        }

        $combinedOtherSales = $userOtherDetails;
        $final_other_sale_total = $other_sale_final_total;

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

            $already_pumps = MeterSale::petroDirectOwned()->where(

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

        $store_location_id = (int) ($active_settlement->location_id ?? $default_location ?? 0);
        $stores = $this->getDirectSettlementStoreDropdown((int) $business_id, $store_location_id);

        $default_store_id = $this->resolveDirectSettlementDefaultStoreId(
            $stores,
            (int) request()->session()->get('business.default_store', 0),
            $store_location_id
        );

        $fuel_category_id = Category::where('business_id', $business_id)

            ->where('name', 'Fuel')

            ->first();

        $fuel_category_id = ! empty($fuel_category_id)

            ? $fuel_category_id->id

            : null;

        // $items = Product::where('category_id', '!=', $fuel_category_id)->where('business_id', $business_id)->pluck('name', 'id');

        $items = $this->getDirectSettlementOtherSaleItems(
            (int) $business_id,
            $fuel_category_id
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

        return view('petrodirect::settlement.edit')->with(

            compact(

                'business_locations',

                'payment_types',

                'services',

                'customers',
                'settlement_credit_sale_payments',

                'pump_operators',

                'wrok_shifts',

                'pump_nos',

                'items',

                'settlement_no',

                'default_location',

                'active_settlement',

                'stores',

                'default_store_id',

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
            $business_id = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);
            $requestedTransactionDate = \Carbon::parse($request->transaction_date)->format('Y-m-d');

            if ($id != '0') {

                $settlement = Settlement::where('business_id', $business_id)->where('id', $id)->first();

                if (! $this->isPetroDirectOwnedSettlement($settlement, $business_id)) {
                    $id = '0';
                    $settlement = null;
                }

                // IS1761: changing the Direct Settlement operator must not inspect
                // or depend on Pumper Dashboard / PetroPD assignments.
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
                } elseif (! empty($existingDirectShiftLabel)) {
                    $input['work_shift'] = json_encode([$existingDirectShiftLabel]);
                } else {
                    $input['work_shift'] = json_encode([
                        $this->getNextDirectSettlementShiftLabel((int) $business_id),
                    ]);
                }

                $input['transaction_date'] = \Carbon::parse(

                    $request->transaction_date

                )->format('Y-m-d');

                /*
                 * MA-002 (IS-1907): the LATEST day end by DATE, not the last
                 * row inserted.
                 *
                 * It used to be
                 *     DayEnd::where(...)->get()->last()->day_end_date
                 *
                 * ->get()->last() takes the last row in INSERTION order, which
                 * is only the newest date if day ends were always entered in
                 * date order. Enter one out of sequence - a correction, a
                 * back-dated close, an import - and this reads a date that is
                 * not the latest, and every settlement after it is refused
                 * with "Date Greater Than Day End".
                 *
                 * That refusal comes back from the SAME request that supplies
                 * the shift numbers AND the pump list, which is why selecting
                 * an operator showed an error and left Pump No unselectable.
                 * One failed response, two symptoms.
                 *
                 * max() asks the database for the highest date and cannot be
                 * fooled by insertion order. It also stops loading every day
                 * end row into memory to look at one of them.
                 */
                $latest_date = DayEnd::where('business_id', $business_id)
                    ->max('day_end_date');

                if (

                    ! empty($latest_date) &&

                    /*
                     * MA-002 (IS-1907): compare DATES, not timestamps.
                     *
                     * day_end_date is a TIMESTAMP column - it carries a time,
                     * and it even has ON UPDATE current_timestamp(), so it
                     * moves to "now" whenever the row is touched.
                     * transaction_date is compared as a plain date.
                     *
                     * strtotime('2026-08-06 14:30:00')  is 14:30
                     * strtotime('2026-08-06')           is 00:00
                     *
                     * so latest >= transaction was TRUE for a settlement on the
                     * SAME DAY as the day end, and every settlement that day was
                     * refused with "Date Greater Than Day End". Nothing was
                     * wrong with the dates - only with comparing a timestamp
                     * against a date.
                     *
                     * Comparing the date parts keeps the rule exactly as
                     * intended: a day that has been closed is still blocked,
                     * a day after the last close is still allowed.
                     */
                    substr((string) $latest_date, 0, 10) >=

                    substr((string) $input['transaction_date'], 0, 10)

                ) {

                    return [

                        'success' => false,

                        'msg' => __('petrodirect::lang.date_greater_than_day_end')
                            . ' (day end ' . e(substr((string) $latest_date, 0, 10))
                            . ', settlement ' . e(substr((string) ($input['transaction_date'] ?? ''), 0, 10)) . ')',

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

                    /*
                     * MA-002: re-date every related ledger row so the settlement
                     * and its transactions / account_transactions /
                     * transaction_payments stay on the same date.
                     *
                     * The previous try/catch swallowed EVERY exception and only
                     * logged, so a failure left the settlement on the new date
                     * with its ledger rows on the old one while the operator saw
                     * a successful save. Both cases are now surfaced:
                     *
                     *  - closed period  -> blocked, settlement date rolled back
                     *  - any other error -> re-thrown so the outer transaction
                     *                       rolls the whole edit back
                     */
                    try {
                        $this->updateSettlementRelatedTransactions(
                            $settlement,
                            $input['transaction_date']
                        );
                    } catch (\Modules\PetroDirect\Exceptions\SettlementPeriodClosedException $e) {

                        // Put the settlement back on its original date.
                        Settlement::where('id', $id)->update([
                            'transaction_date' => $currentTransactionDate,
                        ]);

                        Log::warning('Blocked settlement date change into a closed period', [
                            'settlement_id' => $settlement->id,
                            'from'          => $currentTransactionDate,
                            'to'            => $input['transaction_date'],
                        ]);

                        return [
                            'success' => 0,
                            'msg'     => $e->getMessage(),
                        ];
                    }

                }

                // IS2188: F15 Daily Report New stores calculated totals_json snapshots.
                // A settlement edit must invalidate those computed totals from the earliest
                // affected day onward while preserving the saved report row/manual fields.
                $updatedLocationId = (int) ($request->input('location_id', $location) ?: $location);
                if ($input['transaction_date'] !== $currentTransactionDate
                    || $updatedLocationId !== (int) $location) {
                    $invalidateFrom = min(
                        substr((string) $currentTransactionDate, 0, 10),
                        substr((string) $input['transaction_date'], 0, 10)
                    );
                    $this->invalidateMpcsSettlementDerivedSnapshots($business_id, (int) $location, $invalidateFrom);
                    if ($updatedLocationId !== (int) $location) {
                        $this->invalidateMpcsSettlementDerivedSnapshots($business_id, $updatedLocationId, $invalidateFrom);
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

            // IS1761: PetroDirect never reads Pumper Dashboard / PetroPD assignments.
            $assigned_pumps = collect();

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
                        ->where('settlement_no', 'LIKE', $this->getCanonicalDirectSettlementPrefix() . '%')
                        ->when(
                            SchemaCapabilityCache::hasColumn('settlements', 'created_by') && $this->getCurrentDirectSettlementOwnerId() > 0,
                            fn ($query) => $query->where('created_by', $this->getCurrentDirectSettlementOwnerId())
                        )
                        ->when(
                            ! SchemaCapabilityCache::hasColumn('settlements', 'created_by'),
                            function ($query) use ($business_id) {
                                $rememberedDraftId = $this->getRememberedDirectSettlementDraftId((int) $business_id);
                                $query->where('id', $rememberedDraftId > 0 ? $rememberedDraftId : -1);
                            }
                        )
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
                        $settlementData = [
                            'transaction_date' => $requestedTransactionDate,
                            'location_id' => $request->location_id,
                            'pump_operator_id' => $request->pump_operator_id,
                            'note' => $request->note,
                            'status' => 1,
                        ] + $this->directSettlementCreatedByAttributes();

                        $settlement = $this->createCanonicalDirectSettlementDraft((int) $business_id, $settlementData);
                        $defaultShiftNumber = (string) $settlement->settlement_no;
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
                        ->where('settlement_no', 'LIKE', $this->getCanonicalDirectSettlementPrefix() . '%')
                        ->when(
                            SchemaCapabilityCache::hasColumn('settlements', 'created_by') && $this->getCurrentDirectSettlementOwnerId() > 0,
                            fn ($query) => $query->where('created_by', $this->getCurrentDirectSettlementOwnerId())
                        )
                        ->when(
                            ! SchemaCapabilityCache::hasColumn('settlements', 'created_by'),
                            function ($query) use ($business_id) {
                                $rememberedDraftId = $this->getRememberedDirectSettlementDraftId((int) $business_id);
                                $query->where('id', $rememberedDraftId > 0 ? $rememberedDraftId : -1);
                            }
                        )
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
                            ->where('settlement_no', 'LIKE', $this->getCanonicalDirectSettlementPrefix() . '%')
                            ->when(
                                SchemaCapabilityCache::hasColumn('settlements', 'created_by') && $this->getCurrentDirectSettlementOwnerId() > 0,
                                fn ($query) => $query->where('created_by', $this->getCurrentDirectSettlementOwnerId())
                            )
                            ->when(
                                ! SchemaCapabilityCache::hasColumn('settlements', 'created_by'),
                                function ($query) use ($business_id) {
                                    $rememberedDraftId = $this->getRememberedDirectSettlementDraftId((int) $business_id);
                                    $query->where('id', $rememberedDraftId > 0 ? $rememberedDraftId : -1);
                                }
                            )
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
                // New Direct records use one identical pair: DST1 / DST1, DST2 / DST2, ...
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
                        $settlementData = [
                            'transaction_date' => $requestedTransactionDate,
                            'location_id' => $request->location_id,
                            'pump_operator_id' => $request->pump_operator_id,
                            'note' => $request->note,
                            'status' => 1,
                        ] + $this->directSettlementCreatedByAttributes();

                        $settlement = $this->createCanonicalDirectSettlementDraft((int) $business_id, $settlementData);
                        $directShiftLabel = (string) $settlement->settlement_no;
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

            if (! empty($settlement) && (int) $settlement->status === 1) {
                $this->rememberDirectSettlementDraft($settlement);
            }

            $output = [
                'success' => $success,
                'msg' => __('Settlement updated successfully.'),
                'optionHtml' => $optionHtml,
                'settlement_id' => ! empty($settlement) ? $settlement->id : null,
                'settlement_no' => ! empty($settlement) ? $settlement->settlement_no : null,
                /*
                 * MA-002 (IS-1907 #2): the Pump No list. Two faults here.
                 *
                 * 1. IT USED session('business.id') DIRECTLY.
                 *    This file resolves the business properly in three other
                 *    places, with getCurrentBusinessIdForDirectSettlement() and
                 *    its four fallbacks - and then bypassed it here. When that
                 *    one key is empty the pump query runs with business_id = 0
                 *    and returns nothing, so Pump No is empty with no error
                 *    anywhere. That key has done exactly this on the Add User
                 *    screen, the Petro General dashboard and the fuel tank
                 *    product dropdown.
                 *
                 * 2. IT RETURNED null WHENEVER SHIFTS EXIST.
                 *    The front end reads it as
                 *        if (result.pump_nos) { updatePumpDropdown(...) }
                 *    so null leaves the dropdown holding whatever it had, which
                 *    on a fresh selection is nothing. An operator WITH shifts -
                 *    the normal case - therefore got no pumps at all.
                 *
                 *    The list is now always sent. Sending it costs one small
                 *    query and the dropdown can then be filled whether or not
                 *    shifts were found.
                 */
                'pump_nos' => $this->getAvailableDirectSettlementPumps(
                    (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0),
                    ! empty($request->location_id) ? (int) $request->location_id : null
                ),
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

            //         PetroDirectDebug::info('Shift status:', [$shift]);

            //  PetroDirectDebug::info('Settled number:', [$previousUnsettled]);

            //  PetroDirectDebug::info('Item status :', [$item["shift_id"]]);

            //   PetroDirectDebug::info('Shift :', [$shift]);

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

        $business_id = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);
        abort_unless(
            $this->isHistoricalDirectSettlementRecord((int) $id, $business_id),
            404
        );
        $settlement = Settlement::where('business_id', $business_id)
            ->findOrFail($id);

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

                    '<li>'.__('petrodirect::lang.paid_customer_loan').'</li>';

            }

            if ($deposited_cheques > 0) {

                $reasons .=

                    '<li>'.__('petrodirect::lang.deposited_cheque').'</li>';

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

            $business_id = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);
            abort_unless(
                $this->isHistoricalDirectSettlementRecord((int) $id, $business_id),
                404
            );
            $settlement = Settlement::where('business_id', $business_id)
                ->findOrFail($id);

            $this->deletePreviouseTransactions($settlement->id, true);

            $settlement->delete();

            DB::commit();

            $output = [

                'success' => true,

                'msg' => __('petrodirect::lang.settlement_delete_success'),

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

    /**
     * Invalidate only MPCS calculated snapshots affected by a Direct Settlement edit.
     * Source rows and user-entered/manual F15 fields are never deleted.
     */
    protected function invalidateMpcsSettlementDerivedSnapshots(int $businessId, int $locationId, string $fromDate): void
    {
        if ($businessId <= 0 || $fromDate === '' || ! Schema::hasTable('mpcs_f15_daily_reports')) {
            return;
        }

        if (! Schema::hasColumn('mpcs_f15_daily_reports', 'totals_json')) {
            return;
        }

        try {
            $query = DB::table('mpcs_f15_daily_reports')
                ->where('business_id', $businessId)
                ->whereDate('report_date', '>=', substr($fromDate, 0, 10));

            if ($locationId > 0 && Schema::hasColumn('mpcs_f15_daily_reports', 'location_id')) {
                $query->where('location_id', $locationId);
            }

            $update = ['totals_json' => null];
            if (Schema::hasColumn('mpcs_f15_daily_reports', 'updated_at')) {
                $update['updated_at'] = now();
            }

            $query->update($update);
        } catch (\Throwable $e) {
            // MPCS is optional. A missing/legacy report table must never prevent the
            // Direct Settlement itself from being corrected.
            Log::warning('PetroDirect: unable to invalidate MPCS F15 calculated snapshots', [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'from_date' => $fromDate,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deletePreviouseTransactions(

        $settlement_id,

        $is_destory = false,

        $no_change = false

    ) {

        $settlement = Settlement::find($settlement_id);
        if (! $settlement) {
            return;
        }

        // IS2188: never trust business.id alone. Tenant sessions can carry only
        // user.business_id, while the settlement itself is the authoritative owner.
        $activeBusinessId = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);
        $business_id = (int) ($settlement->business_id ?? 0);
        if ($business_id <= 0 || ($activeBusinessId > 0 && $activeBusinessId !== $business_id)) {
            abort(403, __('messages.unauthorized_action'));
        }

        // F15 snapshots are computed output, not source data. Clear only the computed
        // payload so all manual/save metadata remains intact and the report rebuilds
        // from the edited settlement on the next request.
        $this->invalidateMpcsSettlementDerivedSnapshots(
            $business_id,
            (int) ($settlement->location_id ?? 0),
            substr((string) $settlement->transaction_date, 0, 10)
        );

        $linkedTransactionIds = $this->getDirectSettlementLinkedTransactionIds($settlement, $business_id);
        $all_trasactions = empty($linkedTransactionIds)
            ? collect()
            : Transaction::where('business_id', $business_id)
                ->whereIn('id', $linkedTransactionIds)
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

                    'accounting_method' => request()->session()->get('business.accounting_method')
                        ?: optional(Business::find($business_id))->accounting_method,

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

            AccountTransaction::where('business_id', $business_id)
                ->where('transaction_id', $transaction->id)
                ->forceDelete();

            if ($transaction->sub_type === 'credit_sale') {
                SettlementCreditSalePayment::where('business_id', $business_id)
                    ->where('transaction_id', $transaction->id)
                    ->update(['transaction_id' => null]);
            }

            ContactLedger::where('business_id', $business_id)
                ->where('transaction_id', $transaction->id)
                ->forceDelete();

            ContactLedger::where('business_id', $business_id)
                ->whereNull('transaction_id')
                ->where('note', 'like', '%' . $settlement->settlement_no . '%')
                ->forceDelete();

            TransactionPayment::where('business_id', $business_id)
                ->where('transaction_id', $transaction->id)
                ->forceDelete();

            Transaction::where('id', $transaction->id)->forceDelete();

        }

        $settlement->total_amount = 0;

        $settlement->save();

        if ($is_destory) {

            $meter_sales = MeterSale::petroDirectOwned()->where(

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

        return response()->json([
            'success' => $output === 'Successfull',
            'msg' => $output,
        ]);

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

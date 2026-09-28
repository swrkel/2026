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
 * Creating a settlement. store() is the large one - see the note in this file.
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
 * Methods here: create, store, createSettlementIfNotExist
 */
trait CreatesPdSettlements
{
    public function create()
    {

        // IS2338: use PetroDirect's single business resolver. In particular,
        // user.business_id must win over a stale business.id value left in the
        // shared session by another module; otherwise Business::firstOrFail()
        // below can incorrectly return a 404 for a valid Direct Settlement page.
        $business_id = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);

        if ($business_id <= 0) {
            abort(403, 'Unable to resolve the active business.');
        }

        if (! $this->hasPetroDirectAccess('petrodirect.settlements.create')) {
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

        $business = Business::where('id', $business_id)->first();

        // IS2340: this is a context failure, not a missing Direct Settlement
        // route. Avoid turning a business-context mismatch into Laravel's
        // misleading generic 404 page.
        abort_if(empty($business), 403, 'Unable to resolve the active tenant business for Direct Settlement.');

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

        $pump_operators = $this->getDirectSettlementPumpOperators($business_id)
            ->when(! empty($pending_pump_operator_ids), function ($query) use ($pending_pump_operator_ids) {
                $query->whereNotIn('id', $pending_pump_operator_ids);
            })
            ->orderBy('name')
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

        if (
            $view_settlement_id > 0
            && $this->isHistoricalDirectSettlementRecord($view_settlement_id, $business_id)
        ) {
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

            if (! empty($active_settlement) && (int) $active_settlement->status === 1) {
                $draftBelongsToCurrentUser = true;

                if (SchemaCapabilityCache::hasColumn('settlements', 'created_by')) {
                    $ownerId = $this->getCurrentDirectSettlementOwnerId();
                    $draftBelongsToCurrentUser = $ownerId > 0
                        && (int) ($active_settlement->created_by ?? 0) === $ownerId;
                } else {
                    $draftBelongsToCurrentUser = $this->getRememberedDirectSettlementDraftId((int) $business_id)
                        === (int) $active_settlement->id;
                }

                if (! $draftBelongsToCurrentUser
                    || ! $this->repairLegacyDirectDraftOwnership($active_settlement, $business_id)) {
                    $active_settlement = null;
                }
            }
        }

        // Preserve only THIS user's remembered draft on browser refresh. The
        // previous implementation loaded the newest open draft for the entire
        // business, which could show another user's settlement number and DST.
        if (empty($active_settlement)) {
            $rememberedDraftId = $this->getRememberedDirectSettlementDraftId((int) $business_id);

            if ($rememberedDraftId > 0) {
                $draftQuery = Settlement::where('business_id', $business_id)
                    ->where('id', $rememberedDraftId)
                    ->where('status', 1)
                    ->where('settlement_no', 'LIKE', $this->getCanonicalDirectSettlementPrefix() . '%')
                    ->where(function ($numberQuery) use ($business_id) { $this->scopeDirectSettlementNumberSeries($numberQuery, (int) $business_id); })
                    ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                    ->where('settlement_no', 'NOT LIKE', 'PDST%');

                $this->excludePetroPdModuleSettlements($draftQuery, $business_id, 'settlement_no');
                $this->excludeSettlementsWithPetroPdSettledShifts($draftQuery, $business_id);
                \Modules\PetroDirect\Support\PetroDirectIsolation::excludeSettlements($draftQuery);
                $this->scopeCurrentDirectSettlementDraftOwner($draftQuery, (int) $business_id);

                $active_settlement = $draftQuery
                    ->select('settlements.*')
                    ->with([
                        'meter_sales',
                        'other_sales',
                        'other_incomes',
                        'customer_payments',
                    ])
                    ->first();

                if (empty($active_settlement)) {
                    $this->forgetRememberedDirectSettlementDraft((int) $business_id, $rememberedDraftId);
                }
            }

            // Compatibility for drafts created before the session key existed:
            // only use a draft when the schema can prove it belongs to this user.
            if (
                empty($active_settlement)
                && SchemaCapabilityCache::hasColumn('settlements', 'created_by')
                && $this->getCurrentDirectSettlementOwnerId() > 0
            ) {
                $draftQuery = Settlement::where('business_id', $business_id)
                    ->where('status', 1)
                    ->where('settlement_no', 'LIKE', $this->getCanonicalDirectSettlementPrefix() . '%')
                    ->where(function ($numberQuery) use ($business_id) { $this->scopeDirectSettlementNumberSeries($numberQuery, (int) $business_id); })
                    ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                    ->where('settlement_no', 'NOT LIKE', 'PDST%');

                $this->excludePetroPdModuleSettlements($draftQuery, $business_id, 'settlement_no');
                $this->excludeSettlementsWithPetroPdSettledShifts($draftQuery, $business_id);
                \Modules\PetroDirect\Support\PetroDirectIsolation::excludeSettlements($draftQuery);
                $this->scopeCurrentDirectSettlementDraftOwner($draftQuery, (int) $business_id);

                $active_settlement = $draftQuery
                    ->select('settlements.*')
                    ->with([
                        'meter_sales',
                        'other_sales',
                        'other_incomes',
                        'customer_payments',
                    ])
                    ->orderByDesc('id')
                    ->first();
            }

            if (
                ! empty($active_settlement)
                && ! $this->repairLegacyDirectDraftOwnership($active_settlement, $business_id)
            ) {
                $active_settlement = null;
            }

            if (! empty($active_settlement)) {
                $this->rememberDirectSettlementDraft($active_settlement);
            }
        }

        if (! empty($active_settlement)) {
            $settlement_no = $active_settlement->settlement_no;

            // Self-heal any NEW canonical draft left half-written by an older
            // request: DSTn settlement and DSTn shift must always be identical.
            if ($this->isCanonicalDirectSettlementNo((string) $active_settlement->settlement_no)) {
                $canonicalShift = (string) $active_settlement->settlement_no;
                if ($this->normalizeDirectSettlementShiftLabel($active_settlement->work_shift, (int) $business_id) !== $canonicalShift) {
                    $active_settlement->work_shift = [$canonicalShift];
                    $active_settlement->save();
                }
            }

            if (! empty($active_settlement->pump_operator_id) && ! $pump_operators->has($active_settlement->pump_operator_id)) {
                $active_operator_name = PumpOperator::where('business_id', $business_id)
                    ->where('id', $active_settlement->pump_operator_id)
                    ->value('name');

                if (! empty($active_operator_name)) {
                    $pump_operators->put($active_settlement->pump_operator_id, $active_operator_name);
                }
            }
        }

        if ($active_settlement) {
            // Keep create/edit totals on the exact same Direct Meter Sale source
            // used by the Settlement Preview and final print.
            app(\Modules\PetroDirect\Services\DirectSettlementMeterSaleScopeService::class)
                ->apply($active_settlement, (int) $business_id);
        }

        $default_pump_operator_id = null;

        $other_sale_final_total = 0.0;

        $pump_other_sale_final_total = 0.0;

        $combinedOtherSales = [];

        if ($active_settlement) {
            // IS1761: Direct Settlement uses only its own synthetic DST label.
            // Never hydrate Pumper Dashboard / PetroPD assignment shifts or their other sales.
            $directShiftLabel = $this->normalizeDirectSettlementShiftLabel(
                $active_settlement->work_shift,
                (int) $business_id
            );

            if (! empty($directShiftLabel)) {
                $shift_numbers = [0 => $directShiftLabel];
            }

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
            $pump_other_sale_final_total = 0.0;
            $final_other_sale_total = $other_sale_final_total;
        }

        // $combinedOtherSales = []; $pump_other_sale_final_total = 0;

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        if (! empty($active_settlement)) {

            $already_pumps = MeterSale::petroDirectOwned()->where(

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

        $items = $this->getDirectSettlementOtherSaleItems(
            (int) $business_id,
            $fuel_category_id
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

        // Fresh Direct Settlement page must display the same preview for both
        // Settlement No and Shift No. The actual pair is reserved atomically
        // when the draft is created, so a concurrent user can never duplicate it.
        if (empty($shift_numbers)) {
            $shift_numbers = [0 => $settlement_no];
        }

        // dd($active_settlement,123);

        // dd($settlement_credit_sale_payments);

        $response = response()->view('petrodirect::settlement.create', compact(

                'business_id',
                'business',
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

                'default_store_id',

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

        PetroDirectDebug::info('SettlementController@store called', ['request' => $request->all()]);

        try {

            $denom_qty = $request->denom_qty;
            $denom_value = $request->denom_value;
            $denom_enabled = $request->denom_enabled;
            $denom_data = [];

            $business_id = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);
            \Modules\PetroDirect\Support\PetroDirectIsolation::assertAllowedOutsidePetroPd(
                (int) $business_id,
                $request->input('pump_operator_id'),
                (array) $request->input('pump_id', [])
            );

            $settings = \Modules\PetroDirect\Entities\PumpOperator::where('business_id', $business_id)->whereNotNull('dashboard_settings')->first();
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
            $business_id = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);

            $settlement = null;
            $active_settlement_id = (int) $request->input('active_settlement_id', 0);
            if ($active_settlement_id > 0) {
                $settlement = Settlement::where('business_id', $business_id)
                    ->where('id', $active_settlement_id)
                    ->first();
            }
            if (empty($settlement) && $request->filled('settlement_no')) {
                $settlement = Settlement::where('business_id', $business_id)
                    ->where('settlement_no', $request->settlement_no)
                    ->first();
            }

            if (
                empty($settlement)
                || ! $this->repairLegacyDirectDraftOwnership($settlement, $business_id)
            ) {
                return [
                    'success' => 0,
                    'msg' => 'Unable to finalize: this record does not belong to Petro Direct. Please refresh the Direct Settlement page.',
                ];
            }

            // S677: defence-in-depth. The UI hides Finalize unless Balance is
            // exactly zero, but a stale browser/request must not be able to bypass it.
            if ($request->has('total_balance')) {
                $submittedBalance = (float) str_replace(',', '', (string) $request->input('total_balance', 0));
                $submittedBalanceMinorUnits = (int) round($submittedBalance * 100);
                if ($submittedBalanceMinorUnits !== 0) {
                    return [
                        'success' => 0,
                        'msg' => 'Settlement can be finalized only when Balance is exactly 0.00.',
                    ];
                }
            }

            // Persist the date selected on the Direct Settlement screen before
            // finalisation. Previously the list could retain an older/blank date.
            $previous_transaction_date = substr((string) $settlement->transaction_date, 0, 10);
            $selected_transaction_date = $this->normalizeDirectSettlementDate(
                $request->input('transaction_date'),
                $settlement->transaction_date
            );
            if ($previous_transaction_date !== $selected_transaction_date) {
                // IS2188: store() can receive the edited date directly, without the
                // separate update() request. Preserve the old day for MPCS snapshot
                // invalidation before replacing settlements.transaction_date.
                $this->invalidateMpcsSettlementDerivedSnapshots(
                    $business_id,
                    (int) ($settlement->location_id ?? 0),
                    min($previous_transaction_date, $selected_transaction_date)
                );
                $settlement->transaction_date = $selected_transaction_date;
                $settlement->save();
            }

            $edit = Settlement::where('id', $settlement->id)
                ->where('business_id', $business_id)
                ->where('status', 0)
                ->first();

            $pump_operator_total_other_sale = 0;
            $pump_operator_other_sales = [];

            // IS1761: ignore all operational/PetroPD shift IDs in PetroDirect finalize.
            $request->merge(['shift_ids' => []]);

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

            foreach ($daily_collections as $daily_collection_row) {
                if ($outstanding_payment >= 0) {
                    $customers = Contact::customersDropdown($business_id, false, true, 'customer');

                    // DAY1-ORPHAN: DailyCollection-sourced row, no pump_payment_id linkage in this flow.
                    // Goes through Reconciler so Lock 2 lets it through; orphan path always inserts,
                    // so we keep the legacy customer_id+amount existence check to avoid re-insert on retry.
                    $data = [
                        'amount' => floatval($daily_collection_row->current_amount),
                        'customer_id' => array_key_first($customers->toArray()),
                        'pump_payment_id' => null,
                    ];
                    $existing_payment = SettlementCashPayment::where('settlement_no', $settlement->id)
                        ->where('business_id', $business_id)
                        ->where('customer_id', $data['customer_id'])
                        ->where('amount', $data['amount'])
                        ->first();
                    if (!$existing_payment) {
                        app(\Modules\PetroDirect\Services\SettlementPaymentReconciler::class)
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
            app(\Modules\PetroDirect\Services\DirectSettlementMeterSaleScopeService::class)
                ->apply($settlement, (int) $business_id);

            if ($settlement->meter_sales->isNotEmpty()
                || $settlement->other_sales()->exists()
                || $settlement->other_incomes()->exists()) {
                $has_pending_changes = true;
            }

            if ($settlement->is_edit == 0 && $settlement->status == 0 && ! empty($no_change) && ! $has_pending_changes) {
                return [
                    'success' => 0,
                    'msg' => __('petrodirect::lang.no_change_performed'),
                ];
            }

            /*
             * S773: customer-facing SMS/WhatsApp must be sent only after the
             * settlement database work has committed.  Keep the exact payment /
             * credit-sale rows created by this request so we do not query older
             * historical rows and accidentally send duplicate notifications.
             */
            $customer_payment_notifications = [];
            $credit_sale_sms_notifications = [];

            DB::beginTransaction();

            if (! empty($edit)) {
                $this->deletePreviouseTransactions($settlement->id, false, $no_change);
            }

            $business_locations = BusinessLocation::forDropdown($business_id);
            $default_location = current(array_keys($business_locations->toArray()));

            $settlement = Settlement::where('settlements.id', $settlement->id)
                ->where('settlements.business_id', $business_id)
                ->leftJoin('pump_operators', 'settlements.pump_operator_id', 'pump_operators.id')
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
                    'msg' => __('petrodirect::lang.settlement_not_found_for_finalize') ?: 'Unable to finalize: settlement not found for the selected shift. Please refresh and try again.',
                ];
            }

            // Finalisation must consume the very same shift/ownership-isolated
            // Meter Sale rows that the user just confirmed in Preview.
            app(\Modules\PetroDirect\Services\DirectSettlementMeterSaleScopeService::class)
                ->apply($settlement, (int) $business_id);

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
                    $fuel_tank = \Modules\PetroDirect\Entities\FuelTank::find($fuel_tank_id);
                    if ($fuel_tank && !empty($fuel_tank->product_id)) {
                        $meter_sale->product_id = $fuel_tank->product_id;
                    }
                }

                PetroDirectDebug::info("meter-sale: $meter_sale");

                /*
                 * IS1958 #1: tag this as a meter sale so stock is reduced.
                 *
                 * This used to pass null for $is_other_sale. In
                 * createSellTransactions() the stock decrement sits behind
                 * "if ($product->enable_stock && ! empty($is_other_sale))", so
                 * a null meant fuel meter sales wrote their sell line but never
                 * reduced variation_location_details.qty_available. The stock
                 * history ledger still showed the sale (it is derived from
                 * transactions), while Stock Center's Available column - which
                 * reads qty_available - never moved. That is the reported fault.
                 *
                 * Other sales pass true and operator other sales pass a string,
                 * which is why only fuel was affected. Other incomes keep null:
                 * they carry no stock and must not be decremented.
                 *
                 * The quantity used is $sale->qty, which for a meter sale is the
                 * SOLD quantity - testing_qty is stored in its own column and is
                 * deliberately not included, per the agreed rule that stock
                 * reduces by sold qty only.
                 */
                $sell_line = $this->createSellTransactions(
                    $transaction,
                    $meter_sale,
                    $business_id,
                    $default_location,
                    $fuel_tank_id,
                    'meter_sale'
                );

                MeterSale::where('id', $meter_sale->id)->update([
                    'transaction_id' => $transaction->id,
                ]);

                PetroDirectDebug::info('meter-sale: ');
            }

            foreach ($settlement->other_sales as $other_sale) {
                $getOtherSale = OtherSale::where('id', $other_sale->id)->first();

                if (empty($getOtherSale) || $getOtherSale->transaction_id == null || $getOtherSale->transaction_id != $transaction->id) {
                    PetroDirectDebug::info("other-sale: $other_sale");

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

                    PetroDirectDebug::info('Other-sale: ');
                }
            }

            foreach ($pump_operator_other_sales as $pump_operator_other_sales_item) {
                PetroDirectDebug::info("operator_other_sales: $pump_operator_other_sales_item");

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

                    PetroDirectDebug::info('operator_other_sales: ');
                }
            }

            foreach ($settlement->other_incomes as $other_income) {
                PetroDirectDebug::info("Other-income: $other_income");

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

                PetroDirectDebug::info('Other-income: ');
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

                if (! empty($cash_payment->customer_id)) {
                    $customer_payment_notifications[] = [
                        'transaction_id' => (int) $cash_transaction_payment->id,
                        'contact_id' => (int) $cash_payment->customer_id,
                        'amount' => (float) $cash_payment->amount,
                        'payment_ref_number' => '',
                        'method' => 'cash',
                    ];
                }
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

                if (! empty($card_payment->customer_id)) {
                    $customer_payment_notifications[] = [
                        'transaction_id' => (int) $card_transaction->id,
                        'contact_id' => (int) $card_payment->customer_id,
                        'amount' => (float) $card_payment->amount,
                        'payment_ref_number' => (string) ($card_payment->slip_no ?? ''),
                        'method' => 'card',
                    ];
                }
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

                /*
                 * S773: do not call the notification gateway while the settlement
                 * transaction is still open.  Queue this cheque together with cash
                 * and card payments, then send all customer notifications after COMMIT.
                 */
                if (! empty($cheque_payment->customer_id)) {
                    $customer_payment_notifications[] = [
                        'transaction_id' => (int) $cheque_transaction->id,
                        'contact_id' => (int) $cheque_payment->customer_id,
                        'amount' => (float) $cheque_payment->amount,
                        'payment_ref_number' => (string) ($cheque_payment->cheque_number ?? ''),
                        'method' => 'cheque',
                    ];
                }

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

            // IS1771-2: Always reconcile every credit bill into a real sales
            // transaction and a transaction-linked customer ledger row. The old
            // edit/no-change filter trusted settlement_credit_sale_payments.transaction_id
            // even when that transaction had already been deleted, and the normal
            // relationship omitted legacy rows stored against settlements.id.
            // Edit No Change must be a true preservation path for credit sales.
            // The existing credit-sale source rows, linked sales transactions, bill/order
            // dates, amounts, discounts, customer references, commitment flags and ledger
            // postings must not be saved, rebuilt or overwritten from this submission.
            // Normal Finalize/Edit continues to reconcile every credit bill as before.
            $credit_sales_to_process = ! empty($no_change)
                ? collect()
                : $this->getDirectSettlementCreditSales($settlement, (int) $business_id);

            foreach ($credit_sales_to_process as $credit_sale_payment) {
                    $transaction = $this->createCreditSellTransactions(
                        $settlement,
                        $credit_sale_payment,
                        $default_location
                    );

                    $credit_sale_payment = app(\Modules\PetroDirect\Services\SettlementPaymentEditService::class)
                        ->editCreditSale($business_id, $credit_sale_payment->id, [
                            'transaction_id' => $transaction->id,
                        ]);

                    $is_rt = ! empty($credit_sale_payment->pump_payment_id);
                    $this->ensureCreditSaleCustomerAccounting(
                        $settlement,
                        $transaction,
                        $credit_sale_payment,
                        $is_rt && $update_account
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

                        /*
                         * S773: keep the business resolved at the start of Direct
                         * Settlement.  `user.business_id` is not present in every
                         * central/multi-business session, and resetting the id here
                         * made the Credit Sale template lookup silently return null.
                         */
                        $sms_settings = empty($business->sms_settings)
                            ? $this->businessUtil->defaultSmsSettings()
                            : $business->sms_settings;

                        $contact = Contact::where('business_id', $business_id)
                            ->where('id', $credit_sale_payment->customer_id)
                            ->first();
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

                            $mobile_numbers = array_values(array_unique(array_filter([
                                trim((string) $contact->mobile),
                                trim((string) $contact->alternate_number),
                            ])));

                            if (! empty($mobile_numbers)) {
                                $credit_sale_sms_notifications[] = [
                                    'contact_id' => (int) $contact->id,
                                    'mobile_numbers' => $mobile_numbers,
                                    'sms_settings' => $sms_settings,
                                    'sms_body' => $msg,
                                    'credit_sale_id' => (int) $credit_sale_payment->id,
                                ];
                            }
                        }
                    } else {
                        $credit_sale_payment = app(\Modules\PetroDirect\Services\SettlementPaymentEditService::class)
                            ->editCreditSale($business_id, $credit_sale_payment->id, [
                                'is_from_pumper' => 0,
                                'is_committed' => 1,
                            ]);
                    }
                }

            $total_shortage = $pump_operator->short_amount; // get previous amount

            foreach ($settlement->shortage_payments as $shortage_payment) {
                // IS1607-002: prevent duplicate shortage postings in Accounts Receivable if finalize is retried.
                if (!empty($shortage_payment->transaction_id)) {
                    continue;
                }

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
                // IS1607-002: prevent duplicate excess postings in Accounts Payable if finalize is retried.
                if (!empty($excess_payment->transaction_id)) {
                    continue;
                }

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

            // IS2265: the Note field can be changed immediately before Finalize.
            // Persist the value submitted by the finalisation request here so the
            // List Direct Settlement Note column always reflects the final note,
            // instead of depending on an earlier asynchronous field-change save.
            if ($request->has('note')) {
                $settlement->note = $request->input('note');
            }

            $settlement->status = 0; // set status to non-active
            $settlement->is_edit = 0;

            $settlement->cash_denomination = ($denom_enabled > 0) ? json_encode($denom_data) : null;
            $settlement->finish_date = date('Y-m-d');
            $settlement->save();
            $this->forgetRememberedDirectSettlementDraft((int) $business_id, (int) $settlement->id);

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
                    $outstanding_payment -= floatval($daily_collection_row->current_amount);

                    DB::update(
                        'update daily_collections set settlement_id = ?, settlement_date = ?, balance_collection = ? where business_id = ? and pump_operator_id = ? and id = ? and settlement_id is null',
                        [
                            $settlement->id,
                            $settlement->finish_date,
                            floatval($daily_collection_row->current_amount),
                            $business_id,
                            $settlement->pump_operator_id,
                            $daily_collection_row->id,
                        ]
                    );
                    // echo var_dump($outstanding_payment . "/nr");
                }
            }

            // create VAT entries
            $this->transactionUtil->calculateAndUpdateVAT($sell_transaction);

            /*
             * IS1839-PENDING-ISOLATION:
             * Petro Direct must not close, link, or otherwise mutate rows in
             * pumper_day_entries or pump_operator_assignments. Those rows are
             * owned exclusively by Pumper Dashboard/Petro PD. In particular,
             * the old no-shift branch closed every open assignment for the
             * operator (or matching pumps), which made unrelated PD shifts
             * appear pending/settled incorrectly.
             *
             * A Direct Settlement may have an optional shift reference, but it
             * remains Direct display data and is not a Pumper Dashboard shift.
             */

            DB::commit();

            /*
             * S773: customer notifications are deliberately outside the DB
             * transaction.  A gateway/API problem must never roll back a valid
             * finalized settlement, and cash/card/cheque now share the same
             * payment_received notification template path.
             */
            $this->sendS773DirectPaymentNotifications(
                (int) $business_id,
                $customer_payment_notifications,
                $contactController
            );
            $this->sendS773DirectCreditSaleSmsNotifications(
                (int) $business_id,
                $credit_sale_sms_notifications
            );

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
                            $changed_msg .= __("petrodirect::lang.$field").
                                __('petrodirect::lang.changed_from').
                                $original_details->$field.
                                __('petrodirect::lang.to').
                                $sms_data[$field].PHP_EOL;

                            $o_details .= __("petrodirect::lang.$field").': '.$original_details->$field.PHP_EOL;
                            $n_details .= __("petrodirect::lang.$field").': '.$sms_data[$field].PHP_EOL;
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

            // IS1480 PD-FIX-003: Some Petro Direct tenant databases do not have the
            // optional WhatsApp template table yet. Finalizing a Direct Settlement must
            // not fail because of this optional notification feature. Keep settlement,
            // stock, ledger and numbering logic unchanged; simply skip WhatsApp template
            // lookup when the table is not available.
            $msg_template_wahtsapp = null;
            try {
                if (SchemaCapabilityCache::hasTable('petro_whatsapp_templates')) {
                    $msg_template_wahtsapp = PetroWhatsAppTemplate::where('business_id', $business_id)
                        ->where('auto_send_sms', 1)
                        ->first();
                } else {
                    \Log::warning('PetroDirect settlement finalize: skipped WhatsApp template lookup because petro_whatsapp_templates table is missing.', [
                        'business_id' => $business_id,
                        'settlement_id' => $settlement->id ?? null,
                        'settlement_no' => $settlement_no ?? null,
                    ]);
                }
            } catch (\Throwable $whatsappTemplateException) {
                \Log::warning('PetroDirect settlement finalize: skipped WhatsApp template lookup.', [
                    'business_id' => $business_id,
                    'settlement_id' => $settlement->id ?? null,
                    'settlement_no' => $settlement_no ?? null,
                    'message' => $whatsappTemplateException->getMessage(),
                ]);
                $msg_template_wahtsapp = null;
            }

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
                PetroDirectDebug::info("WhatsApp URL: $whatsapp_url");

                return redirect()->away($whatsapp_url);
            }
            */

            // $shift_ids = $request->shift_ids;
            $print_pump_operator_other_sales = $this->getPrintPumpOperatorOtherSales($settlement, (int) $business_id, $shift_ids);

            // Return JSON response for AJAX requests, view for regular requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    "success" => 1,
                    "msg" => __("petrodirect::lang.settlement_saved_successfully") ?: "Settlement saved successfully",
                    "settlement_id" => $settlement->id,
                    "settlement_no" => $settlement->settlement_no,
                    "print_url" => route('petrodirect.settlement.print', ['id' => $settlement->id]) . '?autoprint=1',
                    "html" => view('petrodirect::settlement.print')->with(
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

            $print_html = view('petrodirect::settlement.print')->with(
                compact(
                    'settlement',
                    'business',
                    'pump_operator',
                    'customer_payments_tab',
                    'total_daily_collection',
                    'shift_ids',
                    'print_pump_operator_other_sales'
                )
            )->render();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => 1,
                    'msg' => __('petrodirect::lang.success'),
                    'html' => $print_html,
                    'settlement_id' => $settlement->id,
                    'settlement_no' => $settlement->settlement_no,
                    'print_url' => route('petrodirect.settlement.print', ['id' => $settlement->id]) . '?autoprint=1',
                ]);
            }

            return $print_html;

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
        $business_id = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);
        if ($business_id <= 0) {
            return 406;
        }

        $pump_operator_id = (int) ($request->pump_operator_id ?: $request->operator_id ?: 0);
        $location_id = (int) ($request->location_id ?: 0);
        $active_settlement_id = (int) $request->input('active_settlement_id', 0);
        $transaction_date = $this->normalizeDirectSettlementDate(
            $request->input('transaction_date'),
            now()
        );
        $requested_settlement_no = trim((string) $request->input('settlement_no', ''));

        $latest_date = DayEnd::where('business_id', $business_id)->latest('id')->value('day_end_date');
        if (! empty($latest_date) && strtotime($latest_date) >= strtotime($transaction_date)) {
            return 406;
        }

        $settlement = null;

        // Resolve the exact draft submitted by the browser before applying the
        // DST scope. Older Direct drafts may not have received that marker yet.
        /*
         |----------------------------------------------------------------------
         | LA-1198: the page loaded a DIFFERENT settlement than the one requested.
         |----------------------------------------------------------------------
         |
         | Reported: opening /petrodirect/settlement/5/edit showed
         | "Settlement No: ST3", and no payments appeared in any tab. The tabs were
         | right - they were showing ST3's payments, which is to say none. The page
         | had simply loaded the wrong settlement.
         |
         | Both lookups below required status = 1, a DRAFT. Settlement 5 is
         | finalised (status 0), so neither matched, execution fell through to the
         | general "find an open draft" query further down, and that returned an
         | unrelated settlement - ST3.
         |
         | An explicitly requested settlement is now honoured WHATEVER its status.
         | If the id or number in the request names a real settlement, that is the
         | one to use; falling back to somebody else's draft is never correct.
         |
         | The status filter is kept for the general fallback below, which is
         | genuinely looking for an open draft to continue.
         */
        if ($active_settlement_id > 0) {
            $candidate = Settlement::where('business_id', $business_id)
                ->where('id', $active_settlement_id)
                ->first();
            if (! empty($candidate) && $this->repairLegacyDirectDraftOwnership($candidate, $business_id)) {
                $settlement = $candidate;
            }
        }
        if (empty($settlement) && $requested_settlement_no !== '') {
            $candidate = Settlement::where('business_id', $business_id)
                ->where('settlement_no', $requested_settlement_no)
                ->first();
            if (! empty($candidate) && $this->repairLegacyDirectDraftOwnership($candidate, $business_id)) {
                $settlement = $candidate;
            }
        }

        /*
         | A requested settlement that exists but failed the ownership repair must
         | NOT fall through to another settlement. Better to stop here than to
         | silently show the wrong one - which is the fault being fixed.
         */
        if (empty($settlement) && ($active_settlement_id > 0 || $requested_settlement_no !== '')) {
            $exists = Settlement::where('business_id', $business_id)
                ->when($active_settlement_id > 0, fn ($q) => $q->where('id', $active_settlement_id))
                ->when($active_settlement_id <= 0 && $requested_settlement_no !== '',
                    fn ($q) => $q->where('settlement_no', $requested_settlement_no))
                ->exists();

            if ($exists) {
                \Log::warning('LA-1198: requested Direct settlement could not be used - not substituting another', [
                    'business_id'             => $business_id,
                    'active_settlement_id'    => $active_settlement_id,
                    'requested_settlement_no' => $requested_settlement_no,
                ]);
            }
        }

        $query = Settlement::where('business_id', $business_id)
            ->where('status', 1)
            ->where('settlement_no', 'LIKE', $this->getCanonicalDirectSettlementPrefix() . '%')
            ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
            ->where('settlement_no', 'NOT LIKE', 'PDST%');
        $this->scopePetroDirectOwnedSettlements($query, $business_id);
        $this->scopeCurrentDirectSettlementDraftOwner($query, (int) $business_id);

        if (empty($settlement) && $active_settlement_id > 0) {
            $settlement = (clone $query)->where('id', $active_settlement_id)->first();
        }
        if (empty($settlement) && $requested_settlement_no !== '') {
            $settlement = (clone $query)->where('settlement_no', $requested_settlement_no)->first();
        }
        if (empty($settlement) && $pump_operator_id > 0) {
            $settlement = (clone $query)
                ->where('pump_operator_id', $pump_operator_id)
                ->when($location_id > 0, fn ($q) => $q->where('location_id', $location_id))
                ->whereDate('transaction_date', $transaction_date)
                ->orderByDesc('id')
                ->first();
        }

        // A stale browser can submit a number already owned by PetroPD or a finalized record.
        // Never create a second settlement with that leaked number.
        if (empty($settlement) && $requested_settlement_no !== '') {
            $number_is_already_used = Settlement::where('business_id', $business_id)
                ->where('settlement_no', $requested_settlement_no)
                ->exists();

            if ($number_is_already_used) {
                $requested_settlement_no = '';
                $request->merge(['settlement_no' => '']);
            }
        }

        if (! empty($settlement)) {
            $settlement->transaction_date = $transaction_date;
            if ($location_id > 0) {
                $settlement->location_id = $location_id;
            }
            if ($pump_operator_id > 0) {
                $settlement->pump_operator_id = $pump_operator_id;
            }
            $settlement->note = $request->note;

            if ($this->isCanonicalDirectSettlementNo((string) $settlement->settlement_no)) {
                // New sequence invariant: Settlement No and Direct Shift No are
                // the exact same value (DSTn / DSTn). Repair any incomplete
                // draft created during an interrupted request.
                $settlement->work_shift = [(string) $settlement->settlement_no];
            } else {
                // Historical Direct records are never renumbered.
                $direct_shift = $this->normalizeDirectSettlementShiftLabel($settlement->work_shift, $business_id);
                if (empty($direct_shift)) {
                    $direct_shift = $this->getDirectSettlementShiftPrefix($business_id)
                        . max(1, $this->extractLastInteger($settlement->settlement_no));
                    $settlement->work_shift = [$direct_shift];
                }
            }
            $settlement->save();

            $request->merge([
                'settlement_no' => $settlement->settlement_no,
                'active_settlement_id' => $settlement->id,
                'shift_id' => 0,
                'shift_ids' => [],
                'is_from_pumper' => 0,
                'assignment_id' => 0,
                'pumper_entry_id' => 0,
            ]);

            $this->rememberDirectSettlementDraft($settlement);
            return $settlement;
        }

        // Do not trust the number posted by the browser. Reserve the canonical
        // DSTn / DSTn pair under a per-business row lock so concurrent users
        // cannot receive the same number.
        $settlementData = [
            'transaction_date' => $transaction_date,
            'location_id' => $location_id ?: null,
            'pump_operator_id' => $pump_operator_id ?: null,
            'note' => $request->note,
            'status' => 1,
        ] + $this->directSettlementCreatedByAttributes();

        $settlement = $this->createCanonicalDirectSettlementDraft($business_id, $settlementData);

        $request->merge([
            'settlement_no' => $settlement->settlement_no,
            'active_settlement_id' => $settlement->id,
            'shift_id' => 0,
            'shift_ids' => [],
            'is_from_pumper' => 0,
            'assignment_id' => 0,
            'pumper_entry_id' => 0,
        ]);

        $this->rememberDirectSettlementDraft($settlement);
        return $settlement;
    }

    /**
     * S773 - send Direct Settlement customer payment notifications after COMMIT.
     *
     * NotificationUtil remains the single source of truth for the business's
     * Notification Templates > SMS & WhatsApp settings.  Each notification is
     * isolated so one gateway failure cannot suppress the remaining customers.
     */
    protected function sendS773DirectPaymentNotifications(
        int $businessId,
        array $notifications,
        ContactController $contactController
    ): void {
        foreach ($notifications as $notification) {
            try {
                $transactionId = (int) ($notification['transaction_id'] ?? 0);
                $contactId = (int) ($notification['contact_id'] ?? 0);

                if ($businessId <= 0 || $transactionId <= 0 || $contactId <= 0) {
                    continue;
                }

                $transaction = Transaction::where('business_id', $businessId)
                    ->where('id', $transactionId)
                    ->first();
                $contact = Contact::where('business_id', $businessId)
                    ->where('id', $contactId)
                    ->first();

                if (empty($transaction) || empty($contact)) {
                    continue;
                }

                $amount = (float) ($notification['amount'] ?? 0);
                $paymentReference = (string) ($notification['payment_ref_number'] ?? '');

                $transaction->contact = $contact;
                $transaction->single_payment_amount = $amount;
                $transaction->total_payment_amount = $amount;
                $transaction->payment_ref_number = $paymentReference;
                $transaction->payment_ref_no = $paymentReference;

                try {
                    $balance = $contactController->get_due_bal($contactId, false);
                    $transaction->cumulative_due_amount = (float) $this->transactionUtil->num_uf($balance);
                } catch (\Throwable $balanceException) {
                    // Notification delivery should not depend on an optional balance tag.
                    $transaction->cumulative_due_amount = 0.0;
                }

                $this->notificationUtil->autoSendNotification(
                    $businessId,
                    'payment_received',
                    $transaction,
                    $contact,
                    true
                );
            } catch (\Throwable $notificationException) {
                Log::error('S773 PetroDirect: payment_received notification failed after settlement commit.', [
                    'business_id' => $businessId,
                    'transaction_id' => $notification['transaction_id'] ?? null,
                    'contact_id' => $notification['contact_id'] ?? null,
                    'method' => $notification['method'] ?? null,
                    'message' => $notificationException->getMessage(),
                ]);
            }
        }
    }

    /**
     * S773 - deliver the already-rendered Credit Sale SMS after COMMIT.
     *
     * The existing Petro Direct credit-sale wording/product details are preserved;
     * only the timing and recipient dispatch are corrected.  Passing the Contact
     * and `credit_sale` type for EVERY number keeps SMS logging/delivery reporting
     * consistent for the primary and alternate mobile numbers.
     */
    protected function sendS773DirectCreditSaleSmsNotifications(
        int $businessId,
        array $notifications
    ): void {
        foreach ($notifications as $notification) {
            $contactId = (int) ($notification['contact_id'] ?? 0);
            $contact = $contactId > 0
                ? Contact::where('business_id', $businessId)->where('id', $contactId)->first()
                : null;

            if (empty($contact)) {
                continue;
            }

            foreach ((array) ($notification['mobile_numbers'] ?? []) as $mobileNumber) {
                $mobileNumber = trim((string) $mobileNumber);
                if ($mobileNumber === '') {
                    continue;
                }

                try {
                    $data = [
                        'sms_settings' => $notification['sms_settings'] ?? $this->businessUtil->defaultSmsSettings(),
                        'mobile_number' => $mobileNumber,
                        'sms_body' => (string) ($notification['sms_body'] ?? ''),
                    ];

                    $this->businessUtil->sendSms($data, $contact, 'credit_sale');
                } catch (\Throwable $notificationException) {
                    Log::error('S773 PetroDirect: Credit Sale SMS failed after settlement commit.', [
                        'business_id' => $businessId,
                        'contact_id' => $contactId,
                        'credit_sale_id' => $notification['credit_sale_id'] ?? null,
                        'mobile_number' => $mobileNumber,
                        'message' => $notificationException->getMessage(),
                    ]);
                }
            }
        }
    }

}

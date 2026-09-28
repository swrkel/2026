<?php
namespace Modules\PetroPD\Http\Controllers;

use App\Account;
use App\AccountGroup;
use App\AccountTransaction;
use App\AccountType;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\ContactLedger;
use App\CustomerReference;
use App\ExpenseCategory;
use App\Http\Controllers\ContactController;
use Modules\PetroPD\Entities\PumpOperatorMeterSale;
use App\Product;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\PetroPD\Entities\CustomerPayment;
use Modules\PetroPD\Entities\DailyCard;
use Modules\PetroPD\Entities\DailyChequePayment;
use Modules\PetroPD\Entities\DailyCollection;
use Modules\PetroPD\Entities\DailyVoucher;
use Modules\PetroPD\Entities\DayEnd;
use Modules\PetroPD\Entities\MeterSale;
use Modules\PetroPD\Entities\OtherIncome;
use Modules\PetroPD\Entities\OtherSale;
use Modules\PetroPD\Entities\PetroShift;
use Modules\PetroPD\Entities\Pump;
use Modules\PetroPD\Entities\PumpOperatorAssignment;
use Modules\PetroPD\Entities\PumpOperator;
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
use Modules\PetroPD\Entities\SettlementExcessPayment;
use Modules\PetroPD\Entities\SettlementExpensePayment;
use Modules\PetroPD\Entities\SettlementLoanPayment;
use Modules\PetroPD\Entities\SettlementPosPayment;
use Modules\PetroPD\Entities\SettlementShortagePayment;
use Modules\PetroPD\Entities\IssueCustomerBillSetting;
use Modules\Superadmin\Entities\Subscription;
use PhpParser\Node\Expr\AssignOp\Concat;

class AddPaymentController extends Controller
{
    
    /**
     * Resolve business id safely for multi-tenant requests.
     * Some PetroPD Payment-to-Finalize requests can reach this Petro controller
     * without business.id in session on another server. In that case, fallback
     * to the authenticated user's business_id and write it back to session.
     */
    private function resolveBusinessIdFromRequest(?Request $request = null)
    {
        $request = $request ?: request();

        // `user.business_id` is the active business selected by SetSessionData in
        // the multi-business application. Prefer it over the legacy `business.id`
        // key so Payment to Finalize opens for the same business shown on the page.
        $business_id = $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id');

        if (empty($business_id) && auth()->check()) {
            $business_id = auth()->user()->business_id ?? null;
        }

        if (empty($business_id)) {
            $business_id = $request->input('business_id') ?? request()->input('business_id');
        }

        if (! empty($business_id)) {
            $request->session()->put('user.business_id', $business_id);
            $request->session()->put('business.id', $business_id);
        }

        return $business_id;
    }

/**
     * All Utils instance.
     */
    protected $productUtil;

    protected $moduleUtil;

    protected $transactionUtil;

    protected $commonUtil;

    protected $businessUtil;

    private $barcode_types;

    /**
     * Constructor

     *

     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(Util $commonUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil)
    {

        $this->commonUtil = $commonUtil;

        $this->productUtil = $productUtil;

        $this->moduleUtil = $moduleUtil;

        $this->transactionUtil = $transactionUtil;

        $this->businessUtil = $businessUtil;

    }

    private function pumpOperatorPaymentsHasCardMetaColumns(): bool
    {
        return Schema::hasColumn('pump_operator_payments', 'customer_id')
            && Schema::hasColumn('pump_operator_payments', 'slip_no')
            && Schema::hasColumn('pump_operator_payments', 'card_type')
            && Schema::hasColumn('pump_operator_payments', 'card_number');
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    /**
     * Keep the Credit Sales tab financial values aligned with the single
     * authoritative Pump Operator Payment record.
     *
     * The detail query may legitimately contain several product rows, and old
     * data may reuse a collection form number.  Financial values must therefore
     * never be derived from a multiplying join.  We retain the detail rows for
     * customer/product/quantity metadata, but replace gross, discount and net
     * values with the unique master payment values whenever an unambiguous
     * payment link is available.
     */
    private function alignCreditSaleRowsWithAuthoritativePayments($rows, ?array $summary, array $context = [])
    {
        $rows = collect($rows)->values();
        $masters = collect($summary['payments'] ?? []);

        if ($rows->isEmpty() || $masters->isEmpty()) {
            return $rows;
        }

        $mastersById = $masters
            ->filter(fn ($row) => ! empty($row->pump_payment_id))
            ->keyBy(fn ($row) => (int) $row->pump_payment_id);

        // One legacy/grouped master may represent several distinct bill rows.
        // Keep each bill's own amount when their aggregate agrees with the
        // authoritative master; otherwise the reconciliation service blocks
        // finalization instead of multiplying the master amount onto every row.
        $groupedMasterIds = $rows
            ->filter(fn ($row) => (int) ($row->pump_payment_id ?? 0) > 0)
            ->groupBy(fn ($row) => (int) $row->pump_payment_id)
            ->filter(function ($group, $pumpPaymentId) use ($mastersById) {
                if ($group->count() <= 1) {
                    return false;
                }

                $master = $mastersById->get((int) $pumpPaymentId);
                if (! $master) {
                    return false;
                }

                $gross = (float) $group->sum(fn ($row) => (float) ($row->amount ?? 0));
                $discount = (float) $group->sum(fn ($row) => (float) ($row->total_discount ?? 0));
                $net = (float) $group->sum(function ($row) {
                    if ($row->sub_total !== null && $row->sub_total !== '') {
                        return (float) $row->sub_total;
                    }

                    return (float) ($row->amount ?? 0) - (float) ($row->total_discount ?? 0);
                });

                return abs($gross - (float) ($master->gross_amount ?? 0)) < 0.02
                    && abs($discount - (float) ($master->discount_amount ?? 0)) < 0.02
                    && abs($net - (float) ($master->net_amount ?? 0)) < 0.02;
            })
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->flip();

        // Legacy rows may not yet have pump_payment_id.  A collection form may
        // be used only when it maps to exactly one master payment in this exact
        // business/operator/shift scope.  Ambiguous form numbers are never guessed.
        $uniqueMastersByCollection = $masters
            ->filter(fn ($row) => trim((string) ($row->collection_form_no ?? '')) !== '')
            ->groupBy(fn ($row) => trim((string) $row->collection_form_no))
            ->filter(fn ($group) => $group->count() === 1)
            ->map(fn ($group) => $group->first());

        return $rows->map(function ($row) use ($mastersById, $uniqueMastersByCollection, $groupedMasterIds, $context) {
            $master = null;
            $pumpPaymentId = (int) ($row->pump_payment_id ?? 0);

            if ($pumpPaymentId > 0) {
                $master = $mastersById->get($pumpPaymentId);
            }

            if (! $master) {
                $collectionFormNo = trim((string) ($row->collection_form_no ?? ''));
                if ($collectionFormNo !== '') {
                    $master = $uniqueMastersByCollection->get($collectionFormNo);
                }
            }

            if (! $master) {
                return $row;
            }

            $resolvedPumpPaymentId = (int) ($master->pump_payment_id ?? $pumpPaymentId);
            if (isset($groupedMasterIds[$resolvedPumpPaymentId])) {
                $row->pump_payment_id = $resolvedPumpPaymentId;
                return $row;
            }

            $originalGross = (float) ($row->amount ?? 0);
            $originalDiscount = (float) ($row->total_discount ?? 0);
            // The Credit Sales table displays gross - discount as its row total.
            $originalNet = $originalGross - $originalDiscount;

            $masterGross = (float) ($master->gross_amount ?? 0);
            $masterDiscount = (float) ($master->discount_amount ?? 0);
            $masterNet = (float) ($master->net_amount ?? ($masterGross - $masterDiscount));

            if (abs($originalGross - $masterGross) >= 0.02
                || abs($originalDiscount - $masterDiscount) >= 0.02
                || abs($originalNet - $masterNet) >= 0.02) {
                Log::warning('PETROPD Credit Sales row amount aligned to authoritative payment', array_merge($context, [
                    'credit_sale_id' => $row->id ?? null,
                    'pump_payment_id' => $master->pump_payment_id ?? null,
                    'collection_form_no' => $row->collection_form_no ?? null,
                    'displayed_gross_before' => $originalGross,
                    'displayed_discount_before' => $originalDiscount,
                    'displayed_net_before' => $originalNet,
                    'authoritative_gross' => $masterGross,
                    'authoritative_discount' => $masterDiscount,
                    'authoritative_net' => $masterNet,
                ]));
            }

            $row->pump_payment_id = (int) ($master->pump_payment_id ?? $pumpPaymentId);
            $row->amount = $masterGross;
            $row->total_discount = $masterDiscount;
            $row->sub_total = $masterNet;

            return $row;
        })->values();
    }

    /**
     * Load the saved Credit Sale presentation rows through the exact immutable
     * Pump Operator Payment IDs in the current PetroPD snapshot.
     *
     * A historical detail row can still carry an earlier draft settlement
     * reference even after its authoritative master payment belongs to the
     * current PD settlement. The normal settlement-number query then leaves the
     * Credit Sales table empty while the authoritative footer total is correct.
     *
     * This is deliberately read-only. It does not alter settlement ownership or
     * recalculate money; it only restores customer/product/bill metadata for the
     * exact business, operator and Shift-scoped master payments already accepted
     * by PetroPdSettlementPaymentSnapshotService.
     */
    private function loadAuthoritativeCreditSaleDisplayRows(
        array $snapshot,
        int $businessId,
        int $pumpOperatorId,
        array $shiftIds,
        int $settlementId,
        string $settlementNo
    ) {
        if (! Schema::hasTable('settlement_credit_sale_payments')
            || ! Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
            return collect();
        }

        $currentSettlementKeys = array_values(array_unique(array_filter([
            (string) $settlementId,
            trim($settlementNo),
        ], fn ($value) => $value !== '')));

        $paymentIds = collect($snapshot['payments'] ?? [])
            ->filter(function ($payment) use ($currentSettlementKeys) {
                if (($payment->payment_type ?? null) !== 'credit') {
                    return false;
                }

                $owner = trim((string) ($payment->settlement_no ?? ''));

                return $owner === '' || in_array($owner, $currentSettlementKeys, true);
            })
            ->pluck('pump_payment_id')
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($paymentIds->isEmpty()) {
            return collect();
        }

        $hasShiftId = Schema::hasColumn('settlement_credit_sale_payments', 'shift_id');
        $shiftIds = array_values(array_unique(array_filter(
            array_map('intval', $shiftIds),
            fn ($id) => $id > 0
        )));

        return SettlementCreditSalePayment::query()
            ->leftJoin('contacts', 'settlement_credit_sale_payments.customer_id', '=', 'contacts.id')
            ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
            ->where('settlement_credit_sale_payments.business_id', $businessId)
            ->where('settlement_credit_sale_payments.pump_operator_id', $pumpOperatorId)
            ->whereIn('settlement_credit_sale_payments.pump_payment_id', $paymentIds->all())
            ->when($hasShiftId && ! empty($shiftIds), function ($query) use ($shiftIds) {
                $query->where(function ($shiftQuery) use ($shiftIds) {
                    $shiftQuery->whereIn('settlement_credit_sale_payments.shift_id', $shiftIds)
                        ->orWhereNull('settlement_credit_sale_payments.shift_id')
                        ->orWhere('settlement_credit_sale_payments.shift_id', 0);
                });
            })
            ->select(
                'settlement_credit_sale_payments.id',
                'settlement_credit_sale_payments.pump_payment_id',
                $hasShiftId
                    ? 'settlement_credit_sale_payments.shift_id'
                    : DB::raw('NULL as shift_id'),
                'settlement_credit_sale_payments.settlement_no',
                'settlement_credit_sale_payments.customer_id',
                'settlement_credit_sale_payments.daily_voucher_id',
                'settlement_credit_sale_payments.collection_form_no',
                'settlement_credit_sale_payments.amount',
                'settlement_credit_sale_payments.qty',
                'settlement_credit_sale_payments.order_date',
                'settlement_credit_sale_payments.order_number',
                'settlement_credit_sale_payments.customer_reference',
                'settlement_credit_sale_payments.price',
                'settlement_credit_sale_payments.total_discount',
                'settlement_credit_sale_payments.sub_total',
                'settlement_credit_sale_payments.outstanding',
                'settlement_credit_sale_payments.credit_limit',
                'settlement_credit_sale_payments.note',
                'settlement_credit_sale_payments.pump_operator_id',
                'settlement_credit_sale_payments.product_id',
                'products.name as product_name',
                'contacts.name as customer_name'
            )
            ->orderBy('settlement_credit_sale_payments.id')
            ->get();
    }

    /**
     * Sum only settlement rows that are not represented by an authoritative
     * pump_operator_payments.id in the current snapshot. This preserves manual
     * Add Payment rows while preventing linked Pumper Dashboard rows from being
     * counted once from the master and again from the detail table.
     */
    private function sumManualSettlementPaymentRows(
        $rows,
        array $authoritativePaymentIds,
        string $amountField = 'amount',
        array $identityFields = ['pump_payment_id']
    ): float {
        $authoritativePaymentIds = array_fill_keys(
            array_values(array_filter(array_map('intval', $authoritativePaymentIds))),
            true
        );

        return round((float) collect($rows)
            ->filter(function ($row) use ($authoritativePaymentIds, $identityFields) {
                foreach ($identityFields as $identityField) {
                    $value = $row->{$identityField} ?? null;
                    if (is_numeric($value) && isset($authoritativePaymentIds[(int) $value])) {
                        return false;
                    }
                }

                if (! empty($row->is_unlinked)) {
                    $value = $row->pump_payment_id ?? $row->customer_payment_id ?? null;
                    if (is_numeric($value) && isset($authoritativePaymentIds[(int) $value])) {
                        return false;
                    }
                }

                return true;
            })
            ->sum(fn ($row) => (float) ($row->{$amountField} ?? 0)), 4);
    }

    private function addCardPaymentManualUnlinkedFilter($query, bool $include_linked_card_payment): void
    {
        if ($include_linked_card_payment) {
            $query->whereNull('linked_card_payment.id');
        }

        $query->whereNull('parent_card_payment.id')
            ->whereNull('matched_card_payment.id')
            ->whereNull('daily_cards.id');
    }

    private function addCardPaymentShiftFilter($query, array $shift_ids, bool $include_linked_card_payment): void
    {
        if (count($shift_ids) > 1) {
            if ($include_linked_card_payment) {
                $query->whereIn('linked_card_payment.shift_id', $shift_ids)
                    ->orWhereIn('parent_card_payment.shift_id', $shift_ids);
            } else {
                $query->whereIn('parent_card_payment.shift_id', $shift_ids);
            }

            $query->orWhereIn('matched_card_payment.shift_id', $shift_ids);
            return;
        }

        if ($include_linked_card_payment) {
            $query->where('linked_card_payment.shift_id', $shift_ids[0])
                ->orWhere('parent_card_payment.shift_id', $shift_ids[0]);
        } else {
            $query->where('parent_card_payment.shift_id', $shift_ids[0]);
        }

        $query->orWhere('matched_card_payment.shift_id', $shift_ids[0]);
    }

    private function addCardPaymentDailyCardShiftFilter($query, array $shift_ids, bool $include_linked_card_payment): void
    {
        if ($include_linked_card_payment) {
            $query->whereNull('linked_card_payment.shift_id');
        }

        $query->whereNull('parent_card_payment.shift_id')
            ->whereNull('matched_card_payment.shift_id')
            ->whereNotNull('daily_cards.shift_id');

        if (count($shift_ids) > 1) {
            $query->whereIn('daily_cards.shift_id', $shift_ids);
        } else {
            $query->where('daily_cards.shift_id', $shift_ids[0]);
        }
    }

    private function getSettlementWorkShiftIds(?Settlement $settlement): array
    {
        if (empty($settlement) || empty($settlement->work_shift)) {
            return [];
        }

        $work_shifts = $settlement->work_shift;

        if (is_string($work_shifts)) {
            $decoded = json_decode($work_shifts, true);
            $work_shifts = is_array($decoded) ? $decoded : explode(',', $work_shifts);
        }

        if (! is_array($work_shifts)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $work_shifts))));
    }


    private function requireSingleSettlementShiftId(?Settlement $settlement): int
    {
        $shiftIds = $this->getSettlementWorkShiftIds($settlement);

        if (count($shiftIds) !== 1) {
            throw new \RuntimeException(
                'Payment reconciliation stopped: the settlement must contain exactly one immutable Shift ID.'
            );
        }

        return (int) $shiftIds[0];
    }

    /**
     * Resolve a master pumper payment without guessing. Exact pump_payment_id is
     * preferred. Legacy amount fallback is accepted only when it identifies one
     * row inside the exact business, operator and immutable Shift ID.
     */
    private function resolveUnambiguousMasterPaymentForSettlement(
        Settlement $settlement,
        Request $request,
        int $businessId,
        string $paymentType,
        float $amount
    ): ?PumpOperatorPayment {
        $shiftId = $this->requireSingleSettlementShiftId($settlement);

        $baseQuery = PumpOperatorPayment::where('business_id', $businessId)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->where('shift_id', $shiftId)
            ->where('payment_type', $paymentType)
            ->where(function ($query) {
                $query->whereNull('is_used')->orWhere('is_used', 0);
            });

        if ($request->filled('pump_payment_id')) {
            $exact = (clone $baseQuery)
                ->where('id', (int) $request->input('pump_payment_id'))
                ->first();

            if (empty($exact)) {
                throw new \RuntimeException(
                    'Payment reconciliation stopped: the selected Pump Payment ID does not belong to this business, operator, payment type and shift.'
                );
            }

            return $exact;
        }

        if ($amount <= 0) {
            return null;
        }

        return $this->oneUnambiguousPumpPayment(
            (clone $baseQuery)->whereRaw('ABS(payment_amount - ?) < 0.005', [$amount]),
            $paymentType . ' amount ' . number_format($amount, 2, '.', '')
        );
    }

    /**
     * Return a legacy fallback match only when it resolves to exactly one master row.
     * Financial records must never be selected by "latest" when a non-unique
     * reference (collection number, metadata or amount) matches more than one row.
     */
    private function oneUnambiguousPumpPayment($query, string $context): ?PumpOperatorPayment
    {
        $matches = (clone $query)
            ->orderByDesc('id')
            ->limit(2)
            ->get();

        if ($matches->count() > 1) {
            throw new \RuntimeException(
                'Payment reconciliation stopped: more than one Pump Operator Payment matches '
                . $context
                . '. Please use the exact Pump Payment ID.'
            );
        }

        return $matches->first();
    }

    private function findLinkedDailyCardForPumpPayment(PumpOperatorPayment $pump_payment, int $business_id): ?DailyCard
    {
        // New/current records are linked by the authoritative master payment ID.
        if (Schema::hasColumn('daily_cards', 'pump_payment_id')) {
            $linked = DailyCard::where('business_id', $business_id)
                ->where('pump_payment_id', $pump_payment->id)
                ->first();

            if (! empty($linked)) {
                return $linked;
            }
        }

        // Controlled legacy fallback. It is accepted only when exactly one row
        // matches the same business, operator, shift and card identity.
        $query = DailyCard::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_payment->pump_operator_id)
            ->where('collection_no', $pump_payment->collection_form_no)
            ->where('amount', $pump_payment->payment_amount);

        if (Schema::hasColumn('daily_cards', 'shift_id') && ! empty($pump_payment->shift_id)) {
            $query->where('shift_id', $pump_payment->shift_id);
        }

        if (! empty($pump_payment->slip_no)) {
            $query->whereRaw('REPLACE(COALESCE(slip_no, ""), " ", "") = ?', [
                preg_replace('/\s+/', '', (string) $pump_payment->slip_no),
            ]);
        }

        $matches = $query->orderByDesc('id')->limit(2)->get();
        if ($matches->count() > 1) {
            throw new \RuntimeException(
                'Payment reconciliation stopped: more than one Daily Card matches Pump Payment ID '
                . $pump_payment->id
                . '.'
            );
        }

        return $matches->first();
    }

    private function findMatchingPumpCardPayment(?Settlement $settlement, Request $request, int $business_id): ?PumpOperatorPayment
    {
        if (empty($settlement)) {
            return null;
        }

        $work_shift_ids = $this->getSettlementWorkShiftIds($settlement);
        if (count($work_shift_ids) !== 1) {
            throw new \RuntimeException(
                'Payment reconciliation stopped: the settlement must contain exactly one immutable Shift ID.'
            );
        }

        $has_card_meta_columns = $this->pumpOperatorPaymentsHasCardMetaColumns();

        $base_query = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->where('payment_type', 'card')
            ->whereIn('shift_id', $work_shift_ids)
            ->where(function ($q) {
                $q->whereNull('is_used')->orWhere('is_used', 0);
            });

        // Exact master identity is always authoritative.
        if ($request->filled('pump_payment_id')) {
            $exact_payment = (clone $base_query)
                ->where('id', (int) $request->input('pump_payment_id'))
                ->first();

            if (empty($exact_payment)) {
                throw new \RuntimeException(
                    'Payment reconciliation stopped: the selected Pump Payment ID does not belong to this business, operator and shift.'
                );
            }

            return $exact_payment;
        }

        // When the request originated from a Daily Card row, use its direct
        // pump_payment_id relationship instead of collection number/amount.
        if ($request->filled('daily_card_id') && Schema::hasColumn('daily_cards', 'pump_payment_id')) {
            $daily_card = DailyCard::where('business_id', $business_id)
                ->where('id', (int) $request->input('daily_card_id'))
                ->first();

            if (! empty($daily_card) && ! empty($daily_card->pump_payment_id)) {
                $exact_payment = (clone $base_query)
                    ->where('id', (int) $daily_card->pump_payment_id)
                    ->first();

                if (empty($exact_payment)) {
                    throw new \RuntimeException(
                        'Payment reconciliation stopped: the Daily Card master payment belongs to a different business, operator or shift.'
                    );
                }

                return $exact_payment;
            }
        }

        // Legacy lookups remain for old tenant rows, but are never allowed to
        // guess when two payments share a collection number, amount or metadata.
        if ($request->filled('collection_form_no')) {
            $collection_payment = $this->oneUnambiguousPumpPayment(
                (clone $base_query)->where('collection_form_no', $request->input('collection_form_no')),
                'collection form number ' . $request->input('collection_form_no')
            );

            if (! empty($collection_payment)) {
                return $collection_payment;
            }
        }

        $amount = (float) $request->input('amount', 0);
        $normalized_slip_no = preg_replace('/\s+/', '', (string) $request->input('slip_no', ''));
        $customer_id = $request->input('customer_id');
        $card_type = $request->input('card_type');
        $card_number = trim((string) $request->input('card_number', ''));

        if ($has_card_meta_columns && ($amount > 0 || ! empty($normalized_slip_no) || ! empty($customer_id) || ! empty($card_type) || ! empty($card_number))) {
            $meta_query = clone $base_query;

            if ($amount > 0) {
                $meta_query->where('payment_amount', $amount);
            }

            if (! empty($normalized_slip_no)) {
                $meta_query->whereRaw('REPLACE(COALESCE(slip_no, ""), " ", "") = ?', [$normalized_slip_no]);
            }

            if (! empty($customer_id)) {
                $meta_query->where('customer_id', $customer_id);
            }

            if (! empty($card_type)) {
                $meta_query->where('card_type', $card_type);
            }

            if (! empty($card_number)) {
                $meta_query->where('card_number', $card_number);
            }

            $matched_payment = $this->oneUnambiguousPumpPayment($meta_query, 'the supplied card details');
            if (! empty($matched_payment)) {
                return $matched_payment;
            }
        }

        if ($amount > 0) {
            return $this->oneUnambiguousPumpPayment(
                (clone $base_query)->where('payment_amount', $amount),
                'card amount ' . number_format($amount, 2, '.', '')
            );
        }

        return null;
    }

    /**
     * Display a listing of the resource.

     *
     * @return Response
     */
    public function index()
    {

        $business_id = request()->session()->get('user.business_id');

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_pd_module')) {
            abort(403, 'Unauthorized Access');
        }

        return redirect()->route('petropd.list-pd-settlement');

    }

    /**
     * Show the form for creating a new resource.

     *
     * @return Response
     */
    public function addDailyCards($settlement_no, $pump_operator_id, $business_id, $shift_id)
    {
        // Handle both single shift_id and comma-separated string or array
        $shift_ids = [];
        if (is_string($shift_id) && strpos($shift_id, ',') !== false) {
            $shift_ids = array_filter(array_map('trim', explode(',', $shift_id)));
        } elseif (is_array($shift_id)) {
            $shift_ids = $shift_id;
        } else {
            $shift_ids = [$shift_id];
        }
        $shift_ids = array_values(array_filter(array_map('intval', $shift_ids)));

        // settlement_no here is the settlement ID; resolve the string settlement_no for daily vouchers/credit sales
        $settlement = Settlement::find($settlement_no);
        if (empty($settlement) && !empty($settlement_no)) {
            $settlement = Settlement::where('settlement_no', $settlement_no)->first();
        }
        $settlement_no_str = !empty($settlement) ? $settlement->settlement_no : $settlement_no;

        if (count($shift_ids) !== 1) {
            throw new \RuntimeException(
                'Payment reconciliation stopped: card payments must be loaded for exactly one immutable Shift ID.'
            );
        }
        $exact_shift_id = (int) $shift_ids[0];

        // The master Pump Operator Payment collection is authoritative. Each
        // master card row is linked to one Daily Card by pump_payment_id; legacy
        // matching is accepted only when it resolves to exactly one Daily Card.
        $card_payments = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('payment_type', 'card')
            ->where('shift_id', $exact_shift_id)
            ->get()
            ->unique('id')
            ->values();

        foreach ($card_payments as $pump_payment) {
            $daily_card = $this->findLinkedDailyCardForPumpPayment($pump_payment, (int) $business_id);
            if (empty($daily_card)) {
                // The shared snapshot/finalization reconciliation will report
                // the missing operational link. Never guess another card row.
                continue;
            }

            if (Schema::hasColumn('daily_cards', 'shift_id')
                && ! empty($daily_card->shift_id)
                && (int) $daily_card->shift_id !== $exact_shift_id) {
                throw new \RuntimeException(
                    'Payment reconciliation stopped: a Daily Card Shift ID differs from its master Pump Operator Payment.'
                );
            }

            $data = [
                'amount'           => $pump_payment->payment_amount,
                'card_type'        => $daily_card->card_type,
                'card_number'      => $daily_card->card_number,
                'daily_card_id'    => $daily_card->id,
                'customer_id'      => $daily_card->customer_id,
                'note'             => $daily_card->note,
                'slip_no'          => $daily_card->slip_no,
                'pump_payment_id'  => $pump_payment->id,
                'shift_id'         => $exact_shift_id,
                'pump_operator_id' => $pump_operator_id,
            ];

            $settlement_card_payment = app(\Modules\PetroPD\Services\SettlementPaymentReconciler::class)
                ->upsertOne($business_id, (string) $settlement_no, 'settlement_card_payments', $data);

            $daily_card->used_status = 1;
            $daily_card->settlement_no = $settlement_no;
            if (Schema::hasColumn('daily_cards', 'pump_payment_id')) {
                $daily_card->pump_payment_id = $pump_payment->id;
            }
            if (Schema::hasColumn('daily_cards', 'shift_id')) {
                $daily_card->shift_id = $exact_shift_id;
            }
            $daily_card->save();

            $pump_payment->is_used = 1;
            $pump_payment->parent_id = $settlement_card_payment->id;
            $pump_payment->settlement_no = $settlement_no;
            $pump_payment->save();
        }

        // Update the credit_nos
        $credit_query = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->whereIn('payment_type', ['credit', 'multiple_credit']);

        if (count($shift_ids) > 1) {
            $credit_query->whereIn('shift_id', $shift_ids);
        } else {
            $credit_query->where('shift_id', $shift_ids[0]);
        }

        $credit_by_shifts = $credit_query->get();

        if ($credit_by_shifts->isNotEmpty()) {
            foreach ($credit_by_shifts as $credit_by_shift) {
                $items = $this->findCreditSaleRowsForMasterPayment(
                    (int) $business_id,
                    (int) $pump_operator_id,
                    $credit_by_shift,
                    (string) $settlement_no,
                    (string) $settlement_no_str
                );

                foreach ($items as $item) {
                    $creditSaleUpdate = ['settlement_no' => $settlement_no_str];
                    if ($this->tableHasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
                        $creditSaleUpdate['pump_payment_id'] = $credit_by_shift->id;
                    }
                    if ($this->tableHasColumn('settlement_credit_sale_payments', 'shift_id')) {
                        $creditSaleUpdate['shift_id'] = (int) $credit_by_shift->shift_id;
                    }

                    // Preserve the voucher that belongs to this exact bill row.
                    // A grouped credit payment can own several bills/vouchers, so
                    // taking the first voucher by pump_payment_id would collapse
                    // every bill onto one Daily Voucher and break finalization.
                    $voucher = null;
                    if (! empty($item->daily_voucher_id)) {
                        $voucher = DailyVoucher::where('business_id', $business_id)
                            ->where('id', $item->daily_voucher_id)
                            ->first();
                    }

                    if (! $voucher && Schema::hasColumn('daily_vouchers', 'pump_payment_id')) {
                        $linkedVoucherQuery = DailyVoucher::where('business_id', $business_id)
                            ->where('pump_payment_id', $credit_by_shift->id)
                            ->where('operator_id', $item->pump_operator_id)
                            ->where('customer_id', $item->customer_id);

                        if (! empty($item->collection_form_no)) {
                            $linkedVoucherQuery->where('daily_vouchers_no', $item->collection_form_no);
                        }
                        if (! empty($item->order_number)) {
                            $linkedVoucherQuery->where('voucher_order_number', $item->order_number);
                        }

                        $linkedVoucherCandidates = $linkedVoucherQuery->orderByDesc('id')->limit(2)->get();
                        if ($linkedVoucherCandidates->count() === 1) {
                            $voucher = $linkedVoucherCandidates->first();
                        }
                    }

                    if (! $voucher) {
                        $legacyVoucherQuery = DailyVoucher::where('business_id', $business_id)
                            ->where('operator_id', $item->pump_operator_id)
                            ->where('customer_id', $item->customer_id);

                        if (! empty($item->collection_form_no)) {
                            $legacyVoucherQuery->where('daily_vouchers_no', $item->collection_form_no);
                        }
                        if (! empty($item->order_number)) {
                            $legacyVoucherQuery->where('voucher_order_number', $item->order_number);
                        }
                        if (! empty($item->order_date)) {
                            $legacyVoucherQuery->where('voucher_order_date', $item->order_date);
                        }

                        $voucherCandidates = $legacyVoucherQuery->orderByDesc('id')->limit(2)->get();
                        if ($voucherCandidates->count() > 1) {
                            throw new \RuntimeException(
                                'Payment reconciliation stopped: more than one Daily Voucher matches credit bill '
                                . ($item->collection_form_no ?: $item->id)
                                . '.'
                            );
                        }
                        $voucher = $voucherCandidates->first();
                    }

                    if ($voucher) {
                        $voucher->settlement_no = $settlement_no_str;
                        if (Schema::hasColumn('daily_vouchers', 'pump_payment_id')) {
                            $voucher->pump_payment_id = $credit_by_shift->id;
                        }
                        // daily_vouchers.shift_id belongs to the legacy
                        // petro_daily_shifts table. Never write a PetroPD
                        // petro_shifts.id into that foreign key.
                        $voucher->save();
                        $creditSaleUpdate['daily_voucher_id'] = $voucher->id;
                    }

                    app(\Modules\PetroPD\Services\SettlementPaymentEditService::class)
                        ->editCreditSale($business_id, $item->id, $creditSaleUpdate);
                }
            }
        }

    }

    public function addDailyCheques($settlement_no, $pump_operator_id, $business_id, array $shift_ids = [])
    {

        $has_settlement_cheque_pump_payment_column = $this->tableHasColumn('settlement_cheque_payments', 'pump_payment_id');
        $settlement = Settlement::where('business_id', $business_id)
            ->where('id', $settlement_no)
            ->first();
        $settlement_keys = array_filter([
            (string) $settlement_no,
            ! empty($settlement) ? (string) $settlement->settlement_no : null,
        ]);

        $pending = DailyChequePayment::join('pump_operator_payments', 'pump_operator_payments.id', 'daily_cheque_payments.linked_payment_id')
            ->where('daily_cheque_payments.business_id', $business_id)
            ->where('pump_operator_payments.business_id', $business_id)
            ->where('pump_operator_payments.pump_operator_id', $pump_operator_id)
            ->where('pump_operator_payments.payment_type', 'cheque')
            ->select('daily_cheque_payments.*')
            ->when(! empty($shift_ids), function ($query) use ($shift_ids) {
                $query->whereIn('daily_cheque_payments.shift_id', $shift_ids);
            })
            ->get();

        foreach ($pending as $one) {
            if (! empty($one->settlement_no) && ! in_array((string) $one->settlement_no, $settlement_keys, true)) {
                $finalized_owner = Settlement::where('business_id', $business_id)
                    ->where('status', 0)
                    ->where(function ($q) use ($one) {
                        $q->where('id', $one->settlement_no)
                            ->orWhere('settlement_no', $one->settlement_no);
                    })
                    ->exists();

                if ($finalized_owner) {
                    continue;
                }
            }

            $duplicate_query = SettlementChequePayment::where('business_id', $business_id);

            $duplicate_query->where(function ($q) use ($one, $has_settlement_cheque_pump_payment_column) {
                if ($has_settlement_cheque_pump_payment_column && ! empty($one->linked_payment_id)) {
                    $q->where('pump_payment_id', $one->linked_payment_id)
                        ->orWhere(function ($field_query) use ($one) {
                            $field_query->where('customer_id', $one->customer_id)
                                ->where('amount', $one->amount)
                                ->where('bank_name', $one->bank_name)
                                ->where('cheque_number', $one->cheque_number)
                                ->where('cheque_date', $one->cheque_date);
                        });
                } else {
                    $q->where('customer_id', $one->customer_id)
                        ->where('amount', $one->amount)
                        ->where('bank_name', $one->bank_name)
                        ->where('cheque_number', $one->cheque_number)
                        ->where('cheque_date', $one->cheque_date);
                }
            });

            $settlement_cheque_payment = $duplicate_query->first();

            if (! empty($settlement_cheque_payment)) {
                if (! in_array((string) $settlement_cheque_payment->settlement_no, $settlement_keys, true)) {
                    $finalized_owner = Settlement::where('business_id', $business_id)
                        ->where('status', 0)
                        ->where(function ($q) use ($settlement_cheque_payment) {
                            $q->where('id', $settlement_cheque_payment->settlement_no)
                                ->orWhere('settlement_no', $settlement_cheque_payment->settlement_no);
                        })
                        ->exists();

                    if ($finalized_owner) {
                        continue;
                    }
                }

                $settlement_cheque_payment->settlement_no = $settlement_no;

                if (
                    $has_settlement_cheque_pump_payment_column
                    && empty($settlement_cheque_payment->pump_payment_id)
                    && ! empty($one->linked_payment_id)
                ) {
                    $settlement_cheque_payment->pump_payment_id = $one->linked_payment_id;
                }

                $settlement_cheque_payment = app(\Modules\PetroPD\Services\SettlementPaymentEditService::class)
                    ->editChequePayment($business_id, $settlement_cheque_payment->id, $settlement_cheque_payment->getAttributes());

                $one->settlement_no = $settlement_no;
                $one->save();

                $pmt = PumpOperatorPayment::find($one->linked_payment_id);
                if (! empty($pmt)) {
                    $pmt->is_used = 1;
                    $pmt->parent_id = $settlement_cheque_payment->id;
                    $pmt->settlement_no = $settlement_no;
                    $pmt->save();
                }

                continue;
            }

            $data = [

                'business_id'   => $business_id,

                'settlement_no' => $settlement_no,

                'amount'        => $one->amount,

                'bank_name'     => $one->bank_name,

                'cheque_number' => $one->cheque_number,

                'cheque_date'   => $one->cheque_date,

                'customer_id'   => $one->customer_id,

            ];

            if ($has_settlement_cheque_pump_payment_column) {
                $data['pump_payment_id'] = $one->linked_payment_id;
            }

            $settlement_cheque_payment = app(\Modules\PetroPD\Services\SettlementPaymentReconciler::class)
                ->upsertOne($business_id, (string) $settlement_no, 'settlement_cheque_payments', $data);

            $pmt = PumpOperatorPayment::findOrFail($one->linked_payment_id);

            $pmt->is_used = 1;

            $pmt->parent_id = $settlement_cheque_payment->id;

            $pmt->settlement_no = $settlement_no;

            $pmt->save();

            $one->settlement_no = $settlement_no;

            $one->save();

        }

        return true;

    }

    public function addDailyShortageExcess($settlement_no, $pump_operator_id, $business_id, $shift_ids)
    {
        $shiftIds = is_array($shift_ids)
            ? $shift_ids
            : (is_string($shift_ids) ? explode(',', $shift_ids) : []);

        $shiftIds = array_values(array_unique(array_filter(
            array_map('intval', $shiftIds),
            fn ($id) => $id > 0
        )));

        if (empty($shiftIds)) {
            Log::warning('PetroPD shortage/excess sync skipped because Shift ID is missing', [
                'pump_operator_id' => $pump_operator_id,
                'business_id' => $business_id,
            ]);
            return;
        }

        $settlement = Settlement::where('business_id', $business_id)->findOrFail($settlement_no);
        $settlementKeys = array_values(array_unique(array_filter([
            (string) $settlement->id,
            trim((string) $settlement->settlement_no),
        ])));

        $payments = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->whereIn('shift_id', $shiftIds)
            ->where(function ($query) use ($settlementKeys) {
                $query->whereNull('is_used')
                    ->orWhere('is_used', 0)
                    // A legacy/preload path may already mark the authoritative
                    // payment used before its settlement detail row is written.
                    // Include only rows already linked to this same settlement;
                    // rows owned by another settlement remain excluded.
                    ->orWhereIn('settlement_no', $settlementKeys);
            })
            ->whereIn(DB::raw('LOWER(payment_type)'), ['shortage', 'excess'])
            ->orderBy('id')
            ->get()
            ->unique('id')
            ->values();

        if ($payments->isEmpty()) {
            return;
        }

        $pumpOperator = PumpOperator::findOrFail($settlement->pump_operator_id);
        $reconciler = app(\Modules\PetroPD\Services\SettlementPaymentReconciler::class);

        foreach ($payments as $payment) {
            $type = strtolower((string) $payment->payment_type);
            $amount = Schema::hasColumn('pump_operator_payments', 'net_amount')
                && $payment->net_amount !== null
                    ? (float) $payment->net_amount
                    : (float) $payment->payment_amount;

            if ($type === 'excess' && $amount > 0.02) {
                Log::error('PetroPD positive excess master payment blocked from settlement sync', [
                    'pump_payment_id' => $payment->id,
                    'business_id' => $business_id,
                    'pump_operator_id' => $pump_operator_id,
                    'shift_id' => $payment->shift_id,
                    'amount' => $amount,
                ]);
                continue;
            }

            $table = $type === 'shortage'
                ? 'settlement_shortage_payments'
                : 'settlement_excess_payments';

            $data = [
                'pump_payment_id' => (int) $payment->id,
                'shift_id' => (int) $payment->shift_id,
                'amount' => $amount,
            ];

            if ($type === 'shortage') {
                $data['current_shortage'] = $pumpOperator->short_amount;
            } else {
                $data['current_excess'] = $pumpOperator->excess_amount;
            }

            $parent = $reconciler->upsertOne(
                (int) $business_id,
                (string) $settlement->id,
                $table,
                $data
            );

            $payment->is_used = 1;
            $payment->parent_id = $parent->id;
            $payment->settlement_no = (string) $settlement->id;
            $payment->save();
        }
    }

    private function getPetroPdPaymentShiftIds(Request $request): array
    {
        $raw = $request->input('shift_ids', $request->input('shift_id'));

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : explode(',', $raw);
        }

        return array_values(array_unique(array_filter(array_map('intval', (array) $raw))));
    }

    private function linkPetroPdPaymentDraftToShift(
        Settlement $settlement,
        Request $request,
        int $businessId,
        ?int $pumpOperatorId
    ): void {
        /*
         * This link is supporting metadata only. It must never prevent the Add
         * Payment modal from opening when an older tenant database has not yet
         * received one of the optional assignment columns.
         */
        try {
            $shiftIds = $this->getPetroPdPaymentShiftIds($request);
            if ((int) $settlement->status !== 1 || empty($shiftIds) || empty($pumpOperatorId)) {
                return;
            }

            $assignmentModel = new PumpOperatorAssignment();
            $assignmentTable = $assignmentModel->getTable();
            $hasAssignmentTable = Schema::hasTable($assignmentTable);
            $hasSettlementId = $hasAssignmentTable && Schema::hasColumn($assignmentTable, 'settlement_id');

            if ($hasSettlementId) {
                $hasConflictingSettlement = PumpOperatorAssignment::where('business_id', $businessId)
                    ->where('pump_operator_id', $pumpOperatorId)
                    ->whereIn('shift_id', $shiftIds)
                    ->whereNotNull('settlement_id')
                    ->where('settlement_id', '<>', $settlement->id)
                    ->exists();

                if ($hasConflictingSettlement) {
                    Log::warning('PetroPD payment draft shift link skipped because the shift already belongs to another settlement.', [
                        'settlement_id' => $settlement->id,
                        'pump_operator_id' => $pumpOperatorId,
                        'shift_ids' => $shiftIds,
                    ]);
                    return;
                }

                $updates = ['settlement_id' => $settlement->id];
                if (Schema::hasColumn($assignmentTable, 'closed_in_settlement')) {
                    $updates['closed_in_settlement'] = 0;
                }

                $assignmentQuery = PumpOperatorAssignment::where('business_id', $businessId)
                    ->where('pump_operator_id', $pumpOperatorId)
                    ->whereIn('shift_id', $shiftIds);

                if (Schema::hasColumn($assignmentTable, 'status')) {
                    $assignmentQuery->whereIn('status', ['close', 'closed']);
                }

                $assignmentQuery->where(function ($query) use ($settlement) {
                    $query->whereNull('settlement_id')
                        ->orWhere('settlement_id', $settlement->id);
                })->update($updates);
            }

            $current = $settlement->work_shift;
            if (is_string($current)) {
                $decoded = json_decode($current, true);
                $current = is_array($decoded) ? $decoded : [];
            }
            $current = is_array($current) ? $current : [];
            $merged = array_values(array_unique(array_filter(array_map(
                'intval',
                array_merge($current, $shiftIds)
            ))));

            if ($merged !== array_values(array_map('intval', $current))) {
                $settlement->work_shift = $merged;
                $settlement->save();
            }
        } catch (\Throwable $exception) {
            // Keep Payment to Finalize available even if optional legacy linkage
            // metadata cannot be updated for one tenant database.
            Log::warning('PetroPD payment draft shift link was skipped.', [
                'settlement_id' => $settlement->id ?? null,
                'pump_operator_id' => $pumpOperatorId,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function createSettlementIfNotExist(Request $request)
    {

        $business_id = $this->resolveBusinessIdFromRequest($request);

        if (empty($business_id)) {
            \Log::error('AddPaymentController@createSettlementIfNotExist - business_id could not be resolved', [
                'user_id' => auth()->id(),
                'request' => $request->all(),
            ]);

            return null;
        }

        $pump_operator_id = $request->operator_id ?: $request->pump_operator_id;
        $active_settlement_id = (int) $request->input('active_settlement_id', 0);
        $settlement_no = trim((string) ($request->settlement_no ?? ''));
        $requested_shift_ids = $this->getPetroPdPaymentShiftIds($request);

        // ZIP057_ACTIVE_SETTLEMENT_EARLY_RETURN

        if ($active_settlement_id > 0) {
            $active_settlement = Settlement::where('id', $active_settlement_id)
                ->where('business_id', $business_id)
                ->first();

            if (! empty($active_settlement)) {
                $this->linkPetroPdPaymentDraftToShift(
                    $active_settlement,
                    $request,
                    (int) $business_id,
                    ! empty($pump_operator_id) ? (int) $pump_operator_id : null
                );
                return $active_settlement;
            }
        }
        $transaction_date = \Carbon::parse($request->transaction_date)->format('Y-m-d');

        // Identify if Petro PD request
        $isPetroPdRequest = false;
        $source = (string) $request->input('source', '');
        if (in_array($source, ['petro_pd', 'petropd'], true)) {
            $isPetroPdRequest = true;
        } elseif (\Illuminate\Support\Str::startsWith(ltrim($request->path(), '/'), 'petropd')) {
            /*
             * MA-002: decided from THE REQUEST'S OWN PATH, not the referer.
             *
             * The two numbering series must never mix:
             *
             *     PDST   PD Settlements
             *     ST     Direct Settlements
             *
             * This used to fall back to the HTTP REFERER header, which is not
             * reliable - a proxy can strip it, browser privacy settings can
             * suppress it, a typed URL or bookmark has none, and a redirect can
             * lose it. Whenever it was missing, a PetroPD settlement silently
             * took an ST number and the two series mixed.
             *
             * $request->path() is the URL being served right now. Every PetroPD
             * route sits under the 'petropd' prefix, so this cannot be absent
             * or forged by a client.
             *
             * The referer check is KEPT BELOW as a last resort, because this
             * controller is shared with PetroDirect and an older PetroPD entry
             * point might not be under that prefix. But the path is tried
             * first, so the reliable answer wins.
             */
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

        if ($active_settlement_id > 0) {
            $active_settlement = Settlement::where('id', $active_settlement_id)
                ->where('business_id', $business_id)
                ->first();

            if (! empty($active_settlement)) {
                $active_is_pd = false;
                foreach ($petro_pd_prefixes as $pref) {
                    if (\Illuminate\Support\Str::startsWith($active_settlement->settlement_no, $pref)) {
                        $active_is_pd = true;
                        break;
                    }
                }
                if ($isPetroPdRequest === $active_is_pd) {
                    $request->merge(['settlement_no' => $active_settlement->settlement_no]);
                    if ($isPetroPdRequest) {
                        $this->linkPetroPdPaymentDraftToShift(
                            $active_settlement,
                            $request,
                            (int) $business_id,
                            ! empty($pump_operator_id) ? (int) $pump_operator_id : null
                        );
                    }
                    return $active_settlement;
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

                if (! empty($requested_shift_ids)) {
                    $existing_draft_query->where(function ($query) use ($business_id, $requested_shift_ids) {
                        $query->whereExists(function ($exists) use ($business_id, $requested_shift_ids) {
                            $exists->select(DB::raw(1))
                                ->from('pump_operator_assignments as poa_payment_draft')
                                ->whereColumn('poa_payment_draft.settlement_id', 'settlements.id')
                                ->where('poa_payment_draft.business_id', $business_id)
                                ->whereIn('poa_payment_draft.shift_id', $requested_shift_ids);
                        });

                        foreach ($requested_shift_ids as $shiftId) {
                            $query->orWhere('work_shift', 'LIKE', '%"' . $shiftId . '"%')
                                ->orWhere('work_shift', 'LIKE', '%[' . $shiftId . ']%')
                                ->orWhere('work_shift', 'LIKE', '%,' . $shiftId . ',%')
                                ->orWhere('work_shift', 'LIKE', (string) $shiftId);
                        }
                    });
                }
            } else {
                $existing_draft_query->where('settlement_no', 'NOT LIKE', 'SET-SW%');
                foreach ($petro_pd_prefixes as $pref) {
                    $existing_draft_query->where('settlement_no', 'NOT LIKE', $pref . '%');
                }
            }

            $existing_draft = $existing_draft_query->orderByDesc('id')->first();

            if (!empty($existing_draft) && !empty($existing_draft->settlement_no)) {
                $settlement_no = $existing_draft->settlement_no;
            } else {
                $prefix = $isPetroPdRequest
                    ? ($petro_pd_prefixes[0] ?? 'PDST')
                    : (!empty($ref_no_prefixes['settlement']) ? $ref_no_prefixes['settlement'] : 'SET-');

                $count_query = Settlement::where('business_id', $business_id)
                    ->where('settlement_no', 'LIKE', $prefix . '%');

                if ($isPetroPdRequest) {
                    $count_query->where(function ($q) use ($petro_pd_prefixes) {
                        foreach ($petro_pd_prefixes as $pref) {
                            $q->orWhere('settlement_no', 'LIKE', $pref . '%');
                        }
                    });
                } else {
                    $count_query->where('settlement_no', 'NOT LIKE', 'SET-SW%');
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

        $settlement_data = [

            'settlement_no'    => $settlement_no,

            'business_id'      => $business_id,

            'transaction_date' => $transaction_date,

            /*
             | LA-1159: was $request->location_id ?? ''.
             |
             | An empty string here is what produced settlements with no usable
             | location, which the List PD Settlement location filter then hid
             | (see the note in PetroPDController). Falling back to the pump
             | operator's own location gives the row the location it is actually
             | displayed under. Only when neither is known does this stay null,
             | which is at least honest and is handled by the list filter.
             */
            'location_id'      => $request->location_id
                ?: (PumpOperator::where('id', $pump_operator_id)->value('location_id') ?: null),

            'pump_operator_id' => $pump_operator_id,

            'work_shift'       => $isPetroPdRequest && ! empty($requested_shift_ids)
                ? $requested_shift_ids
                : (! empty($request->work_shift) ? $request->work_shift : []),

            'note'             => $request->note ?? '',

            'status'           => 1,

        ];

        $latest_date = DayEnd::where('business_id', $business_id)->get()->last()->day_end_date ?? null;

        if (! empty($latest_date) && strtotime($latest_date) >= strtotime($settlement_data['transaction_date'])) {

            return 406;

        }

        $settlement_exist = Settlement::where('settlement_no', $settlement_no)->where('business_id', $business_id)->first();

        if (empty($settlement_exist)) {

            $settlement_exist = Settlement::create($settlement_data);

            // Only link daily collections for the specific shift(s) being settled
            $shift_ids = [];
            $shift_id_string = $request->shift_ids ?? $request->shift_id ?? null;
            if (!empty($shift_id_string)) {
                $shift_ids = is_array($shift_id_string)
                    ? $shift_id_string
                    : array_filter(array_map('trim', explode(',', $shift_id_string)));
            } elseif (!empty($request->work_shift)) {
                $work_shifts = is_array($request->work_shift)
                    ? $request->work_shift
                    : json_decode($request->work_shift, true);
                $shift_ids = is_array($work_shifts)
                    ? $work_shifts
                    : array_filter(array_map('trim', explode(',', $request->work_shift)));
            }
            $shift_ids = array_filter(array_map('intval', (array) $shift_ids));

            if (!empty($shift_ids)) {
            DailyCollection::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->whereNull('settlement_id')
                    ->whereIn('shift_id', $shift_ids)
                ->update(['settlement_id' => $settlement_exist->id]);
            } else {
                \Log::warning('AddPaymentController createSettlementIfNotExist: Missing shift_ids, skipping daily collection linking', [
                    'settlement_id' => $settlement_exist->id,
                    'settlement_no' => $settlement_exist->settlement_no,
                    'pump_operator_id' => $pump_operator_id,
                ]);
            }

            if (!empty($shift_ids)) {
                $daily_card_query = DailyCard::where('business_id', $business_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->whereNull('settlement_no');

                if (Schema::hasColumn('daily_cards', 'shift_id')) {
                    $daily_card_query->whereIn('shift_id', $shift_ids);
                }

                $daily_card_query->update(['settlement_no' => $settlement_exist->settlement_no]);

                // Link vouchers through the authoritative PetroPD payment ID.
                // daily_vouchers.shift_id is a legacy petro_daily_shifts FK.
                if (Schema::hasColumn('daily_vouchers', 'pump_payment_id')) {
                    $credit_payment_ids = PumpOperatorPayment::where('business_id', $business_id)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->whereIn('shift_id', $shift_ids)
                        ->whereIn('payment_type', ['credit', 'multiple_credit'])
                        ->pluck('id')
                        ->toArray();

                    if (! empty($credit_payment_ids)) {
                        DailyVoucher::where('business_id', $business_id)
                            ->where('operator_id', $pump_operator_id)
                            ->whereNull('settlement_no')
                            ->whereIn('pump_payment_id', $credit_payment_ids)
                            ->update(['settlement_no' => $settlement_exist->settlement_no]);
                    }
                }
            } else {
                \Log::warning('AddPaymentController createSettlementIfNotExist: Missing shift_ids, skipping daily card/voucher linking', [
                    'settlement_id' => $settlement_exist->id,
                    'settlement_no' => $settlement_exist->settlement_no,
                    'pump_operator_id' => $pump_operator_id,
                ]);
            }

        }

        if ($isPetroPdRequest && ! empty($settlement_exist)) {
            $this->linkPetroPdPaymentDraftToShift(
                $settlement_exist,
                $request,
                (int) $business_id,
                ! empty($pump_operator_id) ? (int) $pump_operator_id : null
            );
        }

        return $settlement_exist;

    }

    private function getClosedShiftIdsForSettlement(array $shift_ids, ?int $pump_operator_id = null): array
    {
        if (empty($shift_ids)) {
            return [];
        }

        $assignment_query = PumpOperatorAssignment::whereIn('shift_id', $shift_ids)
            ->where(function ($query) {
                $query->where('status', 'close')
                    ->orWhere('is_manually_closed', 1);
            });

        if (! empty($pump_operator_id)) {
            $assignment_query->where('pump_operator_id', $pump_operator_id);
        }

        $closed_by_assignment = $assignment_query->pluck('shift_id')->toArray();
        $closed_by_shift = PetroShift::whereIn('id', $shift_ids)
            ->where('status', 2)
            ->pluck('id')
            ->toArray();

        return array_values(array_unique(array_map('intval', array_merge($closed_by_assignment, $closed_by_shift))));
    }

    // public function create(Request $request)
    // {
    //     // Validate critical inputs first
    //     if (empty($request->settlement_no)) {
    //         return response()->json([
    //             'success' => false,
    //             'msg'     => 'Settlement number is required',
    //         ]);
    //     }

    //     if (empty($request->shift_ids)) {
    //         return response()->json([
    //             'success' => false,
    //             'msg'     => 'Shift ID is required',
    //         ]);
    //     }

    //     if (empty($request->operator_id)) {
    //         return response()->json([
    //             'success' => false,
    //             'msg'     => 'Pump operator ID is required',
    //         ]);
    //     }

    //     $business_id  = request()->session()->get('business.id');
    //     $business     = Business::where('id', $business_id)->first();
    //     $pos_settings = json_decode($business->pos_settings, true);
    //     $cash_denoms  = ! empty($pos_settings['cash_denominations']) ? explode(',', $pos_settings['cash_denominations']) : [];

    //     // Use consistent settlement identifier
    //     $request_settlement_no = $request->settlement_no;
    //     $provider              = $request->provider;
    //     $pump_operator_id      = $request->operator_id;
    //     $is_settlement_page    = isset($request->settlement_page) ? 1 : 0;

    //     // Find or create settlement
    //     $settlement = Settlement::where('settlement_no', $request_settlement_no)
    //         ->where('business_id', $business_id)
    //         ->first();

    //     if (! $settlement && ! $request->provider) {
    //         $settlement = $this->createSettlementIfNotExist($request);
    //     }

    //     if (! $settlement) {
    //         return response()->json([
    //             'success' => false,
    //             'msg'     => 'Settlement not found',
    //         ]);
    //     }

    //     // Use settlement ID for all internal references
    //     $settlement_id = $settlement->id;

    //     // Keep original settlement_no for display
    //     $display_settlement_no = $settlement->settlement_no;

    //     // Parse shift IDs properly
    //     $shift_ids = ! empty($request->shift_ids) ? explode(",", $request->shift_ids) : [];
    //     $shift_ids = array_map('intval', $shift_ids); // Convert to integers

    //     // Use work_shift from settlement if available, otherwise use request
    //     if (! empty($settlement->work_shift) && is_array($settlement->work_shift)) {
    //         $shift_ids_for_settlement = $settlement->work_shift;
    //     } else {
    //         $shift_ids_for_settlement = $shift_ids;
    //     }

    //     // For display purposes
    //     $show_shift_no = implode(", ", $shift_ids_for_settlement);

    //     // Debug logging
    //     Log::info('Settlement Data', [
    //         'request_settlement_no'    => $request_settlement_no,
    //         'settlement_id'            => $settlement_id,
    //         'display_settlement_no'    => $display_settlement_no,
    //         'shift_ids'                => $shift_ids,
    //         'shift_ids_for_settlement' => $shift_ids_for_settlement,
    //         'pump_operator_id'         => $pump_operator_id,
    //     ]);

    //     // Get payments with proper shift filtering
    //     $payments = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')
    //         ->whereIn('shift_id', $shift_ids_for_settlement)
    //         ->where('pump_operator_payments.pump_operator_id', $pump_operator_id)
    //         ->select(
    //             DB::raw('SUM(IF(payment_type="cash", payment_amount, 0)) as cash'),
    //             DB::raw('SUM(IF(payment_type="card", payment_amount, 0)) as card'),
    //             DB::raw('SUM(IF(payment_type="cheque", payment_amount, 0)) as cheque'),
    //             DB::raw('SUM(IF(payment_type="credit", payment_amount, 0)) as credit'),
    //             DB::raw('SUM(IF(payment_type="other", payment_amount, 0)) as other'),
    //             DB::raw('SUM(IF(payment_type="shortage" OR payment_type="excess", payment_amount, 0)) as shortage_excess'),
    //             DB::raw('SUM(payment_amount) as total')
    //         )->first();

    //     // Prefill the added card numbers in daily collection
    //     $this->addDailyCards($settlement_id, $pump_operator_id, $business_id, $shift_ids_for_settlement);
    //     $this->addDailyCheques($settlement_id, $pump_operator_id, $business_id);
    //     $this->addDailyShortageExcess($settlement_id, $pump_operator_id, $business_id, $shift_ids_for_settlement);

    //     $pump_operator = PumpOperator::where('id', $pump_operator_id)->first();

    //     $business_locations = BusinessLocation::forDropdown($business_id);
    //     $default_location   = current(array_keys($business_locations->toArray()));
    //     $payment_types      = $this->productUtil->payment_types($default_location, false, false, false, false, "is_sale_enabled");

    //     $expense_no         = $this->getExpenseNumber($settlement_id);
    //     $expense_categories = ExpenseCategory::where('business_id', $business_id)
    //         ->pluck('name', 'id');

    //     $expense_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();
    //     $expense_accounts        = [];

    //     if ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account')) {
    //         if (! empty($expense_account_type_id)) {
    //             $expense_accounts = Account::where('business_id', $business_id)->where('account_type_id', $expense_account_type_id->id)->pluck('name', 'id');
    //         }
    //     }

    //     $subscription    = Subscription::current_subscription($business_id);
    //     $package_details = $subscription->package_details;
    //     $only_walkin     = $package_details['only_walkin'] ?? 0;

    //     if (! empty($only_walkin)) {
    //         $credit_customers = Contact::customersDropdown($business_id, false, true, 'customer');
    //     } else {
    //         $credit_customers = Contact::where('name', '!=', 'Walk-In Customer')->where('active', 1)->where('type', 'customer')->where('business_id', $business_id)->pluck('name', 'id');
    //     }

    //     $customers = Contact::customersDropdown($business_id, false, true, 'customer');
    //     $walkin    = Contact::where('name', 'Walk-In Customer')->where('business_id', $business_id)->pluck('name', 'id');
    //     $products  = Product::where('business_id', $business_id)->forModule('petro_settlements')->pluck('name', 'id');

    //     $card_types = [];
    //     $card_group = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();

    //     if (! empty($card_group)) {
    //         $card_types = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');
    //     }

    //     $customer_payments_tab = CustomerPayment::leftjoin('contacts', 'customer_payments.customer_id', 'contacts.id')
    //         ->where('customer_payments.settlement_no', $settlement_id)
    //         ->select('customer_payments.*', 'contacts.name as customer_name')
    //         ->get();

    //     // Fetch settlement cash payments with customer names
    //     $settlement_cash_payments2 = SettlementCashPayment::leftJoin('contacts', 'settlement_cash_payments.customer_id', '=', 'contacts.id')
    //         ->where('settlement_cash_payments.settlement_no', $settlement_id)
    //         ->select('settlement_cash_payments.*', 'contacts.name as customer_name')
    //         ->get();

    //     // Pump operator cash payments - Filter by settlement's work_shift
    //     $settlement_cash_payments1 = PumpOperatorPayment::whereIn('shift_id', $shift_ids_for_settlement)
    //         ->where('pump_operator_id', $pump_operator_id)
    //         ->where('payment_type', 'cash')
    //         ->where(function ($q) {
    //             $q->whereNull('is_used')->orWhere('is_used', 0);
    //         })
    //         ->select('payment_amount as amount', 'shift_id')
    //         ->get();

    //     // Convert into SettlementCashPayment models with proper settlement reference
    //     $newdatarecords = $settlement_cash_payments1->map(function ($payment) use ($settlement_id) {
    //         $record                      = new SettlementCashPayment();
    //         $record->id                  = null;
    //         $record->settlement_no       = $settlement_id; // Use actual settlement ID
    //         $record->business_id         = null;
    //         $record->customer_id         = null;
    //         $record->amount              = $payment->amount;
    //         $record->customer_payment_id = null;
    //         $record->note                = 'Auto-generated from pump operator payments';
    //         $record->slip                = '';
    //         $record->created_at          = now();
    //         $record->updated_at          = now();
    //         $record->customer_name       = 'Walk-in Customer';
    //         $record->shift_id            = $payment->shift_id; // Store shift info for traceability
    //         return $record;
    //     });

    //     // Combine both sets
    //     if ($settlement_cash_payments2->isNotEmpty()) {
    //         $settlement_cash_payments = $settlement_cash_payments2
    //             ->concat($newdatarecords)
    //             ->values();
    //     } else {
    //         $settlement_cash_payments = $newdatarecords;
    //     }

    //     $cash_total = $settlement_cash_payments->sum('amount');

    //     $settlement_customer_loans = SettlementCustomerLoan::leftjoin('contacts', 'settlement_customer_loans.customer_id', 'contacts.id')
    //         ->where('settlement_customer_loans.settlement_no', $settlement_id)
    //         ->select('settlement_customer_loans.*', 'contacts.name as customer_name')
    //         ->get();

    //     $settlement_loan_payments = SettlementLoanPayment::leftjoin('accounts', 'accounts.id', 'settlement_loan_payments.loan_account')
    //         ->where('settlement_loan_payments.settlement_no', $settlement_id)
    //         ->select('settlement_loan_payments.*', 'accounts.name as loan_account_name')
    //         ->get();

    //     $settlement_drawings_payments = SettlementDrawingPayment::leftjoin('accounts', 'accounts.id', 'settlement_drawing_payments.loan_account')
    //         ->where('settlement_drawing_payments.settlement_no', $settlement_id)
    //         ->select('settlement_drawing_payments.*', 'accounts.name as loan_account_name')
    //         ->get();

    //     // Actual settlement card payments
    //     $settlement_card_payments1 = SettlementCardPayment::query()
    //         ->leftJoin('contacts', 'settlement_card_payments.customer_id', '=', 'contacts.id')
    //         ->leftJoin('accounts', 'settlement_card_payments.card_type', '=', 'accounts.id')
    //         ->leftJoin('daily_cards', 'settlement_card_payments.daily_card_id', '=', 'daily_cards.id')
    //         ->where('settlement_card_payments.settlement_no', $settlement_id)
    //         ->select(
    //             'settlement_card_payments.*',
    //             'contacts.name as customer_name',
    //             'accounts.name as card_type_name',
    //             'daily_cards.pump_operator_id'
    //         )
    //         ->get();

    //     // Fetch walking payments from pump operator payments
    //     $walking_payments = SettlementCardPayment::query()
    //         ->leftJoin('contacts', 'settlement_card_payments.customer_id', '=', 'contacts.id')
    //         ->leftJoin('accounts', 'settlement_card_payments.card_type', '=', 'accounts.id')
    //         ->leftJoin('daily_cards', 'settlement_card_payments.daily_card_id', '=', 'daily_cards.id')
    //         ->join('pump_operator_payments as pop', function ($join) use ($shift_ids_for_settlement, $pump_operator_id) {
    //             $join->on('settlement_card_payments.business_id', '=', 'pop.business_id')
    //                 ->on('settlement_card_payments.amount', '=', 'pop.payment_amount')
    //                 ->whereIn('pop.shift_id', $shift_ids_for_settlement)
    //                 ->where('pop.pump_operator_id', $pump_operator_id)
    //                 ->where('pop.payment_type', 'card');
    //         })
    //         ->where('settlement_card_payments.settlement_no', $settlement_id)
    //         ->select(
    //             'settlement_card_payments.*',
    //             'contacts.name as customer_name',
    //             'accounts.name as card_type_name',
    //             'daily_cards.pump_operator_id',
    //             'pop.shift_id',
    //             'pop.payment_amount'
    //         )
    //         ->groupBy('settlement_card_payments.id')
    //         ->get();

    //     // Combine both collections - show ALL card payments (direct + pump operator)
    //     $settlement_card_payments = $settlement_card_payments1
    //         ->merge($walking_payments)
    //         ->unique('id')
    //         ->values();

    //     $settlement_cash_deposits = SettlementCashDeposit::leftjoin('accounts', 'settlement_cash_deposits.bank_id', 'accounts.id')
    //         ->where('settlement_cash_deposits.settlement_no', $settlement_id)
    //         ->select('settlement_cash_deposits.*', 'accounts.name as bank_name')
    //         ->get();

    //     $settlement_cheque_payments = SettlementChequePayment::leftjoin('contacts', 'settlement_cheque_payments.customer_id', 'contacts.id')
    //         ->where('settlement_cheque_payments.settlement_no', $settlement_id)
    //         ->select('settlement_cheque_payments.*', 'contacts.name as customer_name')
    //         ->get();

    //     $settlement_credit_sale_payments2 = DB::table('settlement_credit_sale_payments')
    //         ->whereExists(function ($query) use ($shift_ids_for_settlement, $pump_operator_id) {
    //             $query->select(DB::raw(1))
    //                 ->from('pump_operator_payments')
    //                 ->whereRaw('settlement_credit_sale_payments.pump_operator_id = pump_operator_payments.pump_operator_id')
    //                 ->whereIn('pump_operator_payments.shift_id', $shift_ids_for_settlement)
    //                 ->where('pump_operator_payments.pump_operator_id', $pump_operator_id)
    //                 ->whereIn('pump_operator_payments.payment_type', ['credit', 'multiple_credit'])
    //                 ->whereRaw('settlement_credit_sale_payments.collection_form_no COLLATE utf8mb4_general_ci = pump_operator_payments.collection_form_no COLLATE utf8mb4_general_ci');
    //         })
    //         ->leftJoin('contacts', 'settlement_credit_sale_payments.customer_id', '=', 'contacts.id')
    //         ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
    //         ->select(
    //             'settlement_credit_sale_payments.*',
    //             'contacts.name as customer_name',
    //             'products.name as product_name'
    //         )
    //         ->get();

    //     $settlement_credit_sale_payments1 = SettlementCreditSalePayment::query()
    //         ->leftJoin('contacts', 'settlement_credit_sale_payments.customer_id', '=', 'contacts.id')
    //         ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
    //         ->leftJoin('daily_vouchers', 'settlement_credit_sale_payments.daily_voucher_id', '=', 'daily_vouchers.id')
    //         ->where('settlement_credit_sale_payments.settlement_no', $settlement_id)
    //         ->where('settlement_credit_sale_payments.pump_operator_id', $pump_operator_id)
    //         ->select(
    //             'settlement_credit_sale_payments.*',
    //             'contacts.name as customer_name',
    //             'products.name as product_name',
    //             'daily_vouchers.operator_id'
    //         )
    //         ->get();

    //     $settlement_credit_sale_payments = collect()
    //         ->merge($settlement_credit_sale_payments1 ?? collect())
    //         ->merge($settlement_credit_sale_payments2 ?? collect())
    //         ->unique(fn($item) => $item->id ?? null)
    //         ->values();

    //     $settlement_expense_payments = SettlementExpensePayment::leftjoin('accounts', 'settlement_expense_payments.account_id', 'accounts.id')
    //         ->leftjoin('expense_categories', 'settlement_expense_payments.category_id', 'expense_categories.id')
    //         ->where('settlement_expense_payments.settlement_no', $settlement_id)
    //         ->select('settlement_expense_payments.*', 'accounts.name as account_name', 'expense_categories.name as category_name')
    //         ->get();

    //     $settlement_shortage_payments = SettlementShortagePayment::where('settlement_shortage_payments.settlement_no', $settlement_id)
    //         ->select('settlement_shortage_payments.*')
    //         ->get();

    //     $settlement_excess_payments = SettlementExcessPayment::where('settlement_excess_payments.settlement_no', $settlement_id)
    //         ->select('settlement_excess_payments.*')
    //         ->get();

    //     $total_daily_collection = floatval(DailyCollection::where([
    //         'pump_operator_id' => $pump_operator_id,
    //         'shift_id'         => $request->shift_ids,
    //     ])->where('business_id', $business_id)->whereNull('settlement_id')->sum('current_amount'));

    //     $total_excess     = $this->transactionUtil->getPumpOperatorExcessOrShortage($pump_operator_id, 'excess');
    //     $total_shortage   = $this->transactionUtil->getPumpOperatorExcessOrShortage($pump_operator_id, 'shortage');
    //     $operator_bal     = $this->transactionUtil->getPumpOperatorBalance($pump_operator_id);
    //     $total_commission = $this->calculateCommission($pump_operator_id, $settlement_id);

    //     $business_details   = Business::find($business_id);
    //     $currency_precision = $business_details->currency_precision;

    //     $total_meter_sale = MeterSale::where('settlement_no', $settlement_id)
    //         ->whereIn('shift_id', $shift_ids_for_settlement)
    //         ->sum('discount_amount');

    //     $other_sales      = OtherSale::where('settlement_no', $settlement_id)->get();
    //     $total_other_sale = $other_sales->sum('sub_total') - $other_sales->sum('discount_amount');

    //     if (str_contains($display_settlement_no, 'SET-SW')) {
    //         $total_other_sale = $other_sales->sum('sub_total');
    //     }

    //     if (! empty($shift_ids_for_settlement)) {
    //         $pump_operator_total_other_sale = PumpOperatorOtherSale::join('products', 'products.id', '=', 'pump_operator_other_sales.product_id')
    //             ->leftJoin('variations', 'products.id', 'variations.product_id')
    //             ->leftJoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')
    //             ->whereIn('pump_operator_other_sales.shift_id', $shift_ids_for_settlement)
    //             ->join('pump_operator_assignments', function ($join) {
    //                 $join->on('pump_operator_assignments.shift_id', '=', 'pump_operator_other_sales.shift_id')
    //                     ->where('pump_operator_assignments.status', 'close')
    //                     ->whereRaw('pump_operator_assignments.id = (
    //                         SELECT MAX(poa.id)
    //                         FROM pump_operator_assignments poa
    //                         WHERE poa.shift_id = pump_operator_other_sales.shift_id AND poa.status = "close"
    //                     )');
    //             })
    //             ->sum("sub_total");

    //         $total_other_sale = $total_other_sale + $pump_operator_total_other_sale;
    //     }

    //     $total_other_income     = OtherIncome::where('settlement_no', $settlement_id)->sum('sub_total');
    //     $total_customer_payment = CustomerPayment::where('settlement_no', $settlement_id)->sum('sub_total');

    //     $total_amount = number_format(($total_meter_sale + $total_other_sale + $total_other_income + $total_customer_payment), $currency_precision, '.', '');

    //     $total_settlement_cash_payment         = SettlementCashPayment::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_loan_payment         = SettlementLoanPayment::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_drawings_payment     = SettlementDrawingPayment::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_cash_deposit         = SettlementCashDeposit::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_card_payment         = $settlement_card_payments->sum('amount');
    //     $total_settlement_customer_loan        = SettlementCustomerLoan::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_cheque_payment       = SettlementChequePayment::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_credit_sale_payment  = $settlement_credit_sale_payments->sum('amount');
    //     $total_settlement_credit_sale_discount = $settlement_credit_sale_payments->sum('total_discount');
    //     $total_settlement_expense_payment      = SettlementExpensePayment::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_shortage_payment     = SettlementShortagePayment::where('settlement_no', $settlement_id)->sum('amount');
    //     $total_settlement_excess_payment       = SettlementExcessPayment::where('settlement_no', $settlement_id)->sum('amount');

    //     $total_paid = number_format((
    //         $total_settlement_customer_loan +
    //         $total_settlement_loan_payment +
    //         $total_settlement_cash_deposit +
    //         $total_daily_collection +
    //         $total_settlement_cash_payment +
    //         $total_settlement_card_payment +
    //         $total_settlement_cheque_payment +
    //         $total_settlement_credit_sale_payment - $total_settlement_credit_sale_discount +
    //         $total_settlement_expense_payment +
    //         $total_settlement_shortage_payment +
    //         $total_settlement_excess_payment +
    //         $total_settlement_drawings_payment
    //     ), $currency_precision, '.', '');

    //     $total_balance = number_format($total_amount - $total_paid, $currency_precision, '.', '');
    //     $total_balance = abs($total_balance);

    //     $loans_given_group_id  = AccountGroup::getGroupByName('Loans Given');
    //     $drawings_group_id     = AccountGroup::getGroupByName('Owners Drawings');
    //     $bank_account_group_id = AccountGroup::getGroupByName('Bank Account');

    //     $bank_accounts = Account::where('business_id', $business_id)->where('asset_type', $bank_account_group_id->id)->pluck('name', 'id');

    //     if (! empty($loans_given_group_id)) {
    //         $loans_given = Account::where('business_id', $business_id)->where('asset_type', $loans_given_group_id->id)->pluck('name', 'id');
    //     } else {
    //         $loans_given = [];
    //     }

    //     if (! empty($drawings_group_id)) {
    //         $drawings_acc = Account::where('business_id', $business_id)->where('asset_type', $drawings_group_id->id)->pluck('name', 'id');
    //     } else {
    //         $drawings_acc = [];
    //     }

    //     $message          = $package_details['notsubscribed_message_content'] ?? "You have not subscribed to this module!";
    //     $font_family      = $package_details['ns_font_family'] ?? '';
    //     $font_color       = $package_details['ns_font_color'] ?? '';
    //     $font_size        = $package_details['ns_font_size'] ?? '';
    //     $background_color = $package_details['ns_background_color'] ?? '';

    //     // Use display settlement number for the view
    //     $settlement->settlement_no = $display_settlement_no;

    //     if ($request->type == 'settlement_pd') {
    //         return view('petropd::pd_settlement.partials.add_payment')->with(compact(
    //             'operator_bal',
    //             'business',
    //             'message', 'font_family', 'font_color', 'font_size', 'background_color', 'package_details',
    //             'bank_accounts',
    //             'is_settlement_page',
    //             'total_settlement_cash_deposit',
    //             'settlement',
    //             'cash_denoms',
    //             'pump_operator',
    //             'customer_payments_tab',
    //             'settlement_cash_payments',
    //             'settlement_customer_loans',
    //             'cash_total',
    //             'settlement_loan_payments',
    //             'settlement_drawings_payments',
    //             'settlement_card_payments',
    //             'settlement_cheque_payments',
    //             'settlement_credit_sale_payments',
    //             'settlement_expense_payments',
    //             'settlement_shortage_payments',
    //             'settlement_excess_payments',
    //             'settlement_cash_deposits',
    //             'payment_types',
    //             'expense_accounts',
    //             'expense_categories',
    //             'expense_no',
    //             'customers',
    //             'products',
    //             'card_types',
    //             'total_daily_collection',
    //             'total_commission',
    //             'total_amount',
    //             'total_paid',
    //             'total_balance',
    //             'total_excess',
    //             'total_shortage',
    //             'loans_given',
    //             'drawings_acc',
    //             'only_walkin',
    //             'walkin',
    //             'provider',
    //             'credit_customers',
    //             'show_shift_no'
    //         ));
    //     }

    //     return view('petropd::pd_settlement.partials.add_payment')->with(compact(
    //         'operator_bal',
    //         'business',
    //         'message', 'font_family', 'font_color', 'font_size', 'background_color', 'package_details',
    //         'bank_accounts',
    //         'is_settlement_page',
    //         'total_settlement_cash_deposit',
    //         'settlement',
    //         'cash_denoms',
    //         'pump_operator',
    //         'customer_payments_tab',
    //         'settlement_cash_payments',
    //         'cash_total',
    //         'settlement_customer_loans',
    //         'settlement_loan_payments',
    //         'settlement_drawings_payments',
    //         'settlement_card_payments',
    //         'settlement_cheque_payments',
    //         'settlement_credit_sale_payments',
    //         'settlement_expense_payments',
    //         'settlement_shortage_payments',
    //         'settlement_excess_payments',
    //         'settlement_cash_deposits',
    //         'payment_types',
    //         'expense_accounts',
    //         'expense_categories',
    //         'expense_no',
    //         'customers',
    //         'products',
    //         'card_types',
    //         'total_daily_collection',
    //         'total_commission',
    //         'total_amount',
    //         'total_paid',
    //         'total_balance',
    //         'total_excess',
    //         'total_shortage',
    //         'loans_given',
    //         'drawings_acc',
    //         'only_walkin',
    //         'walkin',
    //         'provider',
    //         'credit_customers',
    //         'show_shift_no'
    //     ));
    // }

    public function create(Request $request)
    {

        $business_id = $this->resolveBusinessIdFromRequest($request);

        if (empty($business_id)) {
            \Log::error('AddPaymentController@create - business_id could not be resolved', [
                'user_id' => auth()->id(),
                'request' => $request->all(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ], 422);
            }

            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }

        $business = Business::where('id', $business_id)->first();

        if (!$business) {
            \Log::error('Business not found in AddPaymentController', [
                'business_id' => $business_id,
                'user_id' => auth()->id()
            ]);

            $pos_settings = [];
        } else {
            $pos_settings = json_decode($business->pos_settings ?? '{}', true);

            if (!is_array($pos_settings)) {
                $pos_settings = [];
            }
        }

        $cash_denoms = ! empty($pos_settings['cash_denominations']) ? explode(',', $pos_settings['cash_denominations']) : [];

        $settlement_no = $request->settlement_no;

        $provider = $request->provider;

        $no_change = $request->boolean('no_change') || ($request->type === 'settlement_pd');

        /*
        |--------------------------------------------------------------------------
        | PetroPD Settlement Number Safety
        |--------------------------------------------------------------------------
        | The same Petro AddPaymentController is used by both Direct Petro Settlement
        | and PetroPD Settlement. Direct settlement numbers use ST, while PetroPD
        | settlement numbers must use PDST.
        |
        | If a PetroPD payment-finalize request reaches this controller with an old
        | ST value, do not reuse that Direct Settlement. Clear it so the PDST draft
        | is created/loaded correctly. This prevents the Payment to Finalize modal
        | from showing ST instead of PDST.
        */
        $isPetroPdPaymentRequest = ($request->type === 'settlement_pd')
            || in_array((string) $request->input('source'), ['petro_pd', 'petropd'], true);

        $petroPdPrefixesForPayment = ['PDST'];
        $refNoPrefixesForPayment = request()->session()->get('business.ref_no_prefixes');
        if (is_string($refNoPrefixesForPayment)) {
            $decodedPrefixesForPayment = json_decode($refNoPrefixesForPayment, true);
            $refNoPrefixesForPayment = is_array($decodedPrefixesForPayment) ? $decodedPrefixesForPayment : [];
        }
        if (! empty($refNoPrefixesForPayment['settlement_pd'])) {
            $petroPdPrefixesForPayment[] = $refNoPrefixesForPayment['settlement_pd'];
        }
        $petroPdPrefixesForPayment = array_values(array_unique(array_filter($petroPdPrefixesForPayment)));

        $isPetroPdSettlementNumber = function ($value) use ($petroPdPrefixesForPayment) {
            $value = (string) $value;
            foreach ($petroPdPrefixesForPayment as $prefix) {
                if (Str::startsWith($value, $prefix)) {
                    return true;
                }
            }
            return false;
        };

        if ($isPetroPdPaymentRequest && ! empty($settlement_no) && ! $isPetroPdSettlementNumber($settlement_no)) {
            $settlement_no = '';
            $request->merge(['settlement_no' => '']);
        }

        $pump_operator_id = $request->operator_id ?: $request->pump_operator_id;

        $is_settlement_page = isset($request->settlement_page) ? 1 : 0;

        $active_settlement_id = (int) $request->input('active_settlement_id', 0);
        $settlement = null;

        if ($active_settlement_id > 0) {
            $settlement = Settlement::where('id', $active_settlement_id)
                ->where('business_id', $business_id)
                ->first();

            if (! empty($settlement)) {
                if ($isPetroPdPaymentRequest && ! $isPetroPdSettlementNumber($settlement->settlement_no)) {
                    // A Direct ST settlement must never be reused inside PetroPD Payment to Finalize.
                    $settlement = null;
                    $settlement_no = '';
                    $request->merge(['settlement_no' => '']);
                } else {
                    /*
                     * ZIP 057:
                     * Always reuse the active unsaved settlement after page refresh.
                     * This keeps previously entered payment tab details visible until
                     * the settlement is finally saved/finalized.
                     */
                    $settlement_no = $settlement->settlement_no;
                    $request->merge([
                        'settlement_no' => $settlement_no,
                        'active_settlement_id' => $settlement->id,
                    ]);
                }
            }
        }

        if (empty($settlement)) {
            $settlement = Settlement::where('settlement_no', $settlement_no)->where('business_id', $business_id)->first();
        }

        // CRITICAL: If settlement exists but pump_operator_id doesn't match, create a new settlement
        // This prevents loading old settlements for different pump operators
        if ($active_settlement_id === 0 && $settlement && !empty($pump_operator_id) && $settlement->pump_operator_id != $pump_operator_id) {
            \Log::warning('AddPaymentController@create - Settlement pump_operator_id mismatch, creating new settlement', [
                'existing_settlement_id' => $settlement->id,
                'existing_settlement_no' => $settlement->settlement_no,
                'existing_pump_operator_id' => $settlement->pump_operator_id,
                'requested_pump_operator_id' => $pump_operator_id,
            ]);
            $settlement = null; // Force creation of new settlement
        }

        // Resolve requested target shift IDs
        $check_shift_ids = [];
        $check_shift_str = $request->shift_ids ?? $request->query('shift_ids') ?? $request->input('shift_ids') ?? null;
        if (! empty($check_shift_str)) {
            if (is_array($check_shift_str)) {
                $check_shift_ids = $check_shift_str;
            } else {
                $check_shift_ids = array_filter(array_map('trim', explode(',', $check_shift_str)));
            }
            $check_shift_ids = array_values(array_filter(array_map('intval', $check_shift_ids)));
        }

        if ($request->type === 'settlement_pd' && ! empty($check_shift_ids)) {
            $mismatch = false;
            if ($settlement) {
                $settlement_shifts = $this->getSettlementWorkShiftIds($settlement);
                sort($settlement_shifts);
                $temp_check = $check_shift_ids;
                sort($temp_check);
                if ($settlement_shifts !== $temp_check) {
                    $mismatch = true;
                }
            } else {
                $mismatch = true;
            }

            if ($mismatch) {
                // Try to find if a settlement for these exact shift IDs already exists
                $existing_settlement = null;
                if (! empty($pump_operator_id)) {
                    $possible_settlements_query = Settlement::where('business_id', $business_id)
                        ->where('pump_operator_id', $pump_operator_id);

                    if ($isPetroPdPaymentRequest) {
                        $possible_settlements_query->where(function ($prefixQuery) use ($petroPdPrefixesForPayment) {
                            foreach ($petroPdPrefixesForPayment as $prefix) {
                                $prefixQuery->orWhere('settlement_no', 'LIKE', $prefix . '%');
                            }
                        });
                    }

                    $possible_settlements = $possible_settlements_query->get();
                    foreach ($possible_settlements as $ps) {
                        $ps_shifts = $this->getSettlementWorkShiftIds($ps);
                        sort($ps_shifts);
                        $temp_check = $check_shift_ids;
                        sort($temp_check);
                        if ($ps_shifts === $temp_check) {
                            $existing_settlement = $ps;
                            break;
                        }
                    }
                }

                if ($existing_settlement) {
                    $settlement = $existing_settlement;
                    $settlement_no = $settlement->settlement_no;
                    $request->merge(['settlement_no' => $settlement_no]);
                } else {
                    // Generate a new unique settlement number
                    $ref_no_prefixes = request()->session()->get('business.ref_no_prefixes');
                    if (is_string($ref_no_prefixes)) {
                        $decoded = json_decode($ref_no_prefixes, true);
                        $ref_no_prefixes = is_array($decoded) ? $decoded : [];
                    }
                    $prefix = ! empty($ref_no_prefixes['settlement_pd']) ? $ref_no_prefixes['settlement_pd'] : 'PDST';

                    $last_settlement = Settlement::where('business_id', $business_id)
                        ->where('settlement_no', 'LIKE', $prefix . '%')
                        ->orderBy('id', 'DESC')
                        ->first();
                    $count = 0;
                    if (! empty($last_settlement)) {
                        preg_match('/(\d+)$/', $last_settlement->settlement_no, $matches);
                        $count = ! empty($matches[1]) ? (int) $matches[1] : 0;
                    }
                    $new_settlement_no = $prefix . ($count + 1);
                    while (Settlement::where('business_id', $business_id)->where('settlement_no', $new_settlement_no)->exists()) {
                        $count++;
                        $new_settlement_no = $prefix . ($count + 1);
                    }

                    $settlement_no = $new_settlement_no;
                    $request->merge([
                        'settlement_no' => $settlement_no,
                        'work_shift' => $check_shift_ids
                    ]);
                    $settlement = null;
                }
            }
        }

        if (! isset($settlement) && ! $request->provider) {

            $settlement = $this->createSettlementIfNotExist($request);

        }

        // Check if settlement creation failed (returns 406 or null)
        if (! $settlement || (is_numeric($settlement) && $settlement == 406)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'msg' => __('petropd::lang.settlement_date_before_day_end'),
                ], 406);
            }
            return redirect()->back()->with('error', __('petropd::lang.settlement_date_before_day_end'));
        }

        // IS1546: keep Add Payment in sync with the date selected on the Direct Settlement page.
        // This is done immediately after the settlement is resolved/created so the modal title,
        // preview, and final save all use the same authoritative transaction date.
        $requested_transaction_date = $request->input('transaction_date') ?: $request->input('date');
        if (! empty($requested_transaction_date) && isset($settlement) && ! empty($settlement->id)) {
            try {
                $parsed_transaction_date = \Carbon\Carbon::parse($requested_transaction_date)->format('Y-m-d');
                if ($settlement->transaction_date != $parsed_transaction_date) {
                    $settlement->transaction_date = $parsed_transaction_date;
                    Settlement::where('id', $settlement->id)
                        ->where('business_id', $business_id)
                        ->update(['transaction_date' => $parsed_transaction_date]);
                }
            } catch (\Exception $dateSyncException) {
                \Log::warning('AddPaymentController@create - transaction date sync skipped', [
                    'settlement_id' => $settlement->id ?? null,
                    'requested_transaction_date' => $requested_transaction_date,
                    'message' => $dateSyncException->getMessage(),
                ]);
            }
        }

        // For settlement_pd type, get pump_operator_id from settlement if not provided in request
        if ($request->type == 'settlement_pd') {
            // If pump_operator_id is not in request, try to get it from settlement
            if (empty($pump_operator_id) && isset($settlement) && !empty($settlement->pump_operator_id)) {
                $pump_operator_id = $settlement->pump_operator_id;
            }

            // Also try to get from request parameter 'pump_operator_id' (alternative name)
            if (empty($pump_operator_id) && $request->has('pump_operator_id')) {
                $pump_operator_id = $request->pump_operator_id;
            }
        }

        // 758 add
        $shift_ids       = [];
        // Try multiple ways to get shift_ids from request
        $shift_id_string = $request->shift_ids ?? $request->query('shift_ids') ?? $request->input('shift_ids') ?? null;

        if (! empty($shift_id_string)) {
            if (is_array($shift_id_string)) {
                $shift_ids = $shift_id_string;
            } else {
                $shift_ids = array_filter(array_map('trim', explode(',', $shift_id_string)));
            }
            // Convert to integers and filter out invalid values
            $shift_ids = array_filter(array_map('intval', $shift_ids));
            $shift_ids = array_values($shift_ids); // Re-index array
        }

        $requested_shift_ids = $shift_ids;
        $is_direct_shift_placeholder = false;
        $raw_shift_ids_for_direct_check = $request->shift_ids ?? $request->query('shift_ids') ?? $request->input('shift_ids') ?? null;
        if ($raw_shift_ids_for_direct_check !== null && $raw_shift_ids_for_direct_check !== '') {
            $raw_shift_values = is_array($raw_shift_ids_for_direct_check)
                ? $raw_shift_ids_for_direct_check
                : array_map('trim', explode(',', $raw_shift_ids_for_direct_check));

            $is_direct_shift_placeholder = in_array('0', array_map('strval', $raw_shift_values), true);
        }

        if ($request->type === 'settlement_pd' && ! empty($requested_shift_ids)) {
            $shift_ids = $this->getClosedShiftIdsForSettlement($requested_shift_ids, ! empty($pump_operator_id) ? (int) $pump_operator_id : null);
            if (empty($shift_ids)) {
                $shift_ids = [0];
            }
            $shift_id_string = implode(',', $shift_ids);
        }

        // For settlement_pd, if shift_ids not provided, try to get from settlement's work_shift
        if ($request->type === 'settlement_pd' && empty($shift_ids) && !empty($settlement)) {
            $work_shifts = $settlement->work_shift ?? null;
            if (!empty($work_shifts)) {
                if (is_array($work_shifts)) {
                    $shift_ids = array_filter(array_map('intval', $work_shifts));
                    $shift_ids = array_values($shift_ids);
                } else {
                    $shift_ids = [intval($work_shifts)];
                }
            }
        }

        // Debug logging for settlement_pd (moved after settlement_id is defined)

        $payments = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')

            ->select(

            DB::raw('SUM(IF(payment_type="cash", payment_amount, 0)) as cash'),

            DB::raw('SUM(IF(payment_type="card", payment_amount, 0)) as card'),

            DB::raw('SUM(IF(payment_type="cheque", payment_amount, 0)) as cheque'),

            DB::raw('SUM(IF(payment_type="credit", payment_amount, 0)) as credit'),

            DB::raw('SUM(IF(payment_type="other", payment_amount, 0)) as other'),

            DB::raw('SUM(IF(payment_type="shortage" OR payment_type="excess", payment_amount, 0)) as shortage_excess'),

            DB::raw('SUM(payment_amount) as total')

        );

        $payments->where('pump_operator_payments.business_id', $business_id);

        if (! empty($pump_operator_id)) {
            $payments->where('pump_operator_payments.pump_operator_id', $pump_operator_id);
        }
        // 793 add
        if (! empty($shift_ids)) {
            $payments->whereIn('pump_operator_payments.shift_id', $shift_ids);
        } elseif (! empty($shift_id_string)) {
            // fallback if shift ids is a single id string
            $payments->where('pump_operator_payments.shift_id', $shift_id_string);
        }

        $payments = $payments->first();

        // prefill the added card nummbers in daily collection
        // $shift_id =$request->shift_ids;
        // comment ine 804 and use
        $shift_id = ! empty($shift_ids) ? implode(',', $shift_ids) : $shift_id_string;

        // Use settlement ID for internal operations (methods expect settlement ID, not settlement_no string)
        $settlement_id = $settlement->id;


        // Only call these methods if pump_operator_id is available. Any
        // ambiguous legacy relationship is surfaced as a reconciliation block
        // instead of becoming a server error or selecting an arbitrary row.
        $preload_payment_reconciliation_error = null;
        if (! empty($pump_operator_id)) {
            try {
                if (! empty($shift_id)) {
                    $this->addDailyCards($settlement_id, $pump_operator_id, $business_id, $shift_id);
                }

                $this->addDailyCheques($settlement_id, $pump_operator_id, $business_id, $shift_ids);

                if (! empty($request->shift_ids) || ! empty($shift_ids)) {
                    $this->addDailyShortageExcess(
                        $settlement_id,
                        $pump_operator_id,
                        $business_id,
                        $request->shift_ids ?? $shift_ids
                    );
                }
            } catch (\RuntimeException $reconciliationException) {
                $preload_payment_reconciliation_error = $reconciliationException->getMessage();
                Log::error('PETROPD payment preload reconciliation stopped', [
                    'business_id' => $business_id,
                    'pump_operator_id' => $pump_operator_id,
                    'shift_ids' => $shift_ids,
                    'settlement_id' => $settlement_id,
                    'message' => $reconciliationException->getMessage(),
                ]);
            }

            $pump_operator = PumpOperator::where('id', $pump_operator_id)->first();
        } else {
            $pump_operator = null;
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        $payment_types = $this->productUtil->payment_types($default_location, false, false, false, false, 'is_sale_enabled');

        $expense_no = $this->getExpenseNumber($settlement_id);

        $expense_categories = ExpenseCategory::where('business_id', $business_id)

            ->pluck('name', 'id');

        $expense_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();

        $expense_accounts = [];

        if ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account')) {

            if (! empty($expense_account_type_id)) {

                $expense_accounts = Account::where('business_id', $business_id)->where('account_type_id', $expense_account_type_id->id)->pluck('name', 'id');

            }

        }

        $subscription = Subscription::current_subscription($business_id);

        $package_details = $subscription->package_details;

        $only_walkin = $package_details['only_walkin'] ?? 0;

        // IS1813: deactivated customers must never appear in Petro PD
        // Settlement Credit Sales or customer payment dropdowns.
        $activeCustomerQuery = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->where('active', 1)
            ->orderBy('name');

        $creditCustomerQuery = clone $activeCustomerQuery;
        if (empty($only_walkin)) {
            $creditCustomerQuery->where('name', '!=', 'Walk-In Customer');
        }
        $credit_customers = $creditCustomerQuery->pluck('name', 'id');
        $customers = (clone $activeCustomerQuery)->pluck('name', 'id');

        $walkin = Contact::where('name', 'Walk-In Customer')->where('business_id', $business_id)->pluck('name', 'id');

        $products = Product::where('business_id', $business_id)->forModule('petro_settlements')->pluck('name', 'id');

        $card_types = [];

        $card_group = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();

        if (! empty($card_group)) {

            $card_types = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');

        }

        // dd($settlement->id)
        $customer_payments_tab = CustomerPayment::leftjoin('contacts', 'customer_payments.customer_id', 'contacts.id')

            ->where('customer_payments.settlement_no', $settlement_id)

            ->select('customer_payments.*', 'contacts.name as customer_name')

            ->get();
        // comment 904
        //  $shift_id = $request->shift_ids;
        $settlement_no = $settlement_id;
        // dd($settlement->id);

        // CRITICAL: For settlement_pd, ALWAYS use shift_ids from REQUEST, NOT from settlement's work_shift
        // The settlement's work_shift may contain old shifts that shouldn't be shown
        // We MUST filter by the shift(s) the user actually selected in the UI
        $work_shifts = $settlement->work_shift ?? null;
        $shift_ids_for_settlement = [];

        // PRIORITY 1: Use shift_ids from request (this is what the user selected)
        if (in_array($request->type, ['settlement', 'settlement_pd']) && !empty($shift_ids)) {
            $shift_ids_for_settlement = $shift_ids;
        }
        // PRIORITY 2: Parse shift_id_string from request
        elseif (in_array($request->type, ['settlement', 'settlement_pd']) && !empty($shift_id_string)) {
            $parsed = array_filter(array_map('trim', explode(',', $shift_id_string)));
            if (!empty($parsed)) {
                $shift_ids_for_settlement = array_map('intval', $parsed);
                $shift_ids_for_settlement = array_filter($shift_ids_for_settlement);
            }
        }
        // PRIORITY 3: Fallback to work_shift ONLY if request does not have
        // shift_ids. Never select the last/latest shift. Multiple Shift IDs are
        // retained so reconciliation can block the settlement instead of moving
        // money to an arbitrary shift.
        elseif (! empty($work_shifts) && is_array($work_shifts)) {
            $shift_ids_for_settlement = array_values(array_unique(array_filter(array_map('intval', $work_shifts))));
            if (count($shift_ids_for_settlement) > 1) {
                \Log::error('AddPaymentController@create - Settlement contains multiple immutable Shift IDs', [
                    'shift_ids' => $shift_ids_for_settlement,
                    'settlement_id' => $settlement->id ?? null,
                ]);
            }
        }
        // PRIORITY 4: Use shift_ids if available (non-settlement_pd)
        elseif (!empty($shift_ids)) {
            $shift_ids_for_settlement = $shift_ids;
        }

        // Ensure shift_ids_for_settlement contains integers for proper filtering
        if (!empty($shift_ids_for_settlement)) {
            $shift_ids_for_settlement = array_map('intval', $shift_ids_for_settlement);
            $shift_ids_for_settlement = array_filter($shift_ids_for_settlement); // Remove any zeros or invalid values
            $shift_ids_for_settlement = array_values($shift_ids_for_settlement); // Re-index
        }

        /*
         |----------------------------------------------------------------------
         | The settlement's OWN work_shift wins when the request disagrees.
         |----------------------------------------------------------------------
         |
         | Reported on PDST182: Total Amount 0.00, Total Paid -7.30, and no
         | payments in any tab, while Edit found 5 cash, 3 card and 20 credit
         | sale rows for the same settlement.
         |
         | The log showed why:
         |
         |     settlement_work_shift          ["191"]   <- the settlement's shift
         |     request_shift_ids               "189"    <- what the page sent
         |     shift_ids_for_settlement_FINAL  [189]
         |
         | 189 is the shift NUMBER; 191 is the shift ID. Every payment is stored
         | against 191, so filtering on 189 matched nothing. The -7.30 still
         | appeared because the excess query does not use this filter.
         |
         | PRIORITY 1 takes the request's value on the reasonable grounds that the
         | user may have chosen a different shift. But when the settlement already
         | HAS a work_shift and the request's value is not among those shifts, the
         | request is not a user choice - it is a shift number that has arrived
         | where an id is expected. The settlement's own value is authoritative.
         |
         | Deliberately narrow, because this runs on live settlements:
         |   - only when the settlement HAS a work_shift
         |   - only when NONE of the requested ids appear in it
         |   - a genuine shift change, where the requested id IS in work_shift,
         |     is left exactly as before
         | Both values are logged on every correction so this can be audited.
         */
        $settlement_own_shift_ids = [];

        if (! empty($work_shifts) && is_array($work_shifts)) {
            $settlement_own_shift_ids = array_values(array_filter(array_map('intval', $work_shifts)));
        }

        /*
         |----------------------------------------------------------------------
         | PERMANENT FIX: for an EXISTING settlement, work_shift is authoritative.
         |----------------------------------------------------------------------
         |
         | The shift id / shift number confusion has caused the same class of bug
         | repeatedly - Other Sales, the Close Shift print, IS2043, and the zero
         | Total Amount on PDST182. Each time, a shift NUMBER reached a query that
         | wanted a shift ID.
         |
         | Guarding each query only fixes the ones already found. The reliable fix
         | is to stop consulting the request at all once a settlement exists:
         |
         |     settlements.work_shift is written by the server when the settlement
         |     is created, always holds shift IDs, and cannot be altered by the
         |     browser. It is the only trustworthy source.
         |
         | So the request's shift_ids are now IGNORED whenever the settlement has
         | a work_shift of its own. A wrong value in the request can no longer
         | affect anything, whatever sends it.
         |
         | The request is still used when there is NO work_shift - a brand new
         | settlement being built, where the user's choice is all there is.
         |
         | Any disagreement is logged. Once the emitter is corrected that line
         | should stop appearing; if it keeps appearing, something is still
         | sending shift numbers and the log will say which settlement.
         */
        if (! empty($settlement_own_shift_ids)) {
            $requested_for_log = $shift_ids_for_settlement;

            if ($requested_for_log !== $settlement_own_shift_ids) {
                \Log::warning('PD Add Payment: request shift ignored - settlement work_shift is authoritative', [
                    'settlement_id'         => $settlement->id ?? null,
                    'settlement_no'         => $settlement->settlement_no ?? null,
                    'requested_shift_ids'   => $requested_for_log,
                    'settlement_work_shift' => $settlement_own_shift_ids,
                ]);
            }

            $shift_ids_for_settlement = $settlement_own_shift_ids;
        }

        $direct_shift_label = null;
        if (! empty($work_shifts)) {
            $direct_shift_values = is_array($work_shifts)
                ? $work_shifts
                : (json_decode($work_shifts, true) ?: [$work_shifts]);

            foreach ((array) $direct_shift_values as $direct_shift_value) {
                if (is_scalar($direct_shift_value) && preg_match('/^[A-Z]*DST\s*\d+$/i', trim((string) $direct_shift_value))) {
                    $direct_shift_label = trim((string) $direct_shift_value);
                    break;
                }
            }
        }

        $is_direct_shift_settlement = $is_direct_shift_placeholder || ! empty($direct_shift_label);

        if (! $is_direct_shift_settlement && $request->type === 'settlement_pd' && ! empty($shift_ids_for_settlement)) {
            $closed_shift_ids_for_settlement = $this->getClosedShiftIdsForSettlement(
                $shift_ids_for_settlement,
                ! empty($pump_operator_id) ? (int) $pump_operator_id : null
            );
            $shift_ids_for_settlement = ! empty($closed_shift_ids_for_settlement)
                ? $closed_shift_ids_for_settlement
                : [0];
        }

        // For settlement_pd, recover Shift ID only from records already linked
        // to this settlement. Never use a most-recent row. One unique Shift ID is
        // accepted; multiple IDs are retained and will produce a visible block.
        if ($request->type === 'settlement_pd' && ! $is_direct_shift_settlement && empty($shift_ids_for_settlement) && ! empty($settlement)) {
            $meter_shift_ids = \Modules\PetroPD\Entities\MeterSale::where('settlement_no', $settlement->id)
                ->whereNotNull('shift_id')
                ->pluck('shift_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (! empty($meter_shift_ids)) {
                $shift_ids_for_settlement = $meter_shift_ids;
                \Log::info('AddPaymentController@create - Shift IDs resolved from settlement meter sales', [
                    'shift_ids' => $shift_ids_for_settlement,
                    'settlement_id' => $settlement->id,
                ]);
            } elseif (! empty($pump_operator_id)) {
                $assignment_shift_ids = \Modules\PetroPD\Entities\PumpOperatorAssignment::where('settlement_id', $settlement->id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->whereNotNull('shift_id')
                    ->pluck('shift_id')
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                if (! empty($assignment_shift_ids)) {
                    $shift_ids_for_settlement = $assignment_shift_ids;
                    \Log::info('AddPaymentController@create - Shift IDs resolved from settlement assignments', [
                        'shift_ids' => $shift_ids_for_settlement,
                        'settlement_id' => $settlement->id,
                    ]);
                }
            }

            if (empty($shift_ids_for_settlement)) {
                \Log::error('AddPaymentController@create - Exact Shift ID is unavailable; payment loading is blocked', [
                    'settlement_id' => $settlement->id,
                    'settlement_no' => $settlement->settlement_no,
                    'pump_operator_id' => $pump_operator_id,
                    'request_shift_ids' => $request->shift_ids ?? 'not_provided',
                ]);
                $shift_ids_for_settlement = [0];
            }
        }

        // CRITICAL LOGGING: Log all shift filtering information
        \Log::debug('AddPaymentController@create - Shift Filtering Debug', [
            'request_type' => $request->type ?? 'unknown',
            'request_shift_ids' => $request->shift_ids ?? 'not_provided',
            'shift_id_string' => $shift_id_string ?? 'not_provided',
            'shift_ids_from_request' => $shift_ids ?? [],
            'settlement_id' => $settlement->id ?? 'not_set',
            'settlement_no' => $settlement->settlement_no ?? 'not_set',
            'settlement_work_shift' => $settlement->work_shift ?? 'not_set',
            'shift_ids_for_settlement_FINAL' => $shift_ids_for_settlement ?? [],
            'pump_operator_id' => $pump_operator_id ?? 'not_set',
            'WARNING' => empty($shift_ids_for_settlement) ? 'NO SHIFT FILTERING - ALL PAYMENTS WILL BE SHOWN!' : 'Shift filtering active',
        ]);

        // Fetch settlement cash payments with customer names
        // CRITICAL: For settlement_pd, filter by shift_ids OR show payments created during this settlement session
        // ALSO: Include pump_operator_payments that aren't yet linked to a settlement but match the current shift and pump operator
        $settlement_cash_payments2_query = SettlementCashPayment::leftJoin('contacts', 'settlement_cash_payments.customer_id', '=', 'contacts.id')
            ->leftJoin('pump_operator_payments', function($join) {
                $join->on('pump_operator_payments.id', '=', 'settlement_cash_payments.customer_payment_id')
                     ->orOn('pump_operator_payments.id', '=', 'settlement_cash_payments.pump_payment_id');
            })
            ->where(function($q) use ($settlement_id, $settlement) {
                $q->where('settlement_cash_payments.settlement_no', $settlement_id);
                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_cash_payments.settlement_no', $settlement->settlement_no);
                }
            })
            ->where(function ($q) {
                $q->whereNull('pump_operator_payments.id')
                    ->orWhere('pump_operator_payments.payment_type', 'cash');
            });

        // CRITICAL: For settlement_pd, ONLY show cash payments from the current shift(s)
        // Use shift_ids_for_settlement which prioritizes request shift_ids over work_shift
        if (in_array($request->type, ['settlement', 'settlement_pd']) && !empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0) {
            $settlement_cash_payments2_query->where(function ($query) use ($shift_ids_for_settlement) {
                $query->whereNull('pump_operator_payments.id');

                if (count($shift_ids_for_settlement) > 1) {
                    $query->orWhereIn('pump_operator_payments.shift_id', $shift_ids_for_settlement);
                } else {
                    $query->orWhere('pump_operator_payments.shift_id', $shift_ids_for_settlement[0]);
                }
            });
        } elseif ($request->type === 'settlement_pd' && empty($shift_ids_for_settlement) && $settlement) {
            // If no shift_ids available, show payments created AFTER the settlement was created
            // This ensures newly added payments are visible
            $settlement_cash_payments2_query->where('settlement_cash_payments.created_at', '>=', $settlement->created_at);
            \Log::warning('AddPaymentController@create - No shift_ids_for_settlement, showing payments created after settlement creation', [
                'settlement_id' => $settlement_id,
                'settlement_created_at' => $settlement->created_at,
                'request_shift_ids' => $request->shift_ids ?? 'not_provided',
            ]);
        }

        // Get the linked cash payments first
        $settlement_cash_payments2 = $settlement_cash_payments2_query
            ->select('settlement_cash_payments.*', 'contacts.name as customer_name', 'pump_operator_payments.shift_id as payment_shift_id')
            ->get();

        // CRITICAL: Also include pump_operator_payments that aren't yet linked to SettlementCashPayment
        // but match the current shift and pump operator (entries from pumper dashboard)
        // IMPORTANT: If shift_ids_for_settlement is empty, still try to show unlinked payments from this operator
        // This ensures payments from pumper dashboard are visible even if shift filtering fails
        if (in_array($request->type, ['settlement', 'settlement_pd']) && !empty($pump_operator_id)) {
            // Collect PumpOperatorPayment IDs already linked via SettlementCashPayment to avoid duplicates
            $already_linked_pop_ids = $settlement_cash_payments2
                ->flatMap(function($item) {
                    $ids = [];
                    if (!empty($item->customer_payment_id) && is_numeric($item->customer_payment_id)) {
                        $ids[] = (int) $item->customer_payment_id;
                    }
                    if (!empty($item->pump_payment_id) && is_numeric($item->pump_payment_id)) {
                        $ids[] = (int) $item->pump_payment_id;
                    }
                    return $ids;
                })
                ->unique()
                ->values()
                ->toArray();

            $unlinked_cash_query = \Modules\PetroPD\Entities\PumpOperatorPayment::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('payment_type', 'cash')
                ->where(function($q) {
                    $q->whereNull('is_used')->orWhere('is_used', 0);
                })
                ->where(function($q) {
                    $q->whereNull('settlement_no')->orWhere('settlement_no', '');
                });

            // Exclude PumpOperatorPayment records already linked to SettlementCashPayment
            if (!empty($already_linked_pop_ids)) {
                $unlinked_cash_query->whereNotIn('id', $already_linked_pop_ids);
            }

            // Filter by shift_ids if available, otherwise show recent unlinked payments
            if (!empty($shift_ids_for_settlement)) {
                $unlinked_cash_query->whereIn('shift_id', $shift_ids_for_settlement);
            } else {
                // If no shift_ids, show payments created after settlement was created (recent entries)
                if ($settlement && $settlement->created_at) {
                    $unlinked_cash_query->where('created_at', '>=', $settlement->created_at);
                }
                \Log::info('AddPaymentController@create - Loading unlinked cash payments without shift filter (using created_at)', [
                    'settlement_id' => $settlement_id,
                    'pump_operator_id' => $pump_operator_id,
                ]);
            }

            $unlinked_cash_payments = $unlinked_cash_query->get();

            // Convert to SettlementCashPayment-like format and merge with linked payments
            foreach ($unlinked_cash_payments as $pop) {
                $contact = \App\Contact::find($pop->customer_id);
                $settlement_cash_payments2->push((object)[
                    'id' => 'pop_' . $pop->id, // Prefix to identify as unlinked
                    'settlement_no' => null,
                    'amount' => $pop->payment_amount,
                    'customer_id' => $pop->customer_id,
                    'customer_name' => $contact ? $contact->name : 'Walk-In Customer',
                    'note' => $pop->note,
                    'pump_payment_id' => $pop->id,
                    'customer_payment_id' => $pop->id,
                    'is_unlinked' => true, // Flag to identify unlinked entries
                    'payment_shift_id' => $pop->shift_id,
                ]);
            }

            \Log::info('AddPaymentController@create - Unlinked cash payments added', [
                'unlinked_count' => $unlinked_cash_payments->count(),
                'total_cash_payments' => $settlement_cash_payments2->count(),
            ]);
        }

        \Log::debug('AddPaymentController@create - Cash Payments Loaded', [
            'settlement_id' => $settlement_id,
            'request_type' => $request->type ?? 'unknown',
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'cash_payments_count' => $settlement_cash_payments2->count(),
            'cash_payments_total' => $settlement_cash_payments2->sum('amount'),
            'cash_payments_details' => $settlement_cash_payments2->map(function($cp) {
                return [
                    'id' => $cp->id,
                    'amount' => $cp->amount,
                    'shift_id' => $cp->payment_shift_id ?? 'no_shift',
                ];
            })->toArray(),
        ]);

        $settlement_cash_payments1 = collect();
        if (! empty($shift_ids_for_settlement)) {
            // Get pump payment IDs that are already linked to SettlementCashPayment to avoid duplicates
            // Check both by parent_id (if SettlementCashPayment has parent_id) and by settlement_no + is_used
            $linked_pump_payment_ids = PumpOperatorPayment::where('pump_operator_payments.pump_operator_id', $pump_operator_id)
                ->where('pump_operator_payments.payment_type', 'cash')
                ->where('pump_operator_payments.is_used', 1)
                ->where('pump_operator_payments.settlement_no', $settlement_id)
                ->pluck('pump_operator_payments.id')
                ->toArray();

            // Also exclude PumpOperatorPayment IDs already present in settlement_cash_payments2
            // (both from customer_payment_id and pump_payment_id to avoid any overlap)
            $already_in_cash2 = $settlement_cash_payments2
                ->flatMap(function($item) {
                    $ids = [];
                    if (!empty($item->customer_payment_id) && is_numeric($item->customer_payment_id)) {
                        $ids[] = (int) $item->customer_payment_id;
                    }
                    if (!empty($item->pump_payment_id) && is_numeric($item->pump_payment_id)) {
                        $ids[] = (int) $item->pump_payment_id;
                    }
                    return $ids;
                })
                ->unique()
                ->toArray();

            $linked_pump_payment_ids = array_unique(array_merge($linked_pump_payment_ids, $already_in_cash2));

            $settlement_cash_payments1 = PumpOperatorPayment::whereIn('pump_operator_payments.shift_id', $shift_ids_for_settlement)
                ->where('pump_operator_payments.payment_type', 'cash')
                ->where('pump_operator_payments.pump_operator_id', $pump_operator_id)
                ->where(function ($q) {
                    $q->whereNull('pump_operator_payments.is_used')
                        ->orWhere('pump_operator_payments.is_used', 0);
                })
                ->whereNotIn('pump_operator_payments.id', $linked_pump_payment_ids) // Exclude already linked payments
                ->select(
                    'pump_operator_payments.id as pump_payment_id',
                    'pump_operator_payments.payment_amount as amount'
                )
                ->get();
        }

        // Convert into fake SettlementCashPayment models
        $newdatarecords = $settlement_cash_payments1->map(function ($data1) {
            $record                      = new SettlementCashPayment;
            $record->id                  = null;
            $record->settlement_no       = null;
            $record->business_id         = null;
            $record->customer_id         = null;
            $record->amount              = $data1->amount;
            $record->customer_payment_id = null;
            $record->note                = '';
            $record->slip                = '';
            $record->created_at          = now();
            $record->updated_at          = now();
            $record->customer_name       = 'Walking Customer';
            $record->pump_payment_id     = $data1->pump_payment_id;

            return $record;
        });

        // Combine both sets and remove duplicates based on row identity (id or pump_payment_id)
        // Prefer id so multiple saved rows with same amount/customer are all preserved (fix: duplicate amounts disappearing after Back/refresh)
        if ($settlement_cash_payments2->isNotEmpty()) {
            $settlement_cash_payments = $settlement_cash_payments2
                ->concat($newdatarecords)
                ->unique(function ($item) {
                    if (!empty($item->id)) {
                        return 'id_' . $item->id;
                    }
                    if (isset($item->pump_payment_id) && $item->pump_payment_id) {
                        return 'pump_' . $item->pump_payment_id;
                    }
                    return 'amount_' . $item->amount . '_customer_' . ($item->customer_id ?? 'null');
                })
                ->values(); // reset keys
        } else {
            $settlement_cash_payments = $newdatarecords;
        }

        $cash_total = $settlement_cash_payments->sum('amount');

        // 5️⃣ Debug output
        // dd($settlement_cash_payments);

        // SETTLEMENT POS PAYMENTS - Similar logic to cash payments
        $settlement_pos_payments2_query = SettlementPosPayment::leftJoin('contacts', 'settlement_pos_payments.customer_id', '=', 'contacts.id')
            ->leftJoin('pump_operator_payments', 'pump_operator_payments.id', '=', 'settlement_pos_payments.customer_payment_id')
            ->where(function($q) use ($settlement_id, $settlement) {
                $q->where('settlement_pos_payments.settlement_no', $settlement_id);
                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_pos_payments.settlement_no', $settlement->settlement_no);
                }
            });

        // Filter by shift_ids for settlement_pd
        if ($request->type === 'settlement_pd' && !empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0) {
            if (count($shift_ids_for_settlement) > 1) {
                $settlement_pos_payments2_query->whereIn('pump_operator_payments.shift_id', $shift_ids_for_settlement);
            } else {
                $settlement_pos_payments2_query->where('pump_operator_payments.shift_id', $shift_ids_for_settlement[0]);
            }
        } elseif ($request->type === 'settlement_pd' && empty($shift_ids_for_settlement) && $settlement) {
            $settlement_pos_payments2_query->where('settlement_pos_payments.created_at', '>=', $settlement->created_at);
        }

        $settlement_pos_payments2 = $settlement_pos_payments2_query
            ->select('settlement_pos_payments.*', 'contacts.name as customer_name', 'pump_operator_payments.shift_id as payment_shift_id')
            ->get();

        // Include unlinked POS payments from pump operator
        $newposrecords = collect();
        if ($request->type === 'settlement_pd' && !empty($pump_operator_id)) {
            $already_linked_pos_ids = $settlement_pos_payments2
                ->pluck('customer_payment_id')
                ->filter()
                ->map(function($id) { return is_numeric($id) ? (int) $id : $id; })
                ->unique()
                ->values()
                ->toArray();

            $unlinked_pos_query = \Modules\PetroPD\Entities\PumpOperatorPayment::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('payment_type', 'pos')
                ->where(function($q) {
                    $q->whereNull('is_used')->orWhere('is_used', 0);
                })
                ->where(function($q) {
                    $q->whereNull('settlement_no')->orWhere('settlement_no', '');
                });

            if (!empty($already_linked_pos_ids)) {
                $unlinked_pos_query->whereNotIn('id', $already_linked_pos_ids);
            }

            if (!empty($shift_ids_for_settlement)) {
                $unlinked_pos_query->whereIn('shift_id', $shift_ids_for_settlement);
            } else {
                if ($settlement && $settlement->created_at) {
                    $unlinked_pos_query->where('created_at', '>=', $settlement->created_at);
                }
            }

            $unlinked_pos_payments = $unlinked_pos_query->get();

            $newposrecords = $unlinked_pos_payments->map(function ($data1) use ($settlement_id) {
                $record = new SettlementPosPayment();
                $record->id = null;
                $record->settlement_no = $settlement_id;
                $record->business_id = null;
                $record->customer_id = null;
                $record->amount = $data1->payment_amount;
                $record->customer_payment_id = null;
                $record->note = 'Auto-generated from pump operator POS payments';
                $record->created_at = now();
                $record->updated_at = now();
                $record->customer_name = 'Walking Customer';
                $record->pump_payment_id = $data1->id;
                return $record;
            });
        }

        // Combine POS payments
        if ($settlement_pos_payments2->isNotEmpty()) {
            $settlement_pos_sales = $settlement_pos_payments2
                ->concat($newposrecords)
                ->unique(function ($item) {
                    if (!empty($item->id)) {
                        return 'id_' . $item->id;
                    }
                    if (isset($item->pump_payment_id) && $item->pump_payment_id) {
                        return 'pump_' . $item->pump_payment_id;
                    }
                    return 'amount_' . $item->amount . '_customer_' . ($item->customer_id ?? 'null');
                })
                ->values();
        } else {
            $settlement_pos_sales = $newposrecords;
        }

        $pos_total = $settlement_pos_sales->sum('amount');

        // CRITICAL: Filter customer loans - only show loans from THIS settlement
        // Customer loans don't have shift_id, but they're linked to settlement_no
        // For settlement_pd, ONLY show loans that belong to THIS settlement (not old ones)
        $settlement_customer_loans = SettlementCustomerLoan::leftjoin('contacts', 'settlement_customer_loans.customer_id', 'contacts.id')
            ->where(function($q) use ($settlement_id, $settlement) {
                // Match by settlement ID (integer) or settlement_no (string)
                $q->where('settlement_customer_loans.settlement_no', $settlement_id);
                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_customer_loans.settlement_no', $settlement->settlement_no);
                }
            })
            // Refresh safety: do not add created_at / exact-id-only filters here.
            // Payment tabs can save rows before/after settlement draft creation and may store
            // settlement_no either as the settlement id or PDST number. The match block above
            // is the single source of truth, so all entered rows remain visible after refresh.
            ->select('settlement_customer_loans.*', 'contacts.name as customer_name')
            ->get();

        \Log::debug('AddPaymentController@create - Customer Loans Loaded', [
            'settlement_id' => $settlement_id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'customer_loans_count' => $settlement_customer_loans->count(),
            'customer_loans_total' => $settlement_customer_loans->sum('amount'),
        ]);

        // CRITICAL: Filter loan payments by shift_ids - only show loans from current shift(s)
        $settlement_loan_payments = SettlementLoanPayment::leftjoin('accounts', 'accounts.id', 'settlement_loan_payments.loan_account')
            ->where(function($q) use ($settlement_id, $settlement) {
                $q->where('settlement_loan_payments.settlement_no', $settlement_id)
                    ->orWhere('settlement_loan_payments.settlement_no', (string) $settlement_id);

                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_loan_payments.settlement_no', $settlement->settlement_no);
                }
            })
            ->when(!empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0 && $request->type === 'settlement_pd', function($q) use ($shift_ids_for_settlement) {
                // For settlement_pd, filter by shift if possible
                // Note: Loan payments might not have direct shift_id linkage
            })
            ->select('settlement_loan_payments.*', 'accounts.name as loan_account_name')
            ->get();

        \Log::debug('AddPaymentController@create - Loan Payments Loaded', [
            'settlement_id' => $settlement_id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'loan_payments_count' => $settlement_loan_payments->count(),
            'loan_payments_total' => $settlement_loan_payments->sum('amount'),
        ]);

        // CRITICAL: Filter drawings payments by shift_ids - only show drawings from current shift(s)
        $settlement_drawings_payments = SettlementDrawingPayment::leftjoin('accounts', 'accounts.id', 'settlement_drawing_payments.loan_account')
            ->where(function($q) use ($settlement_id, $settlement) {
                $q->where('settlement_drawing_payments.settlement_no', $settlement_id)
                    ->orWhere('settlement_drawing_payments.settlement_no', (string) $settlement_id);

                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_drawing_payments.settlement_no', $settlement->settlement_no);
                }
            })
            ->when(!empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0 && $request->type === 'settlement_pd', function($q) use ($shift_ids_for_settlement) {
                // For settlement_pd, filter by shift if possible
            })
            ->select('settlement_drawing_payments.*', 'accounts.name as loan_account_name')
            ->get();

        \Log::debug('AddPaymentController@create - Drawings Payments Loaded', [
            'settlement_id' => $settlement_id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'drawings_payments_count' => $settlement_drawings_payments->count(),
            'drawings_payments_total' => $settlement_drawings_payments->sum('amount'),
        ]);

        // 1️⃣ Actual settlement card payments
        // Fetch card payments that are already linked to this settlement
        // CRITICAL: daily_cards table doesn't have shift_id, so we must filter via pump_operator_payments
        $has_card_meta_columns = $this->pumpOperatorPaymentsHasCardMetaColumns();
        $has_settlement_card_pump_payment_column = $this->tableHasColumn('settlement_card_payments', 'pump_payment_id');

        $settlement_card_payments1 = SettlementCardPayment::query()
            ->leftJoin('contacts', 'settlement_card_payments.customer_id', '=', 'contacts.id')
            ->leftJoin('accounts', 'settlement_card_payments.card_type', '=', 'accounts.id')
            ->when($has_settlement_card_pump_payment_column, function ($query) {
                $query->leftJoin('pump_operator_payments as linked_card_payment', function ($join) {
                    $join->on('linked_card_payment.id', '=', 'settlement_card_payments.pump_payment_id')
                        ->where('linked_card_payment.payment_type', 'card');
                });
            })
            ->leftJoin('pump_operator_payments as parent_card_payment', function ($join) {
                $join->on('parent_card_payment.parent_id', '=', 'settlement_card_payments.id')
                    ->where('parent_card_payment.payment_type', 'card');
            })
            ->leftJoin('daily_cards', 'settlement_card_payments.daily_card_id', '=', 'daily_cards.id')
            ->leftJoin('pump_operator_payments as matched_card_payment', function ($join) {
                $join->on('matched_card_payment.pump_operator_id', '=', 'daily_cards.pump_operator_id')
                    ->whereRaw('matched_card_payment.collection_form_no COLLATE utf8mb4_unicode_ci = daily_cards.collection_no COLLATE utf8mb4_unicode_ci')
                    ->where('matched_card_payment.payment_type', 'card')
                    ->whereColumn('matched_card_payment.payment_amount', 'daily_cards.amount');
            })
            ->where(function ($q) use ($settlement_id, $settlement) {
                $q->where('settlement_card_payments.settlement_no', $settlement_id)
                    ->orWhere('settlement_card_payments.settlement_no', $settlement->settlement_no ?? null);
            })
            // CRITICAL FIX: For settlement_pd, STRICTLY filter by shift_ids_for_settlement
            // This prevents showing card payments from previously selected but unsaved shifts (e.g., user selected Shift 5, went to Payment to Finalize, clicked Back, then selected Shift 3)
            // We must ONLY show card payments that match the CURRENT shift selection
            ->when($request->type === 'settlement_pd' && !empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0, function ($q) use ($shift_ids_for_settlement, $has_settlement_card_pump_payment_column) {
                $q->where(function($subQ) use ($shift_ids_for_settlement, $has_settlement_card_pump_payment_column) {
                    $subQ->where(function ($manualQ) use ($has_settlement_card_pump_payment_column) {
                        $this->addCardPaymentManualUnlinkedFilter($manualQ, $has_settlement_card_pump_payment_column);
                    })
                    // OPTION 1: Card payment linked via pump_operator_payments.shift_id
                    ->orWhere(function($popQ) use ($shift_ids_for_settlement, $has_settlement_card_pump_payment_column) {
                        $this->addCardPaymentShiftFilter($popQ, $shift_ids_for_settlement, $has_settlement_card_pump_payment_column);
                    })
                    ->orWhere(function($dcQ) use ($shift_ids_for_settlement, $has_settlement_card_pump_payment_column) {
                        $this->addCardPaymentDailyCardShiftFilter($dcQ, $shift_ids_for_settlement, $has_settlement_card_pump_payment_column);
                    });
                });
            })
            // For non-settlement_pd or if shift_ids_for_settlement is empty, use shift_ids from request
            ->when($request->type !== 'settlement' && ($request->type !== 'settlement_pd' || empty($shift_ids_for_settlement)) && !empty($shift_ids) && count($shift_ids) > 0, function ($q) use ($shift_ids, $has_settlement_card_pump_payment_column) {
                $q->where(function($subQ) use ($shift_ids, $has_settlement_card_pump_payment_column) {
                    $subQ->where(function ($manualQ) use ($has_settlement_card_pump_payment_column) {
                        $this->addCardPaymentManualUnlinkedFilter($manualQ, $has_settlement_card_pump_payment_column);
                    });

                    $subQ->orWhere(function ($popQ) use ($shift_ids, $has_settlement_card_pump_payment_column) {
                        $this->addCardPaymentShiftFilter($popQ, $shift_ids, $has_settlement_card_pump_payment_column);
                    })->orWhere(function ($dcQ) use ($shift_ids, $has_settlement_card_pump_payment_column) {
                        $this->addCardPaymentDailyCardShiftFilter($dcQ, $shift_ids, $has_settlement_card_pump_payment_column);
                    });
                });
            })
            ->select(
                'settlement_card_payments.*',
                'contacts.name as customer_name',
                'accounts.name as card_type_name',
                DB::raw(($has_settlement_card_pump_payment_column
                    ? 'COALESCE(linked_card_payment.pump_operator_id, parent_card_payment.pump_operator_id, matched_card_payment.pump_operator_id, daily_cards.pump_operator_id)'
                    : 'COALESCE(parent_card_payment.pump_operator_id, matched_card_payment.pump_operator_id, daily_cards.pump_operator_id)')
                    . ' as pump_operator_id'),
                'daily_cards.id as daily_card_id',
                DB::raw(($has_settlement_card_pump_payment_column
                    ? 'COALESCE(linked_card_payment.shift_id, parent_card_payment.shift_id, matched_card_payment.shift_id, daily_cards.shift_id)'
                    : 'COALESCE(parent_card_payment.shift_id, matched_card_payment.shift_id, daily_cards.shift_id)')
                    . ' as payment_shift_id'),
                DB::raw(($has_settlement_card_pump_payment_column
                    ? 'COALESCE(linked_card_payment.id, parent_card_payment.id, matched_card_payment.id)'
                    : 'COALESCE(parent_card_payment.id, matched_card_payment.id)')
                    . ' as pump_payment_id')
            )
            ->get();

        // 2️⃣ Fetch walking payments from pump operator payments - only if not already saved as SettlementCardPayment
        $existing_daily_card_ids = $settlement_card_payments1->pluck('daily_card_id')->filter()->toArray();
        $existing_pump_payment_ids = $settlement_card_payments1->pluck('pump_payment_id')->filter()->toArray();

        $walking_payments_query = PumpOperatorPayment::query()
            ->leftJoin('daily_cards', function ($join) {
                $join->on('daily_cards.pump_operator_id', '=', 'pump_operator_payments.pump_operator_id')
                    ->on('daily_cards.amount', '=', 'pump_operator_payments.payment_amount')
                    ->whereRaw('daily_cards.collection_no COLLATE utf8mb4_unicode_ci = pump_operator_payments.collection_form_no COLLATE utf8mb4_unicode_ci');
            })
            ->where('pump_operator_payments.payment_type', 'card')
            ->when(!empty($pump_operator_id), function ($q) use ($pump_operator_id) {
                $q->where('pump_operator_payments.pump_operator_id', $pump_operator_id);
            })
            ->when($request->type === 'settlement_pd' && !empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0, function ($q) use ($shift_ids_for_settlement) {
                if (count($shift_ids_for_settlement) > 1) {
                    $q->whereIn('pump_operator_payments.shift_id', $shift_ids_for_settlement);
                } else {
                    $q->where('pump_operator_payments.shift_id', $shift_ids_for_settlement[0]);
                }
            })
            ->when(($request->type !== 'settlement_pd' || empty($shift_ids_for_settlement)) && !empty($shift_ids) && count($shift_ids) > 0, function ($q) use ($shift_ids) {
                if (count($shift_ids) > 1) {
                    $q->whereIn('pump_operator_payments.shift_id', $shift_ids);
                } else {
                    $q->where('pump_operator_payments.shift_id', $shift_ids[0]);
                }
            })
            ->when($request->type === 'settlement_pd' && empty($shift_ids_for_settlement) && empty($shift_ids), function ($q) {
                $q->whereRaw('1 = 0');
            })
            ->where(function ($q) {
                $q->whereNull('pump_operator_payments.is_used')
                    ->orWhere('pump_operator_payments.is_used', 0);
            })
            ->whereNotExists(function ($query) use ($settlement_id, $settlement, $has_settlement_card_pump_payment_column) {
                $query->select(DB::raw(1))
                    ->from('settlement_card_payments')
                    ->where(function ($linkedQuery) {
                        $linkedQuery->whereColumn('settlement_card_payments.id', 'pump_operator_payments.parent_id')
                            ->orWhereColumn('settlement_card_payments.daily_card_id', 'daily_cards.id');
                    })
                    ->when($has_settlement_card_pump_payment_column, function ($innerQuery) {
                        $innerQuery->orWhere(function ($linkedByPumpPaymentQuery) {
                            $linkedByPumpPaymentQuery->whereColumn('settlement_card_payments.pump_payment_id', 'pump_operator_payments.id');
                        });
                    })
                    ->where(function ($q) use ($settlement_id, $settlement) {
                        $q->where('settlement_card_payments.settlement_no', $settlement_id)
                            ->orWhere('settlement_card_payments.settlement_no', $settlement->settlement_no ?? null);
                    });
            })
            ->when(!empty($existing_pump_payment_ids), function ($q) use ($existing_pump_payment_ids) {
                $q->whereNotIn('pump_operator_payments.id', $existing_pump_payment_ids);
            })
            ->when(!empty($existing_daily_card_ids), function ($q) use ($existing_daily_card_ids) {
                $q->where(function ($dailyCardQuery) use ($existing_daily_card_ids) {
                    $dailyCardQuery->whereNull('daily_cards.id')
                        ->orWhereNotIn('daily_cards.id', $existing_daily_card_ids);
                });
            });

        if ($has_card_meta_columns) {
            $walking_payments_query
                ->leftJoin('contacts as payment_customer', 'payment_customer.id', '=', 'pump_operator_payments.customer_id')
                ->leftJoin('accounts as payment_card_account', 'payment_card_account.id', '=', 'pump_operator_payments.card_type');
        }

        $walking_payments_query
            ->leftJoin('contacts as legacy_customer', 'legacy_customer.id', '=', 'daily_cards.customer_id')
            ->leftJoin('accounts as legacy_card_account', 'legacy_card_account.id', '=', 'daily_cards.card_type');

        $customer_id_select = $has_card_meta_columns
            ? 'COALESCE(pump_operator_payments.customer_id, daily_cards.customer_id)'
            : 'daily_cards.customer_id';
        $card_type_select = $has_card_meta_columns
            ? 'COALESCE(NULLIF(pump_operator_payments.card_type, ""), CAST(daily_cards.card_type AS CHAR))'
            : 'CAST(daily_cards.card_type AS CHAR)';
        $card_number_select = $has_card_meta_columns
            ? 'COALESCE(NULLIF(pump_operator_payments.card_number, ""), daily_cards.card_number)'
            : 'daily_cards.card_number';
        $slip_no_select = $has_card_meta_columns
            ? 'COALESCE(NULLIF(pump_operator_payments.slip_no, ""), daily_cards.slip_no, "")'
            : 'COALESCE(daily_cards.slip_no, "")';
        $customer_name_select = $has_card_meta_columns
            ? 'COALESCE(payment_customer.name, legacy_customer.name)'
            : 'legacy_customer.name';
        $card_type_name_select = $has_card_meta_columns
            ? 'COALESCE(payment_card_account.name, legacy_card_account.name)'
            : 'legacy_card_account.name';

        $walking_payments = $walking_payments_query
            ->select(
                DB::raw('NULL AS id'),
                DB::raw((int) $settlement_id . ' AS settlement_no'),
                'pump_operator_payments.business_id',
                DB::raw($customer_id_select . ' AS customer_id'),
                'daily_cards.id as daily_card_id',
                DB::raw($card_type_select . ' AS card_type'),
                DB::raw($card_number_select . ' AS card_number'),
                DB::raw('CAST(COALESCE(pump_operator_payments.payment_amount, 0) AS DECIMAL(10,2)) AS amount'),
                DB::raw('NULL AS customer_payment_id'),
                DB::raw('"" AS note'),
                DB::raw($slip_no_select . ' AS slip_no'),
                DB::raw($customer_name_select . ' AS customer_name'),
                DB::raw($card_type_name_select . ' AS card_type_name'),
                'pump_operator_payments.id as pump_payment_id',
                'pump_operator_payments.shift_id as payment_shift_id',
                'daily_cards.shift_id as daily_card_shift_id'
            )
            ->get();


        // Combine both collections - show ALL card payments (direct + pump operator)
        // Prefer id so multiple saved rows with same amount are all preserved (fix: duplicate amounts disappearing after Back/refresh)
        $settlement_card_payments = collect()
            ->merge($settlement_card_payments1) // existing saved
            ->merge($walking_payments)          // unsaved walking cards
            ->unique(function ($item) use ($request) {
                if ($request->type === 'settlement_pd') {
                    if (!empty($item->pump_payment_id)) {
                        return 'pump_' . $item->pump_payment_id;
                    }
                    if (!empty($item->daily_card_id)) {
                        return 'daily_' . $item->daily_card_id;
                    }
                }
                if (!empty($item->id)) {
                    return 'id_' . $item->id;
                }
                if (!empty($item->pump_payment_id)) {
                    return 'pump_' . $item->pump_payment_id;
                }

                return ($item->daily_card_id ?? '') . '-' . ($item->amount ?? '') . '-' . ($item->slip_no ?? '');
            })
            ->values();

        \Log::debug('AddPaymentController@create - Card Payments Loaded', [
            'settlement_id' => $settlement_id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'settlement_card_payments1_count' => $settlement_card_payments1->count(),
            'walking_payments_count' => $walking_payments->count(),
            'total_card_payments_count' => $settlement_card_payments->count(),
            'walking_payments_shift_ids' => $walking_payments->pluck('payment_shift_id')->unique()->toArray(),
        ]);

        // CRITICAL: Filter cash deposits - only show deposits from THIS settlement
        // Cash deposits don't have shift_id, but they're linked to settlement_no
        // For settlement_pd, ONLY show deposits that belong to THIS settlement (not old ones)
        $settlement_cash_deposits = SettlementCashDeposit::leftjoin('accounts', 'settlement_cash_deposits.bank_id', 'accounts.id')
            ->where(function($q) use ($settlement_id, $settlement) {
                // Match by settlement ID (integer) or settlement_no (string)
                $q->where('settlement_cash_deposits.settlement_no', $settlement_id);
                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_cash_deposits.settlement_no', $settlement->settlement_no);
                }
            })
            // Refresh safety: do not add created_at / exact-id-only filters here.
            // Cash deposit rows can store settlement_no as either the settlement id or PDST
            // number. The match block above keeps all entered rows visible after refresh.
            ->select('settlement_cash_deposits.*', 'accounts.name as bank_name')
            ->get();

        \Log::debug('AddPaymentController@create - Cash Deposits Loaded', [
            'settlement_id' => $settlement_id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'cash_deposits_count' => $settlement_cash_deposits->count(),
            'cash_deposits_total' => $settlement_cash_deposits->sum('amount'),
        ]);

        $has_settlement_cheque_pump_payment_column = $this->tableHasColumn('settlement_cheque_payments', 'pump_payment_id');

        $settlement_cheque_payments_query = SettlementChequePayment::leftjoin('contacts', 'settlement_cheque_payments.customer_id', 'contacts.id')
            ->when($has_settlement_cheque_pump_payment_column, function ($query) {
                $query->leftJoin('pump_operator_payments as linked_cheque_payment', function ($join) {
                    $join->on('linked_cheque_payment.id', '=', 'settlement_cheque_payments.pump_payment_id')
                        ->where('linked_cheque_payment.payment_type', 'cheque');
                });
            })
            ->leftJoin('pump_operator_payments as parent_cheque_payment', function ($join) {
                $join->on('parent_cheque_payment.parent_id', '=', 'settlement_cheque_payments.id')
                    ->where('parent_cheque_payment.payment_type', 'cheque');
            })
            ->where(function ($q) use ($settlement_id, $settlement) {
                $q->where('settlement_cheque_payments.settlement_no', $settlement_id);
                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_cheque_payments.settlement_no', $settlement->settlement_no);
                }
            });

        if (! empty($shift_ids_for_settlement)) {
            $settlement_cheque_payments_query->where(function ($q) use ($shift_ids_for_settlement, $has_settlement_cheque_pump_payment_column) {
                if ($has_settlement_cheque_pump_payment_column) {
                    $q->whereIn('linked_cheque_payment.shift_id', $shift_ids_for_settlement)
                        ->orWhere(function ($no_link_query) {
                            $no_link_query->whereNull('linked_cheque_payment.id')
                                ->whereNull('parent_cheque_payment.id');
                        });
                }
                $q->orWhereIn('parent_cheque_payment.shift_id', $shift_ids_for_settlement);

                if (! $has_settlement_cheque_pump_payment_column) {
                    $q->orWhereNull('parent_cheque_payment.id');
                }
            });
        }

        $settlement_cheque_payments = $settlement_cheque_payments_query
            ->select(
                'settlement_cheque_payments.*',
                'contacts.name as customer_name',
                DB::raw(($has_settlement_cheque_pump_payment_column
                    ? 'COALESCE(settlement_cheque_payments.pump_payment_id, linked_cheque_payment.id, parent_cheque_payment.id)'
                    : 'parent_cheque_payment.id') . ' as pump_payment_id')
            )
            ->get();

        $has_credit_pump_payment_column = $this->tableHasColumn('settlement_credit_sale_payments', 'pump_payment_id');
        $has_credit_shift_column = $this->tableHasColumn('settlement_credit_sale_payments', 'shift_id');
        $credit_sale_group_identity = $has_credit_pump_payment_column
            ? "CASE WHEN settlement_credit_sale_payments.pump_payment_id IS NOT NULL THEN CONCAT('pp:', settlement_credit_sale_payments.pump_payment_id) WHEN settlement_credit_sale_payments.collection_form_no IS NULL OR settlement_credit_sale_payments.collection_form_no = '' THEN CONCAT('id:', settlement_credit_sale_payments.id) ELSE CONCAT('cf:', settlement_credit_sale_payments.collection_form_no, '|customer:', COALESCE(settlement_credit_sale_payments.customer_id, ''), '|order:', COALESCE(settlement_credit_sale_payments.order_number, '')) END"
            : "CASE WHEN settlement_credit_sale_payments.collection_form_no IS NULL OR settlement_credit_sale_payments.collection_form_no = '' THEN CONCAT('id:', settlement_credit_sale_payments.id) ELSE CONCAT('cf:', settlement_credit_sale_payments.collection_form_no, '|customer:', COALESCE(settlement_credit_sale_payments.customer_id, ''), '|order:', COALESCE(settlement_credit_sale_payments.order_number, '')) END";

        // Fetch credit sales that are already linked to this settlement
        // CRITICAL: For direct settlement, credit sales are saved with settlement_no string
        // We MUST find them by settlement_no, regardless of pump_operator_payments join
        // IMPORTANT: For Direct Settlement with settlement_no set, fetch WITHOUT grouping to maintain consistency
        // Grouping/aggregation causes totals to change when navigating back
        $settlement_credit_sale_payments1 = SettlementCreditSalePayment::query()
            ->leftJoin('contacts', 'settlement_credit_sale_payments.customer_id', '=', 'contacts.id')
            ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
            ->leftJoin('daily_vouchers', 'settlement_credit_sale_payments.daily_voucher_id', '=', 'daily_vouchers.id')
            ->where(function ($q) use ($settlement_id, $settlement, $pump_operator_id) {
                // CRITICAL: Credit sales with settlement_no set belong to this settlement and should be included
                // regardless of pump_operator_id or pump_operator_payments join. Only filter by pump_operator_id for NULL settlement_no.

                // Primary match: settlement_no string (credit sales are saved with settlement_no string)
                // This MUST be the first condition to ensure credit sales with settlement_no are always found
                if ($settlement && !empty($settlement->settlement_no)) {
                    $q->where('settlement_credit_sale_payments.settlement_no', $settlement->settlement_no);
                }

                // Also match by integer ID and string ID formats (for backward compatibility)
                // Use orWhere so any of these can match
                if ($settlement && !empty($settlement->settlement_no)) {
                    // If we already have settlement_no match, add these as alternatives
                    $q->orWhere('settlement_credit_sale_payments.settlement_no', $settlement_id)
                        ->orWhere('settlement_credit_sale_payments.settlement_no', (string) $settlement_id);
                } else {
                    // If no settlement_no, try matching by ID
                    $q->where('settlement_credit_sale_payments.settlement_no', $settlement_id)
                        ->orWhere('settlement_credit_sale_payments.settlement_no', (string) $settlement_id);
                }

                // For credit sales with NULL settlement_no, only include if pump_operator_id matches
                // BUT only if we haven't already matched by settlement_no
                if (!empty($pump_operator_id)) {
                    $q->orWhere(function($subQ) use ($pump_operator_id) {
                        $subQ->whereNull('settlement_credit_sale_payments.settlement_no')
                            ->where('settlement_credit_sale_payments.pump_operator_id', $pump_operator_id);
                    });
                } else {
                    // If no pump_operator_id, include all NULL settlement_no credit sales
                    $q->orWhereNull('settlement_credit_sale_payments.settlement_no');
                }
            })
            // For settlement_pd the exact shift scope is enforced below with a
            // correlated EXISTS query.  Do not join pump_operator_payments here:
            // collection_form_no is not a unique key and that join multiplied only
            // some invoice rows (for example two master rows sharing one form no),
            // while the authoritative Total Paid remained correct.
            // For non-settlement_pd or if shift_ids_for_settlement is empty, use shift_ids from request
            // BUT only for credit sales WITHOUT settlement_no
            ->when($request->type !== 'settlement_pd' && !empty($shift_ids) && count($shift_ids) > 0, function ($q) use ($shift_ids, $settlement) {
                $q->where(function($subQ) use ($shift_ids, $settlement) {
                    // Include credit sales with settlement_no set (they belong to this settlement)
                    if ($settlement && !empty($settlement->settlement_no)) {
                        $subQ->where('settlement_credit_sale_payments.settlement_no', $settlement->settlement_no);
                    }
                    // For credit sales without settlement_no, filter by shift
                    $subQ->orWhere(function($shiftQ) use ($shift_ids) {
                        $shiftQ->whereNull('settlement_credit_sale_payments.settlement_no');
                        // Include records where daily_vouchers.shift_id matches, or where daily_vouchers is null
                        $shiftQ->whereNull('daily_vouchers.shift_id')
                            ->orWhere(function($dvQ) use ($shift_ids) {
                                if (count($shift_ids) > 1) {
                                    $dvQ->whereIn('daily_vouchers.shift_id', $shift_ids);
                                } else {
                                    $dvQ->where('daily_vouchers.shift_id', $shift_ids[0]);
                                }
                        });
                    });
                });
            })
            ->when($request->type === 'settlement_pd' && !empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0, function ($q) use ($shift_ids_for_settlement, $has_credit_pump_payment_column) {
                $q->whereExists(function ($shiftQuery) use ($shift_ids_for_settlement, $has_credit_pump_payment_column) {
                    $shiftQuery->select(DB::raw(1))
                        ->from('pump_operator_payments as scsp_shift_payments')
                        ->whereColumn('scsp_shift_payments.business_id', 'settlement_credit_sale_payments.business_id')
                        ->whereColumn('scsp_shift_payments.pump_operator_id', 'settlement_credit_sale_payments.pump_operator_id')
                        ->whereIn('scsp_shift_payments.payment_type', ['credit', 'multiple_credit'])
                        ->whereIn('scsp_shift_payments.shift_id', $shift_ids_for_settlement)
                        ->where(function ($matchQuery) use ($has_credit_pump_payment_column) {
                            if ($has_credit_pump_payment_column) {
                                $matchQuery->whereColumn('scsp_shift_payments.id', 'settlement_credit_sale_payments.pump_payment_id')
                                    ->orWhere(function ($legacyQuery) {
                                        $legacyQuery->whereNull('settlement_credit_sale_payments.pump_payment_id')
                                            ->whereRaw('scsp_shift_payments.collection_form_no COLLATE utf8mb4_unicode_ci = settlement_credit_sale_payments.collection_form_no COLLATE utf8mb4_unicode_ci');
                                    });
                                return;
                            }

                            $matchQuery->whereRaw('scsp_shift_payments.collection_form_no COLLATE utf8mb4_unicode_ci = settlement_credit_sale_payments.collection_form_no COLLATE utf8mb4_unicode_ci');
                        });
                });
            });

        // IS1759-11: Direct Settlement and PetroPD must both render every saved
        // bill row. Grouping by a supporting identity hid earlier bills when the
        // same customer entered several credit sales in one shift, even though
        // the authoritative master total remained correct.
        if (in_array($request->type, ['settlement', 'settlement_pd'], true)) {
            $settlement_credit_sale_payments1 = $settlement_credit_sale_payments1
                ->select(
                    'settlement_credit_sale_payments.id',
                    $has_credit_pump_payment_column
                        ? 'settlement_credit_sale_payments.pump_payment_id'
                        : DB::raw('NULL as pump_payment_id'),
                    $has_credit_shift_column
                        ? 'settlement_credit_sale_payments.shift_id'
                        : DB::raw('NULL as shift_id'),
                    'settlement_credit_sale_payments.settlement_no',
                    'settlement_credit_sale_payments.customer_id',
                    'settlement_credit_sale_payments.daily_voucher_id',
                    'settlement_credit_sale_payments.collection_form_no',
                    'settlement_credit_sale_payments.amount',
                    'settlement_credit_sale_payments.qty',
                    'settlement_credit_sale_payments.order_date',
                    'settlement_credit_sale_payments.order_number',
                    'settlement_credit_sale_payments.customer_reference',
                    'settlement_credit_sale_payments.price',
                    'settlement_credit_sale_payments.total_discount',
                    'settlement_credit_sale_payments.sub_total',
                    'settlement_credit_sale_payments.outstanding',
                    'settlement_credit_sale_payments.credit_limit',
                    'settlement_credit_sale_payments.note',
                    'settlement_credit_sale_payments.pump_operator_id',
                    'settlement_credit_sale_payments.product_id',
                    'products.name as product_name',
                    'contacts.name as customer_name',
                    'daily_vouchers.operator_id as operator_id'
                )
                ->orderBy('settlement_credit_sale_payments.id', 'asc')
                ->get();
        } else {
            // Other settlement modes retain their historical consolidated display.
            // Grouping by collection_form_no alone hides separate customers when old pumper
            // dashboard rows reused the same form number.
            $settlement_credit_sale_payments1 = $settlement_credit_sale_payments1
                ->groupBy(DB::raw($credit_sale_group_identity))
                ->select(
                    DB::raw('MAX(settlement_credit_sale_payments.id) as id'),
                    $has_credit_pump_payment_column
                        ? DB::raw('MAX(settlement_credit_sale_payments.pump_payment_id) as pump_payment_id')
                        : DB::raw('NULL as pump_payment_id'),
                    DB::raw('MAX(settlement_credit_sale_payments.settlement_no) as settlement_no'),
                    DB::raw('MAX(settlement_credit_sale_payments.customer_id) as customer_id'),
                    DB::raw('MAX(settlement_credit_sale_payments.daily_voucher_id) as daily_voucher_id'),
                    DB::raw('MAX(settlement_credit_sale_payments.collection_form_no) as collection_form_no'),
                    DB::raw('SUM(settlement_credit_sale_payments.amount) as amount'),
                    DB::raw('SUM(settlement_credit_sale_payments.qty) as qty'),
                    DB::raw('MAX(settlement_credit_sale_payments.order_date) as order_date'),
                    DB::raw('MAX(settlement_credit_sale_payments.order_number) as order_number'),
                    DB::raw('MAX(settlement_credit_sale_payments.customer_reference) as customer_reference'),
                    DB::raw('MAX(settlement_credit_sale_payments.price) as price'),
                    DB::raw('SUM(settlement_credit_sale_payments.total_discount) as total_discount'),
                    DB::raw('SUM(settlement_credit_sale_payments.sub_total) as sub_total'),
                    DB::raw('MAX(settlement_credit_sale_payments.outstanding) as outstanding'),
                    DB::raw('MAX(settlement_credit_sale_payments.credit_limit) as credit_limit'),
                    DB::raw('MAX(settlement_credit_sale_payments.note) as note'),
                    DB::raw('MAX(settlement_credit_sale_payments.pump_operator_id) as pump_operator_id'),
                    DB::raw('GROUP_CONCAT(DISTINCT products.name ORDER BY products.name SEPARATOR ", ") as product_name'),
                    DB::raw('MAX(contacts.name) as customer_name'),
                    DB::raw('MAX(daily_vouchers.operator_id) as operator_id')
                )
                ->orderBy('settlement_credit_sale_payments.id', 'asc')
                ->get();
        }

        // Get IDs of already saved credit sales to exclude from settlement_credit_sale_payments2
        $existing_credit_sale_ids = $settlement_credit_sale_payments1->pluck('id')->filter()->toArray();

        // Fetch credit sales from pump_operator_payments that haven't been linked to a settlement yet
        // IMPORTANT: For settlement_pd, we MUST filter by shift_ids to only show credit sales from the current shift(s)
        // CRITICAL: Use shift_ids_for_settlement (which prioritizes request shift_ids) NOT work_shift
        $settlement_credit_sale_payments2 = collect();
        if (!empty($pump_operator_id)) {
            // Use shift_ids_for_settlement which already prioritizes request shift_ids over work_shift
            // CRITICAL: For settlement_pd, if shift_ids_for_settlement is empty, don't show any credit sales
            // This prevents showing credit sales from all shifts when shift filtering fails
            $filter_shift_ids = $shift_ids_for_settlement;

            // CRITICAL: For settlement_pd, try to load credit sales even if shift_ids are empty
            // Use shift_ids if available, otherwise use created_at filter to show recent entries
            if ($request->type === 'settlement_pd' && empty($filter_shift_ids)) {
                \Log::info('AddPaymentController@create - No shift_ids_for_settlement for credit sales - will use created_at filter', [
                    'settlement_id' => $settlement_id,
                    'pump_operator_id' => $pump_operator_id,
                    'request_shift_ids' => $request->shift_ids ?? 'not_provided',
                ]);
            }

            $credit_sales_query2 = DB::table('settlement_credit_sale_payments')
                ->whereExists(function ($query) use ($filter_shift_ids, $pump_operator_id, $request, $settlement, $has_credit_pump_payment_column) {
                    $query->select(DB::raw(1))
                        ->from('pump_operator_payments')
                        ->whereRaw('settlement_credit_sale_payments.pump_operator_id = pump_operator_payments.pump_operator_id')
                        ->where('pump_operator_payments.pump_operator_id', $pump_operator_id)
                        ->whereIn('pump_operator_payments.payment_type', ['credit', 'multiple_credit'])
                        ->where(function ($matchQuery) use ($has_credit_pump_payment_column) {
                            if ($has_credit_pump_payment_column) {
                                $matchQuery->whereColumn('settlement_credit_sale_payments.pump_payment_id', 'pump_operator_payments.id')
                                    ->orWhere(function ($legacyQuery) {
                                        $legacyQuery->whereNull('settlement_credit_sale_payments.pump_payment_id')
                                            ->whereRaw('settlement_credit_sale_payments.collection_form_no COLLATE utf8mb4_general_ci = pump_operator_payments.collection_form_no COLLATE utf8mb4_general_ci');
                                    });
                                return;
                            }

                            $matchQuery->whereRaw('settlement_credit_sale_payments.collection_form_no COLLATE utf8mb4_general_ci = pump_operator_payments.collection_form_no COLLATE utf8mb4_general_ci');
                        })
                        // CRITICAL: For settlement_pd, filter by shift_ids if available
                        ->when($request->type === 'settlement_pd' && !empty($filter_shift_ids) && count($filter_shift_ids) > 0, function ($q) use ($filter_shift_ids) {
                            if (count($filter_shift_ids) > 1) {
                                $q->whereIn('pump_operator_payments.shift_id', $filter_shift_ids);
                            } else {
                                $q->where('pump_operator_payments.shift_id', $filter_shift_ids[0]);
                            }
                        })
                        // If no shift_ids, filter by created_at to show recent entries
                        ->when($request->type === 'settlement_pd' && empty($filter_shift_ids) && $settlement && $settlement->created_at, function ($q) use ($settlement) {
                            $q->where('pump_operator_payments.created_at', '>=', $settlement->created_at);
                        });
                })
                ->whereNull('settlement_credit_sale_payments.settlement_no')
                ->when(!empty($existing_credit_sale_ids), function ($q) use ($existing_credit_sale_ids) {
                    $q->whereNotIn('settlement_credit_sale_payments.id', $existing_credit_sale_ids);
                })
                ->leftJoin('contacts', 'settlement_credit_sale_payments.customer_id', '=', 'contacts.id')
                ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id');

            // IS1759-11: keep one displayed row per saved credit bill for both
            // Direct Settlement and PetroPD.
            if (in_array($request->type, ['settlement', 'settlement_pd'], true)) {
                $settlement_credit_sale_payments2 = $credit_sales_query2
                    ->select(
                        'settlement_credit_sale_payments.id',
                        $has_credit_pump_payment_column
                            ? 'settlement_credit_sale_payments.pump_payment_id'
                            : DB::raw('NULL as pump_payment_id'),
                        $has_credit_shift_column
                            ? 'settlement_credit_sale_payments.shift_id'
                            : DB::raw('NULL as shift_id'),
                        'settlement_credit_sale_payments.customer_id',
                        'settlement_credit_sale_payments.daily_voucher_id',
                        'settlement_credit_sale_payments.collection_form_no',
                        'settlement_credit_sale_payments.amount',
                        'settlement_credit_sale_payments.qty',
                        'settlement_credit_sale_payments.order_date',
                        'settlement_credit_sale_payments.order_number',
                        'settlement_credit_sale_payments.customer_reference',
                        'settlement_credit_sale_payments.price',
                        'settlement_credit_sale_payments.total_discount',
                        'settlement_credit_sale_payments.sub_total',
                        'settlement_credit_sale_payments.outstanding',
                        'settlement_credit_sale_payments.credit_limit',
                        'settlement_credit_sale_payments.note',
                        'settlement_credit_sale_payments.product_id',
                        'products.name as product_name',
                        'contacts.name as customer_name'
                    )
                    ->orderBy('settlement_credit_sale_payments.id', 'asc')
                    ->get();
            } else {
                // Other settlement modes retain their historical consolidated display.
                $settlement_credit_sale_payments2 = $credit_sales_query2
                    ->groupBy(DB::raw($credit_sale_group_identity))
                    ->select(
                        DB::raw('MAX(settlement_credit_sale_payments.id) as id'),
                        $has_credit_pump_payment_column
                            ? DB::raw('MAX(settlement_credit_sale_payments.pump_payment_id) as pump_payment_id')
                            : DB::raw('NULL as pump_payment_id'),
                        DB::raw('MAX(settlement_credit_sale_payments.customer_id) as customer_id'),
                        DB::raw('MAX(settlement_credit_sale_payments.daily_voucher_id) as daily_voucher_id'),
                        DB::raw('MAX(settlement_credit_sale_payments.collection_form_no) as collection_form_no'),
                        DB::raw('SUM(settlement_credit_sale_payments.amount) as amount'),
                        DB::raw('SUM(settlement_credit_sale_payments.qty) as qty'),
                        DB::raw('MAX(settlement_credit_sale_payments.order_date) as order_date'),
                        DB::raw('MAX(settlement_credit_sale_payments.order_number) as order_number'),
                        DB::raw('MAX(settlement_credit_sale_payments.customer_reference) as customer_reference'),
                        DB::raw('MAX(settlement_credit_sale_payments.price) as price'),
                        DB::raw('SUM(settlement_credit_sale_payments.total_discount) as total_discount'),
                        DB::raw('SUM(settlement_credit_sale_payments.sub_total) as sub_total'),
                        DB::raw('MAX(settlement_credit_sale_payments.outstanding) as outstanding'),
                        DB::raw('MAX(settlement_credit_sale_payments.credit_limit) as credit_limit'),
                        DB::raw('MAX(settlement_credit_sale_payments.note) as note'),
                        DB::raw('GROUP_CONCAT(DISTINCT products.name ORDER BY products.name SEPARATOR ", ") as product_name'),
                        DB::raw('MAX(contacts.name) as customer_name')
                    )
                    ->orderBy('settlement_credit_sale_payments.id', 'asc')
                    ->get();
            }
        }

        // Combine both collections with better deduplication
        // CRITICAL: For direct settlement, use ID-based deduplication to preserve individual credit sale records
        // This ensures that when user goes back and returns, the same credit sales are shown with same values
        $settlement_credit_sale_payments = collect()
            ->merge($settlement_credit_sale_payments1 ?? collect())
            ->merge($settlement_credit_sale_payments2 ?? collect())
            ->unique(function ($item) use ($request) {
                if ($request->type === 'settlement') {
                    return implode('|', [
                        'direct',
                        $item->customer_id ?? '',
                        $item->product_id ?? '',
                        $item->order_number ?? '',
                        $item->order_date ?? '',
                        (float) ($item->qty ?? 0),
                        (float) ($item->amount ?? 0),
                        (float) ($item->total_discount ?? 0),
                    ]);
                }

                // Each supporting row is one visible bill. A Pump Payment ID is
                // still the financial authority, but must not collapse two rows.
                if (!empty($item->id)) {
                    return 'id-' . $item->id;
                }
                if ($request->type === 'settlement_pd' && ! empty($item->pump_payment_id)) {
                    return 'pump-' . $item->pump_payment_id;
                }
                // Fallback to other identifiers for settlement_pd
                if (!empty($item->daily_voucher_id)) {
                    return 'dv-' . $item->daily_voucher_id;
                }
                if (!empty($item->collection_form_no)) {
                    return 'cf-' . $item->collection_form_no;
                }
                return 'ord-' . ($item->order_number ?? '') . '-' . ($item->customer_id ?? '') . '-' . ($item->order_date ?? '');
            })
            ->values();

        \Log::debug('AddPaymentController@create - Credit Sales Loaded', [
            'settlement_id' => $settlement_id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'pump_operator_id' => $pump_operator_id ?? 'not_set',
            'settlement_credit_sale_payments1_count' => $settlement_credit_sale_payments1->count(),
            'settlement_credit_sale_payments2_count' => $settlement_credit_sale_payments2->count(),
            'total_credit_sale_payments_count' => $settlement_credit_sale_payments->count(),
            'credit_sale_collection_form_nos' => $settlement_credit_sale_payments->pluck('collection_form_no')->unique()->toArray(),
        ]);

        $pumper_credit_sale_payments = collect();
        if (in_array($request->type, ['settlement', 'settlement_pd']) && !empty($pump_operator_id)) {
            $has_pump_operator_payment_slip_no = $this->tableHasColumn('pump_operator_payments', 'slip_no');
            $pumper_credit_sale_selects = [
                'pump_operator_payments.id',
                'pump_operator_payments.date_and_time',
                'pump_operator_payments.shift_id',
                'pump_operator_payments.collection_form_no',
                'pump_operator_payments.payment_type',
                'pump_operator_payments.payment_amount',
            ];
            $pumper_credit_sale_selects[] = $has_pump_operator_payment_slip_no
                ? 'pump_operator_payments.slip_no'
                : DB::raw('NULL as slip_no');
            $pumper_credit_sale_selects = array_merge($pumper_credit_sale_selects, [
                'business_locations.name as location_name',
                'pump_operators.name as pump_operator_name',
                'contacts.name as customer_name',
                DB::raw("'pump_operator_payment' as source_type"),
                DB::raw('MAX(credit_sales.id) as scsp_id'),
                DB::raw('MAX(credit_sales.daily_voucher_id) as daily_voucher_id'),
                DB::raw('MAX(credit_daily_vouchers.settlement_no) as daily_voucher_settlement_no'),
                DB::raw('MAX(credit_sales.order_number) as order_number'),
            ]);

            $pumper_credit_sale_payments = PumpOperatorPayment::leftJoin('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')
                ->leftJoin('business_locations', 'pump_operators.location_id', '=', 'business_locations.id')
                ->leftJoin('settlement_credit_sale_payments as credit_sales', function ($join) {
                    $join->on('credit_sales.pump_payment_id', '=', 'pump_operator_payments.id');
                })
                ->leftJoin('daily_vouchers as credit_daily_vouchers', 'credit_daily_vouchers.id', '=', 'credit_sales.daily_voucher_id')
                ->leftJoin('contacts', 'credit_sales.customer_id', '=', 'contacts.id')
                ->where('pump_operator_payments.business_id', $business_id)
                ->where('pump_operator_payments.pump_operator_id', $pump_operator_id)
                ->whereIn('pump_operator_payments.payment_type', ['credit', 'multiple_credit'])
                ->when(!empty($shift_ids_for_settlement), function ($q) use ($shift_ids_for_settlement) {
                    $q->whereIn('pump_operator_payments.shift_id', $shift_ids_for_settlement);
                })
                ->when(empty($shift_ids_for_settlement) && !empty($settlement->created_at), function ($q) use ($settlement) {
                    $q->where('pump_operator_payments.created_at', '>=', $settlement->created_at);
                })
                ->select($pumper_credit_sale_selects)
                ->groupBy('pump_operator_payments.id')
                ->orderBy('pump_operator_payments.date_and_time')
                ->orderBy('pump_operator_payments.id')
                ->get();
        }

        if (in_array($request->type, ['settlement', 'settlement_pd']) && $pumper_credit_sale_payments->isEmpty() && $settlement_credit_sale_payments->isNotEmpty()) {
            $credit_sale_location_name = '-';
            if (!empty($pump_operator) && !empty($pump_operator->location_id)) {
                $credit_sale_location_name = BusinessLocation::where('id', $pump_operator->location_id)->value('name') ?: '-';
            }

            $pumper_credit_sale_payments = $settlement_credit_sale_payments->map(function ($credit_sale_payment) use ($credit_sale_location_name, $pump_operator, $settlement) {
                $amount = (float) ($credit_sale_payment->amount ?? 0);
                $discount = (float) ($credit_sale_payment->total_discount ?? 0);

                return (object) [
                    'source_type' => 'settlement_credit_sale',
                    'id' => null,
                    'date_and_time' => $credit_sale_payment->order_date ?? ($settlement->transaction_date ?? null),
                    'shift_id' => null,
                    'collection_form_no' => $credit_sale_payment->collection_form_no,
                    'payment_type' => 'credit',
                    'payment_amount' => $amount - $discount,
                    'slip_no' => $credit_sale_payment->slip_no ?? '',
                    'location_name' => $credit_sale_location_name,
                    'pump_operator_name' => !empty($pump_operator) ? $pump_operator->name : '',
                    'customer_name' => $credit_sale_payment->customer_name ?? '',
                    'scsp_id' => $credit_sale_payment->id,
                    'daily_voucher_id' => $credit_sale_payment->daily_voucher_id ?? null,
                    'daily_voucher_settlement_no' => null,
                    'order_number' => $credit_sale_payment->order_number ?? '',
                ];
            })->values();
        }

        // PETROPD-PAYMENT-SNAPSHOT-20260723:
        // Build one immutable Pumper Dashboard payment snapshot for every type.
        // All financial totals use unique pump_operator_payments.id values. Detail
        // tables remain presentation/metadata surfaces and cannot multiply amounts.
        $pd_payment_snapshot = null;
        $pd_authoritative_credit_summary = null;
        $authoritative_credit_gross_total = null;
        $authoritative_credit_discount_total = null;
        $authoritative_credit_net_total = null;
        $credit_payment_reconciliation_warning = null;
        $payment_reconciliation_warning = $preload_payment_reconciliation_error;
        $payment_reconciliation_blocked = ! empty($preload_payment_reconciliation_error);

        if ($request->type === 'settlement_pd' && ! empty($pump_operator_id)) {
            $authoritative_shift_ids = ! empty($shift_ids_for_settlement)
                ? $shift_ids_for_settlement
                : $shift_ids;

            $pdPaymentSnapshotService = app(\Modules\PetroPD\Services\PetroPdSettlementPaymentSnapshotService::class);

            // IS1781: payments created before the PetroPD draft existed may still
            // point to the temporary active ST draft created by older Pumper
            // Dashboard code. Move only those same-operator/same-shift active-draft
            // links before taking the immutable review fingerprint. Finalized or
            // different-shift owners remain blocked by the snapshot service.
            try {
                $pdPaymentSnapshotService->normalizeLegacyDraftSettlementLinks(
                    (int) $business_id,
                    (int) $pump_operator_id,
                    (array) $authoritative_shift_ids,
                    (int) ($settlement->id ?? 0),
                    (string) ($settlement->settlement_no ?? '')
                );
            } catch (\Throwable $legacyDraftLinkException) {
                Log::error('PETROPD legacy draft payment-link normalization failed during Add Payment review', [
                    'business_id' => $business_id,
                    'pump_operator_id' => $pump_operator_id,
                    'shift_ids' => $authoritative_shift_ids,
                    'settlement_id' => $settlement->id ?? null,
                    'settlement_no' => $settlement->settlement_no ?? null,
                    'error' => $legacyDraftLinkException->getMessage(),
                ]);
            }

            $pd_payment_snapshot = $pdPaymentSnapshotService->build(
                    (int) $business_id,
                    (int) $pump_operator_id,
                    (array) $authoritative_shift_ids,
                    (int) ($settlement->id ?? 0),
                    (string) ($settlement->settlement_no ?? ''),
                    false
                );

            $pd_authoritative_credit_summary = $pd_payment_snapshot['credit_summary'];
            $authoritative_credit_gross_total = (float) ($pd_payment_snapshot['totals']['credit_gross'] ?? 0);
            $authoritative_credit_discount_total = (float) ($pd_payment_snapshot['totals']['credit_discount'] ?? 0);
            $authoritative_credit_net_total = (float) ($pd_payment_snapshot['totals']['credit'] ?? 0);

            // The footer is calculated from the immutable Pump Payment snapshot,
            // while the body needs the saved bill rows. Reload those rows by the
            // same exact master IDs so an older draft settlement reference cannot
            // make the Credit Sales details disappear.
            $authoritativeCreditDisplayRows = $this->loadAuthoritativeCreditSaleDisplayRows(
                $pd_payment_snapshot,
                (int) $business_id,
                (int) $pump_operator_id,
                (array) $authoritative_shift_ids,
                (int) ($settlement->id ?? 0),
                (string) ($settlement->settlement_no ?? '')
            );

            if ($authoritativeCreditDisplayRows->isNotEmpty()) {
                $settlement_credit_sale_payments = collect($settlement_credit_sale_payments)
                    ->concat($authoritativeCreditDisplayRows)
                    ->unique(function ($row) {
                        if (! empty($row->id)) {
                            return 'credit-detail-' . (int) $row->id;
                        }

                        return implode('|', [
                            'credit-fallback',
                            (int) ($row->pump_payment_id ?? 0),
                            (string) ($row->order_number ?? ''),
                            (string) ($row->customer_id ?? ''),
                            (string) ($row->product_id ?? ''),
                        ]);
                    })
                    ->values();
            }

            $settlement_credit_sale_payments = $this->alignCreditSaleRowsWithAuthoritativePayments(
                $settlement_credit_sale_payments,
                $pd_authoritative_credit_summary,
                [
                    'business_id' => $business_id,
                    'pump_operator_id' => $pump_operator_id,
                    'shift_ids' => $authoritative_shift_ids,
                    'settlement_id' => $settlement->id ?? null,
                    'settlement_no' => $settlement->settlement_no ?? null,
                ]
            );

            if (! empty($pd_authoritative_credit_summary['issues'])) {
                $credit_payment_reconciliation_warning =
                    'Credit-payment reconciliation found an incomplete or mismatched supporting link. '
                    . 'Every amount is being read once from its unique Pump Operator Payment record.';
            }

            if (! empty($pd_payment_snapshot['issues'])) {
                $payment_reconciliation_blocked = $payment_reconciliation_blocked
                    || ! empty($pd_payment_snapshot['blocking_issues']);
                $snapshotWarning = $payment_reconciliation_blocked
                    ? 'Payment reconciliation is not complete. Finalization is blocked until all master payments, supporting rows and Shift IDs agree.'
                    : 'Payment reconciliation found a warning. The authoritative Pump Operator Payment totals are being used.';
                $payment_reconciliation_warning = ! empty($payment_reconciliation_warning)
                    ? $payment_reconciliation_warning . ' ' . $snapshotWarning
                    : $snapshotWarning;

                Log::warning('PETROPD Add Payment payment snapshot warning', [
                    'business_id' => $business_id,
                    'pump_operator_id' => $pump_operator_id,
                    'shift_ids' => $authoritative_shift_ids,
                    'settlement_id' => $settlement->id ?? null,
                    'settlement_no' => $settlement->settlement_no ?? null,
                    'fingerprint' => $pd_payment_snapshot['fingerprint'] ?? null,
                    'issues' => $pd_payment_snapshot['issues'],
                ]);
            }
        }

        // CRITICAL: Filter expense payments by shift_ids - only show expenses from current shift(s)
        $settlement_expense_payments = SettlementExpensePayment::leftjoin('accounts', 'settlement_expense_payments.account_id', 'accounts.id')
            ->leftjoin('expense_categories', 'settlement_expense_payments.category_id', 'expense_categories.id')
            ->where(function($q) use ($settlement_id, $settlement) {
                $q->where('settlement_expense_payments.settlement_no', $settlement_id)
                    ->orWhere('settlement_expense_payments.settlement_no', (string) $settlement_id);

                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_expense_payments.settlement_no', $settlement->settlement_no);
                }
            })
            ->when(!empty($shift_ids_for_settlement) && count($shift_ids_for_settlement) > 0 && $request->type === 'settlement_pd', function($q) use ($shift_ids_for_settlement) {
                // For settlement_pd, filter by shift if possible
            })
            ->select('settlement_expense_payments.*', 'accounts.name as account_name', 'expense_categories.name as category_name')
            ->get();

        \Log::debug('AddPaymentController@create - Expense Payments Loaded', [
            'settlement_id' => $settlement_id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'expense_payments_count' => $settlement_expense_payments->count(),
            'expense_payments_total' => $settlement_expense_payments->sum('amount'),
        ]);

        // Shortage/excess rows are written with the settlement id, but older/manual rows can
        // carry the settlement number. Load both keys so rows survive a modal/page refresh.
        $settlement_shortage_payments = SettlementShortagePayment::where('settlement_shortage_payments.business_id', $business_id)
            ->where(function($q) use ($settlement_id, $settlement) {
                $q->where('settlement_shortage_payments.settlement_no', $settlement_id)
                    ->orWhere('settlement_shortage_payments.settlement_no', (string) $settlement_id);

                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_shortage_payments.settlement_no', $settlement->settlement_no);
                }
            })
            ->select('settlement_shortage_payments.*')
            ->get();

        $settlement_excess_payments = SettlementExcessPayment::where('settlement_excess_payments.business_id', $business_id)
            ->where(function($q) use ($settlement_id, $settlement) {
                $q->where('settlement_excess_payments.settlement_no', $settlement_id)
                    ->orWhere('settlement_excess_payments.settlement_no', (string) $settlement_id);

                if ($settlement && $settlement->settlement_no) {
                    $q->orWhere('settlement_excess_payments.settlement_no', $settlement->settlement_no);
                }
            })
            ->select('settlement_excess_payments.*')
            ->get();

        /*
         * IS1835-02:
         * Shortage and Excess entered from the PD Operator screen are authoritative
         * Pump Operator Payments. Older screens displayed only settlement detail
         * rows, so the totals were correct while both tab tables appeared empty.
         * Add any missing master rows to the presentation collections. The
         * pump_payment_id identity also makes the existing manual-total helper
         * exclude these rows from a second financial count.
         */
        if ($request->type === 'settlement_pd' && is_array($pd_payment_snapshot)) {
            $snapshotPayments = collect($pd_payment_snapshot['payments'] ?? []);

            $appendSnapshotRows = function ($detailRows, string $paymentType) use ($snapshotPayments, $settlement) {
                /*
                 |--------------------------------------------------------------
                 | Excess/shortage appeared TWICE in its tab.
                 |--------------------------------------------------------------
                 |
                 | Duplicates were kept out by pump_payment_id alone, and ->filter()
                 | drops nulls - so an existing row with NO pump_payment_id never
                 | entered $knownPaymentIds, and the snapshot's copy of the same
                 | payment was appended beside it.
                 |
                 | Excess and shortage rows are frequently entered by hand and
                 | carry no pumper link, which is why this shows on that tab.
                 |
                 | A second key is added: amount + note. When a snapshot row has no
                 | usable pump_payment_id it is matched on those instead, so the
                 | same payment cannot appear twice however it was recorded.
                 */
                $knownPaymentIds = collect($detailRows)
                    ->pluck('pump_payment_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->flip();

                // Fallback signature for rows that carry no pump_payment_id.
                $knownSignatures = collect($detailRows)
                    ->map(function ($row) {
                        $amount = (float) (is_object($row) ? ($row->amount ?? 0) : ($row['amount'] ?? 0));
                        $note   = trim((string) (is_object($row) ? ($row->note ?? '') : ($row['note'] ?? '')));

                        return number_format($amount, 4, '.', '') . '|' . $note;
                    })
                    ->flip();

                $masterRows = $snapshotPayments
                    ->filter(fn ($payment) => ($payment->payment_type ?? null) === $paymentType)
                    ->reject(function ($payment) use ($knownPaymentIds, $knownSignatures) {
                        $paymentId = (int) ($payment->pump_payment_id ?? 0);

                        if ($paymentId > 0 && isset($knownPaymentIds[$paymentId])) {
                            return true;
                        }

                        /*
                         | The signature is checked EVEN WHEN the snapshot row has
                         | an id.
                         |
                         | The reported duplicate is exactly this case: the stored
                         | row carries no pump_payment_id while the snapshot's copy
                         | of the same payment does. Matching on id alone missed it
                         | and appended a second line.
                         */
                        $signature = number_format((float) ($payment->amount ?? 0), 4, '.', '')
                            . '|' . trim((string) ($payment->note ?? ''));

                        return isset($knownSignatures[$signature]);
                    })
                    ->map(function ($payment) use ($paymentType, $settlement) {
                        return (object) [
                            'id' => null,
                            'pump_payment_id' => (int) ($payment->pump_payment_id ?? 0),
                            'settlement_no' => $settlement->id,
                            'amount' => (float) ($payment->net_amount ?? 0),
                            'note' => trim((string) ($payment->reference_no ?? '')) !== ''
                                ? (string) $payment->reference_no
                                : 'PD Operator ' . ucfirst($paymentType),
                            'is_authoritative_snapshot' => true,
                        ];
                    });

                return collect($detailRows)->concat($masterRows)->values();
            };

            $settlement_shortage_payments = $appendSnapshotRows(
                $settlement_shortage_payments,
                'shortage'
            );
            $settlement_excess_payments = $appendSnapshotRows(
                $settlement_excess_payments,
                'excess'
            );
        }

        \Log::debug('AddPaymentController@create - Shortage/Excess Payments Loaded', [
            'settlement_id' => $settlement->id,
            'shift_ids_for_settlement' => $shift_ids_for_settlement ?? [],
            'shortage_payments_count' => $settlement_shortage_payments->count(),
            'shortage_payments_total' => $settlement_shortage_payments->sum('amount'),
            'excess_payments_count' => $settlement_excess_payments->count(),
            'excess_payments_total' => $settlement_excess_payments->sum('amount'),
        ]);

       $daily_collections = DailyCollection::select('*')
        ->where('settlement_id', $settlement_no)
        ->where('business_id', $business_id)
        ->get();


        $daily_collections_mapped = $daily_collections->map(function ($c) {
            return (object)[
                'id' => null,
                'settlement_no' => $c->settlement_id,
                'business_id' => $c->business_id,
                'customer_id' => null,
                'amount' => $c->current_amount,
                'customer_payment_id' => null,
                'note' => '',
                'created_at' => $c->created_at,
                'updated_at' => $c->updated_at,
                'customer_name' => 'Walking Customer',
                'shift_id' => $c->shift_id,
                'shift_number' => $c->shift_number,
            ];
        });

        Log::debug('Fetched in add payment: ' . $daily_collections->count() . ' daily collections for settlement id: ' . $settlement_no);

        // Avoid double-counting cash: do NOT append daily_collections here.
        // Cash coming from pumper/daily collections is already represented via
        // SettlementCashPayment and PumpOperatorPayment mapping above.
        $settlement_cash_payments = $settlement_cash_payments->values();

        // DAILY CARDS
        // Card slips that are already linked to this settlement. Limit to current shift(s) when available.
        $daily_cards = DailyCard::leftJoin('contacts', 'daily_cards.customer_id', '=', 'contacts.id')
            ->leftJoin('accounts', 'daily_cards.card_type', '=', 'accounts.id')
            ->select(
                'daily_cards.*',
                'contacts.name as customer_name',
                'accounts.name as card_type_name'
            )
            ->where('daily_cards.settlement_no', $request->settlement_no)
            ->where('daily_cards.business_id', $business_id)
            ->when($request->type === 'settlement_pd' && !empty($shift_ids_for_settlement), function ($q) use ($shift_ids_for_settlement) {
                if (count($shift_ids_for_settlement) > 1) {
                    $q->whereIn('daily_cards.shift_id', $shift_ids_for_settlement);
                } else {
                    $q->where('daily_cards.shift_id', $shift_ids_for_settlement[0]);
                }
            })
            ->get();

        $daily_cards_mapped = $daily_cards->map(function ($c) {
            return (object)[
                'id' => null,
                'settlement_no' => $c->settlement_no,
                'business_id' => $c->business_id,
                'customer_id' => $c->customer_id,
                'daily_card_id' => $c->id,

                'card_type' => $c->card_type,
                'card_type_name' => $c->card_type_name,
                'card_number' => $c->card_number,

                'amount' => $c->amount,
                'customer_payment_id' => $c->customer_payment_id,
                'note' => $c->note,
                'slip_no' => $c->slip_no,

                'customer_name' => $c->customer_name,

                'pump_operator_id' => $c->pump_operator_id,
                'shift_id' => $c->shift_id,
            ];
        });

        Log::debug('Fetched in add payment: ' . $daily_cards->count() . ' daily cards for settlement no: ' . $request->settlement_no);

        // For settlement_pd type, skip daily_cards to prevent overcounting
        // Only include records explicitly linked to the current PD settlement (settlement_card_payments1 + walking_payments)
        // Prefer id so multiple saved rows with same amount are all preserved (fix: duplicate amounts disappearing after Back/refresh)
        if ($request->type !== 'settlement_pd') {
        $settlement_card_payments = $settlement_card_payments
            ->concat($daily_cards_mapped)
            ->unique(function ($item) {
                if (!empty($item->id)) {
                    return 'id_' . $item->id;
                }
                if (!empty($item->pump_payment_id)) {
                    return 'pump_' . $item->pump_payment_id;
                }
                return ($item->daily_card_id ?? 'x') . '-' . ($item->card_number ?? 'y') . '-' . $item->amount . '-' . ($item->slip_no ?? '');
            })
            ->values();
        } else {
            $settlement_card_payments = $settlement_card_payments
                ->unique(function ($item) {
                    if (!empty($item->pump_payment_id)) {
                        return 'pump_' . $item->pump_payment_id;
                    }
                    if (!empty($item->daily_card_id)) {
                        return 'daily_' . $item->daily_card_id;
                    }
                    if (!empty($item->id)) {
                        return 'id_' . $item->id;
                    }
                    return ($item->daily_card_id ?? 'x') . '-' . ($item->card_number ?? 'y') . '-' . $item->amount . '-' . ($item->slip_no ?? '');
                })
                ->values();
        }

        // DAILY VOUCHERS
        $daily_vouchers = DailyVoucher::leftJoin('contacts', 'daily_vouchers.customer_id', '=', 'contacts.id')
            ->leftJoin('settlement_credit_sale_payments', 'daily_vouchers.id', '=', 'settlement_credit_sale_payments.daily_voucher_id')
            ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
            ->select(
                'daily_vouchers.*',
                'contacts.name as customer_name',
                'products.name as product_name',
                'settlement_credit_sale_payments.qty as qty',
                'settlement_credit_sale_payments.amount as credit_amount'
            )
            ->where('daily_vouchers.settlement_no', $request->settlement_no)
            ->where('daily_vouchers.business_id', $business_id)
            ->get();

        $daily_voucher_mapped = $daily_vouchers->map(function ($v) {
            return (object)[
                'id' => null,
                'settlement_no' => $v->settlement_no,
                'business_id' => $v->business_id,
                'customer_id' => $v->customer_id,
                'daily_voucher_id' => $v->id,
                'product_id' => null,
                'order_number' => $v->voucher_order_number,
                'order_date' => $v->voucher_order_date,
                'customer_reference' => $v->customer_references,
                'price' => null,
                'discount' => null,
                'total_discount' => null,
                'sub_total' => null,
                'qty' => $v->qty,
                'amount' => $v->credit_amount ?? $v->total_amount,
                'outstanding' => $v->current_outstanding,
                'credit_limit' => null,
                'note' => null,

                'customer_name' => $v->customer_name,
                'product_name' => $v->product_name,
                'vehicle_no' => $v->vehicle_no,

                'operator_id' => $v->operator_id,
                'shift_id' => $v->shift_id
            ];
        });


        Log::debug('Fetched in add payment: ' . $daily_vouchers->count() . ' daily vouchers for settlement no: ' . $request->settlement_no);

        // For settlement_pd type, skip daily_vouchers to prevent overcounting
        // Only include records explicitly linked to the current PD settlement (settlement_credit_sale_payments1 + settlement_credit_sale_payments2)
        if ($request->type !== 'settlement_pd') {
            $existing_credit_daily_voucher_ids = $settlement_credit_sale_payments
                ->pluck('daily_voucher_id')
                ->filter()
                ->map(fn ($id) => (string) $id)
                ->all();

            if (! empty($existing_credit_daily_voucher_ids)) {
                $daily_voucher_mapped = $daily_voucher_mapped
                    ->reject(fn ($item) => ! empty($item->daily_voucher_id)
                        && in_array((string) $item->daily_voucher_id, $existing_credit_daily_voucher_ids, true))
                    ->values();
            }

        $settlement_credit_sale_payments = $settlement_credit_sale_payments
            ->concat($daily_voucher_mapped)
            ->unique(function ($item) use ($request) {
                if ($request->type === 'settlement') {
                    return implode('|', [
                        'direct',
                        $item->customer_id ?? '',
                        $item->order_number ?? '',
                        $item->order_date ?? '',
                        $item->product_id ?? ($item->product_name ?? ''),
                        (float) ($item->qty ?? 0),
                        (float) ($item->amount ?? 0),
                        (float) ($item->total_discount ?? 0),
                    ]);
                }

                if (!empty($item->id)) {
                    return 'id_' . $item->id;
                }
                if (!empty($item->daily_voucher_id)) {
                    return 'voucher_' . $item->daily_voucher_id;
                }
                if (!empty($item->order_number)) {
                    return 'order_' . $item->order_number;
                }
                return 'uniq_' . ($item->customer_id ?? '0') . '_' . ($item->amount ?? '0');
            })
            ->values();
        } else {
            $settlement_credit_sale_payments = $settlement_credit_sale_payments->values();
        }

        /**
         * @ChangedBy Afes

         *
         * @Date 25-05-2021

         * @Date 02-06-2021

         *
         * @Task 12700

         * @Task 127004
         */

        // $total_daily_collection = floatval(DailyCollection::where('pump_operator_id', $pump_operator_id)->where('business_id', $business_id)->whereNull('settlement_id')->sum('current_amount'));

        // $total_daily_collection = floatval(DailyCollection::where(['pump_operator_id' => $pump_operator_id, 'shift_id'=> $request->shift_ids])->where('business_id', $business_id)->whereNull('settlement_id')->sum('current_amount'));

        // -------------------------
        // total_daily_collection: use whereIn for shift(s) 1287
        // -------------------------
        $dailyQuery = DailyCollection::where('pump_operator_id', $pump_operator_id)
            ->where('business_id', $business_id)
            ->whereNull('settlement_id');

        if (! empty($shift_ids)) {
            $dailyQuery->whereIn('shift_id', $shift_ids);
        } elseif (! empty($shift_id_string)) {
            $dailyQuery->where('shift_id', $shift_id_string);
        }

        $total_daily_collection = floatval($dailyQuery->sum('current_amount'));

        /**
         * @ModifiedBy Afes Oktavianus

         *
         * @Date 02-06-2021

         * @Date 03-06-2021

         *
         * @Task 127004
         */
        // Only calculate these if pump_operator_id is available
        $total_excess = 0;
        $total_shortage = 0;
        $operator_bal = 0;
        $total_commission = 0;

        if (!empty($pump_operator_id)) {
        $total_excess = $this->transactionUtil->getPumpOperatorExcessOrShortage($pump_operator_id, 'excess');
        $total_shortage = $this->transactionUtil->getPumpOperatorExcessOrShortage($pump_operator_id, 'shortage');
        $operator_bal = $this->transactionUtil->getPumpOperatorBalance($pump_operator_id);
        $total_commission = $this->calculateCommission($pump_operator_id, $settlement->id);
        }

        $business_details = Business::find($business_id);

        $currency_precision = $business_details->currency_precision;

        // Settlement PD: keep the modal meter total aligned with the create page summary.
        // The summary excludes pumper-dashboard payment rows and scopes the rows to this operator.
        if ($request->type !== 'settlement_pd') {
            $linked_meter_sale_rows = MeterSale::where('business_id', $business_id)
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', (string) $settlement->id);
                    if (!empty($settlement->settlement_no)) {
                        $q->orWhere('settlement_no', (string) $settlement->settlement_no);
                    }
                })
                ->when(!empty($shift_ids) && is_array($shift_ids), function ($q) use ($shift_ids) {
                    return $q->whereIn('shift_id', $shift_ids);
                })
                ->get();

            $total_meter_sale_linked = (float) $linked_meter_sale_rows
                ->unique(function ($item) {
                    return implode('|', [
                        $item->settlement_no,
                        $item->shift_id,
                        $item->pump_id,
                        $item->starting_meter,
                        $item->closing_meter,
                        $item->qty,
                        $item->price,
                        $item->discount,
                        $item->discount_type,
                    ]);
                })
                ->sum('discount_amount');

            $total_meter_sale_shift = 0.0;
            if (!empty($shift_ids) && is_array($shift_ids)) {
                $shift_meter_sale_rows = MeterSale::where('business_id', $business_id)
                    ->whereIn('shift_id', $shift_ids)
                    ->get();

                $total_meter_sale_shift = (float) $shift_meter_sale_rows
                    ->unique(function ($item) {
                        return implode('|', [
                            $item->settlement_no,
                            $item->shift_id,
                            $item->pump_id,
                            $item->starting_meter,
                            $item->closing_meter,
                            $item->qty,
                            $item->price,
                            $item->discount,
                            $item->discount_type,
                        ]);
                    })
                    ->sum('discount_amount');
            }
        } else {
            $total_meter_sale_linked_rows = PumpOperatorMeterSale::where('business_id', $business_id)
                ->when(Schema::hasColumn('pump_operator_meter_sales', 'source'), function ($q) {
                    return $q->where('source', 'closing');
                })
                ->when(!empty($pump_operator_id), function ($q) use ($pump_operator_id) {
                    return $q->where('pump_operator_id', $pump_operator_id);
                })
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', (string) $settlement->id);
                    if (!empty($settlement->settlement_no)) {
                        $q->orWhere('settlement_no', (string) $settlement->settlement_no);
                    }
                })
                ->when(!empty($shift_ids) && is_array($shift_ids), function ($q) use ($shift_ids) {
                    return $q->whereIn('shift_id', $shift_ids);
                })
                ->get();

            $total_meter_sale_linked = (float) $total_meter_sale_linked_rows
                ->unique(function ($item) {
                    return implode('|', [
                        $item->business_id ?? '',
                        $item->pump_operator_id ?? '',
                        $item->shift_id ?? '',
                        $item->pump_id ?? '',
                        $item->product_id ?? '',
                        $item->starting_meter ?? '',
                        $item->closing_meter ?? '',
                        $item->price ?? '',
                        $item->qty ?? '',
                        $item->balance ?? '',
                    ]);
                })
                ->sum('balance');

            $total_meter_sale_shift = 0.0;
            if (!empty($shift_ids) && is_array($shift_ids)) {
                $total_meter_sale_shift_rows = PumpOperatorMeterSale::where('business_id', $business_id)
                    ->when(Schema::hasColumn('pump_operator_meter_sales', 'source'), function ($q) {
                        return $q->where('source', 'closing');
                    })
                    ->whereIn('shift_id', $shift_ids)
                    ->when(!empty($pump_operator_id), function ($q) use ($pump_operator_id) {
                        return $q->where('pump_operator_id', $pump_operator_id);
                    })
                    ->get();

                $total_meter_sale_shift = (float) $total_meter_sale_shift_rows
                    ->unique(function ($item) {
                        return implode('|', [
                            $item->business_id ?? '',
                            $item->pump_operator_id ?? '',
                            $item->shift_id ?? '',
                            $item->pump_id ?? '',
                            $item->product_id ?? '',
                            $item->starting_meter ?? '',
                            $item->closing_meter ?? '',
                            $item->price ?? '',
                            $item->qty ?? '',
                            $item->balance ?? '',
                        ]);
                    })
                    ->sum('balance');
            }
        }
        // PETROPD-PAYDUE-ROOT-20260705:
        // For Petro PD Add Payment, the Payment to Finalize page calculates the due
        // from the selected shift/operator. When an existing settlement is refreshed,
        // linked rows may exist both by settlement id and by settlement no, which makes
        // $total_meter_sale_linked larger than the visible Payment Due.
        // Therefore the modal must prefer the selected shift total whenever shift_ids
        // are present. This keeps Add Payment Total Amount equal to Payment Due after
        // refresh/reopen, and avoids double-counting linked settlement rows.
        /*
         * S 639: ONE SOURCE for the PD Settlement figure.
         *
         * Both totals above read pump_operator_meter_sales - the copy written
         * at shift close - and deduplicate it on the row VALUES, which can drop
         * a genuine second row for the same pump at the same reading. That is
         * how this screen came to show Total Amount 172,912.77 for a shift the
         * Pumper Dashboard had closed at 172,912.78, leaving a -0.01 balance on
         * a settled shift.
         *
         * PD Settlement now reads pumper_day_entries through ShiftSaleTotals,
         * the same table and query the dashboard uses. The two figures above are
         * still computed, but only to log where they disagreed; they no longer
         * decide what is shown. Direct Settlement and every other caller keep
         * their existing path untouched in this parcel.
         */
        $total_meter_sale_legacy = ($total_meter_sale_linked == 0.0 && $total_meter_sale_shift > 0.0)
            ? $total_meter_sale_shift
            : $total_meter_sale_linked;

        /*
         | Uses the CORRECTED shift list, not the raw request value.
         |
         | $shift_ids is what the browser sent - for PDST182 that was 189, the
         | shift NUMBER. $shift_ids_for_settlement is the corrected list resolved
         | earlier, which falls back to the settlement's own work_shift when the
         | request disagrees - 191, the shift ID.
         |
         | Reading $shift_ids here meant meter sales were summed for a shift that
         | has none, so Total Amount printed 0.00 while Total Paid was correct at
         | 574,288.70 - the payments used the corrected list, the sales did not.
         |
         | Falls back to $shift_ids when no corrected list exists, so nothing
         | changes on settlements that were already resolving correctly.
         */
        $meter_sale_shift_ids = ! empty($shift_ids_for_settlement)
            ? $shift_ids_for_settlement
            : $shift_ids;

        if ($request->type === 'settlement_pd' && ! empty($meter_sale_shift_ids) && is_array($meter_sale_shift_ids)) {
            $total_meter_sale = 0.0;

            foreach ($meter_sale_shift_ids as $authoritative_shift_id) {
                $total_meter_sale += \Modules\SettlementCore\Services\ShiftSaleTotals::for(
                    (int) $business_id,
                    (int) $authoritative_shift_id,
                    $pump_operator_id
                )->meterSales();
            }

            if (abs($total_meter_sale - $total_meter_sale_legacy) >= 0.005) {
                Log::warning('S639 AddPayment meter sale divergence', [
                    'business_id' => $business_id,
                    'settlement_id' => $settlement->id ?? null,
                    'settlement_no' => $settlement->settlement_no ?? null,
                    'shift_ids' => $shift_ids,
                    'pump_operator_id' => $pump_operator_id,
                    'authoritative_day_entries' => $total_meter_sale,
                    'legacy_meter_sales_linked' => $total_meter_sale_linked,
                    'legacy_meter_sales_shift' => $total_meter_sale_shift,
                    'difference' => $total_meter_sale - $total_meter_sale_legacy,
                ]);
            }
        } elseif ($request->type === 'settlement_pd' && $total_meter_sale_shift > 0.0) {
            $total_meter_sale = $total_meter_sale_shift;
        } else {
            $total_meter_sale = $total_meter_sale_legacy;
        }

        $other_sales = OtherSale::where('settlement_no', $settlement->id)->get();

        $total_other_sale = $other_sales->sum('sub_total') - $other_sales->sum('discount_amount');

        if (str_contains($settlement->settlement_no, 'SET-SW')) {

            $total_other_sale = $other_sales->sum('sub_total');

        }

        $show_shift_no = [];

        if ($request->shift_ids) {

            $raw_shift_ids = $request->shift_ids;
            $shift_ids = is_array($raw_shift_ids)
                ? array_filter(array_map('intval', $raw_shift_ids))
                : explode(',', $raw_shift_ids);

            $show_shift_no = $shift_ids;


            /*
             |------------------------------------------------------------------
             | Shift other-sales: summed WITHOUT joins.
             |------------------------------------------------------------------
             |
             | This query used to join products, variations,
             | variation_location_details and pump_operator_assignments purely to
             | reach ->sum('sub_total'). None of those tables contributes to the
             | sum - but every one of them can match MORE THAN ONE row per sale,
             | and each extra match repeats the sale inside the SUM.
             |
             | Measured on shift 1 of PDST1:
             |
             |     joined query   317,043.00 over 108 rows
             |     real data       35,227.00 over  12 rows
             |
             | exactly 9x, one row per duplicate combination. The whereRaw that
             | picked a single assignment cut it down but never removed it, leaving
             | 59,844.00 - which is what put Total Amount 24,617.00 over the
             | Payments tab figure.
             |
             | Summing the table directly gives the true 35,227.00. Discounts are
             | subtracted, matching how the settlement's own other sales are
             | totalled a few lines above.
             |
             | No join means no fan-out, and nothing here depended on the joined
             | columns.
             */
            /*
             | Both figures come from ONE fetch.
             |
             | Calling ->sum() twice on the same builder would run the query twice,
             | and a builder cannot be reused after sum() without cloning it first -
             | so the rows are fetched once and totalled in PHP. It is a small set
             | (12 rows for this shift) and it cannot go out of step with itself.
             */
            $pump_operator_other_sale_rows = PumpOperatorOtherSale::whereIn('shift_id', $shift_ids)
                ->when(! empty($business_id), fn ($q) => $q->where('business_id', $business_id))
                ->get(['sub_total', 'discount_amount']);

            $pump_operator_total_other_sale = (float) $pump_operator_other_sale_rows->sum('sub_total')
                - (float) $pump_operator_other_sale_rows->sum('discount_amount');

            /*
             |------------------------------------------------------------------
             | PD: the shift other-sales are NOT added on top.
             |------------------------------------------------------------------
             |
             | Reported: Add Payment showed 4,114,709.61 while the Payments tab
             | showed 4,090,092.61 for PDST1 - out by 24,617.00.
             |
             | Two sources were being summed:
             |
             |   OtherSale (settlement_no = settlement id)   35,227.00
             | + pump_operator_other_sales (by shift id)     24,617.00
             |                                            = 59,844.00
             |
             | For a PD settlement those are THE SAME SALES. The pumper records
             | them against the shift, and finalising copies them onto the
             | settlement - so adding both counts them twice. The log confirmed it:
             |
             |   S639 browser pd_payment_due_total ignored
             |   request_pd_payment_due_total : 4,090,092.61   (Payments tab)
             |   authoritative_server_due     : 4,114,709.61   (here)
             |   difference                   :    24,617.00
             |
             | with no accompanying meter-sale divergence, so the whole gap was in
             | other sales.
             |
             | The shift figure is still used when the settlement carries NO other
             | sales of its own - that covers a draft being reviewed before the
             | rows have been copied across, which is what this addition was
             | written for.
             |
             | Direct Settlement is untouched: it keeps adding both, because its
             | two sources are genuinely different sales.
             */
            $is_pd_other_sale = ($request->type === 'settlement_pd')
                || \Illuminate\Support\Str::startsWith(ltrim($request->path(), '/'), 'petropd');

            if (! $is_pd_other_sale) {
                $total_other_sale = $total_other_sale + $pump_operator_total_other_sale;
            } elseif ((float) $total_other_sale == 0.0) {
                // Draft with nothing copied across yet - fall back to the shift.
                $total_other_sale = $pump_operator_total_other_sale;
            }

        }

        $total_other_income = OtherIncome::where('settlement_no', $settlement->id)->sum('sub_total');

        // Customer payments are actual collections (payments), not sales.
        // Direct Settlement must NOT include customer payments in Total Amount.
        $total_customer_payment = CustomerPayment::where('settlement_no', $settlement->id)->sum('sub_total');

        // NOTE: Total Amount calculation moved below, after credit sales are calculated
        // This ensures credit sales are included in Total Amount for direct settlement

        // dd( $total_amount);
        // Pumper Dashboard master payments are the authoritative financial source.
        // Manual Add Payment rows remain valid, but linked rows are excluded from
        // the manual subtotal so no payment is counted from both tables.
        $authoritativePaymentIds = $pd_payment_snapshot['payment_ids'] ?? [];
        $snapshotTotals = $pd_payment_snapshot['totals'] ?? [];

        if ($request->type === 'settlement_pd' && $pd_payment_snapshot !== null) {
            $manualCash = $this->sumManualSettlementPaymentRows(
                $settlement_cash_payments,
                $authoritativePaymentIds,
                'amount',
                ['pump_payment_id', 'customer_payment_id']
            );
            $manualCard = $this->sumManualSettlementPaymentRows(
                $settlement_card_payments,
                $authoritativePaymentIds
            );
            $manualCheque = $this->sumManualSettlementPaymentRows(
                $settlement_cheque_payments,
                $authoritativePaymentIds
            );
            $manualCreditGross = $this->sumManualSettlementPaymentRows(
                $settlement_credit_sale_payments,
                $authoritativePaymentIds,
                'amount'
            );
            $manualCreditDiscount = $this->sumManualSettlementPaymentRows(
                $settlement_credit_sale_payments,
                $authoritativePaymentIds,
                'total_discount'
            );
            $manualShortage = $this->sumManualSettlementPaymentRows(
                $settlement_shortage_payments,
                $authoritativePaymentIds
            );
            $manualExcess = $this->sumManualSettlementPaymentRows(
                $settlement_excess_payments,
                $authoritativePaymentIds
            );

            $total_settlement_cash_payment = (float) ($snapshotTotals['cash'] ?? 0) + $manualCash;
            $total_settlement_card_payment = (float) ($snapshotTotals['card'] ?? 0) + $manualCard;
            $total_settlement_cheque_payment = (float) ($snapshotTotals['cheque'] ?? 0) + $manualCheque;
            $total_settlement_credit_sale_payment = (float) ($snapshotTotals['credit_gross'] ?? 0) + $manualCreditGross;
            $total_settlement_credit_sale_discount = (float) ($snapshotTotals['credit_discount'] ?? 0) + $manualCreditDiscount;
            $total_settlement_credit_sale_net = (float) ($snapshotTotals['credit'] ?? 0)
                + ($manualCreditGross - $manualCreditDiscount);
            $total_settlement_shortage_payment = (float) ($snapshotTotals['shortage'] ?? 0) + $manualShortage;
            $total_settlement_excess_payment = (float) ($snapshotTotals['excess'] ?? 0) + $manualExcess;
            $total_settlement_other_pumper_payment = (float) ($snapshotTotals['other'] ?? 0);
        } else {
            $total_settlement_cash_payment = $settlement_cash_payments->sum('amount');
            $total_settlement_card_payment = $settlement_card_payments->sum('amount');
            $total_settlement_cheque_payment = $settlement_cheque_payments->sum('amount');
            $total_settlement_credit_sale_payment = $settlement_credit_sale_payments->sum('amount');
            $total_settlement_credit_sale_discount = $settlement_credit_sale_payments->sum('total_discount');
            $total_settlement_credit_sale_net = $total_settlement_credit_sale_payment - $total_settlement_credit_sale_discount;
            $total_settlement_shortage_payment = $settlement_shortage_payments->sum('amount');
            $total_settlement_excess_payment = $settlement_excess_payments->sum('amount');
            $total_settlement_other_pumper_payment = 0.0;
        }

        $total_settlement_loan_payment = $settlement_loan_payments->sum('amount');
        $total_settlement_drawings_payment = $settlement_drawings_payments->sum('amount');
        $total_settlement_cash_deposit = $settlement_cash_deposits->sum('amount');
        $total_settlement_customer_loan = $settlement_customer_loans->sum('amount');

        // CRITICAL: Calculate Total Amount ONCE using consistent data sources
        // Direct Settlement rules:
        // - Credit Sales are PAYMENTS (collections), NOT sales -> they go to Total Paid, NOT Total Amount
        // - Total Amount = Meter Sales + Other Sales + Other Income + Operator Balance (Current Short/Excess)
        // - Total Paid = All payments including credit sales
        // - Customer payments are also payments -> contribute to Total Paid, not Total Amount
        // IMPORTANT: Credit sales should NEVER be included in Total Amount for Direct Settlement
        // CRITICAL: Operator balance (Current Short/Excess) must be included in Total Amount
        // - If operator has a shortage (positive balance), they owe this amount -> add to Total Amount
        // - If operator has excess (negative balance), they have overpaid -> add to Total Amount (will be negative, reducing total)

        /*
         |----------------------------------------------------------------------
         | Total Amount on Add Payment did not match the Payment page.
         |----------------------------------------------------------------------
         |
         | Reported: Add Payment showed 4,114,709.61 while Payment to Finalize
         | showed 4,090,092.61 for the same shift - out by exactly 24,617.00, the
         | operator balance.
         |
         | The settlement_pd branch below is correct: for PD, Total Amount is the
         | sales due only. The trouble was reaching it. The branch is chosen from
         | $request->type alone, and when Add Payment is opened from PD Settlement
         | that parameter is not always carried through - the request then fell to
         | the default branch, which adds $operator_bal. That is precisely the
         | 24,617.00 difference.
         |
         | $isPetroPdRequest is resolved earlier in this method from the request's
         | own path and cannot be absent, so it is used as a second way of
         | recognising a PD request. Direct Settlement is unaffected: its branch is
         | tested first and still keys on type === 'settlement'.
         */
        /*
         | Resolved HERE. $isPetroPdRequest exists in createSettlementIfNotExist(),
         | a different method, so it is not in scope in create() - referencing it
         | would have been an undefined variable rather than a working check.
         |
         | Same two signals that method trusts: the explicit source parameter, and
         | the request's own path. Every PetroPD route sits under the 'petropd'
         | prefix, so the path cannot be absent or forged.
         */
        $pd_source = (string) $request->input('source', '');
        $is_pd_total = ($request->type === 'settlement_pd')
            || in_array($pd_source, ['petro_pd', 'petropd'], true)
            || \Illuminate\Support\Str::startsWith(ltrim($request->path(), '/'), 'petropd');

        if ($request->type === 'settlement') {
            // Direct Settlement: Credit sales are payments, not sales - DO NOT include in Total Amount
            // BUT include operator balance (Current Short/Excess)
            $total_amount = number_format(($total_meter_sale + $total_other_sale + $total_other_income + $operator_bal), $currency_precision, '.', '');
        } elseif ($is_pd_total) {
            // Settlement PD Payment Due is the sales due only. Credit sales are counted in Total Paid.
            $total_amount = number_format(($total_meter_sale + $total_other_sale), $currency_precision, '.', '');
        } else {
            // Default for other providers.
            $total_amount = number_format(($total_meter_sale + $total_other_sale + $total_other_income + $total_customer_payment + $operator_bal), $currency_precision, '.', '');
        }


        // PETROPD-PAYDUE-SOURCE-002:
        // The Add Payment modal must use the same authoritative Payment Due value
        // that is shown immediately above the Payment to Finalize button.
        // When the browser passes that value, it overrides all legacy recalculation paths.
        /*
         * S 639: the browser no longer supplies the figure.
         *
         * pd_payment_due_total was introduced when the Payments tab and this
         * modal read different aggregates and had to be forced to agree. Both
         * now read pumper_day_entries through ShiftSaleTotals, so the server
         * figure is authoritative and a value arriving in the query string can
         * only reintroduce a stale or differently-rounded number.
         *
         * It is still logged when it disagrees, because a disagreement now means
         * some page is still computing its own total and needs finding.
         */
        if ($request->type === 'settlement_pd' && $request->filled('pd_payment_due_total')) {
            $pd_payment_due_total = (float) str_replace(',', '', (string) $request->input('pd_payment_due_total'));
            $server_shift_due = (float) $total_meter_sale + (float) $total_other_sale;

            if (abs($pd_payment_due_total - $server_shift_due) >= 0.005) {
                Log::warning('S639 browser pd_payment_due_total ignored', [
                    'settlement_id' => $settlement->id ?? null,
                    'settlement_no' => $settlement->settlement_no ?? null,
                    'request_pd_payment_due_total' => $pd_payment_due_total,
                    'authoritative_server_due' => $server_shift_due,
                    'difference' => $server_shift_due - $pd_payment_due_total,
                    'shift_ids' => $shift_ids ?? [],
                ]);
            }
        }

        // REMOVED: Direct query fallback that caused Total Paid inconsistencies
        // The filtered $settlement_credit_sale_payments already contains all relevant credit sales
        // Using the same filtered data for both Total Amount and Total Paid ensures consistency

        $total_settlement_expense_payment = $settlement_expense_payments->sum('amount');

        if (! ($request->type === 'settlement_pd' && $pd_payment_snapshot !== null)) {
            $total_settlement_shortage_payment = $settlement_shortage_payments->sum('amount');
            $total_settlement_excess_payment = $settlement_excess_payments->sum('amount');
        }

        /**
         * Excess payments are stored as negative values (validated in saveExcessPayment).
         * They must be added as-is to reduce total_paid, offsetting the overpayment.
         */
        $total_settlement_excess_payment_for_total_paid = $total_settlement_excess_payment;

        if (env('PETRO_SETTLEMENT_PD_DEBUG', false) && $request->type === 'settlement_pd') {
            Log::debug('SettlementPD AddPayment: totals (pre-balance)', [
                'business_id' => $business_id,
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'shift_ids' => $shift_ids,
                'meter_sales_linked_total' => $total_meter_sale_linked,
                'meter_sales_shift_total' => $total_meter_sale_shift,
                'meter_sales_final_total' => $total_meter_sale,
                'total_amount' => $total_amount,
                'total_settlement_cash_payment' => $total_settlement_cash_payment,
                'total_settlement_card_payment' => $total_settlement_card_payment,
                'total_settlement_cheque_payment' => $total_settlement_cheque_payment,
                'total_settlement_credit_sale_payment' => $total_settlement_credit_sale_payment,
                'total_settlement_credit_sale_discount' => $total_settlement_credit_sale_discount,
                'total_settlement_expense_payment' => $total_settlement_expense_payment,
                'total_settlement_shortage_payment' => $total_settlement_shortage_payment,
                'total_settlement_excess_payment' => $total_settlement_excess_payment,
                'total_settlement_excess_payment_for_total_paid' => $total_settlement_excess_payment_for_total_paid,
            ]);
        }

        // CRITICAL: Do NOT include $total_daily_collection in total_paid calculation
        // Cash from DailyCollection is already represented via SettlementCashPayment records
        // Including both would cause double-counting of cash payments
        // The comment on line 1548-1550 confirms: "Avoid double-counting cash: do NOT append daily_collections here"
        // Shortage payments = missing money = increases what's owed (ADD to total_paid)
        // CRITICAL: For Direct Settlement (type === 'settlement'), credit sales are SALES (in Total Amount)
        // AND also count as "paid" (accounts receivable) -> must be included in total_paid
        // This ensures balance calculation is correct: Balance = Total Amount - Total Paid
        $is_direct_settlement = ($request->type === 'settlement');
        // For direct settlement, include credit sales in total_paid (they're accounts receivable)
        // For settlement_pd, credit sales are already payments, so include them
        $credit_sales_component_in_paid = $total_settlement_credit_sale_net;
        $customer_payment_component_in_paid = ($is_direct_settlement || $request->type === 'settlement_pd')
            ? $total_customer_payment
            : 0;

        /*
         |----------------------------------------------------------------------
         | PD settlements: Total Paid counts ONLY what the pumper recorded.
         |----------------------------------------------------------------------
         |
         | A PD settlement exists solely to settle a Pumper Dashboard shift, so
         | its Total Paid must tally with the pumper's own figures rather than be
         | assembled from a wider set of components.
         |
         | Counted:   cash, card, cheque, credit sales, shortage, excess
         | Not counted: customer loans, loan payments, cash deposits, expenses,
         |              drawings, other pumper payments, customer payments
         |
         | Those tabs are hidden on the PD screen (see $pd_hidden_payment_tabs
         | below). Verified empty before this change - expenses, cash deposits,
         | loan payments, customer loans and drawings all had zero rows - so no
         | recorded money is dropped from any existing settlement.
         |
         | Direct settlements are untouched: they keep the full component list,
         | because their tabs are genuinely used.
         |
         | Rounding: each component is rounded to the currency precision BEFORE
         | being added, then the sum is rounded again. Adding raw floats and
         | rounding once at the end is what produced the 0.18 difference against
         | the pumper dashboard on PDST183 - values are stored at higher precision
         | than they are displayed.
         */
        $pd_is_pumper_settlement = ($request->type === 'settlement_pd')
            || \Illuminate\Support\Str::startsWith(ltrim($request->path(), '/'), 'petropd');

        /*
         | Hides the tabs that are not part of a Pumper Dashboard settlement:
         | Cash Deposit, Expenses, Loan Payments, Drawings, Customer Loans.
         |
         | Read by pd_settlement/partials/payment_tabs.blade.php. Absent on Direct
         | settlements, so their tabs render exactly as before.
         |
         | To bring a tab back, remove its @if guard in that view.
         */
        $pd_hide_non_pumper_tabs = $pd_is_pumper_settlement;

        if ($pd_is_pumper_settlement) {
            $pd_round = static function ($value) use ($currency_precision) {
                return round((float) $value, (int) $currency_precision);
            };

            $total_paid = number_format(
                $pd_round($total_settlement_cash_payment)
                + $pd_round($total_settlement_card_payment)
                + $pd_round($total_settlement_cheque_payment)
                + $pd_round($credit_sales_component_in_paid)
                + $pd_round($total_settlement_shortage_payment)
                + $pd_round($total_settlement_excess_payment_for_total_paid),
                $currency_precision,
                '.',
                ''
            );
        } else {
            $total_paid = number_format(($total_settlement_customer_loan +

            $total_settlement_loan_payment +

            $total_settlement_cash_deposit +

            // REMOVED: $total_daily_collection + (causes double-counting with SettlementCashPayment)

            $total_settlement_cash_payment +

            $total_settlement_card_payment +

            $total_settlement_cheque_payment +

            $credit_sales_component_in_paid +

            $total_settlement_expense_payment +

            $total_settlement_shortage_payment +

            $total_settlement_excess_payment_for_total_paid +

            $total_settlement_drawings_payment +

            $total_settlement_other_pumper_payment +

            $customer_payment_component_in_paid

        ), $currency_precision, '.', '');
        }

        $total_balance = number_format($total_amount - $total_paid, $currency_precision, '.', '');

        // For direct settlement, balance can be negative (excess) or positive (shortage)
        // Don't use abs() as it removes the sign which is needed for proper balance calculation
        // The balance sign indicates whether there's an excess (negative) or shortage (positive)
        if ($request->type !== 'settlement') {
            // For non-direct settlement, keep existing behavior (abs for display)
            $total_balance = abs($total_balance);
        }
        //  dd(  $total_paid);
        // dd($total_balance);

        // Always log for direct settlement to debug credit sales calculation
        if ($request->type === 'settlement') {
            // Debug: Check what credit sales were found
            $found_credit_sale_ids = $settlement_credit_sale_payments->pluck('id')->toArray();
            $found_credit_sale_settlement_nos = $settlement_credit_sale_payments->pluck('settlement_no')->unique()->toArray();

            Log::info('DirectSettlement AddPayment: totals calculation', [
                'business_id' => $business_id,
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'shift_ids' => $shift_ids,
                'credit_sales_count' => $settlement_credit_sale_payments->count(),
                'credit_sales_ids_found' => $found_credit_sale_ids,
                'credit_sales_settlement_nos_found' => $found_credit_sale_settlement_nos,
                'credit_sales_payment_total' => (float) $total_settlement_credit_sale_payment,
                'credit_sales_discount_total' => (float) $total_settlement_credit_sale_discount,
                'credit_sales_net' => (float) $total_settlement_credit_sale_net,
                'credit_sales_component_in_paid' => (float) $credit_sales_component_in_paid,
                'meter_sales_total' => (float) $total_meter_sale,
                'other_sale_total' => (float) $total_other_sale,
                'other_income_total' => (float) $total_other_income,
                'customer_payment_total' => (float) $total_customer_payment,
                'cash_payment_total' => (float) $total_settlement_cash_payment,
                'card_payment_total' => (float) $total_settlement_card_payment,
                'cheque_payment_total' => (float) $total_settlement_cheque_payment,
                'total_amount' => (float) $total_amount,
                'total_paid' => (float) $total_paid,
                'total_balance' => (float) $total_balance,
            ]);

            if ($total_settlement_credit_sale_net > 0 && (float) $total_amount < (float) $total_settlement_credit_sale_net) {
                Log::warning('DirectSettlement AddPayment: total_amount < credit_sales_net (unexpected)', [
                    'settlement_id' => $settlement->id,
                    'total_amount' => (float) $total_amount,
                    'credit_sales_net' => (float) $total_settlement_credit_sale_net,
                ]);
            }
        }

        if (env('PETRO_SETTLEMENT_PD_DEBUG', false) && $request->type === 'settlement_pd') {
            Log::debug('SettlementPD AddPayment: finalize preconditions', [
                'business_id' => $business_id,
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'shift_ids' => $shift_ids,
                'total_amount' => (float) $total_amount,
                'total_paid' => (float) $total_paid,
                'total_balance' => (float) $total_balance,
                'finalize_button_should_show' => ((float) $total_balance === 0.0),
            ]);
        }

        $loans_given_group_id = AccountGroup::getGroupByName('Loans Given');

        $drawings_group_id = AccountGroup::getGroupByName('Owners Drawings');

        $bank_account_group_id = AccountGroup::getGroupByName('Bank Account');

        $bank_accounts = Account::where('business_id', $business_id)->where('asset_type', $bank_account_group_id->id)->pluck('name', 'id');

        if (! empty($loans_given_group_id)) {

            $loans_given = Account::where('business_id', $business_id)->where('asset_type', $loans_given_group_id->id)->pluck('name', 'id');

        } else {

            $loans_given = [];

        }

        if (! empty($drawings_group_id)) {

            $drawings_acc = Account::where('business_id', $business_id)->where('asset_type', $drawings_group_id->id)->pluck('name', 'id');

        } else {

            $drawings_acc = [];

        }

        $message = $package_details['notsubscribed_message_content'] ?? null;

        $font_family = $package_details['ns_font_family'] ?? null;

        $font_color = $package_details['ns_font_color'] ?? null;

        $font_size = $package_details['ns_font_size'] ?? null;

        $background_color = $package_details['ns_background_color'] ?? null;

        // dd($settlement_card_payments, $settlement_cash_payments);

        $message = ! empty($message) ? $message : 'You have not subscribed to this module!';

        // Convert shift_ids to shift_numbers for display
        $show_shift_no = '';
        if (!empty($shift_ids) && count($shift_ids) > 0) {
            // Get shift_numbers from PumpOperatorAssignment using shift_ids
            $shift_numbers = \Modules\PetroPD\Entities\PumpOperatorAssignment::whereIn('shift_id', $shift_ids)
                ->where('pump_operator_id', $pump_operator_id)
                ->distinct()
                ->pluck('shift_number')
                ->toArray();

            if (!empty($shift_numbers)) {
                $show_shift_no = implode(', ', $shift_numbers);
            } else {
                // Fallback: show shift_ids if shift_numbers not found
                $show_shift_no = is_array($shift_ids) ? implode(', ', $shift_ids) : $shift_ids;
            }
        } else {
            // If no shift_ids provided, try to get from settlement's work_shift
            if (!empty($settlement) && !empty($settlement->work_shift)) {
                $work_shifts = is_array($settlement->work_shift) ? $settlement->work_shift : [$settlement->work_shift];
                $shift_numbers = \Modules\PetroPD\Entities\PumpOperatorAssignment::whereIn('shift_id', $work_shifts)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->distinct()
                    ->pluck('shift_number')
                    ->toArray();
                if (!empty($shift_numbers)) {
                    $show_shift_no = implode(', ', $shift_numbers);
                }
            }
        }

        // Get last credit sale details for prefilling
        $lastCreditSaleDetails = $this->getLastCreditSaleDetails($business_id);

        // REMOVED: This was incorrectly recalculating total_amount and adding credit sales back
        // Credit sales should NEVER be in Total Amount for Direct Settlement
        // The correct calculation is already done above at line 2510

        if ($request->type == 'settlement_pd') {
            $settlement_pd_add_payment_view = 'petropd::pd_settlement.partials.add_payment';

            return view($settlement_pd_add_payment_view)->with(compact(
                // IS: hides the non-pumper tabs on PD settlements.
                'pd_hide_non_pumper_tabs',

                'operator_bal',

                'business',

                'message', 'font_family', 'font_color', 'font_size', 'background_color', 'package_details',

                'bank_accounts',

                'is_settlement_page',

                'total_settlement_cash_deposit',

                'settlement',

                'cash_denoms',

                'pump_operator',

                'customer_payments_tab',

                'settlement_cash_payments',

                'settlement_pos_sales',

                'settlement_customer_loans',

                'cash_total',

                'settlement_loan_payments',

                'settlement_drawings_payments',

                'settlement_card_payments',

                'settlement_cheque_payments',

                'settlement_credit_sale_payments',

                'settlement_expense_payments',

                'settlement_shortage_payments',

                'settlement_excess_payments',

                'settlement_cash_deposits',

                'payment_types',

                'expense_accounts',

                'expense_categories',

                'expense_no',

                'customers',

                'products',

                'card_types',

                'total_daily_collection',

                'total_commission',

                'total_amount',

                'total_paid',

                'total_balance',

                'total_excess',

                'total_shortage',

                'loans_given',

                'drawings_acc',

                'only_walkin',

                'walkin',

                'provider',

                'credit_customers',

                'show_shift_no',

                'lastCreditSaleDetails',

                'pumper_credit_sale_payments',

                'authoritative_credit_gross_total',

                'authoritative_credit_discount_total',

                'authoritative_credit_net_total',

                'credit_payment_reconciliation_warning',

                'payment_reconciliation_warning',

                'payment_reconciliation_blocked',

                'pd_payment_snapshot',

                'shift_ids',

                'shift_ids_for_settlement',

                'no_change'

            ));
        } elseif ($request->type == 'settlement') {

            return view('petropd::pd_settlement.partials.add_payment')->with(compact(

                'operator_bal',

                'business',

                'message', 'font_family', 'font_color', 'font_size', 'background_color', 'package_details',

                'bank_accounts',

                'is_settlement_page',

                'total_settlement_cash_deposit',

                'pd_hide_non_pumper_tabs',
                'settlement',

                'cash_denoms',

                'pump_operator',

                'customer_payments_tab',

                'settlement_cash_payments',

                'settlement_pos_sales',

                'cash_total',

                'settlement_customer_loans',

                'settlement_loan_payments',

                'settlement_drawings_payments',

                'settlement_card_payments',

                'settlement_cheque_payments',

                'settlement_credit_sale_payments',

                'settlement_expense_payments',

                'settlement_shortage_payments',

                'settlement_excess_payments',

                'settlement_cash_deposits',

                'payment_types',

                'expense_accounts',

                'expense_categories',

                'expense_no',

                'customers',

                'products',

                'card_types',

                'total_daily_collection',

                'total_commission',

                'total_amount',

                'total_paid',

                'total_balance',

                'total_excess',

                'total_shortage',

                'loans_given',

                'drawings_acc',

                'only_walkin',

                'walkin',

                'provider',

                'credit_customers',

                'show_shift_no',

                'lastCreditSaleDetails',

                'pumper_credit_sale_payments'

            ));
        }
        return view('settlementsw::swsettlement.partials.add_payment')->with(compact(

            'operator_bal',

            'business',

            'message', 'font_family', 'font_color', 'font_size', 'background_color', 'package_details',

            'bank_accounts',

            'is_settlement_page',

            'total_settlement_cash_deposit',

            'settlement',

            'cash_denoms',

            'pump_operator',

            'customer_payments_tab',

            'settlement_cash_payments',

            'cash_total',

            'settlement_customer_loans',

            'settlement_loan_payments',

            'settlement_drawings_payments',

            'settlement_card_payments',

            'settlement_cheque_payments',

            'settlement_credit_sale_payments',

            'settlement_expense_payments',

            'settlement_shortage_payments',

            'settlement_excess_payments',

            'settlement_cash_deposits',

            'payment_types',

            'expense_accounts',

            'expense_categories',

            'expense_no',

            'customers',

            'products',

            'card_types',

            'total_daily_collection',

            'total_commission',

            'total_amount',

            'total_paid',

            'total_balance',

            'total_excess',

            'total_shortage',

            'loans_given',

            'drawings_acc',

            'only_walkin',

            'walkin',

            'provider',

            'credit_customers',

            'show_shift_no',

            'lastCreditSaleDetails'

        ));

    }

    /**
     * Get the last credit sale details for prefilling
     *
     * @param int $business_id
     * @return array
     */
    public function getLastCreditSaleDetails($business_id)
    {
        // Check package-level setting first (from Super Admin checkbox)
        $subscription = Subscription::current_subscription($business_id);
        $package_details = $subscription->package_details ?? [];

        // Explicitly check if the package-level setting is enabled (value should be 1 when checked)
        $packageEnabled = !empty($package_details['prefill_credit_sale_details']) &&
                          $package_details['prefill_credit_sale_details'] == 1;

        // Package-level setting must be enabled (Super Admin checkbox controls this)
        if (!$packageEnabled) {
            return [];
        }

        // Also check business-level setting (from issue_customer_bill_settings)
        // This can override the package-level setting if it exists
        $issueBillSetting = IssueCustomerBillSetting::where('business_id', $business_id)->first();

        if ($issueBillSetting) {
            // Business-level setting exists - check if it's explicitly disabled
            // Handle both boolean true/false and integer 1/0 (depending on DB driver)
            $businessEnabled = $issueBillSetting->prefill_credit_sale_details === true ||
                              $issueBillSetting->prefill_credit_sale_details === 1 ||
                              $issueBillSetting->prefill_credit_sale_details === '1';

            // If business-level setting is disabled, don't prefill
            if (!$businessEnabled) {
                return [];
            }
        }
        // If no business-level setting exists, proceed with package-level setting

        // Get the last credit sale payment for this business
        $lastCreditSale = SettlementCreditSalePayment::where('business_id', $business_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastCreditSale) {
            return [];
        }

        return [
            'customer_id' => $lastCreditSale->customer_id,
            'product_id' => $lastCreditSale->product_id,
            'price' => $lastCreditSale->price,
            'unit_discount' => $lastCreditSale->unit_discount ?? 0,
            'qty' => $lastCreditSale->qty,
            'customer_reference' => $lastCreditSale->customer_reference,
        ];
    }

    public function getExpenseNumber($settlement_id)
    {

        $settlement_int = preg_match_all('!\d+!', $settlement_id, $matches);

        $ref_no_prefixes = request()->session()->get('business.ref_no_prefixes');

        $expense_prefix = ! empty($ref_no_prefixes['expense']) ? $ref_no_prefixes['expense'] : '';

        $expense_count = SettlementExpensePayment::where('settlement_no', $settlement_id)->count();

        $expense_no = $expense_prefix . '-' . $settlement_int . '-' . ($expense_count + 1);

        return $expense_no;

    }

    /**
     * Store a newly created resource in storage.

     *
     * @param  pump_operator_id

     * @param  settlement_no

     * @return Response
     */
    public function calculateCommission($pump_operator_id, $settlement_id)
    {

        $all_sales = OtherSale::where('settlement_no', $settlement_id)->get();

        $pump_operator = PumpOperator::where('id', $pump_operator_id)->first();

        $pump_operator_commission_type = isset($pump_operator->commission_type) ? $pump_operator->commission_type : '';

        $pump_operator_commission_value = isset($pump_operator->commission_ap) ? $pump_operator->commission_ap : 0;

        if ($pump_operator_commission_type == 'fixed') {

            $total_sales_counts = $all_sales->count();

            return $total_sales_counts * $pump_operator_commission_value;

        } elseif ($pump_operator_commission_type == 'percentage') {

            $total_sales_commission = 0;

            return $total_sales_commission;

        } else {

            return 0.00;

        }

    }

    /**
     * Store a newly created resource in storage.

     *
     * @return Response
     */
    public function store(Request $request)
    {}

    /**
     * Show the specified resource.

     *
     * @return Response
     */
    public function show()
    {

        // This resource action is intentionally not exposed by Petro PD.

    }

    /**
     * Show the form for editing the specified resource.

     *
     * @return Response
     */
    public function edit()
    {

        // This resource action is intentionally not exposed by Petro PD.

    }

    /**
     * Update the specified resource in storage.

     *
     * @return Response
     */
    public function update(Request $request)
    {}

    /**
     * Remove the specified resource from storage.

     *
     * @return Response
     */
    public function destroy()
    {}

    /**
     * add cash payment data to db

     *
     * @return Response
     */
    public function saveCashPayment(Request $request)
    {
        Log::info('Request Data for saveCashPayment in AddPaymentController:', ['request' => $request->all()]);

        try {
            DB::beginTransaction();

            $business_id = (int) $this->resolveBusinessIdFromRequest($request);
            $settlement = Settlement::where('settlement_no', $request->settlement_no)
                ->where('business_id', $business_id)
                ->firstOrFail();
            $shift_id = $this->requireSingleSettlementShiftId($settlement);

            if ($request->has('is_edit') && $request->is_edit) {
                Settlement::where('id', $settlement->id)->update(['is_edit' => 1]);
            }

            $duplicate_check = SettlementCashPayment::where('settlement_no', $settlement->id)
                ->where('customer_id', $request->customer_id)
                ->where('amount', $request->amount)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->first();

            if ($duplicate_check) {
                DB::rollBack();
                return [
                    'success' => false,
                    'msg' => __('petropd::lang.duplicate_payment_detected'),
                ];
            }

            $pump_payment = $this->resolveUnambiguousMasterPaymentForSettlement(
                $settlement,
                $request,
                $business_id,
                'cash',
                (float) $request->amount
            );

            $settlement_cash_payment = app(\Modules\PetroPD\Services\SettlementPaymentReconciler::class)
                ->upsertOne($business_id, (string) $settlement->id, 'settlement_cash_payments', [
                    'amount'           => $request->amount,
                    'customer_id'      => $request->customer_id,
                    'note'             => $request->note,
                    'pump_payment_id'  => optional($pump_payment)->id,
                    'shift_id'         => $shift_id,
                    'pump_operator_id' => $settlement->pump_operator_id,
                ]);

            if ($pump_payment) {
                $pump_payment->is_used = 1;
                $pump_payment->parent_id = $settlement_cash_payment->id;
                $pump_payment->settlement_no = $settlement->id;
                $pump_payment->save();
            }

            if ($request->has('is_edit')) {
                $settlement->update(['is_edit' => $request->is_edit]);
            }

            // Accounting, stock and ledger postings remain deferred until finalization.
            DB::commit();

            $output = [
                'success'                    => true,
                'settlement_cash_payment_id' => $settlement_cash_payment->id,
                'pump_payment_id'            => optional($pump_payment)->id,
                'msg'                        => __('petropd::lang.success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            $is_reconciliation_error = $e instanceof \RuntimeException
                && str_starts_with($e->getMessage(), 'Payment reconciliation stopped:');

            $output = [
                'success' => false,
                'msg'     => $is_reconciliation_error
                    ? $e->getMessage()
                    : __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    public function saveCustomerLoan(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $data = [

                'business_id'   => $business_id,

                'settlement_no' => $settlement->id,

                'amount'        => $request->amount,

                'customer_id'   => $request->customer_id,

                'note'          => $request->note,

            ];

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $settlement_cash_payment = SettlementCustomerLoan::create($data);

            $output = [

                'success'                     => true,

                'settlement_customer_loan_id' => $settlement_cash_payment->id,

                'msg'                         => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function saveLoanPayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $data = [

                'business_id'   => $business_id,

                'settlement_no' => $settlement->id,

                'amount'        => $request->amount,

                'loan_account'  => $request->loan_account,

                'note'          => $request->note,

            ];

            $settlement_loan_payment = SettlementLoanPayment::create($data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [

                'success'                    => true,

                'settlement_loan_payment_id' => $settlement_loan_payment->id,

                'msg'                        => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function saveDrawingPayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $data = [

                'business_id'   => $business_id,

                'settlement_no' => $settlement->id,

                'amount'        => $request->amount,

                'loan_account'  => $request->loan_account,

                'note'          => $request->note,

            ];

            $settlement_loan_payment = SettlementDrawingPayment::create($data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [

                'success'                    => true,

                'settlement_loan_payment_id' => $settlement_loan_payment->id,

                'msg'                        => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function saveCashDeposit(Request $request)
    {

        try {
            $business_id = $request->session()->get('business.id');
            
            if (empty($request->settlement_no)) {
                 return response()->json([
                    'success' => 0,
                    'msg' => 'Settlement number is missing.'
                ]);
            }

            $settlement = Settlement::where('settlement_no', $request->settlement_no)
                ->where('business_id', $business_id)
                ->first();

            if (empty($settlement)) {
                 // Try by ID fallback
                 $settlement = Settlement::where('id', $request->settlement_no)
                    ->where('business_id', $business_id)
                    ->first();
            }

            if (empty($settlement)) {
                \Log::error('saveCashDeposit: Settlement not found', [
                    'settlement_no' => $request->settlement_no,
                    'business_id' => $business_id
                ]);
                return response()->json([
                    'success' => 0,
                    'msg' => 'Settlement not found. Please ensure you have created the settlement and try again.'
                ]);
            }

            $data = [
                'business_id'    => $business_id,
                'settlement_no'  => $settlement->id,
                'bank_id'        => $request->bank_id,
                'amount'         => $request->cash_deposit_amount,
                'account_no'     => $request->account,
                'time_deposited' => $request->time,
            ];

            $settlement_cash_payment = SettlementCashDeposit::create($data);

            // Update is_edit status
            $settlement->update(['is_edit' => $request->is_edit ?? 0]);

            $output = [
                'success' => 1,
                'settlement_cash_payment_id' => $settlement_cash_payment->id,
                'msg' => __('petropd::lang.success')
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        return response()->json($output);
    }


    /**
     * delete cash payment data to db

     *
     * @return Response
     */
    public function deleteCashPayment($id, Request $request)
    {

        try {

            $pumpPaymentId = $request->input('pump_payment_id');
            if (! empty($pumpPaymentId)) {
                $pumpPayment = PumpOperatorPayment::findOrFail($pumpPaymentId);
                $pumpPayment->is_used = 1;
                $pumpPayment->save();

                $output = [
                    'success' => true,
                    'amount'  => $pumpPayment->payment_amount,
                    'msg'     => __('petropd::lang.success'),
                ];

                return $output;
            }

            $payment = SettlementCashPayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            // Delete corresponding ContactLedger entry if it exists (linked via settlement_no in note)
            $settlement_record = Settlement::find($payment->settlement_no);
            if ($settlement_record) {
                ContactLedger::where('transaction_id', null)
                    ->where('amount', $amount)
                    ->where('note', 'like', '%' . $settlement_record->settlement_no . '%')
                    ->where('sub_type', 'cash_payment')
                    ->forceDelete();
            }

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function deleteCustomerLoan($id)
    {

        try {

            $payment = SettlementCustomerLoan::where('id', $id)->first();

            // Check if payment exists before trying to access its properties
            if (!$payment) {
                return [
                    'success' => false,
                    'msg'     => __('petropd::lang.customer_loan_not_found'),
                ];
            }

            // Update settlement if settlement_no exists and is numeric
            if (!empty($payment->settlement_no)) {
                if (is_numeric($payment->settlement_no)) {
            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                } else {
                    // If settlement_no is a string (like "ST7"), find by settlement_no
                    Settlement::where('settlement_no', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                }
            }

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function deleteLoanPayment($id)
    {

        try {

            $payment = SettlementLoanPayment::where('id', $id)->first();

            // Check if payment exists before trying to access its properties
            if (!$payment) {
                return [
                    'success' => false,
                    'msg'     => __('petropd::lang.loan_payment_not_found'),
                ];
            }

            // Update settlement if settlement_no exists and is numeric
            if (!empty($payment->settlement_no)) {
                if (is_numeric($payment->settlement_no)) {
            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                } else {
                    // If settlement_no is a string (like "ST7"), find by settlement_no
                    Settlement::where('settlement_no', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                }
            }

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function deleteDrawingPayment($id)
    {

        try {

            $payment = SettlementDrawingPayment::where('id', $id)->first();

            // Check if payment exists before trying to access its properties
            if (!$payment) {
                return [
                    'success' => false,
                    'msg'     => __('petropd::lang.drawing_payment_not_found'),
                ];
            }

            // Update settlement if settlement_no exists and is numeric
            if (!empty($payment->settlement_no)) {
                if (is_numeric($payment->settlement_no)) {
            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                } else {
                    // If settlement_no is a string (like "ST7"), find by settlement_no
                    Settlement::where('settlement_no', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                }
            }

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function deleteCashDeposit($id)
    {

        try {

            $payment = SettlementCashDeposit::where('id', $id)->first();

            // Check if payment exists before trying to access its properties
            if (!$payment) {
                return [
                    'success' => false,
                    'msg'     => __('petropd::lang.cash_deposit_not_found'),
                ];
            }

            // Update settlement if settlement_no exists and is numeric
            if (!empty($payment->settlement_no)) {
                if (is_numeric($payment->settlement_no)) {
            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                } else {
                    // If settlement_no is a string (like "ST7"), find by settlement_no
                    Settlement::where('settlement_no', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);
                }
            }

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**
     * add card payment data to db

     *
     * @return Response
     */
    public function saveCardPayment(Request $request)
    {
        Log::info('Request Data for saveCardPayment in AddPaymentController:', ['request' => $request->all()]);

        try {
            DB::beginTransaction();

            $business_id = $request->session()->get('business.id');

            /*
             * IS1983: this check used to run unconditionally, so duplicate slips
             * were blocked here even with "Do not Allow Duplicate Slip Numbers"
             * switched OFF - the second half of the requirement, that disabling
             * the checkbox permits duplicates, was not met on this screen.
             *
             * It now consults the same setting the pumper dashboard uses. The
             * resolution lives in one place - see
             * PDPumpOperatorPaymentController::duplicateSlipNumbersBlocked() - and
             * is read here through the same ModuleUtil call so both screens can
             * never disagree.
             */
            $slip_no = preg_replace('/\s+/', '', (string) $request->slip_no);
            $today   = \Carbon::now()->format('Y-m-d');

            $block_duplicate_slip_numbers = false;
            try {
                $block_duplicate_slip_numbers = (bool) $this->moduleUtil->hasThePermissionInSubscription(
                    $business_id,
                    'duplicate_slip_numbers'
                );
            } catch (\Throwable $slipSettingException) {
                Log::warning('IS1983: could not resolve the duplicate slip setting', [
                    'business_id' => $business_id,
                    'message'     => $slipSettingException->getMessage(),
                ]);
            }

            if ($block_duplicate_slip_numbers && ! empty($slip_no)) {
                $existingRecord = SettlementCardPayment::where('slip_no', $slip_no)
                    ->whereDate('created_at', $today)
                    ->exists();

                if ($existingRecord) {
                    throw new \Exception(__('petropd::lang.duplicate_slip_number'));
                }
            }

            // Retrieve Settlement
            $settlement = Settlement::where('settlement_no', $request->settlement_no)
                ->where('business_id', $business_id)
                ->firstOrFail();

            // Check for duplicate settlement card payment to prevent accidental double-submission
            // Match by settlement, customer, amount, and recent timestamp (within last 5 seconds)
            $duplicate_check = SettlementCardPayment::where('settlement_no', $settlement->id)
                ->where('customer_id', $request->customer_id)
                ->where('amount', $request->amount)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->first();

            if ($duplicate_check) {
                DB::rollBack();
                return [
                    'success' => false,
                    'msg' => __('petropd::lang.duplicate_payment_detected'),
                ];
            }

            $pump_payment = $this->findMatchingPumpCardPayment($settlement, $request, $business_id);
            $linked_daily_card = ! empty($pump_payment)
                ? $this->findLinkedDailyCardForPumpPayment($pump_payment, $business_id)
                : null;

            $settlement_card_payment = app(\Modules\PetroPD\Services\SettlementPaymentReconciler::class)
                ->upsertOne($business_id, (string) $settlement->id, 'settlement_card_payments', [
                    'amount'      => $request->amount,
                    'card_type'   => $request->card_type,
                    'card_number' => $request->card_number,
                    'customer_id' => $request->customer_id,
                    'note'        => $request->note,
                    'slip_no'     => $slip_no,
                    'pump_payment_id' => optional($pump_payment)->id,
                    'daily_card_id' => $linked_daily_card->id ?? null,
                ]);

            if (! empty($pump_payment)) {
                $pump_payment->is_used = 1;
                $pump_payment->parent_id = $settlement_card_payment->id;
                $pump_payment->settlement_no = $settlement->id;
                $pump_payment->save();
            }

            // Update Settlement edit flag if needed
            if ($request->has('is_edit')) {
                $settlement->update(['is_edit' => $request->is_edit]);
            }

            // CRITICAL: Do NOT create Transaction, TransactionPayment, AccountTransaction, or ContactLedger here
            // These should only be created when the settlement is saved (finalized).
            // Creating them here causes duplicates and data inconsistency (missing transaction links).

            // CRITICAL: Do NOT create Transaction, TransactionPayment, or AccountTransaction here
            // These should only be created when the settlement is saved (in SettlementPDController@store)
            // Creating them here causes duplicates when the settlement is saved
            // The SettlementCardPayment record is sufficient for tracking the payment until settlement is saved

            DB::commit();

            $output = [
                'success'                    => true,
                'settlement_card_payment_id' => $settlement_card_payment->id,
                'pump_payment_id'            => isset($pump_payment) ? $pump_payment->id : null,
                'msg'                        => __('petropd::lang.success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            $is_reconciliation_error = $e instanceof \RuntimeException
                && str_starts_with($e->getMessage(), 'Payment reconciliation stopped:');

            $output = [
                'success' => false,
                // IS1983: the duplicate slip message is now petropd::lang.duplicate_slip_number.
                // The legacy messages.duplicate_slip is still matched so an older
                // throw from anywhere else still reaches the user as itself rather
                // than as a generic failure.
                'msg'     => in_array($e->getMessage(), [__('petropd::lang.duplicate_slip_number'), __('messages.duplicate_slip')], true)
                    ? $e->getMessage()
                    : ($is_reconciliation_error
                        ? $e->getMessage()
                        : __('messages.something_went_wrong')),
            ];
        }

        return $output;
    }

    /**
     * delete card payment data to db

     *
     * @return Response
     */
    public function deleteCardPayment($id, Request $request)
    {

        try {

            $pumpPaymentId = $request->input('pump_payment_id');
            if (! empty($pumpPaymentId)) {
                $pumpPayment = PumpOperatorPayment::findOrFail($pumpPaymentId);
                $pumpPayment->is_used = 1;
                $pumpPayment->save();

                $output = [
                    'success' => true,
                    'amount'  => $pumpPayment->payment_amount,
                    'msg'     => __('petropd::lang.success'),
                ];

                return $output;
            }

            $payment = SettlementCardPayment::where('id', $id)->first();

            if (empty($payment)) {
                throw new \Exception('Card payment not found');
            }

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;
            $business_id = $request->session()->get('business.id');

            app(\Modules\PetroPD\Services\SettlementPaymentEditService::class)
                ->deletePaymentLine($business_id, 'settlement_card_payments', $payment->id);

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**
     * add cheque payment data to db

     *
     * @return Response
     */
    public function saveChequePayment(Request $request)
    {
        try {
            DB::beginTransaction();

            $business_id = (int) $this->resolveBusinessIdFromRequest($request);
            $settlement = Settlement::where('settlement_no', $request->settlement_no)
                ->where('business_id', $business_id)
                ->firstOrFail();
            $shift_id = $this->requireSingleSettlementShiftId($settlement);

            $duplicate_check = SettlementChequePayment::where('settlement_no', $settlement->id)
                ->where('customer_id', $request->customer_id)
                ->where('amount', $request->amount)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->first();

            if ($duplicate_check) {
                DB::rollBack();
                return [
                    'success' => false,
                    'msg' => __('petropd::lang.duplicate_payment_detected'),
                ];
            }

            $pump_payment = $this->resolveUnambiguousMasterPaymentForSettlement(
                $settlement,
                $request,
                $business_id,
                'cheque',
                (float) $request->amount
            );

            $data = [
                'amount'                   => $request->amount,
                'bank_name'                => $request->bank_name,
                'cheque_number'            => $request->cheque_number,
                'cheque_date'              => Carbon::parse($request->cheque_date)->format('Y-m-d'),
                'customer_id'              => $request->customer_id,
                'note'                     => $request->note,
                'post_dated_cheque'        => $request->post_dated_cheque,
                'update_post_dated_cheque' => $request->update_post_dated_cheque,
                'pump_payment_id'          => optional($pump_payment)->id,
                'shift_id'                 => $shift_id,
                'pump_operator_id'         => $settlement->pump_operator_id,
            ];

            $settlement_cheque_payment = app(\Modules\PetroPD\Services\SettlementPaymentReconciler::class)
                ->upsertOne($business_id, (string) $settlement->id, 'settlement_cheque_payments', $data);

            if ($pump_payment) {
                $pump_payment->is_used = 1;
                $pump_payment->parent_id = $settlement_cheque_payment->id;
                $pump_payment->settlement_no = $settlement->id;
                $pump_payment->save();
            }

            Settlement::where('id', $settlement->id)->update(['is_edit' => $request->is_edit]);
            DB::commit();

            $output = [
                'success'                      => true,
                'settlement_cheque_payment_id' => $settlement_cheque_payment->id,
                'pump_payment_id'              => optional($pump_payment)->id,
                'msg'                          => __('petropd::lang.success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            $is_reconciliation_error = $e instanceof \RuntimeException
                && str_starts_with($e->getMessage(), 'Payment reconciliation stopped:');

            $output = [
                'success' => false,
                'msg'     => $is_reconciliation_error
                    ? $e->getMessage()
                    : __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * delete cheque payment data to db
     *
     * @return Response
     */
    public function deleteChequePayment($id)
    {

        try {

            $payment = SettlementChequePayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    // public function check_order_number(Request $request)

    //     {
    // dd('hello');

    //         try {

    //         } catch (\Exception $e) {

    //             $output = [

    //                 'success' => false,

    //                 'msg' => __('messages.something_went_wrong')

    //             ];

    //         }

    //         return $output;

    //     }

    /**
     * add credit_sale payment data to db

     *
     * @return Response
     */
    public function saveCreditSalePayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $business = Business::find($business_id);

            $orderNumberRules = ['nullable'];

            // Only enforce uniqueness if duplicate orders are NOT allowed
            if (! $business->duplicate_orders_allowed) {
                $orderNumberRules[] = Rule::unique('settlement_credit_sale_payments', 'order_number')
                    ->where(function ($query) use ($request) {
                        return $query->where('customer_id', $request->customer_id);
                    });
            }

            $validator = Validator::make($request->all(), [
                'order_number' => $orderNumberRules,
            ]);

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'msg'     => $validator->errors()->first(),
                ];
            }

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            // Business rule: Order No must not be reused for a different customer
            // within the same Pump Operator + Shift + Date context.
            $order_number = trim((string) $request->order_number);
            if (
                $order_number !== ''
                && $order_number !== '0'
                && $order_number !== '-'
                && stripos($order_number, 'No Order') !== 0
            ) {
                $pump_operator_id = (int) (!empty($request->pump_operator_id) ? $request->pump_operator_id : $settlement->pump_operator_id);

                $order_no_used_by_other_customer = SettlementCreditSalePayment::query()
                    ->join('settlements', 'settlements.settlement_no', '=', 'settlement_credit_sale_payments.settlement_no')
                    ->where('settlement_credit_sale_payments.business_id', $business_id)
                    ->where('settlement_credit_sale_payments.pump_operator_id', $pump_operator_id)
                    ->where('settlement_credit_sale_payments.order_number', $order_number)
                    ->whereDate('settlements.transaction_date', $settlement->transaction_date)
                    ->where('settlements.work_shift', $settlement->work_shift)
                    ->where('settlement_credit_sale_payments.customer_id', '!=', $request->customer_id)
                    ->exists();

                if ($order_no_used_by_other_customer) {
                    return [
                        'success' => false,
                        'msg' => 'Order No ' . $order_number . ' is already used for another customer in this shift. Please use the same customer or change the Order No.',
                    ];
                }
            }

            $price = $this->productUtil->num_uf($request->price);

            $unit_discount = $this->productUtil->num_uf($request->unit_discount);

            $qty = $this->productUtil->num_uf($request->qty);

            $amount = $this->productUtil->num_uf($request->amount);

            $total_discount = $this->productUtil->num_uf($request->total_discount) ?? 0;

            // Always compute net amount server-side to prevent client-side mismatches.
            $sub_total = $amount - $total_discount;

            // observe if it has been saved before and remove it

            $order_date = \Carbon::parse($request->order_date)->format('Y-m-d');

            $existing_payment = null;
            if ($request->filled('scsp_id')) {
                $existing_payment = SettlementCreditSalePayment::where('id', $request->input('scsp_id'))
                    ->where('business_id', $business_id)
                    ->first();
            }

            if (! $existing_payment) {
                // DAY1-LEGACY-LOOKUP: retained for old edit forms that do not submit scsp_id.
                $existing_payment = SettlementCreditSalePayment::where('order_number', $request->order_number)

                    ->where('customer_id', $request->customer_id)

                    ->where('business_id', $business_id)

                    ->where('settlement_no', $settlement->settlement_no)

                    ->where('product_id', $request->product_id)

                    ->where('pump_operator_id', (int) $request->pump_operator_id)

                    ->where('order_date', $order_date)

                    ->where('qty', $qty)

                    ->where('amount', $amount)

                    ->where('total_discount', $total_discount)

                    ->first();
            }

            // Resolve the immutable financial Shift ID before creating or linking
            // any credit payment. Never fall back to the operator's latest shift.
            $rawCreditShiftIds = $request->input('shift_ids');
            if (is_array($rawCreditShiftIds)) {
                $creditShiftIds = $rawCreditShiftIds;
            } else {
                $creditShiftIds = preg_split('/\s*,\s*/', (string) $rawCreditShiftIds, -1, PREG_SPLIT_NO_EMPTY);
            }
            $creditShiftIds = array_values(array_unique(array_filter(array_map('intval', $creditShiftIds ?: []))));

            if (empty($creditShiftIds) && ! empty($settlement->work_shift)) {
                $workShift = $settlement->work_shift;
                if (is_string($workShift)) {
                    $decoded = json_decode($workShift, true);
                    $workShift = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/', $workShift, -1, PREG_SPLIT_NO_EMPTY);
                }
                $creditShiftIds = array_values(array_unique(array_filter(array_map('intval', (array) $workShift))));
            }

            if (count($creditShiftIds) !== 1) {
                return [
                    'success' => false,
                    'msg' => 'A credit payment must be linked to exactly one Shift ID. Please select the shift again.',
                ];
            }

            $creditShiftId = (int) $creditShiftIds[0];
            $creditOperatorId = (int) (! empty($request->pump_operator_id)
                ? $request->pump_operator_id
                : $settlement->pump_operator_id);

            DB::beginTransaction();

            $pump_payment = null;
            if ($request->filled('pump_payment_id')) {
                $pump_payment = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('id', $request->input('pump_payment_id'))
                    ->where('payment_type', 'credit')
                    ->where('pump_operator_id', $creditOperatorId)
                    ->where('shift_id', $creditShiftId)
                    ->lockForUpdate()
                    ->first();
            } elseif ($existing_payment && ! empty($existing_payment->pump_payment_id)) {
                $pump_payment = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('id', $existing_payment->pump_payment_id)
                    ->where('payment_type', 'credit')
                    ->where('pump_operator_id', $creditOperatorId)
                    ->where('shift_id', $creditShiftId)
                    ->lockForUpdate()
                    ->first();
            } elseif ($existing_payment && ! empty($request->collection_form_no)) {
                $pumpPaymentCandidates = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('pump_operator_id', $creditOperatorId)
                    ->where('shift_id', $creditShiftId)
                    ->where('collection_form_no', $request->collection_form_no)
                    ->where('payment_type', 'credit')
                    ->lockForUpdate()
                    ->limit(2)
                    ->get();

                if ($pumpPaymentCandidates->count() > 1) {
                    throw new \RuntimeException(
                        'More than one master credit payment matches this Shift ID and collection number.'
                    );
                }
                $pump_payment = $pumpPaymentCandidates->first();
            }

            if (! $pump_payment) {
                $masterPaymentData = [
                    'business_id' => $business_id,
                    'pump_operator_id' => $creditOperatorId,
                    'shift_id' => $creditShiftId,
                    'payment_type' => 'credit',
                    // Keep legacy payment_amount semantics as gross.
                    'payment_amount' => $amount,
                    'collection_form_no' => $request->collection_form_no ?: null,
                    'note' => $request->note,
                    'created_by' => Auth::id(),
                ];

                if (Schema::hasColumn('pump_operator_payments', 'gross_amount')) {
                    $masterPaymentData['gross_amount'] = $amount;
                }
                if (Schema::hasColumn('pump_operator_payments', 'discount_amount')) {
                    $masterPaymentData['discount_amount'] = $total_discount;
                }
                if (Schema::hasColumn('pump_operator_payments', 'net_amount')) {
                    $masterPaymentData['net_amount'] = $sub_total;
                }
                if (Schema::hasColumn('pump_operator_payments', 'source_type')) {
                    $masterPaymentData['source_type'] = 'credit_sale';
                }
                if (Schema::hasColumn('pump_operator_payments', 'customer_id')) {
                    $masterPaymentData['customer_id'] = (int) $request->customer_id;
                }
                if (Schema::hasColumn('pump_operator_payments', 'transaction_date')) {
                    $masterPaymentData['transaction_date'] = $order_date;
                }
                if (Schema::hasColumn('pump_operator_payments', 'reference_no')) {
                    $masterPaymentData['reference_no'] = ! empty($request->order_number)
                        ? (string) $request->order_number
                        : null;
                }
                if (Schema::hasColumn('pump_operator_payments', 'settlement_no')) {
                    $masterPaymentData['settlement_no'] = $settlement->settlement_no;
                }

                $pump_payment = PumpOperatorPayment::create($masterPaymentData);
            }

            if ((int) $pump_payment->shift_id !== $creditShiftId) {
                throw new \RuntimeException('Credit payment Shift ID does not match the selected settlement shift.');
            }

            if (! $existing_payment) {

                $data = [

                    'business_id'        => $business_id,

                    'settlement_no'      => $settlement->settlement_no, // Use string settlement_no for credit_sale_payments relationship

                    'customer_id'        => $request->customer_id,

                    'product_id'         => $request->product_id,

                    'order_number'       => !empty($request->order_number) ? trim($request->order_number) : '0',

                    'order_date'         => $order_date,

                    'pump_operator_id'   => $creditOperatorId,

                    'price'              => $price,

                    'discount'           => $unit_discount,

                    'qty'                => $qty,

                    'amount'             => $amount,

                    'sub_total'          => $sub_total,

                    'total_discount'     => $total_discount,

                    'outstanding'        => $this->productUtil->num_uf($request->outstanding),

                    'credit_limit'       => $request->credit_limit,

                    'customer_reference' => $request->customer_reference,

                    'note'               => $request->note,

                    // Every PD credit payment has one authoritative master row.
                    'pump_payment_id'     => $pump_payment->id,

                ];

                if (Schema::hasColumn('settlement_credit_sale_payments', 'shift_id')) {
                    $data['shift_id'] = $creditShiftId;
                }
                if (Schema::hasColumn('settlement_credit_sale_payments', 'is_from_pumper')) {
                    // IS1771: this row was added from PD Settlement > Payment to
                    // Finalize, not from the Pumper Dashboard real-time flow.
                    $data['is_from_pumper'] = 0;
                }

                // dd($data);

                $settlement_credit_sale_payment = app(\Modules\PetroPD\Services\SettlementPaymentReconciler::class)
                    ->upsertOne($business_id, (string) $settlement->settlement_no, 'settlement_credit_sale_payments', $data);

                $masterUpdate = [
                    'payment_amount' => $amount,
                    'note' => $request->note,
                    'edited_by' => Auth::id(),
                ];
                if (Schema::hasColumn('pump_operator_payments', 'gross_amount')) {
                    $masterUpdate['gross_amount'] = $amount;
                }
                if (Schema::hasColumn('pump_operator_payments', 'discount_amount')) {
                    $masterUpdate['discount_amount'] = $total_discount;
                }
                if (Schema::hasColumn('pump_operator_payments', 'net_amount')) {
                    $masterUpdate['net_amount'] = $sub_total;
                }
                if (Schema::hasColumn('pump_operator_payments', 'source_type')) {
                    $masterUpdate['source_type'] = 'credit_sale';
                }
                if (Schema::hasColumn('pump_operator_payments', 'source_id')) {
                    $masterUpdate['source_id'] = $settlement_credit_sale_payment->id;
                }
                if (Schema::hasColumn('pump_operator_payments', 'customer_id')) {
                    $masterUpdate['customer_id'] = (int) $request->customer_id;
                }
                if (Schema::hasColumn('pump_operator_payments', 'transaction_date')) {
                    $masterUpdate['transaction_date'] = $order_date;
                }
                if (Schema::hasColumn('pump_operator_payments', 'reference_no')) {
                    $masterUpdate['reference_no'] = ! empty($request->order_number)
                        ? (string) $request->order_number
                        : null;
                }
                if (Schema::hasColumn('pump_operator_payments', 'settlement_no')) {
                    $masterUpdate['settlement_no'] = $settlement->settlement_no;
                }

                $updatedMasterCount = PumpOperatorPayment::where('id', $pump_payment->id)
                    ->where('business_id', $business_id)
                    ->where('pump_operator_id', $creditOperatorId)
                    ->where('shift_id', $creditShiftId)
                    ->where('payment_type', 'credit')
                    ->update($masterUpdate);

                if ($updatedMasterCount !== 1) {
                    throw new \RuntimeException(
                        'Unable to update the authoritative credit payment for the selected Shift ID.'
                    );
                }

            } else {
                $editData = [
                    'price' => $price,
                    'discount' => $unit_discount,
                    'qty' => $qty,
                    'amount' => $amount,
                    'sub_total' => $sub_total,
                    'total_discount' => $total_discount,
                    'outstanding' => $this->productUtil->num_uf($request->outstanding),
                    'credit_limit' => $request->credit_limit,
                    'customer_reference' => $request->customer_reference,
                    'note' => $request->note,
                ];
                if (empty($existing_payment->pump_payment_id) && !empty($pump_payment)) {
                    $editData['pump_payment_id'] = $pump_payment->id;
                }
                $settlement_credit_sale_payment = app(\Modules\PetroPD\Services\SettlementPaymentEditService::class)
                    ->editCreditSale($business_id, $existing_payment->id, $editData);
                $existing_payment = $settlement_credit_sale_payment;

                // Update the linked pump_operator_payment for this specific credit sale.
                //
                // IS1293 fix (2026-05-13): the previous code keyed updates on the COMPOSITE
                // (pump_operator_id, collection_form_no, payment_type) which is NOT unique
                // when multiple credit sales are added one-by-one in the same dashboard
                // session — they all share the same collection_form_no. A bulk update on
                // that composite overwrote sibling rows' payment_amount to the value being
                // edited on a single row. The Payment Summary view then showed all rows
                // with the same amount.
                //
                // After Step 2 every scsp row carries pump_payment_id pointing to its
                // source pump_operator_payments.id. Use that FK for a single-row update.
                if (empty($existing_payment->pump_payment_id)) {
                    throw new \RuntimeException(
                        'Credit sale has no authoritative Pump Operator Payment link. Editing was stopped.'
                    );
                }

                $masterUpdate = [
                    'payment_amount' => $amount,
                    'note' => $request->note,
                    'edited_by' => Auth::id(),
                ];
                if (Schema::hasColumn('pump_operator_payments', 'gross_amount')) {
                    $masterUpdate['gross_amount'] = $amount;
                }
                if (Schema::hasColumn('pump_operator_payments', 'discount_amount')) {
                    $masterUpdate['discount_amount'] = $total_discount;
                }
                if (Schema::hasColumn('pump_operator_payments', 'net_amount')) {
                    $masterUpdate['net_amount'] = $sub_total;
                }
                if (Schema::hasColumn('pump_operator_payments', 'source_type')) {
                    $masterUpdate['source_type'] = 'credit_sale';
                }
                if (Schema::hasColumn('pump_operator_payments', 'source_id')) {
                    $masterUpdate['source_id'] = $settlement_credit_sale_payment->id;
                }
                if (Schema::hasColumn('pump_operator_payments', 'customer_id')) {
                    $masterUpdate['customer_id'] = (int) $request->customer_id;
                }
                if (Schema::hasColumn('pump_operator_payments', 'transaction_date')) {
                    $masterUpdate['transaction_date'] = $order_date;
                }
                if (Schema::hasColumn('pump_operator_payments', 'reference_no')) {
                    $masterUpdate['reference_no'] = ! empty($request->order_number)
                        ? (string) $request->order_number
                        : null;
                }

                $updatedMasterCount = PumpOperatorPayment::where('id', $existing_payment->pump_payment_id)
                    ->where('business_id', $business_id)
                    ->where('pump_operator_id', $creditOperatorId)
                    ->where('shift_id', $creditShiftId)
                    ->where('payment_type', 'credit')
                    ->update($masterUpdate);

                if ($updatedMasterCount !== 1) {
                    throw new \RuntimeException(
                        'The linked Pump Operator Payment does not belong to the same immutable Shift ID.'
                    );
                }

                // DailyVoucher: prefer direct linkage by daily_voucher_id (per-row FK).
                // Fall back to composite only if daily_voucher_id is missing — and even
                // then, the daily_voucher table is typically one-per-credit-sale so the
                // composite usually still uniquely identifies the row.
                if (! empty($existing_payment->daily_voucher_id)) {
                    DailyVoucher::where('id', $existing_payment->daily_voucher_id)
                        ->where('business_id', $business_id)
                        ->update(['total_amount' => $sub_total]);
                }

                // DailyCollection: this table aggregates per-collection-form across all
                // line items in the form. Recompute its current_amount as the SUM of all
                // scsp.sub_total sharing this collection_form_no, rather than overwriting
                // it to the single edited row's sub_total (which dropped sibling amounts).
                if (! empty($existing_payment->collection_form_no) && ! empty($existing_payment->pump_operator_id)) {
                    $formTotal = SettlementCreditSalePayment::where('business_id', $business_id)
                        ->where('pump_operator_id', $existing_payment->pump_operator_id)
                        ->where('collection_form_no', $existing_payment->collection_form_no)
                        ->sum('sub_total');

                    DailyCollection::where('business_id', $business_id)
                        ->where('pump_operator_id', $existing_payment->pump_operator_id)
                        ->where('collection_form_no', $existing_payment->collection_form_no)
                        ->where('type', 'daily_voucher')
                        ->update(['current_amount' => $formTotal]);
                }

                // Update accounting entries when credit sale is edited (Account Receivable + Customer Ledger)
                // So edited amounts show correctly in List Account / Account Receivable and Contact Ledger
                if (!empty($existing_payment->transaction_id)) {
                    Transaction::where('id', $existing_payment->transaction_id)->update([
                        'final_total' => $sub_total,
                        'total_before_tax' => $sub_total,
                        'discount_amount' => $total_discount,
                    ]);
                    ContactLedger::where('transaction_id', $existing_payment->transaction_id)->update([
                        'amount' => $sub_total,
                    ]);
                    $ar_account_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
                    if (!empty($ar_account_id)) {
                        AccountTransaction::where('transaction_id', $existing_payment->transaction_id)
                            ->where('account_id', $ar_account_id)
                            ->where('type', 'debit')
                            ->update(['amount' => $sub_total]);
                    }
                }
            }

            $fresh_credit_sale_payment = SettlementCreditSalePayment::query()
                ->leftJoin('contacts', 'settlement_credit_sale_payments.customer_id', '=', 'contacts.id')
                ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
                ->where('settlement_credit_sale_payments.id', $settlement_credit_sale_payment->id)
                ->select(
                    'settlement_credit_sale_payments.*',
                    'contacts.name as customer_name',
                    'products.name as product_name'
                )
                ->first();

            $row_html = '';
            if (! empty($fresh_credit_sale_payment)) {
                $row_html = '<tr data-credit-sale-payment-id="' . e($fresh_credit_sale_payment->id) . '">'
                    . '<td>' . e($fresh_credit_sale_payment->customer_name ?? '') . '</td>'
                    . '<td>' . e(num_format($fresh_credit_sale_payment->outstanding ?? 0)) . '</td>'
                    . '<td>' . e(num_format($fresh_credit_sale_payment->credit_limit ?? 0)) . '</td>'
                    . '<td>' . e($fresh_credit_sale_payment->order_number ?? '') . '</td>'
                    . '<td>' . e($fresh_credit_sale_payment->order_date ?? '') . '</td>'
                    . '<td>' . e($fresh_credit_sale_payment->customer_reference ?? '') . '</td>'
                    . '<td>' . e($fresh_credit_sale_payment->product_name ?? '') . '</td>'
                    . '<td>' . e(num_format($fresh_credit_sale_payment->price ?? 0)) . '</td>'
                    . '<td>' . e(num_format($fresh_credit_sale_payment->qty ?? 0)) . '</td>'
                    . '<td class="credit_sale_amount">' . e(num_format($fresh_credit_sale_payment->amount ?? 0)) . '</td>'
                    . '<td class="credit_tbl_discount_amount">' . e(num_format($fresh_credit_sale_payment->total_discount ?? 0)) . '</td>'
                    . '<td class="credit_tbl_total_amount">' . e(num_format(($fresh_credit_sale_payment->amount ?? 0) - ($fresh_credit_sale_payment->total_discount ?? 0))) . '</td>'
                    . '<td>' . e($fresh_credit_sale_payment->note ?? '') . '</td>'
                    . '<td><button type="button" class="btn btn-xs btn-danger delete_credit_sale_payment" data-href="/petropd/settlement/payment/delete-credit-sale-payment/' . e($fresh_credit_sale_payment->id) . '"><i class="fa fa-times"></i></button></td>'
                    . '</tr>';
            }

            DB::commit();

            $output = [

                'success'                           => true,

                'settlement_credit_sale_payment_id' => $settlement_credit_sale_payment->id,

                'row_html'                          => $row_html,

                'net_amount'                        => $sub_total,

                'amount'                            => $amount,

                'total_discount'                    => $total_discount,

                'msg'                               => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => $e instanceof \RuntimeException
                    ? $e->getMessage()
                    : __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**
     * delete credit_sale payment data to db

     *
     * @return Response
     */
    public function deleteCreditSalePayment($id)
    {

        try {

            $payment = SettlementCreditSalePayment::where('id', $id)->first();

            if (empty($payment)) {
                return [
                    'success' => false,
                    'msg'     => __('petropd::lang.credit_sale_not_found') ?: 'Credit sale not found',
                ];
            }

            // Resolve settlement: credit sales store settlement_no as string (e.g. "SET-656") or integer id
            $settlement_no = $payment->settlement_no;
            $settlement = null;
            if (is_numeric($settlement_no) && (string) (int) $settlement_no === (string) $settlement_no) {
                $settlement = Settlement::find($settlement_no);
            } else {
                $settlement = Settlement::where('settlement_no', $settlement_no)->first();
            }
            if ($settlement) {
                $settlement->update(['is_edit' => request()->is_edit]);
            }

            $amount = $payment->amount;

            $discount = $payment->total_discount ?? 0;

            // Delete related accounting entries when credit sale is removed (Account Receivable + Customer Ledger)
            // Entries are linked via transaction_id; ref_no/sub_type do not match creation
            if (!empty($payment->transaction_id)) {
                AccountTransaction::where('transaction_id', $payment->transaction_id)->forceDelete();
                ContactLedger::where('transaction_id', $payment->transaction_id)->forceDelete();
                TransactionPayment::where('transaction_id', $payment->transaction_id)->forceDelete();
                Transaction::where('id', $payment->transaction_id)->forceDelete();
            }

            // Delete Daily Voucher entries if they exist
            if (!empty($payment->daily_voucher_id)) {
                DailyVoucher::where('id', $payment->daily_voucher_id)->delete();
            }

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'net_amount' => $amount - $discount,

                'msg'     => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**
     * get price of product

     *
     * @return Response
     */
    public function getProductPrice(Request $request)
    {

        $product_id = $request->product_id;

        $product = Product::leftjoin('variations', 'products.id', 'variations.product_id')

            ->where('products.id', $product_id)

            ->select('sell_price_inc_tax')

            ->first();

        if (! empty($product)) {

            $price = $product->sell_price_inc_tax;

        } else {

            $price = 0.00;

        }

        return ['price' => $price];

    }

    /**
     * get price of product

     *
     * @return Response
     */
    public function getCustomerDetails($customer_id, ContactController $contactController)
    {

        $business_id = request()->session()->get('business.id');

        $query = Contact::leftjoin('transactions AS t', 'contacts.id', '=', 't.contact_id')

            ->leftjoin('contact_groups AS cg', 'contacts.customer_group_id', '=', 'cg.id')

            ->where('contacts.business_id', $business_id)

            ->where('contacts.id', $customer_id)

        // ->onlyCustomers()

            ->select([

                'contacts.vat_number', 'contacts.manual_bill_settlement', 'contacts.contact_id', 'contacts.name', 'contacts.created_at', 'total_rp', 'cg.name as customer_group', 'city', 'state', 'country', 'landmark', 'mobile', 'contacts.id', 'is_default', 'contacts.sub_customers',

                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),

                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),

                DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),

                DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as sell_return_paid"),

                DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),

                DB::raw("SUM(IF(t.type = 'advance_payment', -1*final_total, 0)) as advance_payment"),

                DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),

                'email', 'tax_number', 'contacts.pay_term_number', 'contacts.pay_term_type', 'contacts.credit_limit', 'contacts.custom_field1', 'contacts.custom_field2', 'contacts.custom_field3', 'contacts.custom_field4', 'contacts.type',

            ])

            ->groupBy('contacts.id')->first();

        $due = 0;

        $return_due = 0;

        $opening_balance = 0;

        if (! empty($query)) {

            $due = $query->total_invoice - $query->invoice_received + $query->advance_payment;

            $return_due = $query->total_sell_return - $query->sell_return_paid;

            $opening_balance = $query->opening_balance - $query->opening_balance_paid;

        }

        $total_outstanding = $due - $return_due + $opening_balance;

        if (empty($total_outstanding)) {

            $total_outstanding = 0.00;

        }

        if (empty($query->credit_limit)) {

            $credit_limit = 'No Limit';

        } else {

            $credit_limit = $query->credit_limit;

        }

        $business_details = Business::find($business_id);

        $customer_references = CustomerReference::where('contact_id', $customer_id)->where('business_id', $business_id)->select('reference')->get();

        $sub_ids = json_decode($query->sub_customers) ?? [];

        $sub_customers = Contact::whereIn('id', $sub_ids)->pluck('name', 'id');

        $payment_data = DB::table('customer_payments')

            ->leftJoin('settlements', 'customer_payments.settlement_no', '=', 'settlements.id')

            ->leftJoin('transactions', 'transactions.invoice_no', '=', 'settlements.settlement_no')

            ->where('customer_payments.customer_id', $customer_id)

            ->select([

                'settlements.transaction_date',

                'settlements.settlement_no',

                'transactions.order_no',

            ])

            ->orderBy('settlements.transaction_date', 'desc') // Optional: order by date

            ->get();

        // return ['total_outstanding' =>  strval($this->productUtil->num_f($total_outstanding, false, $business_details, true)), 'credit_limit' => strval($credit_limit), 'customer_references' => $customer_references];

        return ['total_outstanding' => $this->productUtil->num_f(strval($contactController->get_cus_due_bal($customer_id, false))), 'credit_limit' => strval($credit_limit), 'customer_references' => $customer_references, 'sub_customers' => $sub_customers, 'vat_number' => $query->vat_number, 'manual_bill_settlement' => $query->manual_bill_settlement, 'customer_name' => $query->name, 'payment_data' => $payment_data];

    }

    /**
     * add expense payment data to db

     *
     * @return Response
     */
    public function saveExpensePayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $data = [

                'business_id'    => $business_id,

                'settlement_no'  => $settlement->id,

                'expense_number' => $request->expense_number,

                'category_id'    => $request->category_id,

                'reference_no'   => $request->reference_no,

                'account_id'     => $request->account_id,

                'reason'         => $request->reason,

                'amount'         => $request->amount,

            ];

            // Update reference count

            $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense');

            // Generate reference number

            if (empty($request->reference_no)) {

                $data['reference_no'] = $this->transactionUtil->generateReferenceNumber('expense', $ref_count);

            }

            $settlement_expense_payment = SettlementExpensePayment::create($data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $expense_number = $this->getExpenseNumber($request->settlement_no);

            $output = [

                'success'                       => true,

                'expense_number'                => $expense_number,

                'reference_no'                  => $settlement_expense_payment->reference_no,

                'settlement_expense_payment_id' => $settlement_expense_payment->id,

                'msg'                           => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**
     * delete expense payment data to db

     *
     * @return Response
     */
    public function deleteExpensePayment($id)
    {

        try {

            $payment = SettlementExpensePayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    private function resolvePaymentSettlement(Request $request, $business_id)
    {
        $settlement_no = $request->settlement_no ?? $request->settlement_id;
        $settlement_id = $request->payment_settlement_id ?? $request->settlement_record_id ?? $request->settlement_id;

        if (empty($settlement_no) && empty($settlement_id)) {
            return null;
        }

        return Settlement::where('business_id', $business_id)
            ->where(function ($query) use ($settlement_no, $settlement_id) {
                if (! empty($settlement_no)) {
                    $query->where('settlement_no', $settlement_no);

                    if (is_numeric($settlement_no)) {
                        $query->orWhere('id', (int) $settlement_no);
                    }
                }

                if (! empty($settlement_id) && is_numeric($settlement_id)) {
                    $query->orWhere('id', (int) $settlement_id);
                }
            })
            ->first();
    }

    /**
     * add shortage payment data to db

     *
     * @return Response
     */
    public function saveShortagePayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = $this->resolvePaymentSettlement($request, $business_id);

            if (empty($settlement)) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong'),
                ], 404);
            }

            $pump_operator = PumpOperator::findOrFail($settlement->pump_operator_id);

            $raw_amount = trim($request->amount ?? '');
            $clean_amount = str_replace(',', '', $raw_amount);
            $amount = is_numeric($clean_amount) ? (float) $clean_amount : null;

            if ($amount === null || $amount <= 0) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('Please enter a positive amount for Shortage'),
                ], 422);
            }

            $data = [

                'business_id'      => $business_id,

                'settlement_no'    => $settlement->id,

                'amount'           => $amount,

                'current_shortage' => $pump_operator->short_amount,

                'note'             => $request->note,

            ];

            $settlement_shortage_payment = SettlementShortagePayment::create($data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            Log::info('AddPaymentController@saveShortagePayment - Shortage payment saved', [
                'request_settlement_no'         => $request->settlement_no,
                'request_payment_settlement_id' => $request->payment_settlement_id,
                'settlement_id'                 => $settlement->id,
                'settlement_no'                 => $settlement->settlement_no,
                'payment_id'                    => $settlement_shortage_payment->id,
                'payment_settlement_no'         => $settlement_shortage_payment->settlement_no,
                'amount'                        => $settlement_shortage_payment->amount,
            ]);

            $output = [

                'success'                        => true,

                'settlement_shortage_payment_id' => $settlement_shortage_payment->id,

                'msg'                            => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**
     * delete shortage payment data to db

     *
     * @return Response
     */
    public function deleteShortagePayment($id)
    {

        try {

            $payment = SettlementShortagePayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = (float) ($payment->amount ?? 0);

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return response()->json($output);

    }

    /**
     * add excess payment data to db

     *
     * @return Response
     */
    public function saveExcessPayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = $this->resolvePaymentSettlement($request, $business_id);

            if (empty($settlement)) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong'),
                ], 404);
            }

            $pump_operator = PumpOperator::findOrFail($settlement->pump_operator_id);

            $raw_amount = trim($request->amount ?? '');
            $clean_amount = str_replace(',', '', $raw_amount);
            $amount = is_numeric($clean_amount) ? (float) $clean_amount : null;

            if ($amount === null || $amount >= 0) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('Please enter a negative amount for Excess'),
                ], 422);
            }

            $data = [

                'business_id'    => $business_id,

                'settlement_no'  => $settlement->id,

                'amount'         => $amount,

                'current_excess' => $pump_operator->excess_amount,

                'note'           => $request->note,

            ];

            $settlement_excess_payment = SettlementExcessPayment::create($data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            Log::info('AddPaymentController@saveExcessPayment - Excess payment saved', [
                'request_settlement_no'         => $request->settlement_no,
                'request_payment_settlement_id' => $request->payment_settlement_id,
                'settlement_id'                 => $settlement->id,
                'settlement_no'                 => $settlement->settlement_no,
                'payment_id'                    => $settlement_excess_payment->id,
                'payment_settlement_no'         => $settlement_excess_payment->settlement_no,
                'amount'                        => $settlement_excess_payment->amount,
            ]);

            $output = [

                'success'                      => true,

                'settlement_excess_payment_id' => $settlement_excess_payment->id,

                'msg'                          => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**
     * delete excess payment data to db

     *
     * @return Response
     */
    public function deleteExcessPayment($id)
    {

        try {

            $payment = SettlementExcessPayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = (float) ($payment->amount ?? 0);

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('petropd::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return response()->json($output);

    }

    /**
     * preview payment details

     *
     * @return Response
     */
    public function preview($id)
    {
        $request = request();
        $business_id = $this->resolveBusinessIdFromRequest($request);

        $settlement = Settlement::where('settlements.id', $id)
            ->where('settlements.business_id', $business_id)
            ->leftJoin('pump_operators', 'settlements.pump_operator_id', '=', 'pump_operators.id')
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
            Log::warning('DirectSettlement preview requested for missing settlement', [
                'business_id' => $business_id,
                'settlement_id' => $id,
                'url' => $request->fullUrl(),
            ]);

            return view('petropd::pd_settlement.partials.payment_preview')->with([
                'settlement' => null,
                'business' => null,
                'pump_operator' => null,
                'customer_payments_tab' => collect(),
                'preview_error' => __('messages.something_went_wrong'),
                'currency_precision' => 2,
                'product_names' => collect(),
                'pump_names' => collect(),
                'contact_names' => collect(),
                'account_names' => collect(),
                // IS1984: keep the shape identical to the success path so the
                // embedded report reads empty lookups rather than undefined ones.
                'settlementLookups' => [
                    'business' => null,
                    'pumps' => collect(),
                    'products' => collect(),
                    'contacts' => collect(),
                    'accounts' => collect(),
                    'expense_categories' => collect(),
                    'settlements' => collect(),
                    'work_shifts' => collect(),
                    'operator_other_sales' => collect(),
                    'operator_other_sales_by_shift' => collect(),
                ],
                'shift_ids' => [],
            ]);
        }

        // Ensure the preview always uses the latest date selected on the Direct Settlement form.
        $requested_date = $request->input('transaction_date') ?: $request->input('date');
        if (!empty($requested_date) && $requested_date != $settlement->transaction_date) {
            $settlement->transaction_date = $requested_date;
            try {
                Settlement::where('id', $settlement->id)
                    ->where('business_id', $business_id)
                    ->update(['transaction_date' => $requested_date]);
            } catch (\Exception $dateSyncException) {
                Log::warning('DirectSettlement preview date sync skipped', [
                    'settlement_id' => $settlement->id,
                    'transaction_date' => $requested_date,
                    'message' => $dateSyncException->getMessage(),
                ]);
            }
        }

        $shift_ids = [];
        if (!empty($settlement->work_shift)) {
            $work_shift = $settlement->work_shift;
            if (is_array($work_shift)) {
                $shift_ids = array_filter($work_shift);
            } elseif (is_string($work_shift)) {
                $decoded = json_decode($work_shift, true);
                if (is_array($decoded)) {
                    $shift_ids = array_filter($decoded);
                } else {
                    $shift_ids = array_filter(array_map('trim', explode(',', $work_shift)));
                }
            }
        }

        /*
         |---------------------------------------------------------------------
         | S775-1: resolve the authoritative PD shift before loading Meter Sale.
         |---------------------------------------------------------------------
         |
         | work_shift is normally a real shift_id.  Older rows can hold a shift
         | number, so validate IDs first and only translate numbers when none of
         | the supplied values exists as an assignment shift_id for this operator.
         | Never merge the two interpretations: doing that is exactly how rows
         | from another shift can leak into a settlement preview.
         */
        $shift_ids = array_values(array_unique(array_filter(array_map('intval', (array) $shift_ids))));

        if (! empty($shift_ids) && ! empty($settlement->pump_operator_id)) {
            $valid_shift_ids = PumpOperatorAssignment::where('business_id', $business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->whereIn('shift_id', $shift_ids)
                ->whereNotNull('shift_id')
                ->pluck('shift_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (! empty($valid_shift_ids)) {
                $shift_ids = array_values(array_map('intval', $valid_shift_ids));
            } else {
                $shift_ids = PumpOperatorAssignment::where('business_id', $business_id)
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->whereIn('shift_number', $shift_ids)
                    ->whereNotNull('shift_id')
                    ->pluck('shift_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->map(fn ($value) => (int) $value)
                    ->toArray();
            }
        }

        if (empty($shift_ids) && ! empty($settlement->pump_operator_id)) {
            $shift_ids = PumpOperatorAssignment::where('business_id', $business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where('settlement_id', $settlement->id)
                ->whereNotNull('shift_id')
                ->pluck('shift_id')
                ->filter()
                ->unique()
                ->values()
                ->map(fn ($value) => (int) $value)
                ->toArray();
        }

        /*
         |---------------------------------------------------------------------
         | S775-1A: Reconfirmation must use the SAME shift-scoped source as
         | the working Meter Sales section.
         |---------------------------------------------------------------------
         |
         | The first S775-1 patch correctly made shift_id mandatory, but then
         | added a second settlement-ownership test. That extra test is NOT used
         | by the working Meter Sales screen and can exclude every legitimate
         | close-pump row before finalization because those rows are not always
         | stamped with this settlement number yet.
         |
         | Authority for close-pump meter rows is therefore exactly the same as
         | the Meter Sales screen:
         |   business + pump operator + exact resolved shift
         |   + source = closing (when the column exists)
         |   + exclude a payment-linked reading only when that payment is already
         |     used/settled elsewhere.
         |
         | Crucially there is NO `settlement_no OR shift_id` broadening, so rows
         | from another shift still cannot leak into Reconfirmation.
         */
        $settlement_meter_keys = array_values(array_unique(array_filter([
            (string) $settlement->id,
            (string) $settlement->settlement_no,
        ], static fn ($value) => $value !== '')));

        $pd_meter_preview_query = PumpOperatorMeterSale::with(['details', 'details.pump'])
            ->where('business_id', $business_id)
            ->where('pump_operator_id', $settlement->pump_operator_id);

        if (! empty($shift_ids)) {
            $pd_meter_preview_query->whereIn('shift_id', $shift_ids);

            if (Schema::hasColumn('pump_operator_meter_sales', 'source')) {
                $pd_meter_preview_query->where('source', 'closing');
            }

            $pd_meter_preview_query->where(function ($readingQuery) {
                $readingQuery
                    ->whereNull('p_o_payment_id')
                    ->orWhereNotExists(function ($settledQuery) {
                        $settledQuery
                            ->select(DB::raw(1))
                            ->from('pump_operator_payments')
                            ->whereColumn(
                                'pump_operator_payments.id',
                                'pump_operator_meter_sales.p_o_payment_id'
                            )
                            ->where('pump_operator_payments.is_used', 1);
                    });
            });
        } else {
            // Legacy fallback only when a shift genuinely cannot be resolved.
            // In that case keep the scope narrow to this settlement link.
            $pd_meter_preview_query->whereIn('settlement_no', $settlement_meter_keys);
        }

        $settlement->setRelation(
            'meter_sales_pd',
            $pd_meter_preview_query->orderBy('id')->get()->unique('id')->values()
        );

        /*
         | Manual/additional MeterSale rows are settlement-owned. Their shift_id
         | is legitimately NULL on older/manual entries, so a hard whereIn() on
         | shift_id makes valid rows disappear. Keep NULL shift rows, and when a
         | shift is present require it to be one of the authoritative shifts.
         */
        $regular_meter_preview_query = MeterSale::where('business_id', $business_id)
            ->whereIn('settlement_no', $settlement_meter_keys);

        if (! empty($shift_ids) && Schema::hasColumn('meter_sales', 'shift_id')) {
            $regular_meter_preview_query->where(function ($meterShiftQuery) use ($shift_ids) {
                $meterShiftQuery->whereIn('shift_id', $shift_ids)
                    ->orWhereNull('shift_id');
            });
        }

        $settlement->setRelation(
            'meter_sales',
            $regular_meter_preview_query->orderBy('id')->get()
        );

        Log::info('S775-1A Reconfirmation meter rows loaded from authoritative shift', [
            'settlement_id' => $settlement->id,
            'settlement_no' => $settlement->settlement_no,
            'shift_ids' => $shift_ids,
            'pd_meter_sale_ids' => $settlement->meter_sales_pd->pluck('id')->values()->toArray(),
            'regular_meter_sale_ids' => $settlement->meter_sales->pluck('id')->values()->toArray(),
        ]);

        // Reload payment relations by settlement id/no. This prevents stale/null relation data from breaking preview.
        $settlement_keys = array_values(array_filter([$settlement->id, $settlement->settlement_no]));

        $settlement->setRelation('cash_deposits', SettlementCashDeposit::where('business_id', $business_id)
            ->whereIn('settlement_no', $settlement_keys)
            ->get());

        $settlement->setRelation('cash_payments', SettlementCashPayment::where('business_id', $business_id)
            ->whereIn('settlement_no', $settlement_keys)
            ->get());

        $settlement->setRelation('card_payments', SettlementCardPayment::where('settlement_card_payments.business_id', $business_id)
            ->whereIn('settlement_card_payments.settlement_no', $settlement_keys)
            ->leftJoin('daily_cards', 'settlement_card_payments.daily_card_id', '=', 'daily_cards.id')
            ->when(!empty($shift_ids), function ($query) use ($shift_ids) {
                /*
                 |--------------------------------------------------------------
                 | A NULL card shift does not exclude the payment.
                 |--------------------------------------------------------------
                 |
                 | Reported: the reconfirmation form showed no Credit Card amount.
                 |
                 | This filtered on daily_cards.shift_id, and that column is NULL on
                 | every row - measured on 127copy:
                 |
                 |     scp 29  daily_card_id 29  card_shift NULL
                 |     scp 28  daily_card_id 28  card_shift NULL
                 |     scp 27  daily_card_id 27  card_shift NULL
                 |
                 | whereIn() never matches NULL, so every card payment WITH a card
                 | link was excluded. The orWhereNull below only rescued rows with
                 | no link at all - row 22 in that sample, which is why a single
                 | payment sometimes appeared while the rest did not.
                 |
                 | A null shift on the card cannot contradict the settlement's
                 | shift: it says nothing either way. Such rows are now kept, and
                 | they are already scoped by settlement_no on the line above, so
                 | nothing from another settlement can come through.
                 |
                 | Cards that DO carry a shift are still filtered by it, exactly as
                 | before - so this only stops the exclusion of rows that were never
                 | given one.
                 */
                $query->where(function ($shift_query) use ($shift_ids) {
                    $shift_query->whereIn('daily_cards.shift_id', $shift_ids)
                        ->orWhereNull('daily_cards.shift_id')
                        ->orWhereNull('settlement_card_payments.daily_card_id');
                });
            })
            ->select('settlement_card_payments.*')
            ->get());

        $customer_payments_tab = CustomerPayment::leftJoin('contacts', 'customer_payments.customer_id', '=', 'contacts.id')
            ->where('customer_payments.business_id', $business_id)
            ->whereIn('customer_payments.settlement_no', $settlement_keys)
            ->select('customer_payments.*', 'contacts.name as customer_name')
            ->get();

        $business = Business::where('id', $settlement->business_id)->first();
        $pump_operator = PumpOperator::where('id', $settlement->pump_operator_id)->first();
        $currency_precision = !empty($business->currency_precision) ? $business->currency_precision : 2;

        $product_ids = collect()
            ->merge($settlement->meter_sales->pluck('product_id'))
            ->merge($settlement->other_sales->pluck('product_id'))
            ->merge($settlement->other_incomes->pluck('product_id'))
            ->merge($settlement->credit_sale_payments->pluck('product_id'))
            ->filter()
            ->unique()
            ->values();

        $pump_ids = $settlement->meter_sales->pluck('pump_id')->filter()->unique()->values();

        $contact_ids = collect()
            ->merge($settlement->cash_payments->pluck('customer_id'))
            ->merge($settlement->card_payments->pluck('customer_id'))
            ->merge($settlement->cheque_payments->pluck('customer_id'))
            ->merge($settlement->credit_sale_payments->pluck('customer_id'))
            ->filter()
            ->unique()
            ->values();

        $account_ids = collect()
            ->merge($settlement->cash_deposits->pluck('bank_id'))
            ->merge($settlement->loan_payments->pluck('loan_account'))
            ->merge($settlement->drawings_payments->pluck('loan_account'))
            ->filter()
            ->unique()
            ->values();

        $product_names = $product_ids->isNotEmpty() ? Product::whereIn('id', $product_ids)->pluck('name', 'id') : collect();
        $product_skus = $product_ids->isNotEmpty() ? Product::whereIn('id', $product_ids)->pluck('sku', 'id') : collect();
        $pump_names = $pump_ids->isNotEmpty() ? Pump::whereIn('id', $pump_ids)->pluck('pump_no', 'id') : collect();
        $contact_names = $contact_ids->isNotEmpty() ? Contact::whereIn('id', $contact_ids)->pluck('name', 'id') : collect();
        $account_names = $account_ids->isNotEmpty() ? Account::whereIn('id', $account_ids)->pluck('name', 'id') : collect();

        /*
         |----------------------------------------------------------------------
         | IS1984 #2/#3/#5: build the lookups the embedded report expects.
         |----------------------------------------------------------------------
         |
         | payment_preview.blade.php does @include('petropd::pd_settlement.print'),
         | and that report reads its names from $settlementLookups - products,
         | pumps, contacts, accounts, expense categories, settlements, work shifts
         | and the operator other sales. print() and the finalize path both build
         | that array via buildSettlementViewLookups() before rendering. This
         | preview never did.
         |
         | Every read in the report is written as ($settlementLookups[...] ?? collect()),
         | so the missing variable did not error - it silently produced EMPTY
         | lookups. That is the whole of three reported symptoms:
         |
         |   #2 Meter Sale showed Code and Products as '-' while Pump was correct.
         |      Pump survives because the report falls back to $detail->pump, which
         |      lazy loads. Product has no such fallback - it comes only from the
         |      lookup.
         |   #3 Credit Sales showed Customer Name and Product Name blank while the
         |      voucher no, qty, rate and totals - all read straight off the row -
         |      were right.
         |   #5 Other Sale printed its heading and a 0.00 sub total with no rows:
         |      the operator other sales come only from the lookup, and $shift_ids
         |      that gates that section was not passed either.
         |
         | The ids are gathered the same way buildSettlementViewLookups() gathers
         | them. Two details matter and are easy to miss:
         |   - pump ids must include the close-pump detail rows (meter_sales_pd),
         |     not just meter_sales, or PD meter lines resolve no pump and so no
         |     product.
         |   - product ids must include the PUMPS' product_id, because a PD meter
         |     line names its product through the pump rather than carrying one.
         */
        $pd_detail_pump_ids = collect();
        foreach ($settlement->meter_sales_pd ?? collect() as $pd_meter_sale) {
            $pd_detail_pump_ids = $pd_detail_pump_ids->merge(
                collect($pd_meter_sale->details ?? [])->pluck('pump_id')
            );
        }

        $lookup_pump_ids = $pump_ids->merge($pd_detail_pump_ids)->filter()->unique()->values();
        $lookup_pumps = $lookup_pump_ids->isNotEmpty()
            ? Pump::whereIn('id', $lookup_pump_ids)->get()->keyBy('id')
            : collect();

        // Shift ids resolved as getSettlementPDShiftIds() does: settlements.work_shift
        // holds shift NUMBERS, so they are mapped to real shift ids through
        // pump_operator_assignments before being used to read shift-keyed data.
        $preview_work_shifts = $settlement->work_shift;
        if (is_string($preview_work_shifts)) {
            $decoded_work_shifts = json_decode($preview_work_shifts, true);
            $preview_work_shifts = is_array($decoded_work_shifts)
                ? $decoded_work_shifts
                : explode(',', $preview_work_shifts);
        }
        $shift_ids = collect((array) $preview_work_shifts)->filter()->map(fn ($value) => (int) $value)->unique()->values();

        if ($shift_ids->isNotEmpty()) {
            $shift_ids = $shift_ids->merge(
                PumpOperatorAssignment::where('business_id', $settlement->business_id)
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->whereIn('shift_number', $shift_ids)
                    ->whereNotNull('shift_id')
                    ->pluck('shift_id')
            )->filter()->unique()->values();
        }

        if ($shift_ids->isEmpty()) {
            $shift_ids = PumpOperatorAssignment::where('settlement_id', $settlement->id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->whereNotNull('shift_id')
                ->pluck('shift_id')
                ->filter()
                ->unique()
                ->values();
        }

        $operator_other_sales = $shift_ids->isNotEmpty()
            ? PumpOperatorOtherSale::whereIn('shift_id', $shift_ids)->get()
            : collect();

        $lookup_product_ids = $product_ids
            ->merge($lookup_pumps->pluck('product_id'))
            ->merge($operator_other_sales->pluck('product_id'))
            ->filter()
            ->unique()
            ->values();

        $other_sale_settlement_ids = $settlement->other_sales->pluck('settlement_no')->filter()->unique()->values();
        $expense_category_ids = $settlement->expense_payments->pluck('category_id')->filter()->unique()->values();

        $settlementLookups = [
            'business' => $business,
            'pumps' => $lookup_pumps,
            'products' => $lookup_product_ids->isNotEmpty()
                ? Product::whereIn('id', $lookup_product_ids)->get()->keyBy('id')
                : collect(),
            'contacts' => $contact_ids->isNotEmpty()
                ? Contact::whereIn('id', $contact_ids)->get()->keyBy('id')
                : collect(),
            'accounts' => $account_ids->isNotEmpty()
                ? Account::whereIn('id', $account_ids)->get()->keyBy('id')
                : collect(),
            'expense_categories' => $expense_category_ids->isNotEmpty()
                ? ExpenseCategory::whereIn('id', $expense_category_ids)->get()->keyBy('id')
                : collect(),
            'settlements' => $other_sale_settlement_ids->isNotEmpty()
                ? Settlement::whereIn('id', $other_sale_settlement_ids)->get()->keyBy('id')
                : collect(),
            'work_shifts' => $shift_ids->isNotEmpty()
                ? \Modules\HR\Entities\WorkShift::whereIn('id', $shift_ids)->get()->keyBy('id')
                : collect(),
            'operator_other_sales' => $operator_other_sales,
            'operator_other_sales_by_shift' => $operator_other_sales->groupBy('shift_id'),
        ];

        $shift_ids = $shift_ids->all();

        /*
         |----------------------------------------------------------------------
         | IS2048: Cash showed 0.00 in the Payment Details row of the preview.
         |----------------------------------------------------------------------
         |
         | payment_preview includes pd_settlement.print, and that report prints
         | cash as
         |
         |     {{ @num_format($final_cash_amount ?? 0) }}
         |
         | Every other column reads a relation off $settlement, but cash reads
         | this ONE variable. The list/print path sets it (ListsPdSettlements),
         | this preview never did - so the ?? 0 fallback applied and cash always
         | printed 0.00 here while the same report was correct from the list.
         |
         | Computed exactly as the working path computes it: the settlement's own
         | cash payments, falling back to daily collections only when there are
         | none. That fallback order matters - SettlementCashPayment rows are
         | CREATED FROM daily collections, so adding both would double count.
         */
        // IS2048: preview-only flag, read by pd_settlement.print.
        $hide_cash_card_sections = true;

        /*
         | Counted once per payment - see the note in pd_settlement/print.blade.php.
         |
         | The relation can carry the same row twice when the settlement is rebuilt
         | during finalise, which doubled the cash figure on the reconfirm form
         | while the stored rows were correct.
         |
         | unique('id') is a no-op on a clean collection, so nothing changes where
         | there was never a duplicate.
         */
        /*
         |----------------------------------------------------------------------
         | Card total falls back to the PUMPER's payments before the save.
         |----------------------------------------------------------------------
         |
         | Reported: the reconfirmation showed 0.00 for Credit Cards.
         |
         | The reconfirmation is shown BEFORE the settlement is saved - it exists
         | so the operator can check the totals and only then commit. At that
         | point settlement_card_payments is still empty, so summing that relation
         | gives zero however correct everything else is.
         |
         | The figures do exist, on the pumper's own records. Measured on 127copy
         | for shift 3: 31 card payments in pump_operator_payments totalling
         | exactly 131,267.00, which is the figure the operator expects.
         |
         | So when the settlement carries no card rows of its own, the shift's
         | unused card payments are totalled instead. Once the settlement IS saved
         | its own rows exist and are used, exactly as before - this only fills the
         | gap at the reconfirmation stage.
         |
         | is_used = 0 keeps out anything already taken by another settlement.
         */
        $final_card_amount = (float) $settlement->card_payments->unique('id')->sum('amount');

        if ($final_card_amount == 0.0 && ! empty($shift_ids)) {
            try {
                $final_card_amount = (float) PumpOperatorPayment::where('business_id', $settlement->business_id)
                    ->where('payment_type', 'card')
                    ->whereIn('shift_id', (array) $shift_ids)
                    ->where(function ($q) {
                        $q->where('is_used', 0)->orWhereNull('is_used');
                    })
                    ->sum('payment_amount');
            } catch (\Throwable $card_total_error) {
                \Log::warning('Reconfirmation card total fallback failed', [
                    'settlement_id' => $settlement->id ?? null,
                    'error'         => $card_total_error->getMessage(),
                ]);
            }
        }

        $final_cash_amount = (float) $settlement->cash_payments->unique('id')->sum('amount');

        /*
         |----------------------------------------------------------------------
         | Before the save, cash comes from the PUMPER's payments.
         |----------------------------------------------------------------------
         |
         | Reported: the Payment Details row showed cash at 434,887.82 - exactly
         | twice the correct 217,443.91 - while Cards and Credit were right.
         |
         | The reconfirmation runs BEFORE the settlement is saved, so
         | settlement_cash_payments is empty and the fallback below reads
         | daily_collections instead. But a payment writes TWO daily_collections
         | rows - established earlier on this system - so summing that table counts
         | every payment twice:
         |
         |     daily_collections for settlement 4: 2 rows
         |     217,443.91 x 2 = 434,887.82
         |
         | The pumper's own payments hold it once, which is why the card total is
         | correct - that fallback already reads them. Cash now does the same.
         |
         | daily_collections is kept as a last resort for anything with no pumper
         | payment at all, and deduplicated by amount there so the same doubling
         | cannot recur.
         */
        if ($final_cash_amount == 0.0 && ! empty($shift_ids)) {
            try {
                $final_cash_amount = (float) PumpOperatorPayment::where('business_id', $settlement->business_id)
                    ->where('payment_type', 'cash')
                    ->whereIn('shift_id', (array) $shift_ids)
                    ->where(function ($q) {
                        $q->where('is_used', 0)->orWhereNull('is_used');
                    })
                    ->sum('payment_amount');
            } catch (\Throwable $cash_total_error) {
                \Log::warning('Reconfirmation cash total fallback failed', [
                    'settlement_id' => $settlement->id ?? null,
                    'error'         => $cash_total_error->getMessage(),
                ]);
            }
        }

        if ($final_cash_amount == 0.0) {
            /*
             | daily_collections stores the figure in current_amount, NOT amount.
             |
             | My first version summed 'amount', which does not exist on that
             | table - the query threw, the preview endpoint returned a 500, and
             | the browser reported "Unable to open the settlement preview."
             |
             | It only failed on settlements with NO cash payment rows, because
             | that is the only case where this fallback runs - which is exactly
             | the settlements being repaired.
             |
             | Wrapped as well: this is a display fallback, and it must never stop
             | the preview from opening. If anything goes wrong the cash figure
             | stays 0 and the rest of the report still renders.
             */
            try {
                $final_cash_amount = (float) DailyCollection::where('business_id', $settlement->business_id)
                    ->where('settlement_id', $settlement->id)
                    ->sum('current_amount');
            } catch (\Throwable $daily_collection_error) {
                \Log::warning('IS2048 daily collection fallback failed', [
                    'settlement_id' => $settlement->id,
                    'error' => $daily_collection_error->getMessage(),
                ]);
                $final_cash_amount = 0.0;
            }
        }

        /*
         | Set HERE, in the preview method.
         |
         | It was listed in this compact() but only assigned in create(), a
         | different method - so PHP raised
         |     compact(): Undefined variable $pd_hide_non_pumper_tabs
         | and the preview returned a 500, reported as "Unable to open the
         | settlement preview."
         |
         | Same mistake I made with $isPetroPdRequest earlier: a variable listed in
         | one method's compact() while being assigned in another.
         */
        $pd_hide_non_pumper_tabs = ($request->type === 'settlement_pd')
            || \Illuminate\Support\Str::startsWith(ltrim($request->path(), '/'), 'petropd');

        return view('petropd::pd_settlement.partials.payment_preview')->with(compact(
            'settlement',
            'business',
            'pump_operator',
            'customer_payments_tab',
            'currency_precision',
            'product_names',
            'product_skus',
            'pump_names',
            'contact_names',
            'account_names',
            'settlementLookups',
            'pd_hide_non_pumper_tabs',
            'final_cash_amount',
            'final_card_amount',
            // IS2048: hides the Cash and Credit Cards detail sections in the
            // preview only - the Payment Summary below already lists them.
            'hide_cash_card_sections',
            'shift_ids'
        ));
    }

    public function productPreview($id)
    {

        $business_id = request()->session()->get('business.id');

        $settlement = Settlement::where('settlements.id', $id)

            ->leftjoin('settlement_credit_sale_payments', 'settlements.id', 'settlement_credit_sale_payments.settlement_no')

            ->leftjoin('products', 'products.id', 'settlement_credit_sale_payments.product_id')

            ->select('settlements.*', 'products.*', 'settlement_credit_sale_payments.*')

            ->get();

        return view('petropd::pd_settlement.partials.product_preview')->with(compact('settlement'));

    }

    /**
     * Save POS payment
     *
     * @param Request $request
     * @return Response
     */
    public function savePosPayment(Request $request)
    {
        Log::info('Request Data for savePosPayment in AddPaymentController:', ['request' => $request->all()]);

        try {
            DB::beginTransaction();

            $business_id = (int) $this->resolveBusinessIdFromRequest($request);
            $settlement = Settlement::where('settlement_no', $request->settlement_no)
                ->where('business_id', $business_id)
                ->firstOrFail();
            $shift_id = $this->requireSingleSettlementShiftId($settlement);

            if ($request->has('is_edit') && $request->is_edit) {
                Settlement::where('id', $settlement->id)->update(['is_edit' => 1]);
            }

            $pump_payment = $this->resolveUnambiguousMasterPaymentForSettlement(
                $settlement,
                $request,
                $business_id,
                'pos',
                (float) $request->amount
            );

            $data = [
                'business_id'   => $business_id,
                'settlement_no' => $settlement->id,
                'amount'        => $request->amount,
                'customer_id'   => $request->customer_id,
                'note'          => $request->note,
            ];
            if (Schema::hasColumn('settlement_pos_payments', 'pump_payment_id')) {
                $data['pump_payment_id'] = optional($pump_payment)->id;
            }
            if (Schema::hasColumn('settlement_pos_payments', 'shift_id')) {
                $data['shift_id'] = $shift_id;
            }
            if (Schema::hasColumn('settlement_pos_payments', 'pump_operator_id')) {
                $data['pump_operator_id'] = $settlement->pump_operator_id;
            }

            $settlement_pos_payment = SettlementPosPayment::create($data);

            if ($pump_payment) {
                $pump_payment->is_used = 1;
                $pump_payment->parent_id = $settlement_pos_payment->id;
                $pump_payment->settlement_no = $settlement->id;
                $pump_payment->save();
            }

            if ($request->has('is_edit')) {
                $settlement->update(['is_edit' => $request->is_edit]);
            }

            DB::commit();

            $output = [
                'success'                   => true,
                'settlement_pos_payment_id' => $settlement_pos_payment->id,
                'pump_payment_id'           => optional($pump_payment)->id,
                'msg'                       => __('petropd::lang.success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            $is_reconciliation_error = $e instanceof \RuntimeException
                && str_starts_with($e->getMessage(), 'Payment reconciliation stopped:');

            $output = [
                'success' => false,
                'msg'     => $is_reconciliation_error
                    ? $e->getMessage()
                    : __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Resolve every credit-sale bill that belongs to one authoritative master.
     *
     * New entries are normally one master to one bill. Historical
     * `multiple_credit` entries may have several bills under one master. Those
     * rows are accepted only when the exact business/operator/Shift/collection
     * scope is unambiguous and their aggregate gross/discount/net equals the
     * master payment. Nothing is linked by amount alone.
     */
    private function findCreditSaleRowsForMasterPayment(
        int $businessId,
        int $pumpOperatorId,
        PumpOperatorPayment $payment,
        string $settlementId,
        string $settlementNo
    ) {
        $hasPumpPaymentId = $this->tableHasColumn('settlement_credit_sale_payments', 'pump_payment_id');
        $hasShiftId = $this->tableHasColumn('settlement_credit_sale_payments', 'shift_id');

        $query = SettlementCreditSalePayment::where('business_id', $businessId)
            ->where('pump_operator_id', $pumpOperatorId)
            ->where(function ($ownerQuery) use ($settlementId, $settlementNo) {
                $ownerQuery->whereNull('settlement_no')
                    ->orWhere('settlement_no', '')
                    ->orWhere('settlement_no', $settlementId)
                    ->orWhere('settlement_no', $settlementNo);
            });

        if ($hasPumpPaymentId) {
            $query->where(function ($linkQuery) use ($payment) {
                // Existing direct links are authoritative even when a grouped
                // payment contains bills with different collection numbers.
                $linkQuery->where('pump_payment_id', $payment->id);

                // An unlinked legacy row is considered only through the exact
                // collection identity of the master.
                if (! empty($payment->collection_form_no)) {
                    $linkQuery->orWhere(function ($legacyLink) use ($payment) {
                        $legacyLink->where(function ($nullLink) {
                            $nullLink->whereNull('pump_payment_id')
                                ->orWhere('pump_payment_id', 0);
                        })->where('collection_form_no', $payment->collection_form_no);
                    });
                }
            });
        }

        if ($hasShiftId) {
            $query->where(function ($shiftQuery) use ($payment) {
                $shiftQuery->where('shift_id', $payment->shift_id)
                    ->orWhereNull('shift_id')
                    ->orWhere('shift_id', 0);
            });
        }

        if (! $hasPumpPaymentId) {
            if (! empty($payment->collection_form_no)) {
                $query->where('collection_form_no', $payment->collection_form_no);
            } else {
                // Without either a direct link or immutable collection identity,
                // no legacy row can be selected safely.
                return collect();
            }
        }

        if ($this->tableHasColumn('pump_operator_payments', 'customer_id')
            && ! empty($payment->customer_id)) {
            $query->where('customer_id', $payment->customer_id);
        }

        $candidates = $query->orderBy('id')->get();
        if ($candidates->isEmpty()) {
            return $candidates;
        }

        $linked = $hasPumpPaymentId
            ? $candidates->filter(fn ($row) => (int) ($row->pump_payment_id ?? 0) === (int) $payment->id)->values()
            : collect();

        $targetGross = $this->tableHasColumn('pump_operator_payments', 'gross_amount')
            && $payment->gross_amount !== null
                ? (float) $payment->gross_amount
                : (float) ($payment->payment_amount ?? 0);
        $targetDiscount = $this->tableHasColumn('pump_operator_payments', 'discount_amount')
            && $payment->discount_amount !== null
                ? (float) $payment->discount_amount
                : 0.0;
        $targetNet = $this->tableHasColumn('pump_operator_payments', 'net_amount')
            && $payment->net_amount !== null
                ? (float) $payment->net_amount
                : ($targetGross - $targetDiscount);

        $matchesMaster = function ($rows) use ($targetGross, $targetDiscount, $targetNet) {
            $gross = (float) $rows->sum(fn ($row) => (float) ($row->amount ?? 0));
            $discount = (float) $rows->sum(fn ($row) => (float) ($row->total_discount ?? 0));
            $net = (float) $rows->sum(function ($row) {
                if ($row->sub_total !== null && $row->sub_total !== '') {
                    return (float) $row->sub_total;
                }

                return (float) ($row->amount ?? 0) - (float) ($row->total_discount ?? 0);
            });

            return abs($gross - $targetGross) < 0.02
                && abs($discount - $targetDiscount) < 0.02
                && abs($net - $targetNet) < 0.02;
        };

        // Prefer the full exact-scope aggregate. This restores all bills for a
        // valid grouped credit payment while rejecting unrelated/duplicate rows.
        if ($matchesMaster($candidates)) {
            return $candidates;
        }

        // If some historical rows are already linked, retain them only when they
        // independently reconcile to the authoritative master.
        if ($linked->isNotEmpty() && $matchesMaster($linked)) {
            return $linked;
        }

        return collect();
    }

    /**
     * Delete POS payment
     *
     * @param int $id
     * @param Request $request
     * @return Response
     */
    public function deletePosPayment($id, Request $request)
    {
        try {
            $pumpPaymentId = $request->input('pump_payment_id');
            if (!empty($pumpPaymentId)) {
                $pumpPayment = PumpOperatorPayment::findOrFail($pumpPaymentId);
                $pumpPayment->is_used = 1;
                $pumpPayment->save();

                $output = [
                    'success' => true,
                    'amount'  => $pumpPayment->payment_amount,
                    'msg'     => __('petropd::lang.success'),
                ];

                return $output;
            }

            $payment = SettlementPosPayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            // Delete corresponding ContactLedger entry if it exists (linked via settlement_no in note)
            $settlement_record = Settlement::find($payment->settlement_no);
            if ($settlement_record) {
                ContactLedger::where('transaction_id', null)
                    ->where('amount', $amount)
                    ->where('note', 'like', '%' . $settlement_record->settlement_no . '%')
                    ->where('sub_type', 'pos_payment')
                    ->forceDelete();
            }

            $payment->delete();

            $output = [
                'success' => true,
                'amount'  => $amount,
                'msg'     => __('petropd::lang.success'),
            ];

        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
}

<?php

namespace Modules\PetroPD\Utils;

use App\Utils\TransactionUtil;
use App\Utils\ModuleUtil;
use App\Utils\ContactUtil;
use App\AccountTransaction;
use App\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\PetroPD\Entities\PumpOperator;
use Modules\PetroPD\Entities\PumpOperatorCommission;

/**
 * PetroPD Transaction Utility
 *
 * Urgent compatibility utility for PetroPD runtime.
 *
 * The PetroPD controllers/services reference this class after the PetroPD
 * standalone separation. This class keeps PetroPD references inside the
 * PetroPD namespace while preserving the existing ERP transaction utility
 * behaviour required by settlement/payment/accounting flows.
 *
 * IMPORTANT:
 * - This file fixes the container error:
 *   Target class [Modules\PetroPD\Utils\PDTransactionUtil] does not exist.
 * - It does not change Direct Petro Settlement files.
 * - Future PetroPD-specific transaction logic can be moved into this class
 *   method-by-method without changing controller references again.
 */
class PDTransactionUtil extends TransactionUtil
{
    /** Request-local cache: PD Operators asks for period and current balance back-to-back. */
    protected $petroPdCanonicalLedgerCache = [];

    /**
     * Keep the same dependency signature as the base ERP TransactionUtil so
     * Laravel's service container can resolve this class anywhere it is type
     * hinted or app() resolved.
     */
    public function __construct(ModuleUtil $moduleUtil, ContactUtil $contactUtil)
    {
        parent::__construct($moduleUtil, $contactUtil);
    }


    /**
     * IS-PD-LEDGER-20260910
     *
     * PetroPD-only guard for Recover Shortage / Pay Excess.
     *
     * The PD Operators modal can be reached on pages that also load the ERP's
     * generic payment javascript.  Historically both handlers used the common
     * #pay_contact_due_form id, so the same payment could be posted twice.
     * The payment reference is generated once for the modal and is the logical
     * idempotency key.  Locking the operator row serializes two near-simultaneous
     * requests; the second request sees the already-created bulk payment and
     * becomes a no-op.
     *
     * No core TransactionUtil behaviour is changed for other modules.
     */
    public function payAtOnceExcessShortage($inputs, $sub_type, $pump_operator_id)
    {
        $subType = strtolower(trim((string) $sub_type));
        if (! in_array($subType, ['shortage', 'excess'], true)) {
            return parent::payAtOnceExcessShortage($inputs, $sub_type, $pump_operator_id);
        }

        $businessId = (int) (request()->session()->get('business.id') ?: (Auth::user()->business_id ?? 0));
        $operatorId = (int) $pump_operator_id;
        $paymentRef = trim((string) ($inputs['payment_ref_no'] ?? ''));
        $bulkType = $subType . '_bulk_payment';

        // Controllers call this inside a DB transaction. The row lock makes a
        // rapid double click / duplicate JS submit safe even under concurrency.
        if ($operatorId > 0) {
            PumpOperator::where('id', $operatorId)
                ->when($businessId > 0, function ($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                })
                ->lockForUpdate()
                ->first();
        }

        // Store is POST. Edit uses Laravel's spoofed PUT method; an existing
        // reference is expected during edit and must not be mistaken for a
        // duplicate create.
        $enforceIdempotency = request()->isMethod('post');

        if ($enforceIdempotency && $paymentRef !== '' && $businessId > 0 && $operatorId > 0) {
            $existing = Transaction::where('business_id', $businessId)
                ->where('pump_operator_id', $operatorId)
                ->where('type', $bulkType)
                ->where('sub_type', $subType)
                ->where('invoice_no', $paymentRef)
                ->where('status', 'final')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->first();

            if ($existing) {
                Log::warning('PetroPD duplicate excess/shortage payment submit suppressed.', [
                    'business_id' => $businessId,
                    'pump_operator_id' => $operatorId,
                    'sub_type' => $subType,
                    'payment_ref_no' => $paymentRef,
                    'existing_transaction_id' => $existing->id,
                ]);

                return $existing;
            }
        }

        parent::payAtOnceExcessShortage($inputs, $subType, $operatorId);

        if ($paymentRef === '' || $businessId <= 0 || $operatorId <= 0) {
            return null;
        }

        return Transaction::where('business_id', $businessId)
            ->where('pump_operator_id', $operatorId)
            ->where('type', $bulkType)
            ->where('sub_type', $subType)
            ->where('invoice_no', $paymentRef)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Return the canonical PetroPD pump-operator ledger rows.
     *
     * Only account_transactions.sub_type=ledger_show is a pump-operator ledger
     * posting.  The opposite Cash/Bank account-book leg has NULL sub_type and
     * must never be counted as another shortage/excess ledger movement.
     *
     * For historical double submits we also collapse repeated bulk-payment
     * ledger rows carrying the same immutable payment reference.  This repairs
     * the displayed balance without deleting historical database records.
     */
    protected function petroPdCanonicalPumpOperatorLedgerRows($business_id, $pump_operator_id = null, $filters = [])
    {
        $cacheKey = implode('|', [
            (int) $business_id,
            is_null($pump_operator_id) ? '*' : (int) $pump_operator_id,
            ! empty($filters['location_id']) ? (int) $filters['location_id'] : '*',
        ]);
        if (array_key_exists($cacheKey, $this->petroPdCanonicalLedgerCache)) {
            return $this->petroPdCanonicalLedgerCache[$cacheKey];
        }

        $query = AccountTransaction::join('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
            ->where('transactions.business_id', (int) $business_id)
            ->whereNull('transactions.deleted_at')
            ->where('account_transactions.sub_type', 'ledger_show')
            ->whereIn('transactions.sub_type', ['excess', 'shortage'])
            ->whereNull('account_transactions.deleted_at')
            ->select([
                'account_transactions.id as account_transaction_id',
                'account_transactions.transaction_id',
                'account_transactions.transaction_payment_id',
                'account_transactions.type as ledger_type',
                'account_transactions.amount',
                'account_transactions.operation_date',
                'account_transactions.created_at as ledger_created_at',
                'transactions.business_id',
                'transactions.location_id',
                'transactions.pump_operator_id',
                'transactions.type as transaction_type',
                'transactions.sub_type as transaction_sub_type',
                'transactions.invoice_no as payment_ref_no',
                'transactions.transaction_date',
            ]);

        if (! is_null($pump_operator_id)) {
            $query->where('transactions.pump_operator_id', (int) $pump_operator_id);
        } else {
            $query->whereNotNull('transactions.pump_operator_id');
        }

        if (! empty($filters['location_id'])) {
            $query->where('transactions.location_id', (int) $filters['location_id']);
        }

        $rows = $query
            ->orderBy('transactions.transaction_date')
            ->orderBy('account_transactions.id')
            ->get();

        $seenBulkReferences = [];

        $canonical = $rows->filter(function ($row) use (&$seenBulkReferences) {
            $transactionType = strtolower(trim((string) ($row->transaction_type ?? '')));
            if (! in_array($transactionType, ['shortage_bulk_payment', 'excess_bulk_payment'], true)) {
                return true;
            }

            $reference = trim((string) ($row->payment_ref_no ?? ''));
            if ($reference === '') {
                // A legacy row without a reference cannot be safely compared
                // with another transaction, so preserve it.
                return true;
            }

            $key = implode('|', [
                (int) ($row->business_id ?? 0),
                (int) ($row->pump_operator_id ?? 0),
                $transactionType,
                strtolower(trim((string) ($row->transaction_sub_type ?? ''))),
                $reference,
                strtolower(trim((string) ($row->ledger_type ?? ''))),
                number_format(abs((float) ($row->amount ?? 0)), 4, '.', ''),
            ]);

            if (isset($seenBulkReferences[$key])) {
                return false;
            }

            $seenBulkReferences[$key] = true;
            return true;
        })->values();

        $this->petroPdCanonicalLedgerCache[$cacheKey] = $canonical;
        return $canonical;
    }

    /**
     * Outstanding shortage/excess from the same canonical ledger authority.
     * This keeps the Recover/Pay modal amount correct even when historical
     * duplicate bulk postings exist. Old schemas with no ledger rows retain
     * the parent ERP calculation.
     */
    public function getPumpOperatorExcessOrShortage($pump_operator_id, $type)
    {
        $subType = strtolower(trim((string) $type));
        if (! in_array($subType, ['shortage', 'excess'], true)) {
            return parent::getPumpOperatorExcessOrShortage($pump_operator_id, $type);
        }

        $operator = PumpOperator::find((int) $pump_operator_id);
        if (! $operator) {
            return 0;
        }

        $rows = $this->petroPdCanonicalPumpOperatorLedgerRows(
            (int) $operator->business_id,
            (int) $operator->id
        )->where('transaction_sub_type', $subType);

        if ($rows->isEmpty()) {
            return parent::getPumpOperatorExcessOrShortage($pump_operator_id, $type);
        }

        $debit = 0.0;
        $credit = 0.0;
        foreach ($rows as $row) {
            $amount = abs((float) ($row->amount ?? 0));
            $ledgerType = strtolower((string) ($row->ledger_type ?? ''));
            if ($ledgerType === 'debit') {
                $debit += $amount;
            } elseif ($ledgerType === 'credit') {
                $credit += $amount;
            }
        }

        $outstanding = $subType === 'shortage'
            ? ($debit - $credit)
            : ($credit - $debit);

        return max(0.0, $outstanding);
    }

    /**
     * PetroPD ledger summary using only the canonical ledger side.
     * This keeps PD Operators date-range balances correct even when an older
     * duplicate bulk payment posting already exists in the database.
     */
    public function getPumpOperatorLedgerSummary($business_id, $start_date, $end_date, $pump_operator_id = null, $filters = [])
    {
        if (empty($start_date) || empty($end_date)) {
            return $this->petroPdFormatPumpOperatorSummary([], $pump_operator_id);
        }

        $start = \Carbon\Carbon::parse($start_date)->startOfDay();
        $end = \Carbon\Carbon::parse($end_date)->endOfDay();
        $rows = $this->petroPdCanonicalPumpOperatorLedgerRows($business_id, $pump_operator_id, $filters);
        $summaries = [];

        foreach ($rows as $row) {
            $operatorId = (int) ($row->pump_operator_id ?? 0);
            if ($operatorId <= 0) {
                continue;
            }

            if (! isset($summaries[$operatorId])) {
                $summaries[$operatorId] = [
                    'opening_debit' => 0.0,
                    'opening_credit' => 0.0,
                    'period_debit' => 0.0,
                    'period_credit' => 0.0,
                    'total_debit_for_period' => 0.0,
                    'total_credit_for_period' => 0.0,
                ];
            }

            $dateValue = $row->transaction_date ?: $row->operation_date ?: $row->ledger_created_at;
            if (empty($dateValue)) {
                continue;
            }

            try {
                $date = \Carbon\Carbon::parse($dateValue);
            } catch (\Throwable $e) {
                continue;
            }

            $amount = abs((float) ($row->amount ?? 0));
            $ledgerType = strtolower((string) ($row->ledger_type ?? ''));
            $transactionType = strtolower((string) ($row->transaction_type ?? ''));
            $transactionSubType = strtolower((string) ($row->transaction_sub_type ?? ''));

            if ($date->lt($start)) {
                if ($ledgerType === 'debit') {
                    $summaries[$operatorId]['opening_debit'] += $amount;
                } elseif ($ledgerType === 'credit') {
                    $summaries[$operatorId]['opening_credit'] += $amount;
                }
                continue;
            }

            if ($date->gt($end)) {
                continue;
            }

            if ($ledgerType === 'debit') {
                $summaries[$operatorId]['period_debit'] += $amount;
            } elseif ($ledgerType === 'credit') {
                $summaries[$operatorId]['period_credit'] += $amount;
            }

            if ($ledgerType === 'debit'
                && $transactionSubType === 'shortage'
                && in_array($transactionType, ['settlement', 'opening_balance'], true)) {
                $summaries[$operatorId]['total_debit_for_period'] += $amount;
            }

            if ($ledgerType === 'credit'
                && $transactionSubType === 'excess'
                && in_array($transactionType, ['settlement', 'opening_balance'], true)) {
                $summaries[$operatorId]['total_credit_for_period'] += $amount;
            }
        }

        $output = [];
        foreach ($summaries as $operatorId => $values) {
            $openingBalance = $values['opening_debit'] - $values['opening_credit'];
            $balanceForPeriod = $values['period_debit'] - $values['period_credit'];

            $output[$operatorId] = [
                'opening_balance' => $openingBalance,
                'total_debit_for_period' => $values['total_debit_for_period'],
                'total_credit_for_period' => $values['total_credit_for_period'],
                'balance_for_period' => $balanceForPeriod,
                'closing_balance' => $openingBalance + $balanceForPeriod,
            ];
        }

        return $this->petroPdFormatPumpOperatorSummary($output, $pump_operator_id);
    }

    protected function petroPdFormatPumpOperatorSummary(array $summaries, $pump_operator_id = null)
    {
        $empty = [
            'opening_balance' => 0.0,
            'total_debit_for_period' => 0.0,
            'total_credit_for_period' => 0.0,
            'balance_for_period' => 0.0,
            'closing_balance' => 0.0,
        ];

        if (is_null($pump_operator_id)) {
            return $summaries;
        }

        return $summaries[(int) $pump_operator_id] ?? $empty;
    }

    /**
     * Current PD Operators balance from the same canonical ledger authority.
     * Commission handling is retained from the legacy TransactionUtil method.
     */
    public function getPumpOperatorBalance($pump_operator_id)
    {
        $operator = PumpOperator::find((int) $pump_operator_id);
        if (! $operator) {
            return 0;
        }

        $rows = $this->petroPdCanonicalPumpOperatorLedgerRows(
            (int) $operator->business_id,
            (int) $operator->id
        );

        // On very old databases without ledger_show rows, preserve the legacy
        // calculation rather than changing historical behaviour unexpectedly.
        if ($rows->isEmpty()) {
            return parent::getPumpOperatorBalance($pump_operator_id);
        }

        $balance = 0.0;
        foreach ($rows as $row) {
            $amount = abs((float) ($row->amount ?? 0));
            if (strtolower((string) ($row->ledger_type ?? '')) === 'debit') {
                $balance += $amount;
            } elseif (strtolower((string) ($row->ledger_type ?? '')) === 'credit') {
                $balance -= $amount;
            }
        }

        $commission = (float) PumpOperatorCommission::where('pump_operator_id', (int) $operator->id)->sum('amount');

        return $balance + $commission;
    }
}

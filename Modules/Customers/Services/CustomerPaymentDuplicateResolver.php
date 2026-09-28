<?php

namespace Modules\Customers\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Modules\Customers\Support\SchemaCache;

/**
 * Suppresses legacy duplicate customer-payment display rows without deleting
 * accounting/audit data.
 *
 * Older payment code could save the same receipt twice and resolve the payment
 * reference collision by appending a numeric suffix (for example SP2026/0020
 * and SP2026/0020-1).  IS2272 requires those already-existing records to stop
 * appearing twice in Customer Payment Report and Customer Ledger.
 *
 * We only suppress the suffixed row when the unsuffixed reference exists and
 * the payment fingerprint is otherwise identical.  A short created-at window
 * prevents two intentional payments made later from being treated as one.
 */
class CustomerPaymentDuplicateResolver
{
    private const MAX_CREATED_AT_GAP_SECONDS = 300;

    public function collapse(Collection $rows): Collection
    {
        // IS2276: Customer Ledger can contain several contact_ledgers rows for
        // one persisted transaction payment.  Payment Report is correct because
        // it reads/group the payment source, while the ledger previously counted
        // every physical posting.  Collapse that exact payment-link duplication
        // first; the historical reference-suffix resolver below then handles the
        // older SPxxxx / SPxxxx-1 double-save pattern.
        $rows = $this->collapseExactPaymentLedgerPostings($rows);

        // IS2290: a payment edit/retry can leave ledger rows linked to different
        // transaction_payment IDs even though those payment rows carry the same
        // durable payment reference (or, on old data, the same cheque identity).
        // Payment Report correctly presents that as one logical payment, so the
        // Customer Ledger must use the same identity instead of counting every
        // physical contact_ledgers posting.
        $rows = $this->collapseRepeatedLogicalPaymentRows($rows);

        if ($rows->count() < 2) {
            return $rows->values();
        }

        $rows = $rows->values();
        $byReference = [];

        foreach ($rows as $index => $row) {
            $reference = $this->reference($row);
            if ($reference !== '') {
                $byReference[$reference][] = $index;
            }
        }

        $drop = [];

        foreach ($rows as $index => $row) {
            $reference = $this->reference($row);
            $baseReference = $this->baseReference($reference);

            if ($baseReference === null || empty($byReference[$baseReference])) {
                continue;
            }

            foreach ($byReference[$baseReference] as $baseIndex) {
                if ($this->samePayment($rows[$baseIndex], $row)) {
                    $drop[$index] = true;
                    break;
                }
            }
        }

        return $rows
            ->reject(function ($row, $index) use ($drop) {
                return isset($drop[$index]);
            })
            ->values();
    }

    /**
     * Apply the IS2276 duplicate rule at SQL level before rows are limited or
     * aggregated.  For one transaction_payment_id, customer, direction and
     * amount, only the earliest live contact_ledgers row is authoritative.
     *
     * Rows without transaction_payment_id are deliberately untouched, as are
     * rows with a different amount or debit/credit direction.  This keeps
     * journals, opening balances and genuine split adjustments intact.
     */
    public function applyCanonicalLedgerPostingFilter(Builder $query, string $alias = 'contact_ledgers'): void
    {
        if (!SchemaCache::hasTable('contact_ledgers')
            || !SchemaCache::hasColumn('contact_ledgers', 'id')
            || !SchemaCache::hasColumn('contact_ledgers', 'business_id')
            || !SchemaCache::hasColumn('contact_ledgers', 'contact_id')
            || !SchemaCache::hasColumn('contact_ledgers', 'transaction_payment_id')) {
            return;
        }

        /*
         * IS2290: Payment Report excludes soft-deleted payment revisions.  A
         * legacy contact_ledgers row linked to one of those revisions must not
         * remain visible or affect Customer Register/Dashboard balances.  Keep
         * genuinely unlinked historical ledger rows; suppress only links whose
         * matching transaction_payments row exists and is soft deleted.
         */
        $this->applyDeletedPaymentLinkFilter($query, $alias);

        $hasAmount = SchemaCache::hasColumn('contact_ledgers', 'amount');
        $currentType = $this->ledgerTypeExpression($alias);
        $earlierAlias = 'customers_is2276_earlier_payment_ledger';
        $earlierType = $this->ledgerTypeExpression($earlierAlias);

        $query->where(function (Builder $scope) use (
            $alias,
            $hasAmount,
            $currentType,
            $earlierAlias,
            $earlierType
        ) {
            $scope->whereNull($alias . '.transaction_payment_id')
                ->orWhere($alias . '.transaction_payment_id', '<=', 0)
                ->orWhereNotExists(function (Builder $duplicate) use (
                    $alias,
                    $hasAmount,
                    $currentType,
                    $earlierAlias,
                    $earlierType
                ) {
                    $duplicate->selectRaw('1')
                        ->from('contact_ledgers as ' . $earlierAlias)
                        ->whereColumn($earlierAlias . '.business_id', $alias . '.business_id')
                        ->whereColumn($earlierAlias . '.contact_id', $alias . '.contact_id')
                        ->whereColumn(
                            $earlierAlias . '.transaction_payment_id',
                            $alias . '.transaction_payment_id'
                        )
                        ->whereColumn($earlierAlias . '.id', '<', $alias . '.id')
                        ->whereRaw($earlierType . ' = ' . $currentType);

                    if ($hasAmount) {
                        $duplicate->whereRaw(
                            'ABS(COALESCE(' . $earlierAlias . '.amount, 0) - COALESCE(' . $alias . '.amount, 0)) < 0.0001'
                        );
                    }

                    if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                        $duplicate->whereNull($earlierAlias . '.deleted_at');
                    }
                });
        });

        /*
         * IS2290: the previous predicate handled only repeated rows with the
         * same transaction_payment_id.  Some older save/edit paths created a
         * new payment ID but reused the same payment_ref_no, so all copies were
         * still counted.  Canonicalise that logical reference at SQL level too,
         * before LIMIT and SUM are applied.
         */
        $this->applyCanonicalPaymentReferenceFilter(
            $query,
            $alias,
            $hasAmount,
            $currentType
        );
    }

    /**
     * Remove a customer-ledger row only when its linked payment still exists as
     * a soft-deleted revision. Missing legacy payment records remain untouched.
     */
    private function applyDeletedPaymentLinkFilter(Builder $query, string $alias): void
    {
        if (!SchemaCache::hasTable('transaction_payments')
            || !SchemaCache::hasColumn('transaction_payments', 'id')
            || !SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
            return;
        }

        $paymentAlias = 'customers_is2290_deleted_payment';

        $query->whereNotExists(function (Builder $deletedPayment) use ($alias, $paymentAlias) {
            $deletedPayment->selectRaw('1')
                ->from('transaction_payments as ' . $paymentAlias)
                ->whereColumn(
                    $paymentAlias . '.id',
                    $alias . '.transaction_payment_id'
                )
                ->whereNotNull($paymentAlias . '.deleted_at');

            if (SchemaCache::hasColumn('transaction_payments', 'business_id')) {
                $deletedPayment->whereColumn(
                    $paymentAlias . '.business_id',
                    $alias . '.business_id'
                );
            }
        });
    }

    /**
     * For the same customer, direction and amount, retain the earliest live
     * ledger posting carrying a non-empty payment reference.
     */
    private function applyCanonicalPaymentReferenceFilter(
        Builder $query,
        string $alias,
        bool $hasAmount,
        string $currentType
    ): void {
        if (!SchemaCache::hasTable('transaction_payments')
            || !SchemaCache::hasColumn('transaction_payments', 'id')
            || !SchemaCache::hasColumn('transaction_payments', 'payment_ref_no')) {
            return;
        }

        $earlierLedger = 'customers_is2290_earlier_reference_ledger';
        $earlierPayment = 'customers_is2290_earlier_reference_payment';
        $currentPayment = 'customers_is2290_current_reference_payment';
        $earlierType = $this->ledgerTypeExpression($earlierLedger);

        $query->whereNotExists(function (Builder $duplicate) use (
            $alias,
            $hasAmount,
            $currentType,
            $earlierLedger,
            $earlierPayment,
            $currentPayment,
            $earlierType
        ) {
            $duplicate->selectRaw('1')
                ->from('contact_ledgers as ' . $earlierLedger)
                ->join(
                    'transaction_payments as ' . $earlierPayment,
                    $earlierPayment . '.id',
                    '=',
                    $earlierLedger . '.transaction_payment_id'
                )
                ->join(
                    'transaction_payments as ' . $currentPayment,
                    $currentPayment . '.payment_ref_no',
                    '=',
                    $earlierPayment . '.payment_ref_no'
                )
                ->whereColumn(
                    $currentPayment . '.id',
                    $alias . '.transaction_payment_id'
                )
                ->whereColumn($earlierLedger . '.business_id', $alias . '.business_id')
                ->whereColumn($earlierLedger . '.contact_id', $alias . '.contact_id')
                ->whereColumn($earlierLedger . '.id', '<', $alias . '.id')
                ->whereNotNull($currentPayment . '.payment_ref_no')
                ->where($currentPayment . '.payment_ref_no', '!=', '')
                ->whereRaw($earlierType . ' = ' . $currentType);

            if ($hasAmount) {
                $duplicate->whereRaw(
                    'ABS(COALESCE(' . $earlierLedger . '.amount, 0) - COALESCE(' . $alias . '.amount, 0)) < 0.0001'
                );
            }

            if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                $duplicate->whereNull($earlierLedger . '.deleted_at');
            }

            if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
                $duplicate->whereNull($earlierPayment . '.deleted_at')
                    ->whereNull($currentPayment . '.deleted_at');
            }

            if (SchemaCache::hasColumn('transaction_payments', 'business_id')) {
                $duplicate->whereColumn(
                    $earlierPayment . '.business_id',
                    $earlierLedger . '.business_id'
                )->whereColumn(
                    $currentPayment . '.business_id',
                    $alias . '.business_id'
                );
            }
        });
    }

    /**
     * In-memory safety net for merged ledger rows.  This also covers a row that
     * came from a fallback source after the primary contact-ledger query.
     */
    private function collapseExactPaymentLedgerPostings(Collection $rows): Collection
    {
        $seen = [];

        return $rows->reject(function ($row) use (&$seen) {
            $paymentId = (int) ($row->transaction_payment_id ?? 0);
            if ($paymentId <= 0) {
                return false;
            }

            $customerId = (int) ($row->contact_id ?? $row->customer_id ?? 0);
            $direction = $this->normaliseText($row->acc_transaction_type ?? $row->type ?? '');
            $amount = number_format(abs((float) ($row->amount ?? 0)), 4, '.', '');

            $fingerprint = implode('|', [
                (string) $paymentId,
                (string) $customerId,
                $direction,
                $amount,
            ]);

            if (isset($seen[$fingerprint])) {
                return true;
            }

            $seen[$fingerprint] = true;

            return false;
        })->values();
    }

    /**
     * In-memory counterpart to the SQL reference filter.  It also covers rows
     * merged from a fallback source and old cheque rows that have no reference.
     * No anonymous cash/bank rows are collapsed, so two intentional same-day
     * payments remain separate unless they share a durable identity.
     */
    private function collapseRepeatedLogicalPaymentRows(Collection $rows): Collection
    {
        $seen = [];

        return $rows->reject(function ($row) use (&$seen) {
            $identity = $this->logicalPaymentIdentity($row);
            if ($identity === null) {
                return false;
            }

            $customerId = (int) ($row->contact_id ?? $row->customer_id ?? 0);
            $customer = $customerId > 0
                ? (string) $customerId
                : $this->normaliseText($row->customer_code ?? $row->customer_name ?? '');
            $direction = $this->normaliseText($row->acc_transaction_type ?? $row->type ?? '');
            $amount = number_format(abs((float) ($row->amount ?? 0)), 4, '.', '');

            $fingerprint = implode('|', [
                $identity,
                $customer,
                $direction,
                $amount,
            ]);

            if (isset($seen[$fingerprint])) {
                return true;
            }

            $seen[$fingerprint] = true;

            return false;
        })->values();
    }

    private function logicalPaymentIdentity($row): ?string
    {
        $reference = $this->normaliseText($this->reference($row));
        if ($reference !== '') {
            return 'reference:' . $reference;
        }

        $chequeNumber = $this->normaliseText($row->cheque_number ?? '');
        if ($chequeNumber === '') {
            return null;
        }

        return 'cheque:' . $chequeNumber
            . '|bank:' . $this->normaliseText($row->bank_name ?? '');
    }

    private function ledgerTypeExpression(string $alias): string
    {
        $hasType = SchemaCache::hasColumn('contact_ledgers', 'type');
        $hasAccType = SchemaCache::hasColumn('contact_ledgers', 'acc_transaction_type');

        if ($hasType && $hasAccType) {
            return "LOWER(COALESCE(NULLIF({$alias}.type, ''), NULLIF({$alias}.acc_transaction_type, ''), 'debit'))";
        }
        if ($hasType) {
            return "LOWER(COALESCE(NULLIF({$alias}.type, ''), 'debit'))";
        }
        if ($hasAccType) {
            return "LOWER(COALESCE(NULLIF({$alias}.acc_transaction_type, ''), 'debit'))";
        }

        return "'debit'";
    }

    private function reference($row): string
    {
        return trim((string) ($row->payment_ref_no ?? ''));
    }

    private function baseReference(string $reference): ?string
    {
        if ($reference === '' || !preg_match('/^(.+)-([1-9][0-9]*)$/', $reference, $matches)) {
            return null;
        }

        $base = trim((string) ($matches[1] ?? ''));

        return $base !== '' ? $base : null;
    }

    private function samePayment($left, $right): bool
    {
        if (!$this->sameCustomer($left, $right)) {
            return false;
        }

        if ($this->dateKey($left) !== $this->dateKey($right)) {
            return false;
        }

        if ($this->normaliseText($left->payment_method ?? $left->method ?? '')
            !== $this->normaliseText($right->payment_method ?? $right->method ?? '')) {
            return false;
        }

        if ($this->normaliseText($left->acc_transaction_type ?? $left->type ?? '')
            !== $this->normaliseText($right->acc_transaction_type ?? $right->type ?? '')) {
            return false;
        }

        if (!$this->sameWhenPresent($left->paid_in_type ?? '', $right->paid_in_type ?? '')) {
            return false;
        }

        if (!$this->sameWhenPresent($left->cheque_number ?? '', $right->cheque_number ?? '')
            || !$this->sameWhenPresent($left->bank_name ?? '', $right->bank_name ?? '')) {
            return false;
        }

        if (abs($this->amount($left) - $this->amount($right)) > 0.0001) {
            return false;
        }

        return $this->createdAtCloseEnough($left, $right);
    }

    private function sameCustomer($left, $right): bool
    {
        $leftId = (int) ($left->customer_id ?? $left->contact_id ?? 0);
        $rightId = (int) ($right->customer_id ?? $right->contact_id ?? 0);

        if ($leftId > 0 || $rightId > 0) {
            return $leftId > 0 && $leftId === $rightId;
        }

        return $this->normaliseText($left->customer_code ?? $left->customer_name ?? '')
            === $this->normaliseText($right->customer_code ?? $right->customer_name ?? '');
    }

    private function dateKey($row): string
    {
        $value = $row->paid_on ?? $row->transaction_date ?? $row->created_at ?? null;
        if (empty($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return substr((string) $value, 0, 10);
        }
    }

    private function amount($row): float
    {
        return round((float) ($row->amount ?? 0), 4);
    }

    private function sameWhenPresent($left, $right): bool
    {
        $left = $this->normaliseText($left);
        $right = $this->normaliseText($right);

        return $left === '' || $right === '' || $left === $right;
    }

    private function normaliseText($value): string
    {
        return strtolower(trim((string) $value));
    }

    private function createdAtCloseEnough($left, $right): bool
    {
        $leftCreated = $left->created_at ?? null;
        $rightCreated = $right->created_at ?? null;

        // Old tenant rows can lack created_at.  The remaining fingerprint is
        // still strict and includes the explicit base/-N collision relationship.
        if (empty($leftCreated) || empty($rightCreated)) {
            return true;
        }

        try {
            return abs(Carbon::parse($leftCreated)->diffInSeconds(Carbon::parse($rightCreated), false))
                <= self::MAX_CREATED_AT_GAP_SECONDS;
        } catch (\Throwable $e) {
            return true;
        }
    }
}

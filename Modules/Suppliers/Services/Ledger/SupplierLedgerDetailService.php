<?php

namespace Modules\Suppliers\Services\Ledger;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Builds the standalone Supplier module ledger from the ERP's canonical
 * transactions and transaction_payments tables. Purchases - Old, supplier due
 * payments and advance payments therefore share one visible data source.
 */
class SupplierLedgerDetailService
{
    public function getLedgerDetails(
        int $supplierId,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $locationId = null,
        ?int $businessId = null
    ): array {
        $businessId = $businessId ?: (int) session('user.business_id', session('business.id'));

        $ledgerAdjustments = DB::table('contact_ledgers')
            ->where('contact_id', $supplierId)
            ->whereNull('deleted_at')
            ->whereNotNull('transaction_id')
            ->groupBy('transaction_id')
            ->select([
                'transaction_id',
                DB::raw('MAX(type) as ledger_type'),
                DB::raw('MAX(amount) as ledger_amount'),
                DB::raw('MAX(note) as ledger_note'),
            ]);

        $transactions = DB::table('transactions as t')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
            ->leftJoinSub($ledgerAdjustments, 'cl', 'cl.transaction_id', '=', 't.id')
            ->where('t.business_id', $businessId)
            ->where('t.contact_id', $supplierId)
            ->whereNull('t.deleted_at')
            ->whereIn('t.status', ['final', 'received', 'ordered', 'pending'])
            ->whereIn('t.type', [
                'purchase', 'purchase_return', 'opening_balance', 'ledger',
                'property_purchase', 'expense', 'cheque_return',
                '_deleted_purchase', 'sell_return',
            ])
            ->when($locationId, fn ($query) => $query->where('t.location_id', $locationId))
            ->when($startDate, fn ($query) => $query->where('t.transaction_date', '>=', Carbon::parse($startDate)->startOfDay()))
            ->when($endDate, fn ($query) => $query->where('t.transaction_date', '<=', Carbon::parse($endDate)->endOfDay()))
            ->select([
                't.id', 't.type', 't.transaction_date as date', 't.created_at',
                't.final_total as amount', 't.ref_no', 't.invoice_no', 't.payment_status',
                't.additional_notes', 'bl.name as location_name',
                'cl.ledger_type', 'cl.ledger_amount', 'cl.ledger_note',
            ])
            ->get();

        // S758: Supplier Pay Due root rows can have a legacy/current timestamp
        // while their allocation children carry the exact Paid on selected by the
        // user. Resolve one effective payment date from the allocation family so
        // both historical and new payments show the selected transaction date.
        $hasPaymentParentId = Schema::hasColumn('transaction_payments', 'parent_id');
        $paymentDateExpression = 'tp.paid_on';

        $paymentsQuery = DB::table('transaction_payments as tp')
            ->leftJoin('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id');

        if ($hasPaymentParentId) {
            $allocationDateQuery = DB::table('transaction_payments as child')
                ->where('child.business_id', $businessId)
                ->whereNotNull('child.parent_id')
                ->groupBy('child.parent_id')
                ->select([
                    'child.parent_id',
                    DB::raw('MIN(child.paid_on) as effective_paid_on'),
                ]);

            if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
                $allocationDateQuery->whereNull('child.deleted_at');
            }

            $paymentsQuery->leftJoinSub($allocationDateQuery, 'supplier_payment_dates', function ($join) {
                $join->on('supplier_payment_dates.parent_id', '=', 'tp.id');
            });

            $paymentDateExpression = 'COALESCE(tp.paid_on, supplier_payment_dates.effective_paid_on)';
        }

        $paymentsQuery
            ->where('tp.business_id', $businessId)
            ->where('tp.payment_for', $supplierId)
            ->whereNull('tp.deleted_at');

        if ($hasPaymentParentId) {
            $paymentsQuery->whereNull('tp.parent_id');
        }

        $payments = $paymentsQuery
            ->where(function ($query) {
                $query->whereNull('tp.transaction_id')
                    ->orWhereNull('t.type')
                    ->orWhereNotIn('t.type', [
                        'security_deposit', 'refund_security_deposit',
                        'security_deposit_refund', 'cheque_opening_balance',
                    ]);
            })
            ->when($locationId, function ($query) use ($locationId) {
                $query->where(function ($locationQuery) use ($locationId) {
                    $locationQuery->where('t.location_id', $locationId)
                        ->orWhereNull('tp.transaction_id');
                });
            })
            ->when($startDate, fn ($query) => $query->whereRaw(
                $paymentDateExpression . ' >= ?',
                [Carbon::parse($startDate)->startOfDay()->format('Y-m-d H:i:s')]
            ))
            ->when($endDate, fn ($query) => $query->whereRaw(
                $paymentDateExpression . ' <= ?',
                [Carbon::parse($endDate)->endOfDay()->format('Y-m-d H:i:s')]
            ))
            ->select([
                'tp.id', DB::raw($paymentDateExpression . ' as date'), 'tp.created_at', 'tp.amount',
                'tp.payment_ref_no', 'tp.method', 'tp.note', 'tp.transaction_id',
                't.type as transaction_type', 't.payment_status as transaction_payment_status',
                't.ref_no as transaction_ref_no', 't.invoice_no as transaction_invoice_no',
                'bl.name as location_name',
            ])
            ->get();


        $rows = collect();

        foreach ($transactions as $transaction) {
            $rows->push($this->transactionRow($transaction));
        }

        foreach ($payments as $payment) {
            $rows->push($this->paymentRow($payment));
        }

        // The standalone supplier form stores an opening balance directly on
        // contacts.opening_balance. Older records may therefore have a valid
        // Total Due without a matching transactions.opening_balance row. Add a
        // ledger fallback only when no canonical opening-balance transaction
        // exists, so the list and ledger agree without double-counting.
        $directOpeningBalanceRow = $this->directOpeningBalanceRow(
            $supplierId,
            $businessId,
            $startDate,
            $endDate,
            $locationId
        );

        if ($directOpeningBalanceRow !== null) {
            $rows->push($directOpeningBalanceRow);
        }

        $openingBalance = (float) $rows->sum(static function (array $row): float {
            return ($row['type_key'] ?? '') === 'opening_balance'
                ? (float) ($row['credit'] ?? 0) - (float) ($row['debit'] ?? 0)
                : 0.0;
        });

        $runningBalance = 0.0;
        $rows = $rows
            ->sortBy(fn (array $row) => sprintf(
                '%s|%s|%s',
                $row['sort_date'],
                $row['created_at'],
                $row['sort_id']
            ))
            ->values()
            ->map(function (array $row) use (&$runningBalance) {
                // Positive balance means the business owes the supplier.
                $runningBalance += (float) $row['credit'] - (float) $row['debit'];
                $row['balance'] = round($runningBalance, 4);
                unset($row['sort_date'], $row['created_at'], $row['type_key']);

                return $row;
            });

        return [
            'rows' => $rows,
            'opening_balance' => round($openingBalance, 4),
            'debit_total' => (float) $rows->sum('debit'),
            'credit_total' => (float) $rows->sum('credit'),
            'balance' => (float) $runningBalance,
            'record_count' => $rows->count(),
        ];
    }

    /**
     * Return a synthetic opening-balance row for contacts that store their
     * opening balance directly on the contact record. The fallback is skipped
     * as soon as an eligible canonical opening_balance transaction exists.
     */
    private function directOpeningBalanceRow(
        int $supplierId,
        int $businessId,
        ?string $startDate,
        ?string $endDate,
        ?int $locationId
    ): ?array {
        if (!Schema::hasTable('contacts') || !Schema::hasColumn('contacts', 'opening_balance')) {
            return null;
        }

        if ($this->hasCanonicalOpeningBalanceTransaction($supplierId, $businessId)) {
            return null;
        }

        $query = DB::table('contacts as c')
            ->where('c.id', $supplierId)
            ->where('c.business_id', $businessId);

        $hasContactLocation = Schema::hasColumn('contacts', 'location_id');
        $hasBusinessLocations = Schema::hasTable('business_locations');

        if ($hasContactLocation && $hasBusinessLocations) {
            $query->leftJoin('business_locations as bl', 'bl.id', '=', 'c.location_id');
        }

        if ($locationId && $hasContactLocation) {
            $query->where(function ($locationQuery) use ($locationId) {
                // A null contact location is a business-wide opening balance,
                // so it remains visible under an individual location filter.
                $locationQuery->where('c.location_id', $locationId)
                    ->orWhereNull('c.location_id');
            });
        }

        if ($startDate && Schema::hasColumn('contacts', 'created_at')) {
            $query->where('c.created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate && Schema::hasColumn('contacts', 'created_at')) {
            $query->where('c.created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $select = [
            'c.id',
            'c.opening_balance',
        ];

        if (Schema::hasColumn('contacts', 'contact_id')) {
            $select[] = 'c.contact_id';
        }

        if (Schema::hasColumn('contacts', 'created_at')) {
            $select[] = 'c.created_at';
        }

        if ($hasContactLocation && $hasBusinessLocations) {
            $select[] = 'bl.name as location_name';
        }

        $contact = $query->select($select)->first();

        if (!$contact) {
            return null;
        }

        $openingBalance = (float) ($contact->opening_balance ?? 0);

        if (abs($openingBalance) < 0.0000001) {
            return null;
        }

        $isDebit = $openingBalance < 0;
        $amount = abs($openingBalance);
        $createdAt = $contact->created_at ?? null;
        $reference = $contact->contact_id ?? null;

        return [
            'sort_id' => 'C' . $contact->id,
            'sort_date' => $this->dateForSort($createdAt),
            'created_at' => $this->dateForSort($createdAt),
            'system_entered_date' => $this->displayDate($createdAt),
            'date' => $this->displayDate($createdAt),
            'description' => 'Opening Balance',
            'type' => 'Opening Balance',
            'type_key' => 'opening_balance',
            'payment_status' => $openingBalance > 0 ? 'Due' : 'Overpaid',
            'payment_method' => '-',
            'reference' => $reference ?: '-',
            'location' => $contact->location_name ?? '-',
            'debit' => $isDebit ? $amount : 0.0,
            'credit' => $isDebit ? 0.0 : $amount,
            'balance' => 0.0,
        ];
    }

    private function hasCanonicalOpeningBalanceTransaction(int $supplierId, int $businessId): bool
    {
        if (!Schema::hasTable('transactions')) {
            return false;
        }

        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $supplierId)
            ->where('type', 'opening_balance');

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (Schema::hasColumn('transactions', 'status')) {
            $query->whereIn('status', ['final', 'received', 'ordered', 'pending']);
        }

        return $query->exists();
    }

    private function transactionRow(object $transaction): array
    {
        $type = (string) ($transaction->type ?? 'purchase');
        $amount = $type === 'ledger' && $transaction->ledger_amount !== null
            ? (float) $transaction->ledger_amount
            : (float) ($transaction->amount ?? 0);

        $ledgerType = $transaction->ledger_type ?? null;
        $debitTypes = ['purchase_return', '_deleted_purchase', 'sell_return'];
        $isDebit = $ledgerType === 'debit'
            || ($ledgerType === null && in_array($type, $debitTypes, true));

        if ($type === 'opening_balance' && $amount < 0) {
            $isDebit = true;
        }

        $amount = abs($amount);
        $reference = $transaction->invoice_no ?: $transaction->ref_no;
        if ($type === 'ledger') {
            $reference = $this->formatJournalReference($reference);
        }
        $description = $this->transactionDescription($type, $reference);
        $note = trim((string) ($transaction->ledger_note ?: $transaction->additional_notes));

        if ($note !== '') {
            $description .= ' | ' . $note;
        }

        return [
            'sort_id' => 'T' . $transaction->id,
            'sort_date' => $this->dateForSort($transaction->date),
            'created_at' => $this->dateForSort($transaction->created_at ?: $transaction->date),
            'system_entered_date' => $this->displayDate($transaction->created_at ?: $transaction->date),
            'date' => $this->displayDate($transaction->date),
            'description' => $description,
            'type' => $this->transactionTypeLabel($type),
            'type_key' => $type,
            'payment_status' => $this->paymentStatusLabel($transaction->payment_status ?? null),
            'payment_method' => '-',
            'reference' => $reference ?: '-',
            'location' => $transaction->location_name ?: '-',
            'debit' => $isDebit ? $amount : 0.0,
            'credit' => $isDebit ? 0.0 : $amount,
            'balance' => 0.0,
        ];
    }

    private function paymentRow(object $payment): array
    {
        $reference = $payment->payment_ref_no
            ?: $payment->transaction_invoice_no
            ?: $payment->transaction_ref_no;

        $isAdvance = ($payment->transaction_type ?? null) === 'advance_payment';
        $description = $isAdvance ? 'Advance Payment' : 'Supplier Payment';

        if (!empty($payment->method)) {
            $description .= ' - ' . ucwords(str_replace('_', ' ', (string) $payment->method));
        }

        if (!empty($payment->note)) {
            $description .= ' | ' . $payment->note;
        }

        return [
            'sort_id' => 'P' . $payment->id,
            'sort_date' => $this->dateForSort($payment->date),
            'created_at' => $this->dateForSort($payment->created_at ?: $payment->date),
            'system_entered_date' => $this->displayDate($payment->created_at ?: $payment->date),
            'date' => $this->displayDate($payment->date),
            'description' => $description,
            'type' => $isAdvance ? 'Advance Payment' : 'Payment',
            'type_key' => $isAdvance ? 'advance_payment' : 'payment',
            'payment_status' => 'Paid',
            'payment_method' => $this->paymentMethodLabel($payment->method ?? null),
            'reference' => $reference ?: '-',
            'location' => $payment->location_name ?: '-',
            'debit' => abs((float) $payment->amount),
            'credit' => 0.0,
            'balance' => 0.0,
        ];
    }

    private function transactionDescription(string $type, ?string $reference): string
    {
        $labels = [
            'purchase' => 'Purchase',
            'property_purchase' => 'Property Purchase',
            'expense' => 'Supplier Expense',
            'purchase_return' => 'Purchase Return',
            'opening_balance' => 'Opening Balance',
            'ledger' => 'Ledger Adjustment',
            'cheque_return' => 'Cheque Return',
            '_deleted_purchase' => 'Deleted Purchase Reversal',
            'sell_return' => 'Return / Adjustment',
        ];

        $label = $labels[$type] ?? ucwords(str_replace('_', ' ', $type));

        return $reference ? $label . ': ' . $reference : $label;
    }

    private function transactionTypeLabel(string $type): string
    {
        $labels = [
            'purchase' => 'Purchase',
            'property_purchase' => 'Property Purchase',
            'expense' => 'Supplier Expense',
            'purchase_return' => 'Purchase Return',
            'opening_balance' => 'Opening Balance',
            'ledger' => 'Ledger Adjustment',
            'cheque_return' => 'Cheque Return',
            '_deleted_purchase' => 'Deleted Purchase Reversal',
            'sell_return' => 'Return / Adjustment',
        ];

        return $labels[$type] ?? ucwords(str_replace('_', ' ', $type));
    }

    /**
     * IS2087: Finance stores a durable journal link on the supplier ledger
     * transaction as values such as "Journal: 2".  Keep that storage format
     * untouched, but present it using the system Journal number everywhere in
     * the Supplier Ledger.
     */
    private function formatJournalReference(?string $reference): ?string
    {
        $value = trim((string) $reference);
        if ($value === '') {
            return $reference;
        }

        if (preg_match('/^journal\s*(?:[:#\-]\s*)?(\d+)$/i', $value, $matches)) {
            return 'JOUR' . str_pad((string) ((int) $matches[1]), 4, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^jour\s*0*(\d+)$/i', $value, $matches)) {
            return 'JOUR' . str_pad((string) ((int) $matches[1]), 4, '0', STR_PAD_LEFT);
        }

        return $reference;
    }

    private function paymentStatusLabel($status): string
    {
        $value = trim((string) $status);

        return $value === '' ? '-' : ucwords(str_replace('_', ' ', $value));
    }

    private function paymentMethodLabel($method): string
    {
        $value = trim((string) $method);

        return $value === '' ? '-' : ucwords(str_replace('_', ' ', $value));
    }

    private function displayDate($value): string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : '-';
    }

    private function dateForSort($value): string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : '0000-00-00 00:00:00';
    }
}

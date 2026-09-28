<?php

namespace Modules\Finance\Services\Deposits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * S738 - Customer cheque -> Cheques in Hand compatibility repair.
 *
 * Some Customers-module entry paths persist a valid transaction_payments row
 * (method=cheque, cheque metadata, customer/contact) but do not leave the
 * matching Cheques-in-Hand debit row that Finance's Cheque Deposit workflow
 * consumes.  This service repairs only those explicit customer-cheque rows.
 * It is intentionally idempotent and never infers a cheque from amount/date.
 */
class CustomerChequeInHandSyncService
{
    /** @var array<string,bool> */
    private array $columnCache = [];

    /** @var array<string,?bool> */
    private array $dualContactEvidenceCache = [];

    /**
     * @param array<int,int> $chequeAccountIds
     * @return array{scanned:int,created:int,moved:int,payment_account_fixed:int,skipped:int}
     */
    public function sync(int $businessId, array $chequeAccountIds): array
    {
        $stats = [
            'scanned' => 0,
            'created' => 0,
            'moved' => 0,
            'payment_account_fixed' => 0,
            'skipped' => 0,
        ];

        $chequeAccountIds = array_values(array_unique(array_filter(array_map('intval', $chequeAccountIds))));
        if ($businessId <= 0 || $chequeAccountIds === []) {
            return $stats;
        }

        $schema = DB::connection()->getSchemaBuilder();
        foreach (['transaction_payments', 'account_transactions', 'transactions', 'contacts', 'accounts'] as $table) {
            if (! $schema->hasTable($table)) {
                return $stats;
            }
        }

        try {
            $exactLegacyId = (int) (DB::table('accounts')
                ->where('business_id', $businessId)
                ->whereIn('id', $chequeAccountIds)
                ->whereRaw('LOWER(TRIM(name)) = ?', ['cheques in hand'])
                ->orderBy('id')
                ->value('id') ?: 0);

            $hasParent = $this->hasColumn('transaction_payments', 'parent_id');

            $query = DB::table('transaction_payments as tp')
                ->leftJoin('transactions as t', 't.id', '=', 'tp.transaction_id')
                ->leftJoin('contacts as payment_contact', 'payment_contact.id', '=', 'tp.payment_for')
                ->leftJoin('contacts as transaction_contact', 'transaction_contact.id', '=', 't.contact_id');

            if ($hasParent) {
                $query->leftJoin('transaction_payments as parent_tp', 'parent_tp.id', '=', 'tp.parent_id');
            }

            $query
                ->whereRaw('LOWER(TRIM(COALESCE(tp.method, ""))) = ?', ['cheque'])
                ->where('tp.amount', '>', 0)
                ->where(function ($business) use ($businessId): void {
                    if ($this->hasColumn('transaction_payments', 'business_id')) {
                        $business->where('tp.business_id', $businessId);
                        if ($this->hasColumn('transactions', 'business_id')) {
                            $business->orWhere('t.business_id', $businessId);
                        }
                    } elseif ($this->hasColumn('transactions', 'business_id')) {
                        $business->where('t.business_id', $businessId);
                    }
                })
                ->where(function ($customer): void {
                    $customer
                        ->whereIn('payment_contact.type', ['customer', 'both'])
                        ->orWhereIn('transaction_contact.type', ['customer', 'both']);
                })
                ->where(function ($metadata): void {
                    $metadata->whereRaw('TRIM(COALESCE(tp.cheque_number, "")) <> ""');
                    if ($this->hasColumn('transaction_payments', 'cheque_date')) {
                        $metadata->orWhereNotNull('tp.cheque_date');
                    }
                    if ($this->hasColumn('transaction_payments', 'bank_name')) {
                        $metadata->orWhereRaw('TRIM(COALESCE(tp.bank_name, "")) <> ""');
                    }
                });

            if ($this->hasColumn('transaction_payments', 'deleted_at')) {
                $query->whereNull('tp.deleted_at');
            }

            $select = [
                'tp.id',
                'tp.transaction_id',
                'tp.payment_for',
                'tp.amount',
                'tp.account_id',
                'tp.method',
                'tp.cheque_number',
                'tp.cheque_date',
                'tp.bank_name',
                'tp.paid_on',
                'tp.created_by',
                'tp.created_at',
                't.type as transaction_type',
                't.contact_id as transaction_contact_id',
                'payment_contact.type as payment_contact_type',
                'transaction_contact.type as transaction_contact_type',
            ];

            if ($hasParent) {
                $select[] = 'tp.parent_id';
                $select[] = 'parent_tp.method as parent_method';
                $select[] = 'parent_tp.account_id as parent_account_id';
                $select[] = 'parent_tp.cheque_number as parent_cheque_number';
            }

            $payments = $query->select($select)->orderBy('tp.id')->limit(1500)->get();

            foreach ($payments as $payment) {
                $stats['scanned']++;

                // When the parent itself is the cheque-bearing payment, do not create
                // a second Cheques-in-Hand row for an allocation child.
                if ($hasParent && ! empty($payment->parent_id)) {
                    $parentMethod = strtolower(trim((string) ($payment->parent_method ?? '')));
                    $parentCheque = trim((string) ($payment->parent_cheque_number ?? ''));
                    if ($parentMethod === 'cheque' || $parentCheque !== '') {
                        $stats['skipped']++;
                        continue;
                    }
                }

                if (! $this->isSafeCustomerCheque($payment, $chequeAccountIds, $businessId)) {
                    $stats['skipped']++;
                    continue;
                }

                $targetAccountId = $this->resolveChequeAccountId(
                    $payment,
                    $chequeAccountIds,
                    $exactLegacyId
                );

                if ($targetAccountId <= 0) {
                    $stats['skipped']++;
                    continue;
                }

                DB::transaction(function () use (
                    $businessId,
                    $payment,
                    $targetAccountId,
                    $chequeAccountIds,
                    &$stats
                ): void {
                    $paymentRow = DB::table('transaction_payments')
                        ->where('id', (int) $payment->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $paymentRow) {
                        $stats['skipped']++;
                        return;
                    }

                    // A customer cheque's payment-method account is Cheques in Hand.
                    // Correct an empty/wrong payment account only when the payment is
                    // already proven to be an explicit customer cheque.
                    if ($this->hasColumn('transaction_payments', 'account_id')
                        && (int) ($paymentRow->account_id ?? 0) !== $targetAccountId) {
                        DB::table('transaction_payments')
                            ->where('id', (int) $payment->id)
                            ->update(['account_id' => $targetAccountId]);
                        $stats['payment_account_fixed']++;
                    }

                    $existing = DB::table('account_transactions')
                        ->where('transaction_payment_id', (int) $payment->id)
                        ->where('type', 'debit')
                        ->when($this->hasColumn('account_transactions', 'deleted_at'), function ($q): void {
                            $q->whereNull('deleted_at');
                        })
                        ->orderBy('id')
                        ->get();

                    $alreadyCorrect = $existing->first(function ($row) use ($targetAccountId): bool {
                        return (int) ($row->account_id ?? 0) === $targetAccountId;
                    });

                    if ($alreadyCorrect) {
                        $this->refreshChequeMetadata((int) $alreadyCorrect->id, $paymentRow);
                        return;
                    }

                    // If this payment already has a debit on another payment-method
                    // account, move that row instead of creating a duplicate debit.
                    $movable = $existing->first(function ($row) use ($chequeAccountIds): bool {
                        return ! in_array((int) ($row->account_id ?? 0), $chequeAccountIds, true);
                    });

                    if ($movable) {
                        $update = ['account_id' => $targetAccountId];
                        $this->appendChequeMetadata($update, $paymentRow);
                        DB::table('account_transactions')->where('id', (int) $movable->id)->update($update);
                        $stats['moved']++;
                        return;
                    }

                    $insert = [
                        'account_id' => $targetAccountId,
                        'amount' => (float) $paymentRow->amount,
                        'type' => 'debit',
                        'operation_date' => $paymentRow->paid_on ?: $paymentRow->created_at ?: now(),
                        'transaction_id' => $paymentRow->transaction_id ?: null,
                        'transaction_payment_id' => (int) $paymentRow->id,
                        'created_by' => $paymentRow->created_by ?: (auth()->id() ?: null),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if ($this->hasColumn('account_transactions', 'business_id')) {
                        $insert['business_id'] = $businessId;
                    }
                    if ($this->hasColumn('account_transactions', 'sub_type')) {
                        $insert['sub_type'] = null;
                    }
                    if ($this->hasColumn('account_transactions', 'note')) {
                        $insert['note'] = 'Customer cheque received';
                    }
                    $this->appendChequeMetadata($insert, $paymentRow);

                    DB::table('account_transactions')->insert($insert);
                    $stats['created']++;
                });
            }
        } catch (\Throwable $e) {
            // Cheque Deposit must remain usable even on an unexpected legacy schema.
            Log::warning('Finance S738 customer cheque compatibility sync skipped safely', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
            ]);
        }

        if (($stats['created'] + $stats['moved'] + $stats['payment_account_fixed']) > 0) {
            Log::info('Finance S738 customer cheque compatibility sync completed', [
                'business_id' => $businessId,
                'stats' => $stats,
            ]);
        }

        return $stats;
    }

    /** @param array<int,int> $chequeAccountIds */
    private function isSafeCustomerCheque(object $payment, array $chequeAccountIds, int $businessId): bool
    {
        $paymentType = strtolower(trim((string) ($payment->payment_contact_type ?? '')));
        $transactionContactType = strtolower(trim((string) ($payment->transaction_contact_type ?? '')));
        $transactionType = strtolower(trim((string) ($payment->transaction_type ?? '')));

        if ($paymentType === 'customer' || $transactionContactType === 'customer') {
            return true;
        }

        if ($paymentType === 'supplier' || $transactionContactType === 'supplier') {
            return false;
        }

        // A contact can legitimately be "both" customer and supplier.  Bulk-payment
        // parent rows can also have no direct contact while their child allocations
        // carry the customer.  Inspect the already posted control-account side first:
        // Accounts Receivable proves a customer flow; Accounts Payable proves a
        // supplier flow.  This covers Customer Advance and Customer Bulk Payment
        // without importing Supplier advances.
        if ($paymentType === 'both' || $transactionContactType === 'both'
            || ($paymentType === '' && $transactionContactType === '')) {
            $ledgerEvidence = $this->dualContactLedgerEvidence($payment, $businessId);
            if ($ledgerEvidence !== null) {
                return $ledgerEvidence;
            }

            if (in_array($transactionType, [
                'sell', 'sell_return', 'opening_balance', 'customer_payment',
                'customer_bulk_payment',
            ], true)) {
                return true;
            }

            // For an explicit dual-role contact, an already saved Cheques-in-Hand
            // account is additional evidence that this cheque came through the
            // customer receipt path.  Do not apply this fallback when contact
            // identity is completely absent.
            if (($paymentType === 'both' || $transactionContactType === 'both')
                && in_array((int) ($payment->account_id ?? 0), $chequeAccountIds, true)) {
                return true;
            }
        }

        return false;
    }

    private function dualContactLedgerEvidence(object $payment, int $businessId): ?bool
    {
        $rootId = (int) (($payment->parent_id ?? 0) ?: ($payment->id ?? 0));
        $cacheKey = $businessId . ':' . $rootId;
        if (array_key_exists($cacheKey, $this->dualContactEvidenceCache)) {
            return $this->dualContactEvidenceCache[$cacheKey];
        }

        $receivableId = (int) (DB::table('accounts')
            ->where('business_id', $businessId)
            ->whereRaw('LOWER(TRIM(name)) = ?', ['accounts receivable'])
            ->value('id') ?: 0);
        $payableId = (int) (DB::table('accounts')
            ->where('business_id', $businessId)
            ->whereRaw('LOWER(TRIM(name)) = ?', ['accounts payable'])
            ->value('id') ?: 0);

        if ($receivableId <= 0 && $payableId <= 0) {
            return $this->dualContactEvidenceCache[$cacheKey] = null;
        }

        $familyIds = [(int) ($payment->id ?? 0)];
        if (! empty($payment->parent_id)) {
            $familyIds[] = (int) $payment->parent_id;
        } elseif ($this->hasColumn('transaction_payments', 'parent_id')) {
            $familyIds = array_merge(
                $familyIds,
                DB::table('transaction_payments')
                    ->where('parent_id', (int) ($payment->id ?? 0))
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all()
            );
        }
        $familyIds = array_values(array_unique(array_filter($familyIds)));

        $accountIds = array_values(array_filter([$receivableId, $payableId]));
        $rows = DB::table('account_transactions')
            ->whereIn('transaction_payment_id', $familyIds)
            ->whereIn('account_id', $accountIds)
            ->when($this->hasColumn('account_transactions', 'deleted_at'), function ($q): void {
                $q->whereNull('deleted_at');
            })
            ->get(['account_id', 'type']);

        $hasReceivable = $receivableId > 0 && $rows->contains(function ($row) use ($receivableId): bool {
            return (int) $row->account_id === $receivableId;
        });
        $hasPayable = $payableId > 0 && $rows->contains(function ($row) use ($payableId): bool {
            return (int) $row->account_id === $payableId;
        });

        if ($hasReceivable && ! $hasPayable) {
            return $this->dualContactEvidenceCache[$cacheKey] = true;
        }
        if ($hasPayable && ! $hasReceivable) {
            return $this->dualContactEvidenceCache[$cacheKey] = false;
        }

        return $this->dualContactEvidenceCache[$cacheKey] = null;
    }

    /** @param array<int,int> $chequeAccountIds */
    private function resolveChequeAccountId(object $payment, array $chequeAccountIds, int $exactLegacyId): int
    {
        $direct = (int) ($payment->account_id ?? 0);
        if (in_array($direct, $chequeAccountIds, true)) {
            return $direct;
        }

        $parent = (int) ($payment->parent_account_id ?? 0);
        if (in_array($parent, $chequeAccountIds, true)) {
            return $parent;
        }

        if ($exactLegacyId > 0) {
            return $exactLegacyId;
        }

        return count($chequeAccountIds) === 1 ? (int) $chequeAccountIds[0] : 0;
    }

    private function refreshChequeMetadata(int $accountTransactionId, object $payment): void
    {
        $update = [];
        $this->appendChequeMetadata($update, $payment);
        if ($update !== []) {
            DB::table('account_transactions')->where('id', $accountTransactionId)->update($update);
        }
    }

    /** @param array<string,mixed> $data */
    private function appendChequeMetadata(array &$data, object $payment): void
    {
        if ($this->hasColumn('account_transactions', 'cheque_number')) {
            $data['cheque_number'] = $payment->cheque_number ?: null;
        }
        if ($this->hasColumn('account_transactions', 'cheque_date')) {
            $data['cheque_date'] = $payment->cheque_date ?: ($payment->paid_on ?: null);
        }
        if ($this->hasColumn('account_transactions', 'bank_name')) {
            $data['bank_name'] = $payment->bank_name ?: null;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (! array_key_exists($key, $this->columnCache)) {
            $this->columnCache[$key] = DB::connection()->getSchemaBuilder()->hasColumn($table, $column);
        }

        return $this->columnCache[$key];
    }
}

<?php

namespace Modules\Suppliers\Services\Financial;

use App\AccountTransaction as CoreAccountTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * S763-HOTFIX - Supplier Pay Due double-entry integrity.
 *
 * Required accounting for an outgoing Supplier Pay Due payment:
 *   DR Accounts Payable
 *   CR the payment account selected by the user
 *
 * Supplier Pay Due is saved through the ERP's shared payment controller.  The
 * root transaction_payments row is created before the controller finishes its
 * account_transactions rows, so this guard deliberately waits until request
 * termination before creating a missing leg.  During the request it only
 * normalises rows that already exist.  This avoids racing the shared controller
 * and keeps the operation idempotent.
 */
class SupplierPaymentDoubleEntryGuard
{
    /** @var array<int, true> */
    private array $pendingRootPaymentIds = [];

    /** @var array<int, true> */
    private array $scheduledAfterCommit = [];

    private bool $busy = false;

    public function capturePayment(object $payment): void
    {
        if ($this->busy || ! $this->isLiveSupplierPayDueRequest()) {
            return;
        }

        $paymentId = (int) ($payment->id ?? 0);
        if ($paymentId <= 0 || ! $this->schemaReady()) {
            return;
        }

        $rootId = $this->rootPaymentId($paymentId);
        if ($rootId <= 0) {
            return;
        }

        $context = $this->context($rootId, true);
        if ($context === null) {
            return;
        }

        $this->pendingRootPaymentIds[$rootId] = true;
        $this->scheduleAfterCommit($rootId);
    }

    private function scheduleAfterCommit(int $rootId): void
    {
        if ($rootId <= 0 || isset($this->scheduledAfterCommit[$rootId])) {
            return;
        }

        try {
            $connection = DB::connection();
            if (method_exists($connection, 'afterCommit') && $connection->transactionLevel() > 0) {
                $this->scheduledAfterCommit[$rootId] = true;
                $connection->afterCommit(function () use ($rootId): void {
                    unset($this->scheduledAfterCommit[$rootId]);
                    unset($this->pendingRootPaymentIds[$rootId]);
                    try {
                        $this->ensurePair($rootId, true);
                    } catch (\Throwable $e) {
                        Log::error('Supplier Pay Due after-commit double-entry finalisation failed.', [
                            'transaction_payment_id' => $rootId,
                            'message' => $e->getMessage(),
                        ]);
                    }
                });
            }
        } catch (\Throwable $e) {
            // Laravel versions without afterCommit support use the terminating
            // fallback registered by SuppliersServiceProvider.
        }
    }

    /**
     * Correct an already-created ledger row immediately, but do not create a
     * missing counterpart here because the shared controller may still be about
     * to create it in the same request.
     */
    public function normalizeAccountTransaction(object $accountTransaction): void
    {
        if ($this->busy || ! $this->isLiveSupplierPayDueRequest()) {
            return;
        }

        $paymentId = (int) ($accountTransaction->transaction_payment_id ?? 0);
        $rowId = (int) ($accountTransaction->id ?? 0);
        if ($paymentId <= 0 || $rowId <= 0 || ! $this->schemaReady()) {
            return;
        }

        $rootId = $this->rootPaymentId($paymentId);
        if ($rootId <= 0) {
            return;
        }

        $context = $this->context($rootId, true);
        if ($context === null) {
            return;
        }

        $this->pendingRootPaymentIds[$rootId] = true;

        $accountId = (int) ($accountTransaction->account_id ?? 0);
        $targetType = null;
        if ($accountId === $context->accounts_payable_id) {
            $targetType = 'debit';
        } elseif ($accountId === $context->payment_account_id) {
            $targetType = 'credit';
        }

        if ($targetType === null || strtolower((string) ($accountTransaction->type ?? '')) === $targetType) {
            return;
        }

        $this->busy = true;
        try {
            DB::table('account_transactions')
                ->where('id', $rowId)
                ->update($this->withUpdatedAt(['type' => $targetType]));

            if (method_exists($accountTransaction, 'setAttribute')) {
                $accountTransaction->setAttribute('type', $targetType);
            } else {
                $accountTransaction->type = $targetType;
            }
        } finally {
            $this->busy = false;
        }
    }

    /**
     * Called by the service provider at Laravel termination.  By this point the
     * shared payment controller has committed its normal rows, so any genuinely
     * missing leg can be added without creating a duplicate.
     */
    public function flushPending(): void
    {
        if ($this->busy || $this->pendingRootPaymentIds === []) {
            return;
        }

        $ids = array_keys($this->pendingRootPaymentIds);
        $this->pendingRootPaymentIds = [];

        foreach ($ids as $rootId) {
            try {
                $this->ensurePair((int) $rootId, true);
            } catch (\Throwable $e) {
                Log::error('Supplier Pay Due double-entry finalisation failed.', [
                    'transaction_payment_id' => (int) $rootId,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Historical repair is intentionally conservative.  Only SLP-referenced
     * Supplier root payments are repairable without guessing whether an older
     * root payment represented Pay Due or Purchase Return.
     *
     * @return array{scanned:int,fixed:int,skipped:int}
     */
    public function repairHistorical(?int $businessId = null): array
    {
        $stats = ['scanned' => 0, 'fixed' => 0, 'skipped' => 0];
        if (! $this->schemaReady()) {
            return $stats;
        }

        $query = DB::table('transaction_payments as tp')
            ->join('contacts as c', 'c.id', '=', 'tp.payment_for')
            ->whereIn('c.type', ['supplier', 'both'])
            ->whereNull('tp.transaction_id')
            ->where(function ($q) {
                $q->whereNull('tp.parent_id')->orWhere('tp.parent_id', 0);
            })
            ->where('tp.payment_ref_no', 'like', 'SLP%');

        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $query->whereNull('tp.deleted_at');
        }
        if (! empty($businessId)) {
            $query->where(function ($q) use ($businessId) {
                $q->where('tp.business_id', $businessId)
                    ->orWhere(function ($qq) use ($businessId) {
                        $qq->whereNull('tp.business_id')->where('c.business_id', $businessId);
                    });
            });
        }

        foreach ($query->orderBy('tp.id')->pluck('tp.id') as $paymentId) {
            $stats['scanned']++;
            $context = $this->context((int) $paymentId, false);
            if ($context === null) {
                $stats['skipped']++;
                continue;
            }

            $changed = $this->ensurePair((int) $paymentId, false);
            if ($changed > 0) {
                $stats['fixed'] += $changed;
            }
        }

        return $stats;
    }

    private function ensurePair(int $rootPaymentId, bool $requireLiveRequest): int
    {
        if ($this->busy) {
            return 0;
        }

        $context = $this->context($rootPaymentId, $requireLiveRequest);
        if ($context === null) {
            return 0;
        }

        $this->busy = true;
        try {
            $changed = 0;

            if ((int) ($context->stored_payment_account_id ?? 0) !== (int) $context->payment_account_id
                && empty($context->post_dated_cheque)
                && empty($context->update_post_dated_cheque)) {
                $changed += DB::table('transaction_payments')
                    ->where(function ($q) use ($rootPaymentId) {
                        $q->where('id', $rootPaymentId);
                        if (Schema::hasColumn('transaction_payments', 'parent_id')) {
                            $q->orWhere('parent_id', $rootPaymentId);
                        }
                    })
                    ->update(['account_id' => $context->payment_account_id]);
            }

            $rows = $this->ledgerRows($rootPaymentId);

            $changed += $this->ensureLeg(
                $rows,
                $context,
                $context->payment_account_id,
                'credit'
            );

            // Refresh because the first leg may have repurposed or created a row.
            $rows = $this->ledgerRows($rootPaymentId);
            $changed += $this->ensureLeg(
                $rows,
                $context,
                $context->accounts_payable_id,
                'debit'
            );

            // Remove only exact duplicates for the same payment/account/type/amount.
            $changed += $this->removeExactDuplicates($rootPaymentId, $context->payment_account_id, 'credit', $context->amount);
            $changed += $this->removeExactDuplicates($rootPaymentId, $context->accounts_payable_id, 'debit', $context->amount);

            if ($changed > 0) {
                Log::info('Supplier Pay Due double-entry normalised.', [
                    'transaction_payment_id' => $rootPaymentId,
                    'supplier_id' => $context->supplier_id,
                    'business_id' => $context->business_id,
                    'amount' => $context->amount,
                    'debit_account_id' => $context->accounts_payable_id,
                    'credit_account_id' => $context->payment_account_id,
                    'changed_rows' => $changed,
                ]);
            }

            return $changed;
        } finally {
            $this->busy = false;
        }
    }

    private function ensureLeg($rows, object $context, int $targetAccountId, string $targetType): int
    {
        $amountKey = $this->amountKey($context->amount);

        $matchingAccountRows = $rows->filter(function ($row) use ($targetAccountId, $amountKey) {
            return (int) ($row->account_id ?? 0) === $targetAccountId
                && $this->amountKey((float) ($row->amount ?? 0)) === $amountKey;
        })->values();

        if ($matchingAccountRows->isNotEmpty()) {
            $row = $matchingAccountRows->first();
            if (strtolower((string) ($row->type ?? '')) !== $targetType) {
                return DB::table('account_transactions')
                    ->where('id', (int) $row->id)
                    ->update($this->withUpdatedAt(['type' => $targetType]));
            }
            return 0;
        }

        // If the shared listener used Cash or another fallback account, reuse
        // that same payment-linked leg instead of adding a third ledger row.
        $candidate = $rows->first(function ($row) use ($context, $targetType, $amountKey) {
            if ($this->amountKey((float) ($row->amount ?? 0)) !== $amountKey) {
                return false;
            }

            $rowType = strtolower((string) ($row->type ?? ''));
            $accountId = (int) ($row->account_id ?? 0);

            if ($targetType === 'credit') {
                return $rowType === 'credit'
                    && $accountId !== $context->accounts_payable_id
                    && $accountId !== $context->payment_account_id;
            }

            return $rowType === 'debit'
                && $accountId !== $context->payment_account_id
                && $accountId !== $context->accounts_payable_id;
        });

        if ($candidate) {
            return DB::table('account_transactions')
                ->where('id', (int) $candidate->id)
                ->update($this->withUpdatedAt([
                    'account_id' => $targetAccountId,
                    'type' => $targetType,
                    'operation_date' => $context->paid_on,
                ]));
        }

        $data = [
            'business_id' => $context->business_id,
            'contact_id' => $context->supplier_id,
            'amount' => $context->amount,
            'account_id' => $targetAccountId,
            'type' => $targetType,
            'sub_type' => 'payment',
            'operation_date' => $context->paid_on,
            'created_by' => $context->created_by,
            'transaction_id' => null,
            'transaction_payment_id' => $context->payment_id,
            'note' => $context->note,
            'post_dated_cheque' => $context->post_dated_cheque,
            'update_post_dated_cheque' => $context->update_post_dated_cheque,
            'related_account_id' => $context->related_account_id,
            'skip_account_fallback' => true,
        ];

        CoreAccountTransaction::createAccountTransaction($data);

        return 1;
    }

    private function removeExactDuplicates(int $paymentId, int $accountId, string $type, float $amount): int
    {
        $query = DB::table('account_transactions')
            ->where('transaction_payment_id', $paymentId)
            ->where('account_id', $accountId)
            ->where('type', $type);

        if (Schema::hasColumn('account_transactions', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $rows = $query->orderBy('id')->get(['id', 'amount']);
        $sameAmount = $rows->filter(fn ($row) => $this->amountKey((float) $row->amount) === $this->amountKey($amount))->values();
        if ($sameAmount->count() <= 1) {
            return 0;
        }

        $changed = 0;
        foreach ($sameAmount->slice(1) as $duplicate) {
            $changed += $this->softDeleteAccountTransaction((int) $duplicate->id);
        }

        return $changed;
    }

    private function softDeleteAccountTransaction(int $id): int
    {
        $query = DB::table('account_transactions')->where('id', $id);
        if (Schema::hasColumn('account_transactions', 'deleted_at')) {
            return $query->update($this->withUpdatedAt(['deleted_at' => now()]));
        }

        return $query->delete();
    }

    private function ledgerRows(int $rootPaymentId)
    {
        $query = DB::table('account_transactions')
            ->where('transaction_payment_id', $rootPaymentId);

        if (Schema::hasColumn('account_transactions', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy('id')->get([
            'id', 'account_id', 'type', 'amount', 'operation_date', 'contact_id',
        ]);
    }

    private function context(int $rootPaymentId, bool $requireLiveRequest): ?object
    {
        if (! $this->schemaReady()) {
            return null;
        }

        if ($requireLiveRequest && ! $this->isLiveSupplierPayDueRequest()) {
            return null;
        }

        $paymentQuery = DB::table('transaction_payments')->where('id', $rootPaymentId);
        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $paymentQuery->whereNull('deleted_at');
        }
        $payment = $paymentQuery->first();
        if (! $payment || ! empty($payment->parent_id)) {
            return null;
        }

        // Supplier Pay Due roots are not tied directly to an invoice transaction.
        if (! empty($payment->transaction_id)) {
            return null;
        }

        $supplierId = (int) ($payment->payment_for ?? 0);
        if ($supplierId <= 0) {
            return null;
        }

        $supplier = DB::table('contacts')->where('id', $supplierId)->first(['id', 'type', 'business_id']);
        if (! $supplier || ! in_array(strtolower((string) $supplier->type), ['supplier', 'both'], true)) {
            return null;
        }

        if (! $requireLiveRequest) {
            $reference = strtoupper(trim((string) ($payment->payment_ref_no ?? '')));
            if (strpos($reference, 'SLP') !== 0) {
                return null;
            }
        }

        $businessId = (int) ($payment->business_id ?? 0);
        if ($businessId <= 0) {
            $businessId = (int) ($supplier->business_id ?? 0);
        }
        if ($businessId <= 0) {
            return null;
        }

        $storedPaymentAccountId = (int) ($payment->account_id ?? 0);
        $paymentAccountId = $storedPaymentAccountId;

        // For an ordinary live Supplier Pay Due, the submitted account_id is
        // the user's selected payment account and is the strongest source.
        // Do not override Post Dated Cheque flows: there the root intentionally
        // points to Issued Post Dated Cheques while related_account_id preserves
        // the selected bank account for the later transfer.
        $isPostDated = ! empty($payment->post_dated_cheque) || ! empty($payment->update_post_dated_cheque);
        if ($requireLiveRequest && ! $isPostDated && function_exists('request')) {
            $requestAccountId = request()->input('account_id');
            $requestAccountId = is_numeric($requestAccountId) ? (int) $requestAccountId : 0;
            if ($requestAccountId > 0 && $this->validAccount($requestAccountId, $businessId)) {
                $paymentAccountId = $requestAccountId;
            }
        }

        if ($paymentAccountId <= 0 || ! $this->validAccount($paymentAccountId, $businessId)) {
            return null;
        }

        $accountsPayableId = $this->resolveAccountsPayableAccountId($businessId);
        if ($accountsPayableId <= 0 || $accountsPayableId === $paymentAccountId) {
            return null;
        }

        $amount = abs((float) ($payment->amount ?? 0));
        if ($amount <= 0) {
            return null;
        }

        $paidOn = ! empty($payment->paid_on) ? (string) $payment->paid_on : now()->format('Y-m-d H:i:s');

        return (object) [
            'payment_id' => (int) $payment->id,
            'supplier_id' => $supplierId,
            'business_id' => $businessId,
            'payment_account_id' => $paymentAccountId,
            'stored_payment_account_id' => $storedPaymentAccountId,
            'accounts_payable_id' => $accountsPayableId,
            'amount' => $amount,
            'paid_on' => $paidOn,
            'created_by' => (int) ($payment->created_by ?? 0),
            'note' => ! empty($payment->note) ? (string) $payment->note : 'Pay Due Amount',
            'post_dated_cheque' => (int) ($payment->post_dated_cheque ?? 0),
            'update_post_dated_cheque' => (int) ($payment->update_post_dated_cheque ?? 0),
            'related_account_id' => (int) ($payment->related_account_id ?? 0),
        ];
    }

    private function rootPaymentId(int $paymentId): int
    {
        $payment = DB::table('transaction_payments')->where('id', $paymentId)->first(['id', 'parent_id']);
        if (! $payment) {
            return 0;
        }

        return ! empty($payment->parent_id) ? (int) $payment->parent_id : (int) $payment->id;
    }

    private function isLiveSupplierPayDueRequest(): bool
    {
        if (! function_exists('request')) {
            return false;
        }

        $marker = strtolower(trim((string) request()->input('supplier_payment_context', '')));
        if ($marker === 'pay_due') {
            return true;
        }

        $dueType = strtolower(trim((string) request()->input('due_payment_type', '')));
        if ($dueType === 'purchase') {
            return true;
        }

        return false;
    }

    private function resolveAccountsPayableAccountId(int $businessId): int
    {
        $query = DB::table('accounts')
            ->where('business_id', $businessId)
            ->where(function ($q) {
                $q->whereRaw('LOWER(TRIM(name)) = ?', ['accounts payable'])
                    ->orWhereRaw('LOWER(TRIM(name)) = ?', ['account payable']);
            });

        if (Schema::hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if (Schema::hasColumn('accounts', 'is_closed')) {
            $query->where('is_closed', 0);
        }

        return (int) $query->orderBy('id')->value('id');
    }

    private function validAccount(int $accountId, int $businessId): bool
    {
        $query = DB::table('accounts')->where('id', $accountId)->where('business_id', $businessId);
        if (Schema::hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if (Schema::hasColumn('accounts', 'is_closed')) {
            $query->where('is_closed', 0);
        }

        return $query->exists();
    }

    private function schemaReady(): bool
    {
        try {
            return Schema::hasTable('transaction_payments')
                && Schema::hasTable('contacts')
                && Schema::hasTable('accounts')
                && Schema::hasTable('account_transactions')
                && Schema::hasColumn('transaction_payments', 'payment_for')
                && Schema::hasColumn('transaction_payments', 'account_id')
                && Schema::hasColumn('account_transactions', 'transaction_payment_id');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function withUpdatedAt(array $payload): array
    {
        if (Schema::hasColumn('account_transactions', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        return $payload;
    }

    private function amountKey(float $amount): string
    {
        return number_format(abs($amount), 4, '.', '');
    }
}

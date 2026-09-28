<?php

namespace Modules\Suppliers\Services\Financial;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * S717 v4 - Supplier Advance Payment double-entry integrity.
 *
 * Why this belongs in Suppliers as well as Finance:
 * the shared ERP payment flow may create a root transaction_payments row and
 * allocation children.  The bank account selected by the user can live on the
 * root row while a child is saved with account_id = NULL.  The legacy account
 * listener then falls back to Cash.  This guard propagates the selected account
 * before the account entry is created and repairs any linked Cash row after it
 * is created.  It never changes ordinary Supplier Pay Due payments.
 */
class SupplierAdvanceBankTransferPostingGuard
{
    private static $busy = false;

    /**
     * Called from TransactionPayment::saving.  When the current request comes
     * from Supplier > Advance Payment and uses Bank Transfer, put the selected
     * non-Cash account on every payment row before legacy accounting listeners
     * see it.
     */
    public function preparePayment(object $payment): void
    {
        if (self::$busy || ! $this->isAdvanceRequest()) {
            return;
        }

        $businessId = $this->businessIdFromPayment($payment);
        if ($businessId <= 0) {
            return;
        }

        $parent = null;
        if (Schema::hasTable('transaction_payments')
            && Schema::hasColumn('transaction_payments', 'parent_id')
            && ! empty($payment->parent_id)) {
            $parent = DB::table('transaction_payments')->where('id', (int) $payment->parent_id)->first();
        }

        $method = $this->paymentMethod($payment);
        if ($method === '' && $parent) {
            $method = $this->normalizeMethod((string) ($parent->method ?? ''));
        }
        $requireNonCash = $this->isBankTransferMethod($method);

        $accountId = $this->selectedAccountFromRequest($businessId, $requireNonCash);
        if ($accountId <= 0) {
            $accountId = (int) ($payment->account_id ?? 0);
        }
        if ($accountId <= 0 && $parent) {
            $accountId = (int) ($parent->account_id ?? 0);
        }

        if (! $this->isValidPaymentAccount($accountId, $businessId, $requireNonCash)) {
            return;
        }

        if (method_exists($payment, 'setAttribute')) {
            $payment->setAttribute('account_id', $accountId);
        } else {
            $payment->account_id = $accountId;
        }
    }
    public function normalizePayment(object $payment): int
    {
        $paymentId = (int) ($payment->id ?? 0);
        if ($paymentId <= 0 || self::$busy || ! $this->schemaReady()) {
            return 0;
        }

        self::$busy = true;
        try {
            $context = $this->paymentFamilyContext($paymentId);
            if (! $context || ! $this->isEligibleContext($context)) {
                return 0;
            }

            $expectedAccountId = $this->resolveExpectedAccountId($context);
            if ($expectedAccountId <= 0) {
                return 0;
            }

            if (method_exists($payment, 'setAttribute')) {
                $payment->setAttribute('account_id', $expectedAccountId);
            }

            $changed = 0;
            $changed += $this->syncPaymentFamilyAccounts($context, $expectedAccountId);
            $changed += $this->repairFamilyAccountTransactions($context, $expectedAccountId);

            return $changed;
        } finally {
            self::$busy = false;
        }
    }

    /**
     * Called when an AccountTransaction has just been created.  This second
     * pass covers listener ordering: even if the legacy listener created Cash
     * before the payment saved hook ran, the Cash row is corrected immediately.
     */
    public function normalizeAccountTransaction(object $accountTransaction): int
    {
        $paymentId = (int) ($accountTransaction->transaction_payment_id ?? 0);
        if ($paymentId <= 0 || self::$busy) {
            return 0;
        }

        $payment = DB::table('transaction_payments')->where('id', $paymentId)->first();
        if (! $payment) {
            return 0;
        }

        return $this->normalizePayment($payment);
    }

    /**
     * Plug-and-play historical S717 repair for the active tenant/business.
     * This is called by Suppliers middleware after SetSessionData and
     * tenant.context have selected the correct database. It is idempotent and
     * never chooses a default bank account.
     *
     * @return array{scanned:int,repairable:int,fixed:int,unresolved:int}
     */
    public function repairHistoricalOnce(?int $businessId = null): array
    {
        $empty = ['scanned' => 0, 'repairable' => 0, 'fixed' => 0, 'unresolved' => 0];
        if (! $this->schemaReady()) {
            return $empty;
        }

        if (! $businessId && function_exists('session')) {
            $businessId = (int) session('user.business_id', session('business.id', 0));
        }
        $businessId = (int) $businessId;
        if ($businessId <= 0) {
            return $empty;
        }

        try {
            $database = (string) DB::connection()->getDatabaseName();
        } catch (\Throwable $e) {
            $database = 'unknown';
        }

        $cacheKey = 'suppliers:s717:auto-repair:v4:' . sha1($database . '|' . $businessId);
        try {
            if (Cache::get($cacheKey)) {
                return $empty;
            }
        } catch (\Throwable $e) {
            // Cache failure must not affect Supplier pages.
        }

        try {
            $stats = $this->repairHistorical($businessId);
            try {
                if (($stats['unresolved'] ?? 0) === 0) {
                    Cache::forever($cacheKey, true);
                } else {
                    Cache::put($cacheKey, true, 21600);
                }
            } catch (\Throwable $e) {
                // Safe to re-check later if cache storage is unavailable.
            }
            return $stats;
        } catch (\Throwable $e) {
            Log::warning('Suppliers S717 automatic historical repair skipped safely', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
            ]);
            return $empty;
        }
    }

    /**
     * Repair only positively identifiable historical Supplier Advance Payment
     * families. Rows without an exact selected payment account are left untouched and
     * reported as unresolved.
     *
     * @return array{scanned:int,repairable:int,fixed:int,unresolved:int}
     */
    public function repairHistorical(?int $businessId = null): array
    {
        $stats = ['scanned' => 0, 'repairable' => 0, 'fixed' => 0, 'unresolved' => 0];
        if (! $this->schemaReady()) {
            return $stats;
        }

        $hasParent = Schema::hasColumn('transaction_payments', 'parent_id');
        $query = DB::table('transaction_payments as tp')
            ->leftJoin('transactions as child_t', 'child_t.id', '=', 'tp.transaction_id');

        if ($hasParent) {
            $query->leftJoin('transaction_payments as parent_tp', 'parent_tp.id', '=', 'tp.parent_id')
                ->leftJoin('transactions as parent_t', 'parent_t.id', '=', 'parent_tp.transaction_id');
            $contactSql = 'COALESCE(parent_tp.payment_for, tp.payment_for, child_t.contact_id, parent_t.contact_id)';
        } else {
            $contactSql = 'COALESCE(tp.payment_for, child_t.contact_id)';
        }

        $query->join('contacts as c', function ($join) use ($contactSql) {
                $join->on('c.id', '=', DB::raw($contactSql));
            })
            ->whereIn('c.type', ['supplier', 'both'])
            ->where(function ($advance) use ($hasParent) {
                $advance->where('child_t.type', 'advance_payment');
                if ($hasParent) {
                    $advance->orWhere('parent_t.type', 'advance_payment');
                }
            });

        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $query->whereNull('tp.deleted_at');
        }
        if ($businessId) {
            $query->where('tp.business_id', (int) $businessId);
        }

        $paymentIds = $query->distinct()->orderBy('tp.id')->pluck('tp.id');
        $seenRoots = [];

        foreach ($paymentIds as $paymentId) {
            $context = $this->paymentFamilyContext((int) $paymentId);
            if (! $context || ! $this->isEligibleContext($context)) {
                continue;
            }

            $rootId = (int) ($context->root->id ?? 0);
            if ($rootId > 0 && isset($seenRoots[$rootId])) {
                continue;
            }
            if ($rootId > 0) {
                $seenRoots[$rootId] = true;
            }

            $stats['scanned']++;
            $expected = $this->resolveExpectedAccountId($context);
            $apId = $this->resolveAccountsPayableAccountId((int) $context->business_id);
            if ($expected <= 0 || $apId <= 0) {
                $stats['unresolved']++;
                continue;
            }

            $wrong = $this->countRepairableFamilyRows($context, $expected);
            if ($wrong <= 0) {
                continue;
            }

            $stats['repairable'] += $wrong;
            $this->syncPaymentFamilyAccounts($context, $expected);
            $stats['fixed'] += $this->repairFamilyAccountTransactions($context, $expected);
        }

        return $stats;
    }

    private function paymentFamilyContext(int $paymentId): ?object
    {
        $hasParent = Schema::hasColumn('transaction_payments', 'parent_id');

        $payment = DB::table('transaction_payments')->where('id', $paymentId)->first();
        if (! $payment) {
            return null;
        }

        $root = $payment;
        if ($hasParent && ! empty($payment->parent_id)) {
            $candidate = DB::table('transaction_payments')->where('id', (int) $payment->parent_id)->first();
            if ($candidate) {
                $root = $candidate;
            }
        }

        $familyIds = [(int) $root->id];
        if ($hasParent) {
            $children = DB::table('transaction_payments')
                ->where('parent_id', (int) $root->id)
                ->pluck('id')
                ->map(static fn ($id) => (int) $id)
                ->all();
            $familyIds = array_values(array_unique(array_merge($familyIds, $children)));
        }

        if (! in_array($paymentId, $familyIds, true)) {
            $familyIds[] = $paymentId;
        }

        $family = DB::table('transaction_payments')
            ->whereIn('id', $familyIds)
            ->get()
            ->keyBy('id');

        $transactionIds = $family->pluck('transaction_id')
            ->filter()
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $advanceTransaction = null;
        if ($transactionIds !== []) {
            $advanceTransaction = DB::table('transactions')
                ->whereIn('id', $transactionIds)
                ->where('type', 'advance_payment')
                ->first();
        }

        $supplierId = (int) ($root->payment_for ?? 0);
        if ($supplierId <= 0) {
            foreach ($family as $row) {
                if (! empty($row->payment_for)) {
                    $supplierId = (int) $row->payment_for;
                    break;
                }
            }
        }
        if ($supplierId <= 0 && $advanceTransaction) {
            $supplierId = (int) ($advanceTransaction->contact_id ?? 0);
        }

        $supplier = $supplierId > 0
            ? DB::table('contacts')->where('id', $supplierId)->first(['id', 'type', 'business_id'])
            : null;

        $businessId = (int) ($root->business_id ?? 0);
        if ($businessId <= 0 && $supplier) {
            $businessId = (int) ($supplier->business_id ?? 0);
        }
        if ($businessId <= 0 && $advanceTransaction) {
            $businessId = (int) ($advanceTransaction->business_id ?? 0);
        }

        $method = $this->normalizeMethod((string) ($root->method ?? ''));
        if ($method === '') {
            foreach ($family as $row) {
                $candidateMethod = $this->normalizeMethod((string) ($row->method ?? ''));
                if ($candidateMethod !== '') {
                    $method = $candidateMethod;
                    break;
                }
            }
        }
        if ($this->isAdvanceRequest()) {
            $requestMethod = $this->paymentMethod($payment);
            if ($requestMethod !== '') {
                $method = $requestMethod;
            }
        }

        $context = (object) [
            'root' => $root,
            'family' => $family,
            'family_ids' => $familyIds,
            'transaction_ids' => $transactionIds,
            'advance_transaction' => $advanceTransaction,
            'supplier' => $supplier,
            'supplier_id' => $supplierId,
            'business_id' => $businessId,
            'method' => $method,
            // The explicit request marker is used only for the live request.
            // Historical repair relies on transactions.type=advance_payment.
            'advance_request' => $this->isAdvanceRequest(),
        ];

        return $context;
    }

    private function isEligibleContext(object $context): bool
    {
        $supplierType = strtolower((string) ($context->supplier->type ?? ''));
        $isSupplier = in_array($supplierType, ['supplier', 'both'], true);
        $isAdvance = ! empty($context->advance_transaction) || ! empty($context->advance_request);

        return $isSupplier && $isAdvance;
    }
    private function resolveExpectedAccountId(object $context): int
    {
        $businessId = (int) ($context->business_id ?? 0);
        if ($businessId <= 0) {
            return 0;
        }

        $requireNonCash = $this->isBankTransferMethod((string) ($context->method ?? ''));

        if (! empty($context->advance_request)) {
            $requestAccount = $this->selectedAccountFromRequest($businessId, $requireNonCash);
            if ($this->isValidPaymentAccount($requestAccount, $businessId, $requireNonCash)) {
                return $requestAccount;
            }
        }

        $rootAccount = (int) ($context->root->account_id ?? 0);
        if ($this->isValidPaymentAccount($rootAccount, $businessId, $requireNonCash)) {
            return $rootAccount;
        }

        foreach ($context->family as $row) {
            $candidate = (int) ($row->account_id ?? 0);
            if ($this->isValidPaymentAccount($candidate, $businessId, $requireNonCash)) {
                return $candidate;
            }
        }

        if (Schema::hasColumn('transaction_payments', 'related_account_id')) {
            $rootRelated = (int) ($context->root->related_account_id ?? 0);
            if ($this->isValidPaymentAccount($rootRelated, $businessId, $requireNonCash)) {
                return $rootRelated;
            }
            foreach ($context->family as $row) {
                $candidate = (int) ($row->related_account_id ?? 0);
                if ($this->isValidPaymentAccount($candidate, $businessId, $requireNonCash)) {
                    return $candidate;
                }
            }
        }

        // Historical compatibility: an existing credit leg can identify the
        // selected payment-method account if account_id was lost from the
        // payment row. Accounts Payable is never a payment-method account.
        if (Schema::hasTable('account_transactions')) {
            $apId = $this->resolveAccountsPayableAccountId($businessId);
            $query = DB::table('account_transactions as at')
                ->where('at.business_id', $businessId)
                ->where('at.type', 'credit')
                ->where(function ($link) use ($context) {
                    $link->whereIn('at.transaction_payment_id', $context->family_ids);
                    if ($context->transaction_ids !== []) {
                        $link->orWhereIn('at.transaction_id', $context->transaction_ids);
                    }
                });
            if (Schema::hasColumn('account_transactions', 'deleted_at')) {
                $query->whereNull('at.deleted_at');
            }
            foreach ($query->pluck('at.account_id') as $candidate) {
                $candidate = (int) $candidate;
                if ($candidate !== $apId && $this->isValidPaymentAccount($candidate, $businessId, $requireNonCash)) {
                    return $candidate;
                }
            }
        }

        return 0;
    }
    private function syncPaymentFamilyAccounts(object $context, int $expectedAccountId): int
    {
        $businessId = (int) $context->business_id;
        $requireNonCash = $this->isBankTransferMethod((string) ($context->method ?? ''));
        if (! $this->isValidPaymentAccount($expectedAccountId, $businessId, $requireNonCash)) {
            return 0;
        }

        $changed = 0;
        foreach ($context->family as $row) {
            $current = (int) ($row->account_id ?? 0);
            if ($current === $expectedAccountId) {
                continue;
            }
            if ($current > 0 && ! $this->isCashAccount($current, $businessId)) {
                continue;
            }
            if ($current > 0 && $expectedAccountId === $current) {
                continue;
            }

            $changed += DB::table('transaction_payments')
                ->where('id', (int) $row->id)
                ->update(['account_id' => $expectedAccountId]);
        }
        return $changed;
    }
    private function familyAccountRows(object $context)
    {
        $query = DB::table('account_transactions as at')
            ->leftJoin('accounts as a', 'a.id', '=', 'at.account_id')
            ->where('at.business_id', (int) $context->business_id)
            ->where(function ($link) use ($context) {
                $link->whereIn('at.transaction_payment_id', $context->family_ids);
                if ($context->transaction_ids !== []) {
                    $link->orWhere(function ($transactionLink) use ($context) {
                        $transactionLink->whereIn('at.transaction_id', $context->transaction_ids)
                            ->whereNull('at.transaction_payment_id');
                    });
                }
            });
        if (Schema::hasColumn('account_transactions', 'deleted_at')) {
            $query->whereNull('at.deleted_at');
        }
        return $query->select([
            'at.id', 'at.account_id', 'at.transaction_id', 'at.transaction_payment_id',
            'at.type', 'at.sub_type', 'at.amount', 'at.operation_date', 'a.name as account_name',
        ])->get();
    }

    private function countRepairableFamilyRows(object $context, int $expectedAccountId): int
    {
        $apId = $this->resolveAccountsPayableAccountId((int) $context->business_id);
        if ($apId <= 0) {
            return 0;
        }
        return $this->familyAccountRows($context)->filter(function ($row) use ($context, $expectedAccountId, $apId) {
            return $this->repairTargetForRow($row, $context, $expectedAccountId, $apId) > 0;
        })->count();
    }
    private function repairFamilyAccountTransactions(object $context, int $expectedAccountId): int
    {
        $businessId = (int) $context->business_id;
        $apId = $this->resolveAccountsPayableAccountId($businessId);
        if ($apId <= 0 || $expectedAccountId <= 0) {
            return 0;
        }

        $rows = $this->familyAccountRows($context);
        $targetSignatures = [];
        foreach ($rows as $row) {
            $type = strtolower((string) ($row->type ?? ''));
            if (! in_array($type, ['debit', 'credit'], true)) {
                continue;
            }
            $target = $type === 'debit' ? $apId : $expectedAccountId;
            if ((int) ($row->account_id ?? 0) === $target) {
                $targetSignatures[$this->rowSignature($row, $type)] = true;
            }
        }

        $changed = 0;
        foreach ($rows as $row) {
            $type = strtolower((string) ($row->type ?? ''));
            if (! in_array($type, ['debit', 'credit'], true)) {
                continue;
            }
            $target = $this->repairTargetForRow($row, $context, $expectedAccountId, $apId);
            if ($target <= 0) {
                continue;
            }
            $signature = $this->rowSignature($row, $type);
            if (! empty($targetSignatures[$signature])) {
                $changed += $this->removeAccountTransaction((int) $row->id);
                continue;
            }
            $payload = ['account_id' => $target];
            if (Schema::hasColumn('account_transactions', 'updated_at')) {
                $payload['updated_at'] = now();
            }
            $changed += DB::table('account_transactions')->where('id', (int) $row->id)->update($payload);
            $targetSignatures[$signature] = true;
        }

        if ($changed > 0) {
            Log::info('Suppliers corrected advance payment double-entry posting', [
                'supplier_id' => (int) $context->supplier_id,
                'business_id' => $businessId,
                'payment_ids' => $context->family_ids,
                'payment_account_id' => $expectedAccountId,
                'accounts_payable_id' => $apId,
                'changed_rows' => $changed,
            ]);
        }
        return $changed;
    }
    private function selectedAccountFromRequest(int $businessId, bool $requireNonCash = false): int
    {
        if (! function_exists('request')) {
            return 0;
        }
        foreach ([
            request()->input('account_id'), request()->input('payment.account_id'),
            request()->input('bank_account_id'), request()->input('bank_transfer_account_id'),
            request()->input('payment_account_id'), request()->input('payment_account'),
        ] as $candidate) {
            $id = is_numeric($candidate) ? (int) $candidate : 0;
            if ($this->isValidPaymentAccount($id, $businessId, $requireNonCash)) {
                return $id;
            }
        }
        return 0;
    }
    private function paymentMethod(object $payment): string
    {
        $method = (string) ($payment->method ?? '');
        if (function_exists('request')) {
            $requestMethod = (string) request()->input('method', request()->input('payment.method', ''));
            if (trim($requestMethod) !== '') {
                $method = $requestMethod;
            }
        }
        return $this->normalizeMethod($method);
    }
    private function businessIdFromPayment(object $payment): int
    {
        $businessId = (int) ($payment->business_id ?? 0);
        if ($businessId <= 0 && function_exists('session')) {
            $businessId = (int) session('user.business_id', session('business.id', 0));
        }
        return $businessId;
    }

    private function isAdvanceRequest(): bool
    {
        if (! function_exists('request')) {
            return false;
        }

        $marker = strtolower(trim((string) request()->input('supplier_payment_context', '')));
        if ($marker === 'advance_payment') {
            return true;
        }

        // The shared ERP currently opens/saves this workflow through the
        // /payments/advance-payment/... endpoint.  Route detection keeps the
        // source-side guard working even if a published JS asset is stale.
        try {
            if (request()->is('payments/advance-payment*')) {
                return true;
            }
        } catch (\Throwable $e) {
            // Continue with submitted-field compatibility checks below.
        }

        return strtolower(trim((string) request()->input('type', ''))) === 'advance_payment';
    }

    private function normalizeMethod(string $method): string
    {
        $method = strtolower(trim($method));
        return str_replace(['-', ' '], '_', $method);
    }

    private function isBankTransferMethod(string $method): bool
    {
        return in_array($this->normalizeMethod($method), ['bank_transfer', 'banktransfer', 'bank'], true);
    }

    private function isValidPaymentAccount(int $accountId, int $businessId, bool $requireNonCash = false): bool
    {
        if ($accountId <= 0 || $businessId <= 0 || ! Schema::hasTable('accounts')) {
            return false;
        }
        $query = DB::table('accounts')->where('id', $accountId)->where('business_id', $businessId);
        if (Schema::hasColumn('accounts', 'deleted_at')) { $query->whereNull('deleted_at'); }
        if (Schema::hasColumn('accounts', 'is_closed')) { $query->where('is_closed', 0); }
        $account = $query->first(['id', 'name']);
        if (! $account || $this->isAccountsPayableName((string) $account->name)) {
            return false;
        }
        return ! $requireNonCash || strtolower(trim((string) $account->name)) !== 'cash';
    }
    private function resolveAccountsPayableAccountId(int $businessId): int
    {
        if ($businessId <= 0 || ! Schema::hasTable('accounts')) { return 0; }
        $query = DB::table('accounts')->where('business_id', $businessId)
            ->where(function ($q) {
                $q->whereRaw('LOWER(TRIM(name)) = ?', ['accounts payable'])
                    ->orWhereRaw('LOWER(TRIM(name)) = ?', ['account payable']);
            });
        if (Schema::hasColumn('accounts', 'deleted_at')) { $query->whereNull('deleted_at'); }
        if (Schema::hasColumn('accounts', 'is_closed')) { $query->where('is_closed', 0); }
        return (int) $query->orderBy('id')->value('id');
    }

    private function isAccountsPayableName(string $name): bool
    {
        return in_array(strtolower(trim($name)), ['accounts payable', 'account payable'], true);
    }

    private function repairTargetForRow(object $row, object $context, int $paymentAccountId, int $apId): int
    {
        $type = strtolower((string) ($row->type ?? ''));
        $current = (int) ($row->account_id ?? 0);
        $businessId = (int) $context->business_id;
        if (! in_array($type, ['debit', 'credit'], true)) { return 0; }

        if ($type === 'debit') {
            if ($current === $apId) { return 0; }
            if ($current <= 0 || $current === $paymentAccountId || $this->isCashAccount($current, $businessId)) {
                return $apId;
            }
            return 0;
        }

        if ($current === $paymentAccountId) { return 0; }
        if ($current <= 0 || $current === $apId || ($paymentAccountId !== $current && $this->isCashAccount($current, $businessId))) {
            return $paymentAccountId;
        }
        return 0;
    }

    private function rowSignature(object $row, string $type): string
    {
        $transactionId = (int) ($row->transaction_id ?? 0);
        $paymentId = (int) ($row->transaction_payment_id ?? 0);
        $link = $transactionId > 0 ? 't:' . $transactionId : 'p:' . $paymentId;

        return strtolower($type) . '|' . $link . '|' . $this->amountKey((float) ($row->amount ?? 0));
    }

    private function amountKey(float $amount): string
    {
        return number_format(abs($amount), 4, '.', '');
    }

    private function removeAccountTransaction(int $id): int
    {
        $query = DB::table('account_transactions')->where('id', $id);
        if (Schema::hasColumn('account_transactions', 'deleted_at')) {
            $payload = ['deleted_at' => now()];
            if (Schema::hasColumn('account_transactions', 'updated_at')) { $payload['updated_at'] = now(); }
            return $query->update($payload);
        }
        return $query->delete();
    }

    private function isCashAccount(int $accountId, int $businessId): bool
    {
        if ($accountId <= 0 || $businessId <= 0) {
            return false;
        }

        return DB::table('accounts')
            ->where('id', $accountId)
            ->where('business_id', $businessId)
            ->whereRaw('LOWER(TRIM(name)) = ?', ['cash'])
            ->exists();
    }

    private function schemaReady(): bool
    {
        try {
            return Schema::hasTable('transaction_payments')
                && Schema::hasTable('transactions')
                && Schema::hasTable('contacts')
                && Schema::hasTable('accounts')
                && Schema::hasTable('account_transactions')
                && Schema::hasColumn('transaction_payments', 'account_id')
                && Schema::hasColumn('transaction_payments', 'method');
        } catch (\Throwable $e) {
            return false;
        }
    }
}

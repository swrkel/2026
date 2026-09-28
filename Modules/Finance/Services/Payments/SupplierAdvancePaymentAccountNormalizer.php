<?php

namespace Modules\Finance\Services\Payments;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * S717 - Supplier Advance Payment / Bank Transfer account integrity.
 *
 * The shared ERP payment flow can save a root payment and one or more child
 * allocation rows.  The selected bank account may exist only on the root.  A
 * child with account_id = NULL can make the legacy accounting listener fall
 * back to Cash, which creates the exact wrong Cash Account Book entry reported
 * in S717.
 *
 * This normalizer is intentionally narrow:
 *   - Supplier (or Both) contact
 *   - transaction type = advance_payment, OR the explicit live Supplier
 *     advance-payment request marker
 *   - effective payment method = Bank Transfer
 *   - only Cash/empty account rows are changed
 *
 * It never guesses a bank account.  The exact selected non-Cash account must be
 * present on the request, root payment, child payment, or legacy related field.
 */
class SupplierAdvancePaymentAccountNormalizer
{
    private static $busy = false;

    /**
     * Pre-save protection for the live Supplier Advance Payment request.
     * Setting account_id on the model before shared listeners run prevents the
     * Cash fallback rather than repairing it later.
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

        if (! $this->isValidPaymentBusinessAccount($accountId, $businessId, $requireNonCash)) {
            return;
        }

        if (method_exists($payment, 'setAttribute')) {
            $payment->setAttribute('account_id', $accountId);
        } else {
            $payment->account_id = $accountId;
        }
    }
    public function normalize(object $accountTransaction): bool
    {
        $paymentId = (int) ($accountTransaction->transaction_payment_id ?? 0);
        if ($paymentId > 0) {
            return $this->normalizePaymentId($paymentId) > 0;
        }

        $transactionId = (int) ($accountTransaction->transaction_id ?? 0);
        if ($transactionId <= 0 || ! $this->schemaReady()) {
            return false;
        }

        $paymentId = (int) DB::table('transaction_payments')
            ->where('transaction_id', $transactionId)
            ->orderBy('id')
            ->value('id');

        return $paymentId > 0 && $this->normalizePaymentId($paymentId) > 0;
    }

    /**
     * Normalize the root/child payment family containing $paymentId.
     */
    public function normalizePaymentId(int $paymentId): int
    {
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

            $changed = $this->syncPaymentFamilyAccounts($context, $expectedAccountId);
            $changed += $this->repairFamilyAccountTransactions($context, $expectedAccountId);

            return $changed;
        } finally {
            self::$busy = false;
        }
    }

    /**
     * Plug-and-play historical repair.
     *
     * Runs automatically in the active tenant/business context and remembers
     * completion in the configured Laravel cache. No artisan migration or SQL
     * import is required. If some old rows cannot yet be resolved to the exact
     * user-selected non-Cash account, retry after a short interval instead of
     * guessing an account.
     *
     * @return array{scanned:int,repairable:int,fixed:int,unresolved:int}
     */
    public function repairHistoricalOnce(?int $businessId = null): array
    {
        $empty = ['scanned' => 0, 'repairable' => 0, 'fixed' => 0, 'unresolved' => 0];
        if (! $this->schemaReady()) {
            return $empty;
        }

        $businessId = $businessId ?: $this->sessionBusinessId();
        if ($businessId <= 0) {
            return $empty;
        }

        try {
            $database = (string) DB::connection()->getDatabaseName();
        } catch (\Throwable $e) {
            $database = 'unknown';
        }

        $cacheKey = 'finance:s717:auto-repair:v4:' . sha1($database . '|' . $businessId);

        try {
            if (Cache::get($cacheKey)) {
                return $empty;
            }
        } catch (\Throwable $e) {
            // Cache availability must never block Finance or Supplier pages.
        }

        try {
            $stats = $this->repairHistorical($businessId, false);

            try {
                if (($stats['unresolved'] ?? 0) === 0) {
                    Cache::forever($cacheKey, true);
                } else {
                    // Retry unresolved legacy rows later; never guess an account.
                    Cache::put($cacheKey, true, 21600);
                }
            } catch (\Throwable $e) {
                // Repair succeeded; a cache failure only means it may re-check.
            }

            return $stats;
        } catch (\Throwable $e) {
            Log::warning('Finance S717 automatic historical repair skipped safely', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
            ]);

            return $empty;
        }
    }

    private function sessionBusinessId(): int
    {
        if (! function_exists('session')) {
            return 0;
        }

        return (int) session('user.business_id', session('business.id', 0));
    }

    /**
     * Historical repair for previously misposted Supplier Advance Payments. Only families with an actual transactions.type=advance_payment
     * row are eligible; historical rows are never inferred from amount/date.
     *
     * @return array{scanned:int,repairable:int,fixed:int,unresolved:int}
     */
    public function repairHistorical(?int $businessId = null, bool $dryRun = false): array
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
            $query->where('tp.business_id', $businessId);
        }

        $paymentIds = $query->distinct()->orderBy('tp.id')->pluck('tp.id');
        $seenRoots = [];

        foreach ($paymentIds as $paymentId) {
            $context = $this->paymentFamilyContext((int) $paymentId);
            if (! $context || ! $this->isEligibleContext($context, false)) {
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
            $expected = $this->resolveExpectedAccountId($context, false);
            $apId = $this->resolveAccountsPayableAccountId((int) $context->business_id);
            if ($expected <= 0 || $apId <= 0) {
                $stats['unresolved']++;
                continue;
            }

            $wrongCount = $this->countRepairableFamilyRows($context, $expected);
            if ($wrongCount <= 0) {
                continue;
            }

            $stats['repairable'] += $wrongCount;
            if ($dryRun) {
                continue;
            }

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
                $candidate = $this->normalizeMethod((string) ($row->method ?? ''));
                if ($candidate !== '') {
                    $method = $candidate;
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

        return (object) [
            'root' => $root,
            'family' => $family,
            'family_ids' => $familyIds,
            'transaction_ids' => $transactionIds,
            'advance_transaction' => $advanceTransaction,
            'supplier' => $supplier,
            'supplier_id' => $supplierId,
            'business_id' => $businessId,
            'method' => $method,
            'advance_request' => $this->isAdvanceRequest(),
        ];
    }

    private function isEligibleContext(object $context, bool $allowRequestMarker = true): bool
    {
        $supplierType = strtolower((string) ($context->supplier->type ?? ''));
        $isSupplier = in_array($supplierType, ['supplier', 'both'], true);
        $isAdvance = ! empty($context->advance_transaction)
            || ($allowRequestMarker && ! empty($context->advance_request));

        return $isSupplier && $isAdvance;
    }
    private function resolveExpectedAccountId(object $context, bool $allowRequest = true): int
    {
        $businessId = (int) ($context->business_id ?? 0);
        if ($businessId <= 0) {
            return 0;
        }

        $requireNonCash = $this->isBankTransferMethod((string) ($context->method ?? ''));

        if ($allowRequest && ! empty($context->advance_request)) {
            $requestAccount = $this->selectedAccountFromRequest($businessId, $requireNonCash);
            if ($this->isValidPaymentBusinessAccount($requestAccount, $businessId, $requireNonCash)) {
                return $requestAccount;
            }
        }

        $rootAccount = (int) ($context->root->account_id ?? 0);
        if ($this->isValidPaymentBusinessAccount($rootAccount, $businessId, $requireNonCash)) {
            return $rootAccount;
        }

        foreach ($context->family as $row) {
            $candidate = (int) ($row->account_id ?? 0);
            if ($this->isValidPaymentBusinessAccount($candidate, $businessId, $requireNonCash)) {
                return $candidate;
            }
        }

        if (Schema::hasColumn('transaction_payments', 'related_account_id')) {
            $rootRelated = (int) ($context->root->related_account_id ?? 0);
            if ($this->isValidPaymentBusinessAccount($rootRelated, $businessId, $requireNonCash)) {
                return $rootRelated;
            }
            foreach ($context->family as $row) {
                $candidate = (int) ($row->related_account_id ?? 0);
                if ($this->isValidPaymentBusinessAccount($candidate, $businessId, $requireNonCash)) {
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
                if ($candidate !== $apId && $this->isValidPaymentBusinessAccount($candidate, $businessId, $requireNonCash)) {
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
        if (! $this->isValidPaymentBusinessAccount($expectedAccountId, $businessId, $requireNonCash)) {
            return 0;
        }

        $changed = 0;
        foreach ($context->family as $row) {
            $current = (int) ($row->account_id ?? 0);
            if ($current === $expectedAccountId) {
                continue;
            }
            if ($current > 0 && ! $this->isCashAccountId($current, $businessId)) {
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
            Log::info('Finance corrected Supplier advance payment double-entry posting', [
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
            if ($this->isValidPaymentBusinessAccount($id, $businessId, $requireNonCash)) {
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

        // Compatibility with the existing shared Advance Payment form which
        // may already submit type=advance_payment.
        return strtolower(trim((string) request()->input('type', ''))) === 'advance_payment';
    }

    private function normalizeMethod(string $method): string
    {
        return str_replace(['-', ' '], '_', strtolower(trim($method)));
    }

    private function isBankTransferMethod(string $method): bool
    {
        return in_array($this->normalizeMethod($method), ['bank_transfer', 'banktransfer', 'bank'], true);
    }

    private function isValidPaymentBusinessAccount(int $accountId, int $businessId, bool $requireNonCash = false): bool
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
            if ($current <= 0 || $current === $paymentAccountId || $this->isCashAccountId($current, $businessId)) {
                return $apId;
            }
            return 0;
        }

        if ($current === $paymentAccountId) { return 0; }
        if ($current <= 0 || $current === $apId || ($paymentAccountId !== $current && $this->isCashAccountId($current, $businessId))) {
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

    private function isCashAccountId(int $accountId, int $businessId): bool
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
            return Schema::hasTable('account_transactions')
                && Schema::hasTable('transaction_payments')
                && Schema::hasTable('transactions')
                && Schema::hasTable('contacts')
                && Schema::hasTable('accounts')
                && Schema::hasColumn('transaction_payments', 'account_id')
                && Schema::hasColumn('transaction_payments', 'method');
        } catch (\Throwable $e) {
            return false;
        }
    }
}

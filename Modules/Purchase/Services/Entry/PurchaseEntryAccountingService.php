<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Purchase\Utils\PurchaseSchemaUtil;

class PurchaseEntryAccountingService
{
    public function __construct(protected PurchaseSchemaUtil $schema)
    {
    }

    /** @param array<int, array<string, mixed>> $payments */
    public function post(
        int $businessId,
        int $locationId,
        int $userId,
        int $transactionId,
        string $reference,
        string $operationDate,
        float $purchaseTotal,
        float $paidTotal,
        array $payments
    ): void {
        if (! Schema::hasTable('account_transactions')) {
            return;
        }

        // Purchases must increase Finished Goods only. Do not fall back to
        // Raw Material/Stock accounts, because that moves the purchase into the
        // wrong account book (CH1 IS2105 - Purchase).
        $stockAccount = $this->findFinishedGoodsAccount($businessId, $locationId);
        $payableAccount = $this->findAccount($businessId, $locationId, [
            'accounts payable', 'account payable', 'trade creditors', 'supplier payable', 'supplier payables',
        ]);

        if ($stockAccount) {
            $this->entry($stockAccount, $businessId, 'debit', $purchaseTotal, $reference, $operationDate, $userId, $transactionId, null, 'Purchase stock received');
        } else {
            Log::warning('Purchase module: Finished Goods account was not found; purchase saved without inventory account posting.', [
                'transaction_id' => $transactionId,
                'business_id' => $businessId,
                'location_id' => $locationId,
            ]);
        }

        foreach ($payments as $payment) {
            if (empty($payment['account_id']) || empty($payment['payment_id']) || (float) $payment['amount'] <= 0) {
                continue;
            }

            $paymentReference = trim((string) ($payment['payment_ref_no'] ?? '')) ?: $reference;

            $this->entry(
                (int) $payment['account_id'],
                $businessId,
                'credit',
                (float) $payment['amount'],
                $paymentReference,
                $operationDate,
                $userId,
                $transactionId,
                (int) $payment['payment_id'],
                'Purchase payment - ' . ($payment['method'] ?? 'payment')
            );
        }

        $outstanding = max(0, round($purchaseTotal - $paidTotal, 6));
        if ($outstanding > 0.000001) {
            if ($payableAccount) {
                $this->entry($payableAccount, $businessId, 'credit', $outstanding, $reference, $operationDate, $userId, $transactionId, null, 'Supplier payable');
            } else {
                Log::warning('Purchase module: Accounts Payable account was not found; the due amount remains in the canonical supplier transaction ledger.', [
                    'transaction_id' => $transactionId,
                    'outstanding' => $outstanding,
                ]);
            }
        }
    }

    /**
     * Post a payment added after the purchase was originally saved.
     * The payment credits the selected cash/bank account and debits Accounts Payable.
     */
    public function postAdditionalPayment(
        int $businessId,
        int $locationId,
        int $userId,
        int $transactionId,
        int $paymentId,
        int $paymentAccountId,
        float $amount,
        string $reference,
        string $operationDate,
        string $method
    ): void {
        if ($amount <= 0.000001 || ! Schema::hasTable('account_transactions')) {
            return;
        }

        $payableAccount = $this->findAccount($businessId, $locationId, [
            'accounts payable', 'account payable', 'trade creditors', 'supplier payable', 'supplier payables',
        ]);

        if (! $payableAccount) {
            throw new \RuntimeException('Accounts Payable account was not found. The supplier payment was not posted.');
        }

        $this->entry(
            $paymentAccountId,
            $businessId,
            'credit',
            $amount,
            $reference,
            $operationDate,
            $userId,
            $transactionId,
            $paymentId,
            'Additional purchase payment - ' . ($method !== '' ? $method : 'payment')
        );

        $this->entry(
            $payableAccount,
            $businessId,
            'debit',
            $amount,
            $reference,
            $operationDate,
            $userId,
            $transactionId,
            $paymentId,
            'Supplier payable payment'
        );
    }

    public function isCashAccount(int $businessId, int $accountId): bool
    {
        if (! Schema::hasTable('accounts')) {
            return false;
        }

        $query = DB::table('accounts')
            ->where('accounts.business_id', $businessId)
            ->where('accounts.id', $accountId);
        if (Schema::hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('accounts.deleted_at');
        }
        if (Schema::hasTable('account_groups') && Schema::hasColumn('accounts', 'asset_type')) {
            $query->leftJoin('account_groups', 'account_groups.id', '=', 'accounts.asset_type')
                ->addSelect('account_groups.name as group_name');
        }
        $query->addSelect('accounts.name');
        $row = $query->first();
        if (! $row) {
            return false;
        }

        $text = strtolower(trim(($row->group_name ?? '') . ' ' . $row->name));

        return str_contains($text, 'cash');
    }

    public function accountBalance(int $businessId, int $accountId): float
    {
        if (! Schema::hasTable('account_transactions')) {
            return 0.0;
        }

        $query = DB::table('account_transactions')
            ->where('business_id', $businessId)
            ->where('account_id', $accountId);
        if (Schema::hasColumn('account_transactions', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if (Schema::hasColumn('account_transactions', 'new_deleted_at')) {
            $query->whereNull('new_deleted_at');
        }

        $row = $query
            ->selectRaw("SUM(CASE WHEN type = 'debit' THEN amount ELSE -amount END) as balance")
            ->first();

        return (float) ($row->balance ?? 0);
    }

    /**
     * Resolve the Finished Goods account for purchase stock postings.
     *
     * Resolution order is intentionally strict:
     *  1. Location-configured finished_goods_account.
     *  2. Account name clearly identifying Finished Goods.
     *  3. Account belonging to the Finished Goods account group.
     *
     * Raw Material is deliberately not a fallback for purchases.
     */
    protected function findFinishedGoodsAccount(int $businessId, int $locationId): ?int
    {
        if (! Schema::hasTable('accounts')) {
            return null;
        }

        $configured = $this->configuredFinishedGoodsAccount($businessId, $locationId);
        if ($configured) {
            return $configured;
        }

        $query = DB::table('accounts')->where('business_id', $businessId);
        if (Schema::hasColumn('accounts', 'is_closed')) {
            $query->where('is_closed', 0);
        }
        if (Schema::hasColumn('accounts', 'disabled')) {
            $query->where('disabled', 0);
        }
        if (Schema::hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $columns = ['id', 'name'];
        if (Schema::hasColumn('accounts', 'location_id')) {
            $columns[] = 'location_id';
        }
        if (Schema::hasColumn('accounts', 'asset_type')) {
            $columns[] = 'asset_type';
        }

        $rows = $query->get($columns)->sortBy(function ($row) use ($locationId): int {
            $scope = (string) ($row->location_id ?? 'all');
            if ($scope === (string) $locationId) {
                return 0;
            }
            if ($scope === '' || strtolower($scope) === 'all') {
                return 1;
            }

            return 2;
        });

        $finishedNames = [
            'finished goods account',
            'finished goods accounting',
            'finished goods',
            'finished good account',
            'finished good',
        ];

        foreach ($rows as $row) {
            if (! $this->accountMatchesLocation($row, $locationId)) {
                continue;
            }

            $name = $this->normalizeAccountName((string) $row->name);
            if (in_array($name, $finishedNames, true) || str_contains($name, 'finished goods')) {
                return (int) $row->id;
            }
        }

        if (Schema::hasTable('account_groups')
            && Schema::hasColumn('accounts', 'asset_type')
            && Schema::hasColumn('account_groups', 'id')
            && Schema::hasColumn('account_groups', 'name')) {
            $groupQuery = DB::table('account_groups');
            if (Schema::hasColumn('account_groups', 'business_id')) {
                $groupQuery->where('business_id', $businessId);
            }

            $finishedGroupIds = $groupQuery
                ->get(['id', 'name'])
                ->filter(function ($group): bool {
                    $name = $this->normalizeAccountName((string) $group->name);

                    return $name === 'finished goods account' || str_contains($name, 'finished goods');
                })
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            if ($finishedGroupIds !== []) {
                foreach ($rows as $row) {
                    if (! $this->accountMatchesLocation($row, $locationId)) {
                        continue;
                    }
                    if (isset($row->asset_type) && in_array((int) $row->asset_type, $finishedGroupIds, true)) {
                        return (int) $row->id;
                    }
                }
            }
        }

        return null;
    }

    protected function configuredFinishedGoodsAccount(int $businessId, int $locationId): ?int
    {
        if ($locationId <= 0
            || ! Schema::hasTable('business_locations')
            || ! Schema::hasColumn('business_locations', 'default_payment_accounts')) {
            return null;
        }

        $json = DB::table('business_locations')
            ->where('business_id', $businessId)
            ->where('id', $locationId)
            ->value('default_payment_accounts');
        $settings = json_decode((string) $json, true);
        if (! is_array($settings)) {
            return null;
        }

        $config = $settings['finished_goods_account'] ?? null;
        if (! is_array($config)) {
            return null;
        }

        $accountId = (int) ($config['account'] ?? 0);
        if ($accountId <= 0) {
            return null;
        }

        $query = DB::table('accounts')
            ->where('business_id', $businessId)
            ->where('id', $accountId);
        if (Schema::hasColumn('accounts', 'is_closed')) {
            $query->where('is_closed', 0);
        }
        if (Schema::hasColumn('accounts', 'disabled')) {
            $query->where('disabled', 0);
        }
        if (Schema::hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $columns = ['id'];
        if (Schema::hasColumn('accounts', 'location_id')) {
            $columns[] = 'location_id';
        }
        $account = $query->first($columns);
        if (! $account || ! $this->accountMatchesLocation($account, $locationId)) {
            return null;
        }

        return (int) $account->id;
    }

    protected function accountMatchesLocation(object $row, int $locationId): bool
    {
        $scope = (string) ($row->location_id ?? 'all');

        return $scope === '' || strtolower($scope) === 'all' || $scope === (string) $locationId;
    }

    protected function normalizeAccountName(string $name): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? $name);
    }

    /** @param array<int, string> $names */
    protected function findAccount(int $businessId, int $locationId, array $names): ?int
    {
        if (! Schema::hasTable('accounts')) {
            return null;
        }

        $normalized = array_map(
            fn (string $name): string => strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? $name),
            $names
        );
        $query = DB::table('accounts')->where('business_id', $businessId);
        if (Schema::hasColumn('accounts', 'is_closed')) {
            $query->where('is_closed', 0);
        }
        if (Schema::hasColumn('accounts', 'disabled')) {
            $query->where('disabled', 0);
        }
        if (Schema::hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $columns = ['id', 'name'];
        if (Schema::hasColumn('accounts', 'location_id')) {
            $columns[] = 'location_id';
        }
        $rows = $query->get($columns)->sortBy(function ($row) use ($locationId): int {
            $scope = (string) ($row->location_id ?? 'all');
            if ($scope === (string) $locationId) {
                return 0;
            }
            if ($scope === '' || strtolower($scope) === 'all') {
                return 1;
            }

            return 2;
        });

        foreach ($rows as $row) {
            $scope = (string) ($row->location_id ?? 'all');
            if ($scope !== '' && strtolower($scope) !== 'all' && $scope !== (string) $locationId) {
                continue;
            }
            $name = strtolower(preg_replace('/\s+/', ' ', trim((string) $row->name)) ?? (string) $row->name);
            if (in_array($name, $normalized, true)) {
                return (int) $row->id;
            }
        }

        foreach ($rows as $row) {
            $scope = (string) ($row->location_id ?? 'all');
            if ($scope !== '' && strtolower($scope) !== 'all' && $scope !== (string) $locationId) {
                continue;
            }
            $name = strtolower((string) $row->name);
            foreach ($normalized as $needle) {
                if (str_contains($name, $needle)) {
                    return (int) $row->id;
                }
            }
        }

        return null;
    }

    protected function entry(
        int $accountId,
        int $businessId,
        string $type,
        float $amount,
        string $reference,
        string $operationDate,
        int $userId,
        int $transactionId,
        ?int $paymentId,
        string $note
    ): void {
        if ($amount <= 0) {
            return;
        }

        DB::table('account_transactions')->insert($this->schema->filter('account_transactions', [
            'account_id' => $accountId,
            'business_id' => $businessId,
            'type' => $type,
            'txnType' => 'purchase',
            'sub_type' => 'ledger_show',
            'amount' => $amount,
            'reff_no' => $reference,
            'operation_date' => $operationDate,
            'created_by' => $userId,
            'transaction_id' => $transactionId,
            'transaction_payment_id' => $paymentId,
            'note' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }
}

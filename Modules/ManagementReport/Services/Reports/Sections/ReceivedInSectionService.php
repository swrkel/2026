<?php
namespace Modules\ManagementReport\Services\Reports\Sections;

use Illuminate\Support\Facades\DB;
use Modules\ManagementReport\Support\ReportContext;
use Modules\ManagementReport\Support\TenantConnection;

/**
 * 8053 - "Received In" section.
 *
 * IMPORTANT:
 * The source of truth is the Accounts Module ledger, not POS / settlement
 * operational tables. Amounts are read from `account_transactions` and shown
 * sector-wise using the Accounts Module account-group / account structure.
 *
 * Received In = DEBIT movements posted to configured payment account sectors
 * during the selected report period. Opening-balance entries are excluded.
 *
 * Payment sectors are discovered primarily from Business Location
 * `default_payment_accounts` mappings, so custom payment types/account groups
 * are included automatically. Legacy standard payment groups are used only as
 * a compatibility fallback when a business has no usable mapping saved.
 */
class ReceivedInSectionService extends BaseSectionService
{
    public function key()
    {
        return 'received_in';
    }

    public function build(ReportContext $context)
    {
        if (!$this->schema->table('accounts') || !$this->schema->table('account_transactions')) {
            return [
                'rows' => [],
                'sector_totals' => [],
                'total' => 0.0,
                'source' => 'Accounts Module',
            ];
        }

        $paymentGroupIds = $this->configuredPaymentGroupIds($context);
        $paymentAccountIds = $this->configuredPaymentAccountIds($context);

        // Compatibility fallback for older tenants where payment methods were
        // not mapped in business_locations.default_payment_accounts.
        if (!$paymentGroupIds && !$paymentAccountIds) {
            $paymentGroupIds = $this->legacyPaymentGroupIds($context);
        }

        $accounts = $this->paymentAccounts($context, $paymentGroupIds, $paymentAccountIds);
        if ($accounts->isEmpty()) {
            return [
                'rows' => [],
                'sector_totals' => [],
                'total' => 0.0,
                'source' => 'Accounts Module',
            ];
        }

        $movementTotals = $this->debitTotalsByAccount($context, $accounts->pluck('id')->all());

        $rows = [];
        $sectorTotals = [];
        foreach ($accounts as $account) {
            $amount = $this->amount($movementTotals[(int) $account->id] ?? 0.0);

            // Do not clutter the Daily Management Report with Accounts that had
            // no Received-In movement in the selected period.
            if ($amount == 0.0) {
                continue;
            }

            $sector = trim((string) ($account->sector_name ?? ''));
            if ($sector === '') {
                $sector = 'Other Payment Accounts';
            }

            $rows[] = [
                'sector' => $sector,
                'account_id' => (int) $account->id,
                'account' => (string) ($account->account_name ?: ('Account #' . $account->id)),
                'amount' => $amount,
            ];

            $sectorTotals[$sector] = ($sectorTotals[$sector] ?? 0.0) + $amount;
        }

        usort($rows, function ($a, $b) {
            $sectorCompare = strnatcasecmp($a['sector'], $b['sector']);
            return $sectorCompare !== 0 ? $sectorCompare : strnatcasecmp($a['account'], $b['account']);
        });
        ksort($sectorTotals, SORT_NATURAL | SORT_FLAG_CASE);

        $sectorRows = [];
        foreach ($sectorTotals as $sector => $amount) {
            $sectorRows[] = [
                'sector' => $sector,
                'amount' => $this->amount($amount),
            ];
        }

        return [
            'rows' => $rows,
            'sector_totals' => $sectorRows,
            'total' => $this->amount(array_sum($sectorTotals)),
            'source' => 'Accounts Module',
        ];
    }

    /**
     * Collect the account-group ids selected against enabled payment methods in
     * Business Location -> default_payment_accounts.
     */
    protected function configuredPaymentGroupIds(ReportContext $context)
    {
        if (!$this->schema->table('business_locations') ||
            !$this->schema->column('business_locations', 'default_payment_accounts')) {
            return [];
        }

        $query = TenantConnection::db()->table('business_locations')
            ->where('business_id', $context->businessId);

        if ($context->locationId && $this->schema->column('business_locations', 'id')) {
            $query->where('id', $context->locationId);
        }

        if ($this->schema->column('business_locations', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $groupIds = [];
        foreach ($query->pluck('default_payment_accounts') as $json) {
            foreach ($this->paymentMappings($json) as $mapping) {
                if (!$this->mappingEnabled($mapping)) {
                    continue;
                }

                $mapped = $mapping['account'] ?? $mapping['account_group'] ?? $mapping['account_group_id'] ?? null;
                if ($mapped !== null && ctype_digit((string) $mapped)) {
                    $groupIds[] = (int) $mapped;
                }
            }
        }

        $groupIds = array_values(array_unique(array_filter($groupIds)));
        if (!$groupIds || !$this->schema->table('account_groups')) {
            return [];
        }

        $groups = TenantConnection::db()->table('account_groups')->whereIn('id', $groupIds);
        if ($this->schema->column('account_groups', 'business_id')) {
            $groups->where('business_id', $context->businessId);
        }
        if ($this->schema->column('account_groups', 'deleted_at')) {
            $groups->whereNull('deleted_at');
        }

        return $groups->pluck('id')->map(function ($id) { return (int) $id; })->all();
    }

    /**
     * Some older/custom installs stored a real Account id rather than an
     * Account Group id in the same mapping field. Detect those ids as a second
     * path; this keeps the report compatible without changing stored settings.
     */
    protected function configuredPaymentAccountIds(ReportContext $context)
    {
        if (!$this->schema->table('business_locations') ||
            !$this->schema->column('business_locations', 'default_payment_accounts')) {
            return [];
        }

        $query = TenantConnection::db()->table('business_locations')
            ->where('business_id', $context->businessId);

        if ($context->locationId && $this->schema->column('business_locations', 'id')) {
            $query->where('id', $context->locationId);
        }
        if ($this->schema->column('business_locations', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $mappedIds = [];
        foreach ($query->pluck('default_payment_accounts') as $json) {
            foreach ($this->paymentMappings($json) as $mapping) {
                if (!$this->mappingEnabled($mapping)) {
                    continue;
                }
                $mapped = $mapping['account'] ?? $mapping['account_id'] ?? null;
                if ($mapped !== null && ctype_digit((string) $mapped)) {
                    $mappedIds[] = (int) $mapped;
                }
            }
        }

        if (!$mappedIds) {
            return [];
        }

        $mappedIds = array_values(array_unique($mappedIds));

        // Current Super Admin / Business Location UI stores Account Group ids.
        // Only treat a mapped id as a literal Account id when it does NOT resolve
        // to an Account Group for this business.
        $groupIds = [];
        if ($this->schema->table('account_groups')) {
            $groupQuery = TenantConnection::db()->table('account_groups')->whereIn('id', $mappedIds);
            if ($this->schema->column('account_groups', 'business_id')) {
                $groupQuery->where('business_id', $context->businessId);
            }
            $groupIds = $groupQuery->pluck('id')->map(function ($id) { return (int) $id; })->all();
        }

        $literalAccountIds = array_values(array_diff($mappedIds, $groupIds));
        if (!$literalAccountIds) {
            return [];
        }

        return TenantConnection::db()->table('accounts')
            ->where('business_id', $context->businessId)
            ->whereIn('id', $literalAccountIds)
            ->pluck('id')
            ->map(function ($id) { return (int) $id; })
            ->all();
    }

    protected function paymentMappings($json)
    {
        if (is_string($json)) {
            $decoded = json_decode($json, true);
        } elseif (is_array($json)) {
            $decoded = $json;
        } else {
            $decoded = [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        // Current structure: {"methods":{"cash":{...}, ...}}
        if (isset($decoded['methods']) && is_array($decoded['methods'])) {
            return array_values(array_filter($decoded['methods'], 'is_array'));
        }

        // Legacy structure: {"cash":{"is_enabled":1,"account":X}, ...}
        $rows = [];
        foreach ($decoded as $key => $value) {
            if (is_array($value) && (isset($value['account']) || isset($value['is_enabled']) || isset($value['account_group']))) {
                $rows[] = $value;
            }
        }

        // Older indexed structure saved name/is_enabled/account as parallel arrays.
        if (!$rows && isset($decoded['account']) && is_array($decoded['account'])) {
            foreach ($decoded['account'] as $index => $account) {
                $rows[] = [
                    'account' => $account,
                    'is_enabled' => $decoded['is_enabled'][$index] ?? 1,
                    'is_sale_enabled' => $decoded['is_sale_enabled'][$index] ?? null,
                    'is_purchase_return_enabled' => $decoded['is_purchase_return_enabled'][$index] ?? null,
                ];
            }
        }

        return $rows;
    }

    protected function mappingEnabled(array $mapping)
    {
        if (array_key_exists('is_enabled', $mapping) && (string) $mapping['is_enabled'] === '0') {
            return false;
        }

        // Received In uses every enabled payment type. Do not require the sales
        // flag because the same account can legitimately receive purchase-return
        // and other business receipts.
        return true;
    }

    /**
     * Standard account-group names used by the existing Accounts/Finance module.
     * This is only a fallback when there is no saved payment-account mapping.
     */
    protected function legacyPaymentGroupIds(ReportContext $context)
    {
        if (!$this->schema->table('account_groups') || !$this->schema->column('account_groups', 'name')) {
            return [];
        }

        $query = TenantConnection::db()->table('account_groups');
        if ($this->schema->column('account_groups', 'business_id')) {
            $query->where('business_id', $context->businessId);
        }
        if ($this->schema->column('account_groups', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $groups = $query->select(['id', 'name'])->get();
        $ids = [];
        foreach ($groups as $group) {
            $name = strtolower(trim((string) $group->name));
            if ($name === '') continue;

            foreach (['cash', 'bank', 'cheque', 'check', 'card', 'cpc', 'wallet', 'merchant', 'gateway', 'mobile money', 'digital payment', 'online payment'] as $needle) {
                if (strpos($name, $needle) !== false) {
                    $ids[] = (int) $group->id;
                    break;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    protected function paymentAccounts(ReportContext $context, array $groupIds, array $accountIds)
    {
        $query = TenantConnection::db()->table('accounts')
            ->where('accounts.business_id', $context->businessId);

        $hasGroups = $this->schema->table('account_groups') && $this->schema->column('accounts', 'asset_type');
        if ($hasGroups) {
            $query->leftJoin('account_groups', 'account_groups.id', '=', 'accounts.asset_type');
        }

        if ($groupIds || $accountIds) {
            $query->where(function ($scope) use ($groupIds, $accountIds) {
                if ($groupIds && $this->schema->column('accounts', 'asset_type')) {
                    $scope->whereIn('accounts.asset_type', $groupIds);
                    if ($accountIds) {
                        $scope->orWhereIn('accounts.id', $accountIds);
                    }
                } elseif ($accountIds) {
                    $scope->whereIn('accounts.id', $accountIds);
                }
            });
        } else {
            // Without a mapped/fallback payment sector, returning all accounts
            // would incorrectly treat Inventory/Expense/etc debit postings as
            // cash received. An empty result is safer and auditable.
            $query->whereRaw('1 = 0');
        }

        if ($this->schema->column('accounts', 'deleted_at')) {
            $query->whereNull('accounts.deleted_at');
        }
        if ($this->schema->column('accounts', 'is_closed')) {
            $query->where(function ($q) {
                $q->whereNull('accounts.is_closed')->orWhere('accounts.is_closed', 0);
            });
        }
        if ($this->schema->column('accounts', 'status')) {
            $query->where(function ($q) {
                $q->whereNull('accounts.status')->orWhereNotIn('accounts.status', ['inactive', 'closed', 'deleted']);
            });
        }

        // Prefer account-level location scope where it exists. Some tenant DBs
        // do not have location_id on account_transactions.
        if ($context->locationId && $this->schema->column('accounts', 'location_id')) {
            $query->where('accounts.location_id', $context->locationId);
        }
        if ($context->storeId && $this->schema->column('accounts', 'store_id')) {
            $query->where('accounts.store_id', $context->storeId);
        }

        $select = [
            'accounts.id',
            DB::raw('accounts.name AS account_name'),
        ];
        if ($hasGroups) {
            $select[] = DB::raw('account_groups.name AS sector_name');
        } else {
            $select[] = DB::raw("'Other Payment Accounts' AS sector_name");
        }

        return $query->select($select)->orderBy('sector_name')->orderBy('account_name')->get();
    }

    protected function debitTotalsByAccount(ReportContext $context, array $accountIds)
    {
        if (!$accountIds) return [];

        $date = $this->schema->firstColumn('account_transactions', ['operation_date', 'transaction_date', 'date', 'created_at']);
        $amount = $this->schema->firstColumn('account_transactions', ['amount', 'transaction_amount']);
        $type = $this->schema->firstColumn('account_transactions', ['type', 'entry_type']);
        if (!$date || !$amount || !$type || !$this->schema->column('account_transactions', 'account_id')) {
            return [];
        }

        $query = TenantConnection::db()->table('account_transactions')
            ->whereIn('account_transactions.account_id', $accountIds)
            ->where('account_transactions.' . $type, 'debit')
            ->whereBetween('account_transactions.' . $date, [$context->startDate, $context->endDate]);

        if ($this->schema->column('account_transactions', 'business_id')) {
            $query->where('account_transactions.business_id', $context->businessId);
        }
        if ($this->schema->column('account_transactions', 'deleted_at')) {
            $query->whereNull('account_transactions.deleted_at');
        }
        if ($this->schema->column('account_transactions', 'sub_type')) {
            $query->where(function ($q) {
                $q->whereNull('account_transactions.sub_type')
                    ->orWhereNotIn('account_transactions.sub_type', ['opening_balance']);
            });
        }

        if ($context->locationId && $this->schema->column('account_transactions', 'location_id')) {
            $query->where('account_transactions.location_id', $context->locationId);
        }
        if ($context->storeId && $this->schema->column('account_transactions', 'store_id')) {
            $query->where('account_transactions.store_id', $context->storeId);
        }

        $rows = $query->select(
            'account_transactions.account_id',
            DB::raw('SUM(ABS(account_transactions.' . $amount . ')) AS received_amount')
        )->groupBy('account_transactions.account_id')->get();

        $totals = [];
        foreach ($rows as $row) {
            $totals[(int) $row->account_id] = $this->amount($row->received_amount);
        }

        return $totals;
    }
}

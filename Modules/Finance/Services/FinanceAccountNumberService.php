<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\AccountType;
use RuntimeException;

/**
 * Finance account numbering backed by the authoritative Super Admin setup:
 *
 *   Super Admin -> Super Admin Settings -> Default Accounts -> Account Numbers
 *   table: account_numbers (business_id, account_type, prefix, account_number)
 *
 * account_numbers.account_type points to default_account_types.id. Tenant
 * account_types are resolved primarily through account_types.default_account_type_id.
 * The selected subtype is checked first; parent types are compatibility fallback.
 *
 * The configured Account Number is the STARTING number for that type/subtype.
 * Finance suggests the next value, but Add/Edit remain manually editable.
 */
class FinanceAccountNumberService
{
    /**
     * Return the next Account Number for a selected tenant Account Type.
     */
    public function nextNumberForType(AccountType $accountType, int $businessId): ?string
    {
        $setup = $this->setupForType($accountType, $businessId);
        if (empty($setup)) {
            return null;
        }

        $prefix = $this->normalisePrefix((string) ($setup->prefix ?? ''));
        $startRaw = trim((string) ($setup->account_number ?? ''));
        $start = (int) $startRaw;
        if ($start < 1) {
            return null;
        }

        $typeIds = $this->tenantTypeIdsForSetup($setup, $businessId);
        if (empty($typeIds)) {
            $typeIds = [(int) $accountType->id];
        }

        $maxUsed = null;
        $numbers = Account::withTrashed()
            ->where('business_id', $businessId)
            ->whereIn('account_type_id', $typeIds)
            ->whereNotNull('account_number')
            ->pluck('account_number');

        foreach ($numbers as $number) {
            $sequence = $this->sequenceFromAccountNumber((string) $number, $prefix);
            if ($sequence === null) {
                continue;
            }
            $maxUsed = $maxUsed === null ? $sequence : max($maxUsed, $sequence);
        }

        $next = max($start, 1);
        if ($maxUsed !== null) {
            $next = max($next, $maxUsed + 1);
        }

        // A manually edited Account or another configured series may already
        // use the same formatted value. Keep this series moving forward until
        // a business-wide free Account Number is found.
        do {
            $candidate = $this->formatNumber($prefix, $next, $startRaw);
            $exists = Account::withTrashed()
                ->where('business_id', $businessId)
                ->where('account_number', $candidate)
                ->exists();
            if ($exists) {
                $next++;
            }
        } while ($exists);

        return $candidate;
    }

    /**
     * Resolve the Super Admin Account Numbers row for a Finance Account Type.
     */
    public function setupForType(AccountType $accountType, int $businessId)
    {
        if ($businessId <= 0 || ! Schema::hasTable('account_numbers')) {
            return null;
        }

        $lineage = $this->typeLineage($accountType, $businessId);
        if (empty($lineage)) {
            return null;
        }

        // 1. Canonical mapping: selected subtype first, then its parents.
        if (Schema::hasColumn('account_types', 'default_account_type_id')) {
            foreach ($lineage as $type) {
                $defaultTypeId = (int) ($type->default_account_type_id ?? 0);
                if ($defaultTypeId <= 0) {
                    continue;
                }

                $setup = DB::table('account_numbers')
                    ->where('business_id', $businessId)
                    ->where('account_type', $defaultTypeId)
                    ->first();
                if (! empty($setup)) {
                    return $setup;
                }
            }
        }

        // 2. Legacy fallback by Default Account Type name.
        if (Schema::hasTable('default_account_types')) {
            foreach ($lineage as $type) {
                $name = trim((string) ($type->name ?? ''));
                if ($name === '') {
                    continue;
                }

                $setup = DB::table('account_numbers')
                    ->join('default_account_types as dat', 'dat.id', '=', 'account_numbers.account_type')
                    ->where('account_numbers.business_id', $businessId)
                    ->whereRaw('LOWER(dat.name) = ?', [strtolower($name)])
                    ->select('account_numbers.*')
                    ->first();
                if (! empty($setup)) {
                    return $setup;
                }
            }
        }

        // 3. Old installations sometimes stored tenant Account Type ids.
        foreach ($lineage as $type) {
            $setup = DB::table('account_numbers')
                ->where('business_id', $businessId)
                ->where('account_type', (int) $type->id)
                ->first();
            if (! empty($setup)) {
                return $setup;
            }
        }

        return null;
    }

    /**
     * Read-only mapping used only by any older Finance URL that still requests
     * the former Task-8061 Account Numbers popup. Super Admin is authoritative.
     */
    public function mappingForBusiness(int $businessId): array
    {
        if ($businessId <= 0 || ! Schema::hasTable('account_numbers')) {
            return [];
        }

        $query = DB::table('account_numbers as an')
            ->where('an.business_id', $businessId);

        if (Schema::hasTable('default_account_types')) {
            $query->leftJoin('default_account_types as dat', 'dat.id', '=', 'an.account_type')
                ->addSelect('dat.name as type_name');
        }

        $rows = $query->addSelect([
                'an.id', 'an.account_type', 'an.prefix', 'an.account_number',
            ])
            ->orderBy('an.account_type')
            ->get();

        return $rows->map(function ($row) use ($businessId) {
            $typeName = (string) ($row->type_name ?? ('Type #' . $row->account_type));
            $tenantType = $this->tenantTypeForDefaultType((int) $row->account_type, $businessId, $typeName);

            return [
                'key' => (string) $row->account_type,
                'label' => $typeName,
                'prefix' => (string) ($row->prefix ?? ''),
                'start_number' => (int) ($row->account_number ?? 0),
                'default_start_number' => (int) ($row->account_number ?? 0),
                'account_count' => $tenantType
                    ? Account::withTrashed()->where('business_id', $businessId)->where('account_type_id', $tenantType->id)->count()
                    : 0,
                'next_number' => $tenantType ? ($this->nextNumberForType($tenantType, $businessId) ?? '') : '',
            ];
        })->all();
    }

    /**
     * Kept only for backward route compatibility. Finance must not own a second
     * numbering setup now that Super Admin is the single source of truth.
     */
    public function saveAndRenumber(int $businessId, array $starts, ?int $userId = null): array
    {
        throw new RuntimeException(
            'Account Number starting values are managed in Super Admin -> Super Admin Settings -> Default Accounts -> Account Numbers.'
        );
    }

    /**
     * Renumber all existing accounts according to the current Super Admin setup.
     * This is used for the initial backfill/deployment command. Manual changes
     * made later in Add/Edit are not touched until this explicit sync is run or
     * the Super Admin numbering setup itself is saved again.
     */
    public function renumberExistingAccountsForBusiness(int $businessId): array
    {
        if ($businessId <= 0) {
            throw new RuntimeException('Unable to determine the business.');
        }
        if (! Schema::hasTable('account_numbers') || ! Schema::hasTable('accounts') || ! Schema::hasTable('account_types')) {
            throw new RuntimeException('Required Account Number tables are missing.');
        }

        return DB::transaction(function () use ($businessId): array {
            $setups = DB::table('account_numbers')
                ->where('business_id', $businessId)
                ->orderBy('id')
                ->get();

            if ($setups->isEmpty()) {
                return ['updated_accounts' => 0, 'configured_series' => 0];
            }

            $types = AccountType::where('business_id', $businessId)->get()->keyBy('id');
            $setupsByAccountType = $setups->keyBy(function ($row) {
                return (int) $row->account_type;
            });
            $setupsByName = [];

            if (Schema::hasTable('default_account_types')) {
                $defaultTypeNames = DB::table('default_account_types')
                    ->whereIn('id', $setups->pluck('account_type')->map(function ($id) { return (int) $id; })->all())
                    ->pluck('name', 'id');

                foreach ($setups as $setup) {
                    $name = strtolower(trim((string) ($defaultTypeNames[(int) $setup->account_type] ?? '')));
                    if ($name !== '') {
                        $setupsByName[$name] = $setup;
                    }
                }
            }

            // Resolve each Account Type only once, then reuse the result for
            // every Account. This keeps Super Admin saves fast even with a
            // large Chart of Accounts.
            $setupByTenantTypeId = [];
            foreach ($types as $type) {
                $setupByTenantTypeId[(int) $type->id] = $this->setupForTypeFromLoadedMaps(
                    $type,
                    $types,
                    $setupsByAccountType,
                    $setupsByName
                );
            }

            $mappedBySetup = [];
            $mappedAccountIds = [];

            $accounts = Account::withTrashed()
                ->where('business_id', $businessId)
                ->orderBy('id')
                ->get(['id', 'account_type_id', 'account_number']);

            foreach ($accounts as $account) {
                $setup = $setupByTenantTypeId[(int) $account->account_type_id] ?? null;
                if (empty($setup)) {
                    continue;
                }

                $setupId = (int) $setup->id;
                if (! isset($mappedBySetup[$setupId])) {
                    $mappedBySetup[$setupId] = ['setup' => $setup, 'accounts' => []];
                }
                $mappedBySetup[$setupId]['accounts'][] = $account;
                $mappedAccountIds[(int) $account->id] = true;
            }

            $finalNumbers = [];
            $usedTargets = [];
            foreach ($mappedBySetup as $group) {
                $setup = $group['setup'];
                $prefix = $this->normalisePrefix((string) ($setup->prefix ?? ''));
                $startRaw = trim((string) ($setup->account_number ?? ''));
                $next = max(1, (int) $startRaw);

                foreach ($group['accounts'] as $account) {
                    $number = $this->formatNumber($prefix, $next, $startRaw);
                    $key = strtolower($number);
                    if (isset($usedTargets[$key])) {
                        throw new RuntimeException(
                            'The configured Account Number ranges overlap at ' . $number . '. Please correct the prefixes/starting numbers in Super Admin.'
                        );
                    }
                    $usedTargets[$key] = true;
                    $finalNumbers[(int) $account->id] = $number;
                    $next++;
                }
            }

            if (! empty($usedTargets)) {
                $collision = Account::withTrashed()
                    ->where('business_id', $businessId)
                    ->when(! empty($mappedAccountIds), function ($query) use ($mappedAccountIds) {
                        $query->whereNotIn('id', array_keys($mappedAccountIds));
                    })
                    ->whereIn('account_number', array_values($finalNumbers))
                    ->first(['id', 'name', 'account_number']);

                if ($collision) {
                    throw new RuntimeException(
                        'Account Number ' . $collision->account_number . ' is already used by an account outside the configured series (' . $collision->name . ').'
                    );
                }
            }

            $now = now();
            foreach (array_keys($finalNumbers) as $accountId) {
                DB::table('accounts')
                    ->where('business_id', $businessId)
                    ->where('id', $accountId)
                    ->update([
                        'account_number' => '__FIN_SUPERADMIN_SYNC__' . $accountId . '__',
                        'updated_at' => $now,
                    ]);
            }

            foreach ($finalNumbers as $accountId => $number) {
                DB::table('accounts')
                    ->where('business_id', $businessId)
                    ->where('id', $accountId)
                    ->update([
                        'account_number' => $number,
                        'updated_at' => $now,
                    ]);
            }

            return [
                'updated_accounts' => count($finalNumbers),
                'configured_series' => count($mappedBySetup),
            ];
        });
    }

    private function tenantTypeIdsForSetup($targetSetup, int $businessId): array
    {
        $setups = DB::table('account_numbers')
            ->where('business_id', $businessId)
            ->get();
        $setupsByAccountType = $setups->keyBy(function ($row) {
            return (int) $row->account_type;
        });
        $setupsByName = [];

        if (Schema::hasTable('default_account_types')) {
            $defaultTypeNames = DB::table('default_account_types')
                ->whereIn('id', $setups->pluck('account_type')->map(function ($id) { return (int) $id; })->all())
                ->pluck('name', 'id');

            foreach ($setups as $setup) {
                $name = strtolower(trim((string) ($defaultTypeNames[(int) $setup->account_type] ?? '')));
                if ($name !== '') {
                    $setupsByName[$name] = $setup;
                }
            }
        }

        $types = AccountType::where('business_id', $businessId)->get()->keyBy('id');
        $ids = [];
        foreach ($types as $type) {
            $resolved = $this->setupForTypeFromLoadedMaps($type, $types, $setupsByAccountType, $setupsByName);
            if ($resolved && (int) $resolved->id === (int) $targetSetup->id) {
                $ids[] = (int) $type->id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function setupForTypeFromLoadedMaps(AccountType $accountType, $types, $setupsByAccountType, array $setupsByName)
    {
        $lineage = [];
        $cursor = $accountType;
        $visited = [];

        for ($depth = 0; $depth < 20 && $cursor; $depth++) {
            $id = (int) ($cursor->id ?? 0);
            if ($id <= 0 || isset($visited[$id])) {
                break;
            }
            $visited[$id] = true;
            $lineage[] = $cursor;

            $parentId = (int) ($cursor->parent_account_type_id ?? 0);
            if ($parentId <= 0) {
                break;
            }
            $cursor = $types->get($parentId);
        }

        if (Schema::hasColumn('account_types', 'default_account_type_id')) {
            foreach ($lineage as $type) {
                $defaultTypeId = (int) ($type->default_account_type_id ?? 0);
                if ($defaultTypeId > 0 && $setupsByAccountType->has($defaultTypeId)) {
                    return $setupsByAccountType->get($defaultTypeId);
                }
            }
        }

        foreach ($lineage as $type) {
            $name = strtolower(trim((string) ($type->name ?? '')));
            if ($name !== '' && isset($setupsByName[$name])) {
                return $setupsByName[$name];
            }
        }

        foreach ($lineage as $type) {
            $tenantTypeId = (int) ($type->id ?? 0);
            if ($tenantTypeId > 0 && $setupsByAccountType->has($tenantTypeId)) {
                return $setupsByAccountType->get($tenantTypeId);
            }
        }

        return null;
    }

    private function typeLineage(AccountType $accountType, int $businessId): array
    {
        $lineage = [];
        $cursor = $accountType;
        $visited = [];

        for ($depth = 0; $depth < 20 && $cursor; $depth++) {
            $id = (int) ($cursor->id ?? 0);
            if ($id <= 0 || isset($visited[$id])) {
                break;
            }
            $visited[$id] = true;
            $lineage[] = $cursor;

            $parentId = (int) ($cursor->parent_account_type_id ?? 0);
            if ($parentId <= 0) {
                break;
            }
            $cursor = AccountType::where('business_id', $businessId)->find($parentId);
        }

        return $lineage;
    }

    private function tenantTypeForDefaultType(int $defaultTypeId, int $businessId, string $fallbackName): ?AccountType
    {
        if (Schema::hasColumn('account_types', 'default_account_type_id')) {
            $type = AccountType::where('business_id', $businessId)
                ->where('default_account_type_id', $defaultTypeId)
                ->first();
            if ($type) {
                return $type;
            }
        }

        if ($fallbackName !== '') {
            return AccountType::where('business_id', $businessId)
                ->whereRaw('LOWER(name) = ?', [strtolower($fallbackName)])
                ->first();
        }

        return null;
    }

    private function normalisePrefix(string $prefix): string
    {
        return rtrim(trim($prefix), "- \t\n\r\0\x0B");
    }

    private function formatNumber(string $prefix, int $sequence, string $configuredStartRaw = ''): string
    {
        $digits = (string) max(1, $sequence);
        $configuredStartRaw = trim($configuredStartRaw);
        if ($configuredStartRaw !== '' && ctype_digit($configuredStartRaw) && strlen($configuredStartRaw) > strlen($digits)) {
            $digits = str_pad($digits, strlen($configuredStartRaw), '0', STR_PAD_LEFT);
        }

        return $prefix === '' ? $digits : $prefix . '-' . $digits;
    }

    private function sequenceFromAccountNumber(string $accountNumber, string $prefix): ?int
    {
        $accountNumber = trim($accountNumber);
        if ($prefix === '') {
            return ctype_digit($accountNumber) ? (int) $accountNumber : null;
        }

        $pattern = '/^' . preg_quote($prefix, '/') . '-(\d+)$/i';
        if (! preg_match($pattern, $accountNumber, $match)) {
            return null;
        }

        return (int) $match[1];
    }
}

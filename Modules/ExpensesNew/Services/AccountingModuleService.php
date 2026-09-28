<?php

namespace Modules\ExpensesNew\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingModuleService
{
    public const CASH = 'cash';
    public const CARD = 'card';
    public const CHEQUE = 'cheque';
    public const BANK = 'bank';

    /** @var array<string, bool> */
    private static array $columnCache = [];

    /**
     * Generic accounting destinations retained for backward compatibility in
     * expnew_expenses.accounting_module. The user selects the actual Finance
     * account through expnew_expenses.bank_account_id.
     */
    public function options(): Collection
    {
        return collect(self::labels());
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::CASH => 'Cash',
            self::CARD => 'Card',
            self::CHEQUE => 'Cheque',
            self::BANK => 'Bank',
        ];
    }

    /** @return array<string, string> */
    public static function paymentMethodMap(): array
    {
        return [
            'cash' => self::CASH,
            'card' => self::CARD,
            'cheque' => self::CHEQUE,
            'bank_transfer' => self::BANK,
        ];
    }

    public static function forPaymentMethod(?string $paymentMethod): ?string
    {
        return self::paymentMethodMap()[$paymentMethod ?? ''] ?? null;
    }

    public static function allowedValues(): array
    {
        return array_keys(self::labels());
    }

    public static function allowedPaymentMethods(): array
    {
        return array_keys(self::paymentMethodMap());
    }

    /**
     * Payment methods enabled for Expenses at each business location.
     * The source is business_locations.default_payment_accounts, which is the
     * same JSON maintained by Super Admin -> Manage New -> Payment Options.
     * No Superadmin class is referenced, keeping Expenses-New standalone.
     *
     * @return array<string,array<string,string>>
     */
    public function paymentMethodsByLocation(int $businessId): array
    {
        $locations = $this->paymentLocationRows($businessId);
        $result = [];

        foreach ($locations as $location) {
            $locationId = (string) $location->id;
            $decoded = $this->decodeLocationPaymentSettings($location);
            // IS2341: Payment Options is authoritative. Empty/missing config
            // means no Expense payment methods are selectable; never resurrect
            // the legacy Cash/Card/Cheque/Bank Transfer list.
            if ($decoded === null || $decoded === []) {
                $result[$locationId] = [];
                continue;
            }

            $methods = [];
            foreach ($decoded as $method => $settings) {
                $method = trim((string) $method);
                if (! $this->isPaymentMethodKey($method) || ! is_array($settings)) {
                    continue;
                }
                if (! $this->truthy($settings['is_enabled'] ?? 0)
                    || ! $this->truthy($settings['is_expense_enabled'] ?? 0)) {
                    continue;
                }
                $methods[$method] = $this->paymentMethodLabel($method);
            }

            $result[$locationId] = $methods;
        }

        return $result;
    }

    /** @return array<string,array<string,string>> */
    public function paymentAccountingModuleMapByLocation(int $businessId): array
    {
        $result = [];
        foreach ($this->paymentMethodsByLocation($businessId) as $locationId => $methods) {
            foreach ($methods as $method => $label) {
                $result[$locationId][$method] = $this->classifyPaymentMethod($method);
            }
        }

        return $result;
    }

    /**
     * Resolve Finance accounts from the account/account-group link saved with
     * each Payment Option. This is the important IS2318 path: the form no longer
     * guesses from a hard-coded list when explicit location mapping exists.
     *
     * @return array<string,array<string,array<int,array{id:int,name:string,location_id:?int}>>>
     */
    public function paymentAccountOptionsByLocation(int $businessId): array
    {
        $locations = $this->paymentLocationRows($businessId);
        $methodsByLocation = $this->paymentMethodsByLocation($businessId);
        $legacyOptions = $this->accountOptionsByPaymentMethod($businessId);
        $accounts = $this->allBusinessAccountsForPaymentOptions($businessId);
        $result = [];

        foreach ($locations as $location) {
            $locationId = (string) $location->id;
            $decoded = $this->decodeLocationPaymentSettings($location);
            $configured = is_array($decoded) && $decoded !== [];

            foreach (($methodsByLocation[$locationId] ?? []) as $method => $label) {
                $options = [];
                $settings = $configured && isset($decoded[$method]) && is_array($decoded[$method])
                    ? $decoded[$method]
                    : [];
                $linkedIds = $this->linkedIds(
                    $settings['account'] ?? $settings['accounts'] ?? $settings['account_ids'] ?? null
                );

                if ($linkedIds !== []) {
                    foreach ($accounts as $account) {
                        $scope = isset($account['location_id']) && $account['location_id'] !== null
                            ? (string) $account['location_id']
                            : 'all';
                        if ($scope !== 'all' && $scope !== '' && $scope !== $locationId) {
                            continue;
                        }

                        $accountId = (int) $account['id'];
                        $groupId = (int) ($account['group_id'] ?? 0);
                        if (in_array($accountId, $linkedIds, true)
                            || ($groupId > 0 && in_array($groupId, $linkedIds, true))) {
                            $options[] = [
                                'id' => $accountId,
                                'name' => (string) $account['name'],
                                'location_id' => $account['location_id'],
                            ];
                        }
                    }
                } elseif (! $configured && isset($legacyOptions[$method])) {
                    // Old databases/locations with no Payment Options JSON keep
                    // the previous working semantic account selection.
                    foreach ($legacyOptions[$method] as $option) {
                        $scope = isset($option['location_id']) && $option['location_id'] !== null
                            ? (string) $option['location_id']
                            : 'all';
                        if ($scope === 'all' || $scope === '' || $scope === $locationId) {
                            $options[] = $option;
                        }
                    }
                }

                $result[$locationId][$method] = $this->uniqueOptions($options);
            }
        }

        return $result;
    }

    public function isPaymentMethodAllowed(int $businessId, int $locationId, string $paymentMethod): bool
    {
        if ($locationId <= 0 || trim($paymentMethod) === '') {
            return false;
        }

        $methods = $this->paymentMethodsByLocation($businessId)[(string) $locationId] ?? [];

        return array_key_exists($paymentMethod, $methods);
    }

    public function accountingModuleForPaymentMethod(int $businessId, int $locationId, string $paymentMethod): ?string
    {
        if (! $this->isPaymentMethodAllowed($businessId, $locationId, $paymentMethod)) {
            return null;
        }

        return $this->classifyPaymentMethod($paymentMethod);
    }

    public function isLocationAccountAllowed(
        int $businessId,
        int $locationId,
        string $paymentMethod,
        int $accountId
    ): bool {
        if ($accountId <= 0 || $locationId <= 0) {
            return false;
        }

        $options = $this->paymentAccountOptionsByLocation($businessId)[(string) $locationId][$paymentMethod] ?? [];
        foreach ($options as $option) {
            if ((int) $option['id'] === $accountId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the actual Finance accounts that are relevant to each payment
     * method. This remains inside Expenses-New and uses shared master data only;
     * there is no dependency on Finance controllers, models, routes, or views.
     *
     * @return array<string, array<int, array{id:int,name:string,location_id:?int}>>
     */
    public function accountOptionsByPaymentMethod(int $businessId): array
    {
        $empty = array_fill_keys(self::allowedPaymentMethods(), []);

        if (! Schema::hasTable('accounts')) {
            return $empty;
        }

        $query = DB::table('accounts as account')
            ->where('account.business_id', $businessId);

        if ($this->hasColumn('accounts', 'is_main_account')) {
            $query->where(function ($builder): void {
                $builder->whereNull('account.is_main_account')
                    ->orWhere('account.is_main_account', 0);
            });
        }
        if ($this->hasColumn('accounts', 'is_closed')) {
            $query->where(function ($builder): void {
                $builder->whereNull('account.is_closed')
                    ->orWhere('account.is_closed', 0);
            });
        }
        if ($this->hasColumn('accounts', 'disabled')) {
            $query->where(function ($builder): void {
                $builder->whereNull('account.disabled')
                    ->orWhere('account.disabled', 0);
            });
        }
        if ($this->hasColumn('accounts', 'visible')) {
            $query->where(function ($builder): void {
                $builder->whereNull('account.visible')
                    ->orWhere('account.visible', 1);
            });
        }
        if ($this->hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('account.deleted_at');
        }

        $select = ['account.id', 'account.name'];
        $select[] = $this->hasColumn('accounts', 'location_id')
            ? 'account.location_id'
            : DB::raw('NULL as location_id');

        if (Schema::hasTable('account_groups') && $this->hasColumn('accounts', 'asset_type')) {
            $query->leftJoin('account_groups as account_group', 'account_group.id', '=', 'account.asset_type');
            $select[] = 'account_group.name as group_name';
        } else {
            $select[] = DB::raw('NULL as group_name');
        }

        if (Schema::hasTable('account_types') && $this->hasColumn('accounts', 'account_type_id')) {
            $query->leftJoin('account_types as account_type', 'account_type.id', '=', 'account.account_type_id');
            $select[] = 'account_type.name as type_name';
        } else {
            $select[] = DB::raw('NULL as type_name');
        }

        $rows = $query->select($select)
            ->orderBy('account.name')
            ->get();

        $cash = [];
        $card = [];
        $cheque = [];
        $bank = [];

        foreach ($rows as $row) {
            $name = $this->normalize((string) ($row->name ?? ''));
            $group = $this->normalize((string) ($row->group_name ?? ''));
            $type = $this->normalize((string) ($row->type_name ?? ''));
            $haystack = trim($name . ' ' . $group . ' ' . $type);

            $option = [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'location_id' => isset($row->location_id) ? (int) $row->location_id : null,
            ];

            $isCheque = str_contains($haystack, 'cheque') || str_contains($haystack, 'check in hand');
            $isCard = str_contains($group, 'card')
                || str_contains($name, 'card')
                || str_contains($haystack, 'credit debit');
            $isCash = str_contains($group, 'cash')
                || in_array($name, ['cash', 'petty cash', 'cash account'], true)
                || str_starts_with($name, 'cash ');
            $isBank = str_contains($group, 'bank')
                || str_contains($name, 'bank')
                || str_contains($type, 'bank');

            if ($isCash && ! str_contains($haystack, 'cash flow')) {
                $cash[] = $option;
            }
            if ($isCard) {
                $card[] = $option;
            }
            if ($isCheque) {
                $cheque[] = $option;
            }
            if ($isBank && ! $isCheque) {
                $bank[] = $option;
            }
        }

        // A cheque payment may be issued directly from a bank account. Prefer
        // cheque-specific accounts, then include available bank accounts.
        $cheque = $this->uniqueOptions(array_merge($cheque, $bank));

        return [
            'cash' => $this->uniqueOptions($cash),
            'card' => $this->uniqueOptions($card),
            'cheque' => $cheque,
            'bank_transfer' => $this->uniqueOptions($bank),
        ];
    }

    public function isAccountAllowed(int $businessId, string $paymentMethod, int $accountId): bool
    {
        if ($accountId <= 0 || ! in_array($paymentMethod, self::allowedPaymentMethods(), true)) {
            return false;
        }

        foreach ($this->accountOptionsByPaymentMethod($businessId)[$paymentMethod] ?? [] as $option) {
            if ((int) $option['id'] === $accountId) {
                return true;
            }
        }

        return false;
    }

    private function paymentLocationRows(int $businessId): Collection
    {
        if (! Schema::hasTable('business_locations')) {
            return collect();
        }

        $columns = ['id'];
        if ($this->hasColumn('business_locations', 'default_payment_accounts')) {
            $columns[] = 'default_payment_accounts';
        }

        $query = DB::table('business_locations')->where('business_id', $businessId);
        if ($this->hasColumn('business_locations', 'is_active')) {
            $query->where(function ($builder): void {
                $builder->whereNull('is_active')->orWhere('is_active', 1);
            });
        }
        if ($this->hasColumn('business_locations', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy('id')->get($columns);
    }

    private function decodeLocationPaymentSettings(object $location): ?array
    {
        if (! $this->hasColumn('business_locations', 'default_payment_accounts')) {
            return null;
        }

        $decoded = json_decode((string) ($location->default_payment_accounts ?? ''), true);

        return is_array($decoded) ? $decoded : null;
    }

    /** @return array<int,array{id:int,name:string,location_id:?int,group_id:int}> */
    private function allBusinessAccountsForPaymentOptions(int $businessId): array
    {
        if (! Schema::hasTable('accounts')) {
            return [];
        }

        $query = DB::table('accounts as account')->where('account.business_id', $businessId);
        foreach ([['is_closed', 0], ['disabled', 0]] as [$column, $activeValue]) {
            if ($this->hasColumn('accounts', $column)) {
                $query->where(function ($builder) use ($column, $activeValue): void {
                    $builder->whereNull('account.' . $column)->orWhere('account.' . $column, $activeValue);
                });
            }
        }
        if ($this->hasColumn('accounts', 'visible')) {
            $query->where(function ($builder): void {
                $builder->whereNull('account.visible')->orWhere('account.visible', 1);
            });
        }
        if ($this->hasColumn('accounts', 'is_main_account')) {
            $query->where(function ($builder): void {
                $builder->whereNull('account.is_main_account')->orWhere('account.is_main_account', 0);
            });
        }
        if ($this->hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('account.deleted_at');
        }

        $select = ['account.id', 'account.name'];
        $select[] = $this->hasColumn('accounts', 'location_id')
            ? 'account.location_id'
            : DB::raw('NULL as location_id');
        $select[] = $this->hasColumn('accounts', 'asset_type')
            ? 'account.asset_type as group_id'
            : DB::raw('0 as group_id');

        return $query->select($select)->orderBy('account.name')->get()->map(static function ($row): array {
            return [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'location_id' => isset($row->location_id) ? (int) $row->location_id : null,
                'group_id' => isset($row->group_id) ? (int) $row->group_id : 0,
            ];
        })->all();
    }

    /** @return array<int,int> */
    private function linkedIds(mixed $value): array
    {
        $ids = [];
        $values = is_array($value) ? $value : [$value];
        array_walk_recursive($values, static function ($item) use (&$ids): void {
            if (is_numeric($item) && (int) $item > 0) {
                $ids[] = (int) $item;
            }
        });

        return array_values(array_unique($ids));
    }

    private function classifyPaymentMethod(string $method): string
    {
        $key = $this->normalize($method);
        $key = str_replace(['-', ' '], '_', $key);

        if ($key === 'cash' || str_contains($key, 'cash')) {
            return self::CASH;
        }
        if ($key === 'cheque' || str_contains($key, 'cheque') || str_contains($key, 'check')) {
            return self::CHEQUE;
        }
        if ($key === 'card' || $key === 'own_cards' || str_contains($key, 'card')) {
            return self::CARD;
        }

        // Direct deposits, transfers, pre-payments and unknown/custom methods
        // all post against the explicitly selected Finance account. BANK is the
        // neutral backward-compatible module bucket for that metadata column.
        return self::BANK;
    }

    private function paymentMethodLabel(string $method): string
    {
        $special = [
            'bank_transfer' => 'Bank Transfer',
            'direct_bank_deposit' => 'Direct Bank Deposit',
            'own_cards' => 'Own Cards',
            'credit_sale' => 'Credit Sale',
            'pre_payments' => 'Pre Payments',
        ];

        return $special[$method] ?? ucwords(str_replace(['_', '-'], ' ', $method));
    }

    private function isPaymentMethodKey(string $method): bool
    {
        if ($method === '' || strlen($method) > 100 || preg_match('/[\x00-\x1F\x7F]/', $method)) {
            return false;
        }

        $technical = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', $method), '_'));

        return ! in_array($technical, ['location_id', 'business_id', 'id', '_token', '_method'], true);
    }

    private function truthy(mixed $value): bool
    {
        return in_array($value, [1, '1', true, 'true', 'yes', 'on'], true);
    }

    /** @param array<int, array{id:int,name:string,location_id:?int}> $options */
    private function uniqueOptions(array $options): array
    {
        $unique = [];
        foreach ($options as $option) {
            $unique[(int) $option['id']] = $option;
        }

        return array_values($unique);
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?: $value;

        return $value;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (! array_key_exists($key, self::$columnCache)) {
            self::$columnCache[$key] = Schema::hasColumn($table, $column);
        }

        return self::$columnCache[$key];
    }
}

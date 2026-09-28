<?php

namespace Modules\Superadmin\Services;

use App\Business;
use App\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps the centrally configured default payment methods available in every
 * operational business/location without overwriting user changes.
 *
 * Authority rules:
 * - The method list + default enable/account template comes from the CENTRAL
 *   system.default_payment_accounts setting.
 * - A location's explicit default_payment_accounts choices are authoritative
 *   once a user has changed them. We only add genuinely missing system methods,
 *   fill missing legacy fields safely, and remove known technical/form keys.
 * - Operational data is read/written in the business's own tenant database,
 *   never by applying a central registry id to the current/default connection.
 */
class PaymentMethodDefaultsService
{
    /** @var array<string,string> */
    private array $runtimeConnections = [];

    /**
     * Every operational page selector is driven by these stored flags.
     * Unknown/custom legacy methods must default OFF for a page until the user
     * explicitly selects that page in Manage New -> Payment Options.
     *
     * @var array<int,string>
     */
    private const OPERATION_FLAGS = [
        'is_purchase_enabled',
        'is_sale_enabled',
        'is_expense_enabled',
        'is_purchase_return_enabled',
        'is_sale_return_enabled',
    ];

    /**
     * Technical/form keys that have historically leaked into the JSON and then
     * appeared as fake payment methods (for example "Location Id").
     *
     * @var array<int,string>
     */
    private const NON_PAYMENT_KEYS = [
        'location_id',
        'business_id',
        'id',
        '_token',
        '_method',
    ];

    /**
     * Built-in payment methods already defined by the application.
     *
     * These values mirror BusinessController::updatePaymentMthds(), which is
     * the existing system baseline. They are not a new user-facing catalogue:
     * Super Admin Settings may still override any field and may add custom
     * methods. Existing location values always win once a user changes them.
     */
    public function builtInDefaults(): array
    {
        return [
            'cash' => [
                'is_enabled' => 1, 'is_purchase_enabled' => 1, 'is_sale_enabled' => 1,
                'is_expense_enabled' => 1, 'is_purchase_return_enabled' => 1, 'is_sale_return_enabled' => 1,
                'is_custom' => 0, 'account' => null,
            ],
            'credit_sale' => [
                'is_enabled' => 1, 'is_purchase_enabled' => 0, 'is_sale_enabled' => 1,
                'is_expense_enabled' => 0, 'is_purchase_return_enabled' => 0, 'is_sale_return_enabled' => 1,
                'is_custom' => 0, 'account' => null,
            ],
            'own_cards' => [
                'is_enabled' => 1, 'is_purchase_enabled' => 1, 'is_sale_enabled' => 0,
                'is_expense_enabled' => 1, 'is_purchase_return_enabled' => 1, 'is_sale_return_enabled' => 0,
                'is_custom' => 0, 'account' => null,
            ],
            'card' => [
                'is_enabled' => 1, 'is_purchase_enabled' => 0, 'is_sale_enabled' => 1,
                'is_expense_enabled' => 0, 'is_purchase_return_enabled' => 0, 'is_sale_return_enabled' => 1,
                'is_custom' => 0, 'account' => null,
            ],
            'cheque' => [
                'is_enabled' => 1, 'is_purchase_enabled' => 0, 'is_sale_enabled' => 1,
                'is_expense_enabled' => 0, 'is_purchase_return_enabled' => 0, 'is_sale_return_enabled' => 1,
                'is_custom' => 0, 'account' => null,
            ],
            'direct_bank_deposit' => [
                'is_enabled' => 1, 'is_purchase_enabled' => 1, 'is_sale_enabled' => 1,
                'is_expense_enabled' => 1, 'is_purchase_return_enabled' => 1, 'is_sale_return_enabled' => 1,
                'is_custom' => 0, 'account' => null,
            ],
            'bank_transfer' => [
                'is_enabled' => 1, 'is_purchase_enabled' => 1, 'is_sale_enabled' => 1,
                'is_expense_enabled' => 1, 'is_purchase_return_enabled' => 1, 'is_sale_return_enabled' => 1,
                'is_custom' => 0, 'account' => null,
            ],
            'pre_payments' => [
                'is_enabled' => 1, 'is_purchase_enabled' => 1, 'is_sale_enabled' => 1,
                'is_expense_enabled' => 1, 'is_purchase_return_enabled' => 1, 'is_sale_return_enabled' => 1,
                'is_custom' => 0, 'account' => null,
            ],
        ];
    }

    /**
     * Return the effective system payment template.
     *
     * The built-in methods are the minimum baseline. The stored Super Admin
     * system/default_payment_accounts JSON overrides those defaults and may add
     * custom methods. This fixes the circular old behaviour where the Settings
     * page/location creation derived the method list from an existing location.
     */
    public function systemDefaults(): array
    {
        $central = CentralContext::trueCentralConnectionName();
        $configured = $this->readSystemDefaultsFromConnection($central);
        $defaults = [];

        foreach ($this->builtInDefaults() as $method => $settings) {
            if (! $this->isPaymentMethodKey($method)) {
                continue;
            }

            $defaults[$method] = $this->normaliseSettings(
                is_array($settings) ? $settings : (array) $settings,
                [],
                0
            );
        }

        foreach ($configured as $method => $settings) {
            $method = trim((string) $method);
            if (! $this->isPaymentMethodKey($method)) {
                continue;
            }

            $settings = is_array($settings) ? $settings : (array) $settings;
            $baseline = $defaults[$method] ?? [];

            // A system-level method is a catalogue/default, not a business-level
            // custom row. Unknown operation flags default OFF until that business
            // explicitly enables them in Manage New -> Payment Options.
            $defaults[$method] = $this->normaliseSettings(
                $settings,
                $baseline,
                0
            );
            // Anything defined in the system catalogue is a system method.
            // Do not allow an old is_custom flag in stored settings to turn it
            // into a removable business-level custom row.
            $defaults[$method]['is_custom'] = 0;
        }

        return $defaults;
    }

    /**
     * Ensure one already-resolved operational location contains the complete
     * effective system payment catalogue.
     *
     * This is the plug-and-play runtime path used by Util::payment_types().
     * It repairs only the location being used and preserves every explicit
     * user choice. Missing methods/fields are filled from the central template.
     *
     * @return array<string,array>
     */
    public function ensureLocationForRuntime($location): array
    {
        if (empty($location)) {
            return [];
        }

        if (! is_object($location)) {
            $location = \App\BusinessLocation::find($location);
        }

        if (empty($location)) {
            return [];
        }

        $connectionName = method_exists($location, 'getConnectionName')
            ? ($location->getConnectionName() ?: config('database.default'))
            : config('database.default');

        $connection = DB::connection($connectionName);
        $schema = $connection->getSchemaBuilder();

        if (! $schema->hasTable('business_locations')) {
            return [];
        }

        $businessId = (int) ($location->business_id ?? 0);
        $locationId = (int) ($location->id ?? 0);

        if ($businessId <= 0 || $locationId <= 0) {
            return [];
        }

        $defaults = $this->systemDefaults();
        $existingRaw = \json_decode((string) ($location->default_payment_accounts ?? ''), true);
        $existingRaw = is_array($existingRaw) ? $existingRaw : [];

        $accountMaps = $this->accountMapsForBusinesses(
            $connectionName,
            [$businessId],
            $defaults
        );

        // Clean technical keys and complete legacy/custom rows first. A custom
        // row with a missing per-screen flag is OFF for that screen, never ON.
        $merged = $this->normaliseLocationMethods(
            $existingRaw,
            $defaults,
            $accountMaps[$businessId] ?? []
        );

        // New system methods are added without altering any explicit business
        // choice already stored for an existing method.
        foreach ($defaults as $method => $settings) {
            if (array_key_exists($method, $merged)) {
                continue;
            }

            $defaultAccountId = ! empty($settings['account'])
                ? (int) $settings['account']
                : 0;

            $mappedAccount = $defaultAccountId > 0
                ? (($accountMaps[$businessId] ?? [])[$defaultAccountId] ?? null)
                : null;

            $newSettings = $this->normaliseSettings(
                is_array($settings) ? $settings : (array) $settings,
                [],
                0
            );
            $newSettings['account'] = $mappedAccount;
            $merged[$method] = $newSettings;
        }

        if ($merged !== $existingRaw) {
            $connection->table('business_locations')
                ->where('id', $locationId)
                ->where('business_id', $businessId)
                ->update([
                    'default_payment_accounts' => \json_encode($merged),
                ]);

            try {
                $location->default_payment_accounts = \json_encode($merged);
            } catch (\Throwable $ignore) {
                // The database update is authoritative.
            }
        }

        return $merged;
    }

    private function isPaymentMethodKey(string $method): bool
    {
        $method = trim($method);
        if ($method === '') {
            return false;
        }

        // Compare a normalised form so historical pollution such as
        // "Location Id", "location-id" and "location_id" is treated equally.
        $technicalKey = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '_', $method));
        $technicalKey = trim($technicalKey, '_');
        if (in_array($technicalKey, self::NON_PAYMENT_KEYS, true)) {
            return false;
        }

        // Preserve legitimate legacy/custom keys even if an older screen stored
        // spaces or dashes. New methods are still generated as snake_case by
        // the Manage New UI.
        return strlen($method) <= 100
            && ! preg_match('/[\x00-\x1F\x7F]/', $method);
    }

    /**
     * Complete one method without ever converting a missing custom operation
     * flag into an enabled flag.
     */
    private function normaliseSettings(array $current, array $baseline = [], int $customDefault = 0): array
    {
        $normalised = array_replace([
            'is_enabled' => 0,
            'is_purchase_enabled' => 0,
            'is_sale_enabled' => 0,
            'is_expense_enabled' => 0,
            'is_purchase_return_enabled' => 0,
            'is_sale_return_enabled' => 0,
            'is_custom' => $customDefault,
            'account' => null,
        ], $baseline, $current);

        $normalised['is_enabled'] = ! empty($normalised['is_enabled']) ? 1 : 0;
        foreach (self::OPERATION_FLAGS as $flag) {
            $normalised[$flag] = ! empty($normalised[$flag]) ? 1 : 0;
        }
        $normalised['is_custom'] = ! empty($normalised['is_custom']) ? 1 : 0;

        if ($normalised['account'] === '' || $normalised['account'] === 0 || $normalised['account'] === '0') {
            $normalised['account'] = null;
        }

        return $normalised;
    }

    /**
     * Normalise persisted location JSON while preserving valid custom methods
     * and every explicit business choice. Technical keys are removed.
     *
     * @param array<string,array> $existing
     * @param array<string,array> $defaults
     * @param array<int,int> $accountMap
     * @return array<string,array>
     */
    private function normaliseLocationMethods(array $existing, array $defaults, array $accountMap = []): array
    {
        $clean = [];

        foreach ($existing as $method => $settings) {
            $method = trim((string) $method);
            if (! $this->isPaymentMethodKey($method)) {
                continue;
            }

            $settings = is_array($settings) ? $settings : (array) $settings;
            $baseline = $defaults[$method] ?? [];

            if ($baseline !== [] && array_key_exists('account', $baseline)) {
                $defaultAccountId = ! empty($baseline['account']) ? (int) $baseline['account'] : 0;
                if ($defaultAccountId > 0) {
                    $baseline['account'] = $accountMap[$defaultAccountId] ?? null;
                } else {
                    $baseline['account'] = null;
                }
            }

            $customDefault = array_key_exists($method, $defaults) ? 0 : 1;
            $clean[$method] = $this->normaliseSettings($settings, $baseline, $customDefault);

            // System methods must never become removable custom rows merely
            // because an older/buggy form posted the wrong is_custom value.
            if (array_key_exists($method, $defaults)) {
                $clean[$method]['is_custom'] = 0;
            }
        }

        return $clean;
    }

    private function readSystemDefaultsFromConnection(string $connectionName): array
    {
        $connection = DB::connection($connectionName);
        $schema = $connection->getSchemaBuilder();
        $table = (new \App\System())->getTable();
        if (! $schema->hasTable($table)) {
            return [];
        }

        $raw = $connection->table($table)
            ->where('key', 'default_payment_accounts')
            ->value('value');
        $decoded = is_array($raw) ? $raw : \json_decode((string) $raw, true);
        $decoded = is_array($decoded) ? $decoded : [];

        $clean = [];
        foreach ($decoded as $method => $settings) {
            $method = trim((string) $method);
            if ($this->isPaymentMethodKey($method)) {
                $clean[$method] = is_array($settings) ? $settings : (array) $settings;
            }
        }

        return $clean;
    }

    /**
     * Make the built-in system catalogue physically present in one database so
     * future BusinessUtil::addLocation() calls see the complete list. Existing
     * configured values/custom methods are preserved.
     *
     * @return int Number of methods newly added to the system catalogue.
     */
    private function ensureSystemCatalogue(string $connectionName, bool $apply): int
    {
        $connection = DB::connection($connectionName);
        $schema = $connection->getSchemaBuilder();
        $table = (new \App\System())->getTable();
        if (! $schema->hasTable($table)) {
            return 0;
        }

        $existing = $this->readSystemDefaultsFromConnection($connectionName);
        $merged = $existing;
        $added = 0;

        foreach ($this->builtInDefaults() as $method => $baseline) {
            if (! array_key_exists($method, $merged)) {
                $merged[$method] = $baseline;
                $added++;
                continue;
            }
            $current = is_array($merged[$method]) ? $merged[$method] : (array) $merged[$method];
            // Fill only fields that never existed; explicit system settings win.
            $merged[$method] = array_replace($baseline, $current);
        }

        if ($apply && ($added > 0 || $merged !== $existing)) {
            $row = $connection->table($table)->where('key', 'default_payment_accounts')->first();
            if ($row) {
                $connection->table($table)->where('key', 'default_payment_accounts')
                    ->update(['value' => \json_encode($merged)]);
            } else {
                $connection->table($table)->insert([
                    'key' => 'default_payment_accounts',
                    'value' => \json_encode($merged),
                ]);
            }
        }

        return $added;
    }

    /**
     * Resolve the selected CENTRAL registry business to the database and row
     * that actually owns its locations/accounts.
     *
     * @return array{connection:string,database:string,business_id:int}
     */
    public function operationalContext(Business $registryBusiness): array
    {
        /*
         | IS2341: Payment Options must follow the SAME Super Admin database
         | mode that selected the business.
         |
         | CentralContext::findBusinessOrFail() deliberately follows the active
         | tenant database when SUPERADMIN_USE_ACTIVE_CONNECTION=true. In that
         | mode the selected business id is LOCAL to the active database and
         | must never be reinterpreted as a central-registry id. Purchase and
         | Expenses on that host read the same active database, so Payment
         | Options must save there too.
         */
        if ((bool) config('tenancy.superadmin_use_active_connection', false)) {
            $active = CentralContext::connectionName();
            $connection = DB::connection($active);
            $schema = $connection->getSchemaBuilder();
            $businessId = (int) ($registryBusiness->id ?? 0);

            if ($businessId <= 0) {
                throw new \RuntimeException('A valid business id is required for Payment Options.');
            }

            if (! $schema->hasTable('business')) {
                throw new \RuntimeException(
                    'Active database [' . (string) $connection->getDatabaseName() . '] has no business table.'
                );
            }

            $exists = $connection->table('business')
                ->where('id', $businessId)
                ->exists();

            if (! $exists) {
                throw new \RuntimeException(
                    'Business [' . $businessId . '] does not exist in active database [' .
                    (string) $connection->getDatabaseName() . '].'
                );
            }

            return [
                'connection' => $active,
                'database' => (string) $connection->getDatabaseName(),
                'business_id' => $businessId,
            ];
        }

        /*
         | Normal estate-wide mode:
         | the selected model is a true CENTRAL registry business. Resolve it
         | to the tenant database that owns its business_locations/accounts.
         */
        $central = CentralContext::trueCentralConnectionName();
        $registryId = (int) ($registryBusiness->id ?? 0);
        if ($registryId <= 0) {
            throw new \RuntimeException('A valid central business id is required for Payment Options.');
        }

        // Pin the model to true central in normal mode. This is safe here
        // because CentralContext::findBusinessOrFail() also reads central when
        // the active-connection opt-out above is disabled.
        $centralRegistryBusiness = Business::on($central)->find($registryId);
        if (! $centralRegistryBusiness) {
            throw new \RuntimeException(
                'Business [' . $registryId . '] is not present in the central business registry.'
            );
        }
        $registryBusiness = $centralRegistryBusiness;

        $tenantId = trim((string) ($registryBusiness->tenant_id ?? ''));

        // A null/blank tenant_id means this business itself lives in central.
        if ($tenantId === '') {
            return [
                'connection' => $central,
                'database' => (string) DB::connection($central)->getDatabaseName(),
                'business_id' => (int) $registryBusiness->id,
            ];
        }

        $tenant = Tenant::on($central)->whereKey($tenantId)->first();
        if (! $tenant) {
            throw new \RuntimeException(
                'The tenant [' . $tenantId . '] for this business is not registered in the central tenants table.'
            );
        }

        $database = (string) $tenant->getDatabaseName();
        if ($database === '') {
            throw new \RuntimeException('The tenant [' . $tenantId . '] has no database name.');
        }

        $connection = $this->connectionForDatabase($database);
        $schema = DB::connection($connection)->getSchemaBuilder();

        if (! $schema->hasTable('business')) {
            throw new \RuntimeException('Tenant database [' . $database . '] has no business table.');
        }

        $query = DB::connection($connection)->table('business');
        $tenantBusiness = null;
        $globalUid = trim((string) ($registryBusiness->global_uid ?? ''));

        // global_uid is the safest cross-database identity.
        if ($globalUid !== '' && $schema->hasColumn('business', 'global_uid')) {
            $tenantBusiness = (clone $query)
                ->select('id')
                ->where('global_uid', $globalUid)
                ->first();

            if (! $tenantBusiness) {
                // Never fall back to numeric id after a uid is known: numeric
                // ids are per-database and may belong to another company.
                throw new \RuntimeException(
                    'No business with global_uid [' . $globalUid . '] exists in tenant database [' . $database . '].'
                );
            }
        }

        if (! $tenantBusiness) {
            // Compatibility for older rows that pre-date global_uid.
            $name = trim((string) ($registryBusiness->name ?? ''));
            $companyNumber = trim((string) ($registryBusiness->company_number ?? ''));

            if ($name !== '' && $companyNumber !== ''
                && $schema->hasColumn('business', 'name')
                && $schema->hasColumn('business', 'company_number')) {
                $matches = (clone $query)
                    ->select('id')
                    ->where('name', $name)
                    ->where('company_number', $companyNumber)
                    ->limit(2)
                    ->get();

                if ($matches->count() === 1) {
                    $tenantBusiness = $matches->first();
                }
            }

            if (! $tenantBusiness && $name !== '' && $schema->hasColumn('business', 'name')) {
                $matches = (clone $query)
                    ->select('id')
                    ->where('name', $name)
                    ->limit(2)
                    ->get();

                if ($matches->count() === 1) {
                    $tenantBusiness = $matches->first();
                }
            }

            if (! $tenantBusiness) {
                $byId = (clone $query)
                    ->select(['id', 'name'])
                    ->where('id', (int) $registryBusiness->id)
                    ->first();

                if ($byId && ($name === '' || (string) ($byId->name ?? '') === $name)) {
                    $tenantBusiness = $byId;
                }
            }
        }

        if (! $tenantBusiness) {
            throw new \RuntimeException(
                'Could not safely match this central business to a business row in tenant database [' . $database . '].'
            );
        }

        return [
            'connection' => $connection,
            'database' => $database,
            'business_id' => (int) $tenantBusiness->id,
        ];
    }

    /**
     * Prepare Payment Options for Manage New. Missing system-defined methods
     * are inserted into the selected business's locations. Explicit saved values
     * are preserved; missing legacy fields are completed safely and known
     * technical/form keys are removed.
     *
     * @return array{locations:Collection,account_groups:Collection,context:array}
     */
    public function prepareBusinessForManage(Business $registryBusiness): array
    {
        $defaults = $this->systemDefaults();
        if ($defaults === []) {
            throw new \RuntimeException(
                'No default payment methods are configured in Super Admin Settings.'
            );
        }

        $context = $this->operationalContext($registryBusiness);
        $this->ensureDefaultsForBusiness($context, $defaults, true);

        $connection = DB::connection($context['connection']);
        $locations = $connection->table('business_locations')
            ->where('business_id', $context['business_id'])
            ->select(['id', 'name', 'default_payment_accounts'])
            ->orderBy('name')
            ->get();

        $accountGroups = collect();
        if ($connection->getSchemaBuilder()->hasTable('account_groups')) {
            $accountGroups = $connection->table('account_groups')
                ->where('business_id', $context['business_id'])
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        return [
            'locations' => $locations,
            'account_groups' => $accountGroups,
            'context' => $context,
        ];
    }

    /**
     * Save Manage New payment options into the selected business's operational
     * database. Location ids are validated against that business before update.
     */
    public function saveBusinessOptions(Business $registryBusiness, array $postedByLocation): int
    {
        $context = $this->operationalContext($registryBusiness);
        $connection = DB::connection($context['connection']);
        $systemMethods = array_fill_keys(array_keys($this->systemDefaults()), true);
        $written = 0;

        $connection->transaction(function () use (
            $connection,
            $context,
            $postedByLocation,
            $systemMethods,
            &$written
        ): void {
            foreach ($postedByLocation as $locationId => $posted) {
                if (! is_array($posted) || empty($posted['name']) || ! is_array($posted['name'])) {
                    continue;
                }

                $location = $connection->table('business_locations')
                    ->where('business_id', $context['business_id'])
                    ->where('id', (int) $locationId)
                    ->lockForUpdate()
                    ->first(['id']);

                if (! $location) {
                    continue;
                }

                // The submitted rows are authoritative for this location.
                // Removed custom methods disappear; unchecked operation flags
                // are written as 0; technical/form keys can never become methods.
                $payments = [];

                foreach ($posted['name'] as $index => $methodName) {
                    $methodName = trim((string) $methodName);
                    if (! $this->isPaymentMethodKey($methodName)) {
                        continue;
                    }

                    if (array_key_exists($methodName, $payments)) {
                        throw new \RuntimeException(
                            'Duplicate payment method [' . $methodName . '] was submitted for location [' . (int) $locationId . '].'
                        );
                    }

                    $isSystemMethod = isset($systemMethods[$methodName]);
                    $payments[$methodName] = $this->normaliseSettings([
                        'is_enabled'                 => (int) ($posted['is_enabled'][$index] ?? 0),
                        'is_purchase_enabled'        => (int) ($posted['is_purchase_enabled'][$index] ?? 0),
                        'is_sale_enabled'            => (int) ($posted['is_sale_enabled'][$index] ?? 0),
                        'is_expense_enabled'         => (int) ($posted['is_expense_enabled'][$index] ?? 0),
                        'is_purchase_return_enabled' => (int) ($posted['is_purchase_return_enabled'][$index] ?? 0),
                        'is_sale_return_enabled'     => (int) ($posted['is_sale_return_enabled'][$index] ?? 0),
                        'is_custom'                  => $isSystemMethod ? 0 : 1,
                        'account'                    => $posted['account'][$index] ?? null,
                    ], [], $isSystemMethod ? 0 : 1);
                }

                if ($payments === []) {
                    continue;
                }

                $connection->table('business_locations')
                    ->where('id', (int) $location->id)
                    ->where('business_id', $context['business_id'])
                    ->update([
                        'default_payment_accounts' => \json_encode($payments),
                    ]);

                $written++;
            }
        });

        return $written;
    }

    /**
     * Repair all existing databases. Only missing SYSTEM methods are added.
     * Existing user configuration is never overwritten.
     *
     * @return array{databases:int,businesses:int,locations:int,locations_changed:int,methods_added:int,errors:array}
     */
    public function repairAllLocations(bool $apply = false): array
    {
        $defaults = $this->systemDefaults();
        if ($defaults === []) {
            throw new \RuntimeException(
                'No default payment methods are configured in Super Admin Settings; repair aborted.'
            );
        }

        $central = CentralContext::trueCentralConnectionName();
        $targets = [];
        $centralDatabase = (string) DB::connection($central)->getDatabaseName();
        $targets[$centralDatabase] = $central;

        try {
            foreach (Tenant::on($central)->get() as $tenant) {
                $database = (string) $tenant->getDatabaseName();
                if ($database !== '') {
                    $targets[$database] = $this->connectionForDatabase($database);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Default payment repair could not enumerate tenants.', [
                'message' => $e->getMessage(),
            ]);
        }

        $totals = [
            'databases' => 0,
            'businesses' => 0,
            'locations' => 0,
            'locations_changed' => 0,
            'methods_added' => 0,
            'catalogue_methods_added' => 0,
            'errors' => [],
        ];

        foreach ($targets as $database => $connectionName) {
            try {
                $totals['catalogue_methods_added'] += $this->ensureSystemCatalogue($connectionName, $apply);
                $stats = $this->repairDatabase($connectionName, $defaults, $apply);
                $totals['databases']++;
                $totals['businesses'] += $stats['businesses'];
                $totals['locations'] += $stats['locations'];
                $totals['locations_changed'] += $stats['locations_changed'];
                $totals['methods_added'] += $stats['methods_added'];
            } catch (\Throwable $e) {
                $totals['errors'][] = $database . ': ' . $e->getMessage();
                Log::warning('Default payment repair skipped database.', [
                    'database' => $database,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $totals;
    }

    /**
     * @return array{businesses:int,locations:int,locations_changed:int,methods_added:int}
     */
    private function repairDatabase(string $connectionName, array $defaults, bool $apply): array
    {
        $connection = DB::connection($connectionName);
        $schema = $connection->getSchemaBuilder();

        if (! $schema->hasTable('business_locations')) {
            return [
                'businesses' => 0,
                'locations' => 0,
                'locations_changed' => 0,
                'methods_added' => 0,
            ];
        }

        $locations = $connection->table('business_locations')
            ->select(['id', 'business_id', 'default_payment_accounts'])
            ->orderBy('business_id')
            ->orderBy('id')
            ->get();

        $businessIds = $locations->pluck('business_id')->map(static fn ($id) => (int) $id)->unique()->values();
        $accountMaps = $this->accountMapsForBusinesses($connectionName, $businessIds->all(), $defaults);

        $changedLocations = 0;
        $methodsAdded = 0;

        foreach ($locations as $location) {
            $existing = \json_decode((string) ($location->default_payment_accounts ?? ''), true);
            $existing = is_array($existing) ? $existing : [];
            $businessAccountMap = $accountMaps[(int) $location->business_id] ?? [];

            $merged = $this->normaliseLocationMethods($existing, $defaults, $businessAccountMap);
            foreach ($defaults as $method => $settings) {
                if (array_key_exists($method, $merged)) {
                    continue;
                }

                $settings = is_array($settings) ? $settings : [];
                $defaultAccountId = ! empty($settings['account']) ? (int) $settings['account'] : 0;
                $newSettings = $this->normaliseSettings($settings, [], 0);
                $newSettings['account'] = $defaultAccountId > 0
                    ? ($businessAccountMap[$defaultAccountId] ?? null)
                    : null;
                $merged[$method] = $newSettings;
                $methodsAdded++;
            }

            if ($merged === $existing) {
                continue;
            }

            $changedLocations++;

            if ($apply) {
                $connection->table('business_locations')
                    ->where('id', (int) $location->id)
                    ->where('business_id', (int) $location->business_id)
                    ->update(['default_payment_accounts' => \json_encode($merged)]);
            }
        }

        return [
            'businesses' => $businessIds->count(),
            'locations' => $locations->count(),
            'locations_changed' => $changedLocations,
            'methods_added' => $methodsAdded,
        ];
    }

    /**
     * Ensure missing system methods for one operational business.
     */
    private function ensureDefaultsForBusiness(array $context, array $defaults, bool $apply): void
    {
        $connection = DB::connection($context['connection']);
        $locations = $connection->table('business_locations')
            ->where('business_id', $context['business_id'])
            ->select(['id', 'business_id', 'default_payment_accounts'])
            ->get();

        $accountMaps = $this->accountMapsForBusinesses(
            $context['connection'],
            [$context['business_id']],
            $defaults
        );

        foreach ($locations as $location) {
            $existing = \json_decode((string) ($location->default_payment_accounts ?? ''), true);
            $existing = is_array($existing) ? $existing : [];
            $businessAccountMap = $accountMaps[$context['business_id']] ?? [];

            $merged = $this->normaliseLocationMethods($existing, $defaults, $businessAccountMap);

            foreach ($defaults as $method => $settings) {
                if (array_key_exists($method, $merged)) {
                    continue;
                }

                $settings = is_array($settings) ? $settings : [];
                $defaultAccountId = ! empty($settings['account']) ? (int) $settings['account'] : 0;
                $newSettings = $this->normaliseSettings($settings, [], 0);
                $newSettings['account'] = $defaultAccountId > 0
                    ? ($businessAccountMap[$defaultAccountId] ?? null)
                    : null;
                $merged[$method] = $newSettings;
            }

            if ($merged !== $existing && $apply) {
                $connection->table('business_locations')
                    ->where('id', (int) $location->id)
                    ->where('business_id', $context['business_id'])
                    ->update(['default_payment_accounts' => \json_encode($merged)]);
            }
        }
    }

    /**
     * Map central DefaultAccount ids to each local business's accounts.id.
     *
     * @return array<int,array<int,int>>
     */
    private function accountMapsForBusinesses(string $connectionName, array $businessIds, array $defaults): array
    {
        $connection = DB::connection($connectionName);
        $schema = $connection->getSchemaBuilder();
        $maps = [];

        if ($businessIds === []
            || ! $schema->hasTable('accounts')
            || ! $schema->hasColumn('accounts', 'business_id')
            || ! $schema->hasColumn('accounts', 'default_account_id')) {
            return $maps;
        }

        $defaultAccountIds = [];
        foreach ($defaults as $settings) {
            $settings = is_array($settings) ? $settings : [];
            if (! empty($settings['account'])) {
                $defaultAccountIds[] = (int) $settings['account'];
            }
        }
        $defaultAccountIds = array_values(array_unique(array_filter($defaultAccountIds)));

        if ($defaultAccountIds === []) {
            return $maps;
        }

        $rows = $connection->table('accounts')
            ->whereIn('business_id', array_values(array_unique(array_map('intval', $businessIds))))
            ->whereIn('default_account_id', $defaultAccountIds)
            ->select(['id', 'business_id', 'default_account_id'])
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $businessId = (int) $row->business_id;
            $defaultId = (int) $row->default_account_id;
            if (! isset($maps[$businessId][$defaultId])) {
                $maps[$businessId][$defaultId] = (int) $row->id;
            }
        }

        return $maps;
    }

    /**
     * Return/reuse a dedicated runtime connection for one database. Never
     * mutate Laravel's default connection while a Super Admin request is alive.
     */
    private function connectionForDatabase(string $database): string
    {
        if (isset($this->runtimeConnections[$database])) {
            return $this->runtimeConnections[$database];
        }

        foreach ((array) config('database.connections', []) as $name => $config) {
            if (is_array($config) && (string) ($config['database'] ?? '') === $database) {
                return $this->runtimeConnections[$database] = (string) $name;
            }
        }

        $base = (array) config('database.connections.mysql', []);
        if ($base === []) {
            $base = (array) config('database.connections.' . config('database.default'), []);
        }
        if ($base === []) {
            throw new \RuntimeException('No database connection template is available for tenant [' . $database . '].');
        }

        $name = 'superadmin_payment_' . substr(sha1($database), 0, 12);
        $base['database'] = $database;
        config(['database.connections.' . $name => $base]);
        DB::purge($name);

        return $this->runtimeConnections[$database] = $name;
    }
}

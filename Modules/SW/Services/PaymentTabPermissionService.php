<?php

namespace Modules\SW\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Visibility for the five core SW Payments recording tabs.
 *
 * These belong to the standalone SW module. They must not inherit the legacy
 * Daily Collection switches from Superadmin. Role permissions remain the user-
 * level gate for viewing/using an available tab.
 */
class PaymentTabPermissionService
{
    /** @var array<string, array{key:string,default:bool,label:string}> */
    private const TABS = [
        'daily_cash' => [
            'key' => 'sw_payments_daily_cash',
            'default' => true,
            'label' => 'Daily Cash',
        ],
        'daily_credit_sales' => [
            'key' => 'sw_payments_daily_credit_sales',
            'default' => true,
            'label' => 'Daily Credit Sales',
        ],
        'daily_cards' => [
            'key' => 'sw_payments_daily_cards',
            'default' => true,
            'label' => 'Daily Cards',
        ],
        'daily_shortage_excess' => [
            'key' => 'sw_payments_daily_shortage_excess',
            'default' => true,
            'label' => 'Daily Shortage Excess',
        ],
        'daily_cheques' => [
            'key' => 'sw_payments_daily_cheques',
            'default' => true,
            'label' => 'Daily Cheques',
        ],
    ];

    /** @var array<int, array<string, mixed>|null> */
    private static array $requestPackageDetails = [];

    /** @var array<int, int> */
    private static array $requestCentralBusinessIds = [];

    public static function definitions(): array
    {
        return self::TABS;
    }

    public function enabled(string $tab, int $businessId): bool
    {
        if (! isset(self::TABS[$tab])) {
            return false;
        }

        $definition = self::TABS[$tab];
        $details = $this->packageDetails($businessId);

        /*
         | IS2209: the five SW Payments tabs are core parts of the standalone
         | SW module.  The legacy Daily Collection switches in Superadmin are
         | for a different/older feature and must not hide these tabs.
         |
         | IS2208 briefly mirrored those legacy switches into sw_payments_*
         | keys. Existing subscriptions may therefore contain accidental 0
         | values. Do not honour those copied values unless a future dedicated
         | SW Payments visibility UI explicitly marks the configuration as
         | intentional with sw_payments_visibility_configured.
        */
        $explicitlyConfigured = is_array($details)
            && $this->truthy($details['sw_payments_visibility_configured'] ?? false);

        if ($explicitlyConfigured && array_key_exists($definition['key'], $details)) {
            return $this->truthy($details[$definition['key']]);
        }

        // Until a dedicated SW Payments visibility control is configured, all
        // five core recording tabs are available. Role permissions still
        // decide which of those tabs a user may see/use.
        return (bool) $definition['default'];
    }

    /** @return array<string, bool> */
    public function states(int $businessId): array
    {
        $states = [];
        foreach (array_keys(self::TABS) as $tab) {
            $states[$tab] = $this->enabled($tab, $businessId);
        }

        return $states;
    }

    /** @return array<string, mixed>|null */
    private function packageDetails(int $businessId): ?array
    {
        if ($businessId <= 0) {
            return null;
        }

        if (array_key_exists($businessId, self::$requestPackageDetails)) {
            return self::$requestPackageDetails[$businessId];
        }

        try {
            $connection = $this->centralConnectionName();
            $centralBusinessId = $this->centralBusinessId($businessId, $connection);
            $today = date('Y-m-d');

            $query = DB::connection($connection)
                ->table('subscriptions')
                ->where('business_id', $centralBusinessId)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereDate('end_date', '>=', $today)
                        ->orWhereNull('end_date');
                });

            if (Schema::connection($connection)->hasColumn('subscriptions', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            $row = $query
                ->orderByDesc('end_date')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first();

            if (empty($row) || empty($row->package_details)) {
                return self::$requestPackageDetails[$businessId] = [];
            }

            $decoded = is_array($row->package_details)
                ? $row->package_details
                : json_decode((string) $row->package_details, true);

            return self::$requestPackageDetails[$businessId] = is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) {
            // Do not turn a temporary central-read problem into an SW Payments
            // 500. Falling back to the declared defaults is deterministic and
            // safer than exposing every optional tab.
            return self::$requestPackageDetails[$businessId] = null;
        }
    }

    private function centralConnectionName(): string
    {
        if (class_exists(\Modules\Superadmin\Services\CentralContext::class)) {
            return \Modules\Superadmin\Services\CentralContext::trueCentralConnectionName();
        }

        if (! empty(config('database.connections.system.database'))) {
            return 'system';
        }

        return (string) config('tenancy.database.central_connection', config('database.default'));
    }

    /**
     * Resolve the central business row by global UID where available. Tenant
     * business ids are not globally unique across this application.
     */
    private function centralBusinessId(int $businessId, string $connection): int
    {
        if (isset(self::$requestCentralBusinessIds[$businessId])) {
            return self::$requestCentralBusinessIds[$businessId];
        }

        try {
            if (Schema::hasTable('business') && Schema::hasColumn('business', 'global_uid')) {
                $uid = DB::table('business')->where('id', $businessId)->value('global_uid');

                if (! empty($uid)
                    && Schema::connection($connection)->hasTable('business')
                    && Schema::connection($connection)->hasColumn('business', 'global_uid')) {
                    $centralId = DB::connection($connection)
                        ->table('business')
                        ->where('global_uid', $uid)
                        ->value('id');

                    if (! empty($centralId)) {
                        return self::$requestCentralBusinessIds[$businessId] = (int) $centralId;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Legacy installs without global_uid fall through to business id.
        }

        return self::$requestCentralBusinessIds[$businessId] = $businessId;
    }

    private function truthy($value): bool
    {
        if (is_array($value) || is_object($value)) {
            return true;
        }

        $flag = is_string($value) ? strtolower(trim($value)) : $value;

        return in_array($flag, [1, '1', true, 'true', 'yes', 'on', 'enabled'], true);
    }
}

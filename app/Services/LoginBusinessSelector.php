<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Builds the login business selector from the CENTRAL registry.
 *
 * All database discovery stays outside Blade views. On tenant hosts the
 * central rows are limited to the active tenant using the reconciled
 * tenant_id/global_uid identity, with company_number retained only as a
 * compatibility fallback for older tenant databases.
 */
class LoginBusinessSelector
{
    public function businesses(): Collection
    {
        $central = $this->centralConnection();
        $today = now()->toDateString();

        if (! Schema::connection($central)->hasTable('business')) {
            return collect();
        }

        $query = DB::connection($central)
            ->table('business')
            ->leftJoin('subscriptions', function ($join) use ($today) {
                $join->on('business.id', '=', 'subscriptions.business_id')
                    ->whereDate('subscriptions.start_date', '<=', $today)
                    ->whereDate('subscriptions.end_date', '>=', $today)
                    ->where('subscriptions.status', 'approved');
            })
            ->leftJoin('business_locations', 'business_locations.business_id', '=', 'business.id')
            ->whereNotNull('business.name')
            ->whereNotNull('business.company_number');

        $this->scopeToCurrentTenant($query, $central);

        $selects = [
            'business.id',
            'business.name',
            'business.company_number',
            'business.common_settings',
            'subscriptions.package_details',
            DB::raw('MIN(business_locations.name) as location_name'),
        ];

        if (Schema::connection($central)->hasColumn('business', 'tenant_id')) {
            $selects[] = 'business.tenant_id';
        }
        if (Schema::connection($central)->hasColumn('business', 'global_uid')) {
            $selects[] = 'business.global_uid';
        }

        $groupBy = [
            'business.id',
            'business.name',
            'business.company_number',
            'business.common_settings',
            'subscriptions.package_details',
        ];
        if (Schema::connection($central)->hasColumn('business', 'tenant_id')) {
            $groupBy[] = 'business.tenant_id';
        }
        if (Schema::connection($central)->hasColumn('business', 'global_uid')) {
            $groupBy[] = 'business.global_uid';
        }

        $businesses = $query->select($selects)->groupBy($groupBy)->get();
        $tenantNames = $this->tenantNameMap($central);
        $currentTenantName = $this->currentTenantName();

        return $businesses
            ->filter(function ($business) {
                $package = json_decode($business->package_details ?? '{}', true) ?: [];
                $common = json_decode($business->common_settings ?? '{}', true) ?: [];

                // Preserve the existing login selector rule, but derive it from
                // the already-loaded central subscription instead of querying
                // ModuleUtil again with a central business id on a tenant DB.
                $petro = ! empty($package['enable_petro_module']);
                $myAuto = ! empty($common['is_my_auto']) || ! empty($package['my_auto']);

                return $petro || $myAuto;
            })
            ->map(function ($business) use ($tenantNames, $currentTenantName) {
                $tenantKey = (string) ($business->tenant_id ?? '');
                $business->tenant_name = $tenantNames[$tenantKey]
                    ?? $currentTenantName
                    ?? ($tenantKey !== '' ? $tenantKey : 'Central');

                return $business;
            })
            ->values();
    }

    private function scopeToCurrentTenant($query, string $central): void
    {
        if (! function_exists('tenancy') || ! tenancy()->initialized) {
            return;
        }

        $tenant = function_exists('current_tenant') ? current_tenant() : tenant();
        $tenantData = (array) ($tenant->data ?? []);
        $tenantKeys = array_values(array_unique(array_filter([
            (string) ($tenant->id ?? ''),
            (string) data_get($tenantData, 'tenancy_db_name', ''),
        ])));

        $tenantGlobalUids = [];
        $tenantCompanyNumbers = [];

        try {
            if (Schema::hasTable('business')) {
                if (Schema::hasColumn('business', 'global_uid')) {
                    $tenantGlobalUids = DB::table('business')
                        ->whereNotNull('global_uid')
                        ->pluck('global_uid')
                        ->filter()->values()->all();
                }

                $tenantCompanyNumbers = DB::table('business')
                    ->whereNotNull('company_number')
                    ->pluck('company_number')
                    ->filter()->values()->all();
            }
        } catch (\Throwable $e) {
            // The central tenant_id scope below remains authoritative where
            // the tenant business table is incomplete during a new setup.
        }

        $hasTenantId = Schema::connection($central)->hasColumn('business', 'tenant_id');
        $hasGlobalUid = Schema::connection($central)->hasColumn('business', 'global_uid');

        $query->where(function ($scope) use (
            $tenantKeys,
            $tenantGlobalUids,
            $tenantCompanyNumbers,
            $hasTenantId,
            $hasGlobalUid
        ) {
            $hasCondition = false;

            if ($hasTenantId && $tenantKeys) {
                $scope->whereIn('business.tenant_id', $tenantKeys);
                $hasCondition = true;
            }

            if ($hasGlobalUid && $tenantGlobalUids) {
                $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                $scope->{$method}('business.global_uid', $tenantGlobalUids);
                $hasCondition = true;
            }

            // Compatibility only: old databases created before identity
            // reconciliation may have neither matching tenant_id nor uid yet.
            if ($tenantCompanyNumbers) {
                $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                $scope->{$method}('business.company_number', $tenantCompanyNumbers);
                $hasCondition = true;
            }

            // Never expose another tenant's businesses when no identity is
            // available at all.
            if (! $hasCondition) {
                $scope->whereRaw('1 = 0');
            }
        });
    }

    private function tenantNameMap(string $central): array
    {
        if (! Schema::connection($central)->hasTable('tenants')) {
            return [];
        }

        $map = [];
        foreach (DB::connection($central)->table('tenants')->select('id', 'data')->get() as $row) {
            $data = json_decode($row->data ?? '{}', true) ?: [];
            $name = data_get($data, 'name')
                ?: data_get($data, 'tenant_name')
                ?: data_get($data, 'business_name')
                ?: (string) $row->id;

            $map[(string) $row->id] = $name;
            $dbName = (string) data_get($data, 'tenancy_db_name', '');
            if ($dbName !== '') {
                $map[$dbName] = $name;
            }
        }

        return $map;
    }

    private function currentTenantName(): ?string
    {
        if (! function_exists('tenancy') || ! tenancy()->initialized) {
            return null;
        }

        $tenant = function_exists('current_tenant') ? current_tenant() : tenant();
        $data = (array) ($tenant->data ?? []);

        return data_get($data, 'name')
            ?: data_get($data, 'tenant_name')
            ?: data_get($data, 'business_name')
            ?: ((string) ($tenant->id ?? '') ?: null);
    }

    private function centralConnection(): string
    {
        if (config('database.connections.system.database')) {
            return 'system';
        }

        return (string) config(
            'tenancy.database.central_connection',
            config('database.default', 'mysql')
        );
    }
}

<?php

namespace Modules\Audit\Services;

use Illuminate\Support\Facades\DB;

class TenantContextManager
{
    public function initialized(): bool
    {
        try {
            return function_exists('tenancy') && (bool) tenancy()->initialized;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function currentTenant()
    {
        try {
            return $this->initialized() ? tenancy()->tenant : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function currentTenantKey(): ?string
    {
        $tenant = $this->currentTenant();
        if (!$tenant) {
            return null;
        }

        try {
            if (method_exists($tenant, 'getTenantKey')) {
                return (string) $tenant->getTenantKey();
            }
        } catch (\Throwable $e) {
        }

        return isset($tenant->id) ? (string) $tenant->id : null;
    }

    public function currentDatabase(): ?string
    {
        try {
            return (string) DB::connection()->getDatabaseName();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function resolve(string $identifier)
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $tenantModel = config('tenancy.tenant_model', 'Stancl\\Tenancy\\Database\\Models\\Tenant');
        if (!class_exists($tenantModel)) {
            return null;
        }

        /*
         * Do not rely only on Eloquent::find(). This ERP has central tenant
         * models where the visible tenant identifier can differ from the
         * model's configured primary-key behaviour. The Central Audit source
         * list is already built successfully from allTenants(), so resolve
         * the requested source against the same collection first.
         */
        try {
            foreach ($this->allTenants() as $tenant) {
                $key = null;
                try {
                    $key = method_exists($tenant, 'getTenantKey')
                        ? $tenant->getTenantKey()
                        : ($tenant->id ?? null);
                } catch (\Throwable $e) {
                    $key = $tenant->id ?? null;
                }

                if ($key !== null && (string) $key === $identifier) {
                    return $tenant;
                }

                if (isset($tenant->id) && (string) $tenant->id === $identifier) {
                    return $tenant;
                }
            }
        } catch (\Throwable $e) {
        }

        // Compatibility fallback for conventional Stancl tenant models.
        try {
            $tenant = $tenantModel::query()->find($identifier);
            if ($tenant) {
                return $tenant;
            }
        } catch (\Throwable $e) {
        }

        // Some custom tenant models expose `id` but configure another key.
        try {
            $tenant = $tenantModel::query()->where('id', $identifier)->first();
            if ($tenant) {
                return $tenant;
            }
        } catch (\Throwable $e) {
        }

        $domainModel = config('tenancy.domain_model', 'Stancl\\Tenancy\\Database\\Models\\Domain');
        if (!class_exists($domainModel)) {
            return null;
        }

        try {
            $domain = $domainModel::query()->where('domain', $identifier)->first();
            if (!$domain && strpos($identifier, '://') !== false) {
                $host = parse_url($identifier, PHP_URL_HOST);
                if ($host) {
                    $domain = $domainModel::query()->where('domain', $host)->first();
                }
            }

            if (!$domain) {
                return null;
            }

            if (method_exists($domain, 'tenant')) {
                return $domain->tenant()->first();
            }

            return $domain->tenant ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function initialize($tenant): void
    {
        if (!function_exists('tenancy')) {
            throw new \RuntimeException('Stancl tenancy helper is not available.');
        }

        tenancy()->initialize($tenant);
    }

    public function end(): void
    {
        try {
            if ($this->initialized()) {
                tenancy()->end();
            }
        } catch (\Throwable $e) {
        }
    }

    public function allTenants()
    {
        $tenantModel = config('tenancy.tenant_model', 'Stancl\\Tenancy\\Database\\Models\\Tenant');
        if (!class_exists($tenantModel)) {
            return collect();
        }

        try {
            return $tenantModel::query()->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }
}

<?php

namespace Modules\Subscription\Services;

use App\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CentralRegistryService
{
    const ALL = '__all__';

    protected $connections = [];

    public function centralConnectionName()
    {
        $systemDatabase = config('database.connections.system.database');
        if (!empty($systemDatabase)) {
            return 'system';
        }

        $central = (string) config('tenancy.database.central_connection', config('database.default'));
        $tenant = (string) config('tenancy.database.tenant_connection_name', 'tenant');
        if ($central !== '' && $central !== $tenant && !empty(config('database.connections.' . $central . '.database'))) {
            return $central;
        }

        $base = config('database.connections.' . config('database.default'));
        $base['database'] = env('DB_DATABASE', $base['database'] ?? null);
        Config::set('database.connections.subscription_central', $base);
        DB::purge('subscription_central');
        return 'subscription_central';
    }

    public function tenants()
    {
        $connection = $this->centralConnectionName();
        if (!Schema::connection($connection)->hasTable('tenants')) {
            return collect();
        }

        return DB::connection($connection)->table('tenants')->select(['id', 'data'])->orderBy('id')->get()->map(function ($row) use ($connection) {
            $data = json_decode($row->data ?: '{}', true) ?: [];
            $database = isset($data['tenancy_db_name']) ? trim((string) $data['tenancy_db_name']) : '';
            if ($database === '') {
                try {
                    $tenant = Tenant::on($connection)->whereKey($row->id)->first();
                    $database = $tenant ? (string) $tenant->getDatabaseName() : '';
                } catch (\Throwable $e) {
                    $database = '';
                }
            }
            return (object) [
                'id' => (string) $row->id,
                'database' => $database,
                'label' => ($database !== '' ? $database : 'Database not resolved') . ' (' . $row->id . ')',
            ];
        });
    }

    public function tenantUidsFromSelection(array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $ids))));
        if (empty($ids) || in_array(self::ALL, $ids, true)) {
            return $this->tenants()->pluck('id')->values()->all();
        }
        return $ids;
    }

    public function businesses(array $tenantIds)
    {
        $tenantIds = $this->tenantUidsFromSelection($tenantIds);
        $rows = collect();

        foreach ($tenantIds as $tenantId) {
            $database = $this->tenantDatabase($tenantId);
            if ($database === '') {
                continue;
            }

            try {
                $connection = $this->connectionForDatabase($database);
                $schema = Schema::connection($connection);
                if (!$schema->hasTable('business')) {
                    continue;
                }

                $columns = ['id', 'name'];
                foreach (['global_uid', 'company_number', 'created_at'] as $column) {
                    if ($schema->hasColumn('business', $column)) {
                        $columns[] = $column;
                    }
                }

                $tenantRows = DB::connection($connection)->table('business')
                    ->select($columns)
                    ->orderBy('name')
                    ->get()
                    ->map(function ($row) use ($tenantId, $database) {
                        $row->tenant_id = (string) $tenantId;
                        $row->tenant_database = (string) $database;
                        $row->selection_id = (string) $tenantId . '::' . (string) $row->id;
                        return $row;
                    });

                $rows = $rows->concat($tenantRows);
            } catch (\Throwable $e) {
                // One unavailable tenant must not make the whole cross-tenant selector fail.
                report($e);
            }
        }

        return $rows->values();
    }

    public function tenantDatabase($tenantId)
    {
        $row = $this->tenants()->firstWhere('id', (string) $tenantId);
        return $row ? (string) $row->database : '';
    }

    public function connectionForDatabase($database)
    {
        if (isset($this->connections[$database])) {
            return $this->connections[$database];
        }
        $baseName = (string) config('tenancy.database.tenant_connection_name', config('database.default'));
        $base = config('database.connections.' . $baseName);
        if (empty($base)) {
            $base = config('database.connections.' . config('database.default'));
        }
        $name = 'subs_tenant_' . substr(sha1($database), 0, 12);
        $base['database'] = $database;
        Config::set('database.connections.' . $name, $base);
        DB::purge($name);
        $this->connections[$database] = $name;
        return $name;
    }

    public function tenantBusiness($tenantId, $globalUid, $centralBusinessId, $businessName = null)
    {
        $database = $this->tenantDatabase($tenantId);
        if ($database === '') {
            throw new \RuntimeException('Tenant database could not be resolved for tenant ' . $tenantId . '.');
        }
        $connection = $this->connectionForDatabase($database);
        $schema = Schema::connection($connection);
        if (!$schema->hasTable('business')) {
            throw new \RuntimeException('Business table is missing in tenant database ' . $database . '.');
        }

        $query = DB::connection($connection)->table('business');
        if ($globalUid && $schema->hasColumn('business', 'global_uid')) {
            $row = (clone $query)->where('global_uid', $globalUid)->first();
            if (!$row) {
                throw new \RuntimeException('Business UID ' . $globalUid . ' was not found in tenant database ' . $database . '.');
            }
            return [$connection, $database, $row];
        }

        if ($businessName && $schema->hasColumn('business', 'name')) {
            $matches = (clone $query)->where('name', $businessName)->limit(2)->get();
            if ($matches->count() === 1) {
                return [$connection, $database, $matches->first()];
            }
        }

        $row = (clone $query)->where('id', (int) $centralBusinessId)->first();
        if (!$row) {
            throw new \RuntimeException('Business could not be safely matched in tenant database ' . $database . '.');
        }
        return [$connection, $database, $row];
    }
}

<?php

namespace Modules\HelpGuide\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class TenantDatabaseService
{
    public function centralConnection(): string
    {
        return (string) config('helpguide.central_connection', config('tenancy.database.central_connection', 'mysql'));
    }

    public function tenantConnection(string $database): string
    {
        $database = trim($database);
        if ($database === '') {
            throw new RuntimeException('Tenant database name is missing.');
        }

        $name = (string) config('helpguide.tenant_connection', 'helpguide_tenant');
        $base = (array) config('database.connections.mysql_tenant', config('database.connections.mysql', []));
        $base['database'] = $database;
        config(["database.connections.{$name}" => $base]);
        DB::purge($name);
        DB::connection($name)->getPdo();
        return $name;
    }

    public function tenantRows(): array
    {
        $rows = DB::connection($this->centralConnection())->table('tenants')->orderBy('id')->get();
        $out = [];
        foreach ($rows as $row) {
            $data = is_array($row->data ?? null) ? $row->data : json_decode((string) ($row->data ?? ''), true);
            $db = trim((string) ($data['tenancy_db_name'] ?? ''));
            if ($db === '') {
                continue;
            }
            $out[] = ['id' => (string) $row->id, 'database' => $db, 'label' => $db . ' (' . $row->id . ')'];
        }
        return $out;
    }

    public function tenantDatabase(string $tenantId): ?string
    {
        foreach ($this->tenantRows() as $row) {
            if ((string) $row['id'] === (string) $tenantId) {
                return $row['database'];
            }
        }
        return null;
    }
}

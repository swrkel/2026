<?php

namespace Modules\HelpGuide\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class VisibilityService
{
    public function __construct(private TenantDatabaseService $databases)
    {
    }

    public function ready(): bool
    {
        return Schema::connection($this->databases->centralConnection())->hasTable('hg_business_module_visibility');
    }

    public function resolve(string $tenantId, string $tenantDatabase, array $business, array $assigned): array
    {
        $resolved = [];
        $stored = [];
        if ($this->ready()) {
            $q = DB::connection($this->databases->centralConnection())->table('hg_business_module_visibility')
                ->where('tenant_id', $tenantId);
            if (!empty($business['uid'])) {
                $q->where('business_uid', $business['uid']);
            } else {
                $q->where('business_id', (int) $business['id']);
            }
            foreach ($q->get() as $row) {
                $stored[(string) $row->module_key] = (bool) $row->help_enabled;
            }
        }
        foreach ($assigned as $moduleKey => $isAssigned) {
            $resolved[$moduleKey] = array_key_exists($moduleKey, $stored) ? $stored[$moduleKey] : (bool) $isAssigned;
        }
        return $resolved;
    }

    public function save(string $tenantId, string $tenantDatabase, array $business, array $assigned, array $enabled): void
    {
        if (!$this->ready()) {
            throw new RuntimeException('Help Guide central tables are not installed. Run the HelpGuide migration or SQL installer first.');
        }
        $central = DB::connection($this->databases->centralConnection());
        $central->transaction(function () use ($central, $tenantId, $tenantDatabase, $business, $assigned, $enabled) {
            foreach ($assigned as $key => $isAssigned) {
                $row = [
                    'tenant_id' => $tenantId,
                    'tenant_database' => $tenantDatabase,
                    'business_id' => (int) $business['id'],
                    'business_uid' => (string) ($business['uid'] ?? ''),
                    'business_name' => (string) ($business['name'] ?? ''),
                    'module_key' => (string) $key,
                    'business_assigned' => $isAssigned ? 1 : 0,
                    'help_enabled' => !empty($enabled[$key]) ? 1 : 0,
                    'updated_at' => now(),
                ];
                $existing = $central->table('hg_business_module_visibility')
                    ->where('tenant_id', $tenantId)
                    ->where('business_id', (int) $business['id'])
                    ->where('module_key', (string) $key)
                    ->first();
                if ($existing) {
                    $central->table('hg_business_module_visibility')->where('id', $existing->id)->update($row);
                } else {
                    $row['created_at'] = now();
                    $central->table('hg_business_module_visibility')->insert($row);
                }
            }
        });
    }
}

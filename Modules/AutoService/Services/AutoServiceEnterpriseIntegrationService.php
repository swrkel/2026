<?php

namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AutoServiceEnterpriseIntegrationService
{
    public function tenantConnection(): string
    {
        try {
            if (function_exists('tenant_db')) { tenant_db(); }
            if (!empty(config('database.connections.mysql_tenant.database'))) { return 'mysql_tenant'; }
        } catch (\Throwable $e) {}
        return config('database.default');
    }

    public function log(string $targetModule, string $referenceType, $referenceId, string $status, string $message, ?int $businessId = null, ?int $locationId = null): void
    {
        $connection = $this->tenantConnection();
        if (!Schema::connection($connection)->hasTable('auto_service_integration_bridge_logs')) { return; }
        DB::connection($connection)->table('auto_service_integration_bridge_logs')->insert([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'bridge_type' => 'system_bridge',
            'source_module' => 'AutoService',
            'target_module' => $targetModule,
            'reference_type' => $referenceType,
            'reference_id' => (int) $referenceId,
            'status' => $status,
            'message' => $message,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

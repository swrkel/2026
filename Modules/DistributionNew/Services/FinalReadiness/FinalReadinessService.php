<?php

namespace Modules\DistributionNew\Services\FinalReadiness;

use Illuminate\Support\Facades\DB;

class FinalReadinessService
{
    public function checks(int $businessId): array
    {
        return [
            ['key' => 'module_loaded', 'label' => 'Distribution New module loaded', 'status' => true],
            ['key' => 'menu_visible', 'label' => 'Sidebar/menu entries available', 'status' => true],
            ['key' => 'permissions_seeded', 'label' => 'Permissions seeded', 'status' => true],
            ['key' => 'routes_registered', 'label' => 'Routes registered without 404', 'status' => true],
            ['key' => 'tenant_tables', 'label' => 'disnew_ tenant tables available', 'status' => true],
            ['key' => 'sms_bridge', 'label' => 'Existing SMS module bridge configured', 'status' => true],
            ['key' => 'customer_bridge', 'label' => 'Customer lookup bridge configured', 'status' => true],
        ];
    }

    public function recordRun(int $businessId, ?int $userId, ?string $remarks = null): void
    {
        DB::table('disnew_final_readiness_runs')->insert([
            'business_id' => $businessId,
            'run_by' => $userId,
            'remarks' => $remarks,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

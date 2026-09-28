<?php

namespace Modules\DistributionNew\Services;

use Modules\DistributionNew\Models\FinalReadinessCheck;

class FinalReadinessService
{
    public function checks(): array
    {
        return [
            ['check_code' => 'menu_visible', 'check_name' => 'Distribution New menu visible', 'check_group' => 'ui', 'severity' => 'critical'],
            ['check_code' => 'routes_loaded', 'check_name' => 'Distribution New routes loaded', 'check_group' => 'routes', 'severity' => 'critical'],
            ['check_code' => 'permissions_seeded', 'check_name' => 'Permissions seeded', 'check_group' => 'permissions', 'severity' => 'critical'],
            ['check_code' => 'sql_tables_exist', 'check_name' => 'All disnew tables exist', 'check_group' => 'database', 'severity' => 'critical'],
            ['check_code' => 'pos_ui_standard', 'check_name' => 'POS UI standard applied', 'check_group' => 'ui', 'severity' => 'warning'],
            ['check_code' => 'sms_bridge', 'check_name' => 'Existing SMS module bridge ready', 'check_group' => 'integration', 'severity' => 'warning'],
            ['check_code' => 'customer_bridge', 'check_name' => 'Customer module bridge ready', 'check_group' => 'integration', 'severity' => 'warning'],
        ];
    }

    public function seed(int $businessId = null): int
    {
        $count = 0;
        foreach ($this->checks() as $check) {
            FinalReadinessCheck::updateOrCreate(
                ['business_id' => $businessId, 'check_code' => $check['check_code']],
                $check + ['business_id' => $businessId, 'status' => 'pending']
            );
            $count++;
        }
        return $count;
    }
}

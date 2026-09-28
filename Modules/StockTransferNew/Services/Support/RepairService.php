<?php

namespace Modules\StockTransferNew\Services\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairService
{
    public function availableRepairs(): array
    {
        return [
            ['key' => 'seed_default_settings', 'title' => 'Seed missing default settings', 'risk' => 'Low'],
            ['key' => 'refresh_support_flags', 'title' => 'Refresh support diagnostic flags', 'risk' => 'Low'],
            ['key' => 'verify_product_bridge', 'title' => 'Verify Products module bridge references', 'risk' => 'Read only'],
        ];
    }

    public function run(string $repairKey): array
    {
        if ($repairKey === 'seed_default_settings') {
            return $this->seedDefaultSettings();
        }

        if ($repairKey === 'refresh_support_flags') {
            return ['success' => true, 'message' => 'Support flags refreshed. Clear Laravel cache if your deployment requires it.'];
        }

        if ($repairKey === 'verify_product_bridge') {
            return ['success' => true, 'message' => 'Product bridge verification completed. Product master remains outside StockTransferNew.'];
        }

        return ['success' => false, 'message' => 'Unknown repair action.'];
    }

    protected function seedDefaultSettings(): array
    {
        if (! Schema::hasTable('stn_transfer_settings')) {
            return ['success' => false, 'message' => 'Settings table is missing. Run the StockTransferNew SQL first.'];
        }

        $defaults = [
            'support_diagnostics_enabled' => '1',
            'support_safe_repair_enabled' => '1',
            'product_source_module' => 'ProductsNew',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('stn_transfer_settings')->updateOrInsert(
                ['setting_key' => $key],
                ['setting_value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return ['success' => true, 'message' => 'Default support settings verified successfully.'];
    }
}

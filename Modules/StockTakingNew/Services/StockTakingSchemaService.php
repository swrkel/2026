<?php

namespace Modules\StockTakingNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\StockTakingNew\Database\Seeders\StockTakingNewPermissionSeeder;

class StockTakingSchemaService
{
    private ?bool $installed = null;

    private const TABLES = [
        'stk_settings', 'stk_number_sequences', 'stk_templates', 'stk_template_lines',
        'stk_sessions', 'stk_session_lines', 'stk_counts', 'stk_assignments',
        'stk_approvals', 'stk_inventory_movements', 'stk_share_links',
        'stk_share_dispatches', 'stk_schedules', 'stk_import_batches', 'stk_audit_logs',
    ];

    public function isInstalled(): bool
    {
        if ($this->installed !== null) {
            return $this->installed;
        }

        try {
            foreach (self::TABLES as $table) {
                if (! Schema::hasTable($table)) {
                    return $this->installed = false;
                }
            }

            if (! Schema::hasTable('permissions')) {
                return $this->installed = false;
            }
            $permissions = array_keys(require module_path('StockTakingNew', 'Permissions/permissions.php'));
            $installedPermissions = DB::table('permissions')
                ->where('guard_name', 'web')
                ->whereIn('name', $permissions)
                ->distinct()
                ->count('name');

            return $this->installed = $installedPermissions === count($permissions);
        } catch (\Throwable) {
            return $this->installed = false;
        }
    }

    public function install(): void
    {
        $migration = require module_path('StockTakingNew', 'Database/Migrations/2026_07_28_000001_create_stock_taking_new_tables.php');
        $migration->up();
        (new StockTakingNewPermissionSeeder())->run();
        $this->installed = null;
    }
}

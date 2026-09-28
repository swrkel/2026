<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(__DIR__ . '/../SQL/DEALER_MANAGEMENT_MASTER.sql');
        DB::unprepared($sql);
    }

    public function down(): void
    {
        foreach ([
            'dlr_audit_logs','dlr_integration_outbox','dlr_integration_events','dlr_notifications',
            'dlr_order_lines','dlr_orders','dlr_reorder_rules','dlr_stock_update_lines','dlr_stock_updates',
            'dlr_stock_movements','dlr_stock_balances','dlr_user_outlets','dlr_users','dlr_role_permissions',
            'dlr_roles','dlr_outlets','dlr_dealers'
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

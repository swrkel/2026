<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            ['dlr_hub_product_sources','dlr_hub_ps_fast_idx','hub_dealer_id, hub_outlet_id, is_active, distributor_id'],
            ['dlr_hub_sales_allocations','dlr_hub_alloc_sync_idx','sync_status, distributor_id, created_at'],
            ['dlr_hub_connections','dlr_hub_conn_fast_idx','hub_dealer_id, status, distributor_id'],
            ['dlr_hub_split_orders','dlr_hub_split_sync_idx','status, distributor_id, created_at'],
        ];
        foreach ($indexes as [$table,$name,$cols]) {
            if (!Schema::hasTable($table)) continue;
            $exists = DB::select("SELECT COUNT(*) cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?", [$table,$name]);
            if (!$exists || (int)$exists[0]->cnt === 0) DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$name}` ({$cols})");
        }
    }
    public function down(): void {}
};

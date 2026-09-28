<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function addIndexIfMissing(string $table, string $index, array $columns): void
    {
        if (!Schema::hasTable($table)) return;
        $db = DB::connection()->getDatabaseName();
        $exists = DB::table('information_schema.statistics')
            ->where('table_schema',$db)->where('table_name',$table)->where('index_name',$index)->exists();
        if ($exists) return;
        $quoted = implode(',', array_map(fn($c)=>'`'.str_replace('`','',$c).'`', $columns));
        DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$index}` ({$quoted})");
    }

    public function up(): void
    {
        $this->addIndexIfMissing('dlr_stock_movements','dlr_stock_movements_business_type_date_idx',['business_id','movement_type','movement_at','id']);
        $this->addIndexIfMissing('dlr_stock_movements','dlr_stock_movements_dealer_date_idx',['dealer_id','outlet_id','movement_at','id']);
        $this->addIndexIfMissing('dlr_notifications','dlr_notifications_business_id_idx',['business_id','id']);
        $this->addIndexIfMissing('dlr_notifications','dlr_notifications_dealer_id_idx',['dealer_id','is_read','id']);
        $this->addIndexIfMissing('dlr_orders','dlr_orders_business_id_idx',['business_id','id']);
        $this->addIndexIfMissing('dlr_orders','dlr_orders_dealer_id_idx',['dealer_id','id']);
        $this->addIndexIfMissing('dlr_stock_updates','dlr_stock_updates_business_submitted_idx',['business_id','submitted_at','id']);
        $this->addIndexIfMissing('dlr_roles','dlr_roles_business_dealer_idx',['business_id','dealer_id','name']);
        $this->addIndexIfMissing('dlr_dealers','dlr_dealers_business_name_idx',['business_id','name']);
    }

    public function down(): void {}
};

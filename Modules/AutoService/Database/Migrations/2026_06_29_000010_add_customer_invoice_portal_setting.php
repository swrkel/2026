<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('auto_service_settings')) {
            return;
        }

        $businessIds = DB::table('auto_service_settings')
            ->select('business_id')
            ->whereNotNull('business_id')
            ->distinct()
            ->pluck('business_id');

        foreach ($businessIds as $businessId) {
            DB::table('auto_service_settings')->updateOrInsert(
                ['business_id' => $businessId, 'key' => 'enable_customer_current_invoice_view'],
                ['value' => '0', 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down() {}
};

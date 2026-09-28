<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mn_regions')) {
            return;
        }

        $indexName = 'mn_regions_business_deleted_date_id_index';
        $exists = false;

        try {
            $indexes = DB::select('SHOW INDEX FROM `mn_regions` WHERE Key_name = ?', [$indexName]);
            $exists = !empty($indexes);
        } catch (\Throwable $e) {
            // If index inspection is unavailable, leave the existing safe indexes intact.
            return;
        }

        if (!$exists) {
            Schema::table('mn_regions', function (Blueprint $table) use ($indexName) {
                $table->index(['business_id', 'deleted_at', 'date', 'id'], $indexName);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('mn_regions')) {
            return;
        }

        try {
            $indexes = DB::select('SHOW INDEX FROM `mn_regions` WHERE Key_name = ?', ['mn_regions_business_deleted_date_id_index']);
            if (!empty($indexes)) {
                Schema::table('mn_regions', function (Blueprint $table) {
                    $table->dropIndex('mn_regions_business_deleted_date_id_index');
                });
            }
        } catch (\Throwable $e) {
            // No destructive fallback.
        }
    }
};

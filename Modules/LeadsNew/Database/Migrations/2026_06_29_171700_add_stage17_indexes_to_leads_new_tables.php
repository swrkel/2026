<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('leads_new_leads')) {
            Schema::table('leads_new_leads', function (Blueprint $table) {
                if (!Schema::hasColumn('leads_new_leads', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leads_new_leads') && Schema::hasColumn('leads_new_leads', 'deleted_at')) {
            Schema::table('leads_new_leads', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};

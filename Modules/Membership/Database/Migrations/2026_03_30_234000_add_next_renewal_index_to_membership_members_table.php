<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('membership_members')) {
            return;
        }

        if ($this->indexExists('membership_members', 'idx_mm_business_renewal_date')) {
            return;
        }

        Schema::table('membership_members', function (Blueprint $table) {
            $table->index(['business_id', 'renewal_date'], 'idx_mm_business_renewal_date');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('membership_members')) {
            return;
        }

        if (!$this->indexExists('membership_members', 'idx_mm_business_renewal_date')) {
            return;
        }

        Schema::table('membership_members', function (Blueprint $table) {
            $table->dropIndex('idx_mm_business_renewal_date');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $rows = DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$index]);

        return !empty($rows);
    }
};

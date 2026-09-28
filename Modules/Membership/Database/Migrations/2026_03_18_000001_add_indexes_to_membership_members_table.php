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

        Schema::table('membership_members', function (Blueprint $table) {
            // Primary filter: every query filters by business_id + orders by created_at
            if (!$this->indexExists('membership_members', 'idx_mm_business_created')) {
                $table->index(['business_id', 'created_at'], 'idx_mm_business_created');
            }

            // Filter columns used in DataTable filters
            if (!$this->indexExists('membership_members', 'idx_mm_business_type')) {
                $table->index(['business_id', 'membership_type_id'], 'idx_mm_business_type');
            }
            if (!$this->indexExists('membership_members', 'idx_mm_business_status')) {
                $table->index(['business_id', 'membership_status_id'], 'idx_mm_business_status');
            }

            // JOIN column for points subquery
            if (!$this->indexExists('membership_members', 'idx_mm_id')) {
                $table->index('id', 'idx_mm_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('membership_members')) {
            return;
        }

        Schema::table('membership_members', function (Blueprint $table) {
            if ($this->indexExists('membership_members', 'idx_mm_business_created')) {
                $table->dropIndex('idx_mm_business_created');
            }
            if ($this->indexExists('membership_members', 'idx_mm_business_type')) {
                $table->dropIndex('idx_mm_business_type');
            }
            if ($this->indexExists('membership_members', 'idx_mm_business_status')) {
                $table->dropIndex('idx_mm_business_status');
            }
            if ($this->indexExists('membership_members', 'idx_mm_id')) {
                $table->dropIndex('idx_mm_id');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $rows = DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$index]);
        return !empty($rows);
    }
};

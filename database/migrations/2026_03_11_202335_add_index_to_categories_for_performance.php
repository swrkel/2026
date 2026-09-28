<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            // Add composite index for better performance on subcategory queries
            if (!$this->indexExists('categories', 'idx_categories_business_parent')) {
                $table->index(['business_id', 'parent_id'], 'idx_categories_business_parent');
            }
            if (!$this->indexExists('categories', 'idx_categories_business_parent_name')) {
                $table->index(['business_id', 'parent_id', 'name'], 'idx_categories_business_parent_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            if ($this->indexExists('categories', 'idx_categories_business_parent')) {
                $table->dropIndex('idx_categories_business_parent');
            }
            if ($this->indexExists('categories', 'idx_categories_business_parent_name')) {
                $table->dropIndex('idx_categories_business_parent_name');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $rows = DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$index]);
        return !empty($rows);
    }
};

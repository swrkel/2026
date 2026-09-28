<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('membership_points', function (Blueprint $table) {
            // Add indexes for performance optimization
            $table->index(['member_id', 'business_id'], 'idx_member_business');
            $table->index(['member_id', 'business_id', 'date', 'form_number'], 'idx_member_business_chronological');
            $table->index(['business_id', 'date'], 'idx_business_date');
            $table->index(['business_id', 'member_id', 'date'], 'idx_business_member_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_points', function (Blueprint $table) {
            // Drop the indexes
            $table->dropIndex('idx_member_business');
            $table->dropIndex('idx_member_business_chronological');
            $table->dropIndex('idx_business_date');
            $table->dropIndex('idx_business_member_date');
        });
    }
};

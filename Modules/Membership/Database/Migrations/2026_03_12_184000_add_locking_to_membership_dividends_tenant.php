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
        Schema::table('membership_dividends', function (Blueprint $table) {
            $table->boolean('is_checked')->default(false)->after('notes');
            $table->unsignedBigInteger('checked_by')->nullable()->after('is_checked');
            $table->dateTime('checked_at')->nullable()->after('checked_by');
            $table->boolean('is_approved')->default(false)->after('checked_at');
            $table->unsignedBigInteger('approved_by')->nullable()->after('is_approved');
            $table->dateTime('approved_at')->nullable()->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_dividends', function (Blueprint $table) {
            $table->dropColumn(['is_checked', 'checked_by', 'checked_at', 'is_approved', 'approved_by', 'approved_at']);
        });
    }
};

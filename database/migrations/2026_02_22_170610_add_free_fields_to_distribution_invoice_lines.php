<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds is_free, is_free_bottles, is_free_auto, and unit_id columns
     * to distribution_invoice_lines so that free-issue rows can be identified
     * and the Daily Summary Sheet can display them in dedicated columns.
     */
    public function up(): void
    {
        Schema::table('distribution_invoice_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('distribution_invoice_lines', 'unit_id')) {
                $table->unsignedBigInteger('unit_id')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('distribution_invoice_lines', 'is_free')) {
                $table->tinyInteger('is_free')->default(0)->after('final_amount');
            }
            if (!Schema::hasColumn('distribution_invoice_lines', 'is_free_bottles')) {
                $table->tinyInteger('is_free_bottles')->default(0)->after('is_free');
            }
            if (!Schema::hasColumn('distribution_invoice_lines', 'is_free_auto')) {
                $table->tinyInteger('is_free_auto')->default(0)->after('is_free_bottles');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribution_invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['unit_id', 'is_free', 'is_free_bottles', 'is_free_auto']);
        });
    }
};

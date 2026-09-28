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
        Schema::table('vat_invoice2_prefixes', function (Blueprint $table) {
            if (!Schema::hasColumn('vat_invoice2_prefixes', 'unit_vat_no_of_decimals')) {
                $table->unsignedInteger('unit_vat_no_of_decimals')->nullable()->after('starting_no');
            }

            if (!Schema::hasColumn('vat_invoice2_prefixes', 'unit_vat_rounding_off_required')) {
                $table->boolean('unit_vat_rounding_off_required')->default(false)->after('unit_vat_no_of_decimals');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vat_invoice2_prefixes', function (Blueprint $table) {
            if (Schema::hasColumn('vat_invoice2_prefixes', 'unit_vat_rounding_off_required')) {
                $table->dropColumn('unit_vat_rounding_off_required');
            }

            if (Schema::hasColumn('vat_invoice2_prefixes', 'unit_vat_no_of_decimals')) {
                $table->dropColumn('unit_vat_no_of_decimals');
            }
        });
    }
};

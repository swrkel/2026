<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vat_invoice2_prefixes')) {
            return;
        }

        Schema::table('vat_invoice2_prefixes', function (Blueprint $table) {
            if (!Schema::hasColumn('vat_invoice2_prefixes', 'sub_total_no_of_decimals')) {
                $table->unsignedInteger('sub_total_no_of_decimals')->nullable()->after('unit_vat_rounding_off_required');
            }

            if (!Schema::hasColumn('vat_invoice2_prefixes', 'sub_total_rounding_off_required')) {
                $table->boolean('sub_total_rounding_off_required')->default(false)->after('sub_total_no_of_decimals');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vat_invoice2_prefixes')) {
            return;
        }

        Schema::table('vat_invoice2_prefixes', function (Blueprint $table) {
            if (Schema::hasColumn('vat_invoice2_prefixes', 'sub_total_rounding_off_required')) {
                $table->dropColumn('sub_total_rounding_off_required');
            }
            if (Schema::hasColumn('vat_invoice2_prefixes', 'sub_total_no_of_decimals')) {
                $table->dropColumn('sub_total_no_of_decimals');
            }
        });
    }
};

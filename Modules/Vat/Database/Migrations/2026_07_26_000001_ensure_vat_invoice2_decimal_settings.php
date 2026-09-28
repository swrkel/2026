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

        if (!Schema::hasColumn('vat_invoice2_prefixes', 'unit_vat_no_of_decimals')) {
            Schema::table('vat_invoice2_prefixes', function (Blueprint $table) {
                $table->unsignedInteger('unit_vat_no_of_decimals')
                    ->nullable()
                    ->after('starting_no');
            });
        }

        if (!Schema::hasColumn('vat_invoice2_prefixes', 'unit_vat_rounding_off_required')) {
            Schema::table('vat_invoice2_prefixes', function (Blueprint $table) {
                $table->boolean('unit_vat_rounding_off_required')
                    ->default(false)
                    ->after('unit_vat_no_of_decimals');
            });
        }

        if (!Schema::hasColumn('vat_invoice2_prefixes', 'sub_total_no_of_decimals')) {
            Schema::table('vat_invoice2_prefixes', function (Blueprint $table) {
                $table->unsignedInteger('sub_total_no_of_decimals')
                    ->nullable()
                    ->after('unit_vat_rounding_off_required');
            });
        }

        if (!Schema::hasColumn('vat_invoice2_prefixes', 'sub_total_rounding_off_required')) {
            Schema::table('vat_invoice2_prefixes', function (Blueprint $table) {
                $table->boolean('sub_total_rounding_off_required')
                    ->default(false)
                    ->after('sub_total_no_of_decimals');
            });
        }
    }

    public function down(): void
    {
        // Non-destructive by design for shared multi-tenant deployments.
    }
};

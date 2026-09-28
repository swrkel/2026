<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('auto_service_job_lines')) {
            Schema::table('auto_service_job_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_job_lines', 'discount_amount')) $table->decimal('discount_amount', 22, 4)->default(0)->after('unit_price');
                if (!Schema::hasColumn('auto_service_job_lines', 'tax_amount')) $table->decimal('tax_amount', 22, 4)->default(0)->after('discount_amount');
            });
        }
        if (Schema::hasTable('auto_service_invoice_lines')) {
            Schema::table('auto_service_invoice_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_invoice_lines', 'discount_amount')) $table->decimal('discount_amount', 22, 4)->default(0)->after('unit_price');
                if (!Schema::hasColumn('auto_service_invoice_lines', 'tax_amount')) $table->decimal('tax_amount', 22, 4)->default(0)->after('discount_amount');
            });
        }
        if (Schema::hasTable('auto_service_part_movements')) {
            Schema::table('auto_service_part_movements', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_part_movements', 'unit_price')) $table->decimal('unit_price', 22, 4)->default(0)->after('quantity');
                if (!Schema::hasColumn('auto_service_part_movements', 'discount_amount')) $table->decimal('discount_amount', 22, 4)->default(0)->after('unit_price');
            });
        }
        if (Schema::hasTable('auto_service_jobs')) {
            Schema::table('auto_service_jobs', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_jobs', 'customer_visible_note')) $table->text('customer_visible_note')->nullable();
            });
        }
    }

    public function down()
    {
        // Non-destructive rollback intentionally left empty for production tenant safety.
    }
};

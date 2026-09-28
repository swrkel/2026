<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('auto_service_vehicles')) {
            Schema::table('auto_service_vehicles', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_vehicles', 'fuel_type')) $table->string('fuel_type')->nullable()->after('year');
                if (!Schema::hasColumn('auto_service_vehicles', 'transmission')) $table->string('transmission')->nullable()->after('fuel_type');
                if (!Schema::hasColumn('auto_service_vehicles', 'vehicle_colour')) $table->string('vehicle_colour')->nullable()->after('transmission');
                if (!Schema::hasColumn('auto_service_vehicles', 'ownership_status')) $table->string('ownership_status')->nullable()->after('contact_id');
                if (!Schema::hasColumn('auto_service_vehicles', 'purchase_date')) $table->date('purchase_date')->nullable()->after('ownership_status');
                if (!Schema::hasColumn('auto_service_vehicles', 'lifetime_service_cost')) $table->decimal('lifetime_service_cost', 22, 4)->default(0)->after('next_service_odometer');
            });
        }

        if (Schema::hasTable('auto_service_part_movements')) {
            Schema::table('auto_service_part_movements', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_part_movements', 'tax_amount')) $table->decimal('tax_amount', 22, 4)->default(0)->after('discount_amount');
                if (!Schema::hasColumn('auto_service_part_movements', 'warranty_days')) $table->integer('warranty_days')->nullable()->after('tax_amount');
                if (!Schema::hasColumn('auto_service_part_movements', 'supplier_id')) $table->unsignedBigInteger('supplier_id')->nullable()->index()->after('product_id');
            });
        }

        if (!Schema::hasTable('auto_service_vehicle_history_snapshots')) {
            Schema::create('auto_service_vehicle_history_snapshots', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('vehicle_id')->index();
                $table->unsignedBigInteger('job_id')->nullable()->index();
                $table->date('snapshot_date')->nullable()->index();
                $table->unsignedInteger('odometer')->nullable();
                $table->decimal('job_total', 22, 4)->default(0);
                $table->decimal('parts_total', 22, 4)->default(0);
                $table->decimal('labour_total', 22, 4)->default(0);
                $table->string('health_status')->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['vehicle_id', 'job_id'], 'as_vehicle_history_vehicle_job_unique');
            });
        }

        if (Schema::hasTable('auto_service_jobs') && Schema::hasTable('auto_service_vehicle_history_snapshots')) {
            DB::statement("INSERT IGNORE INTO auto_service_vehicle_history_snapshots (business_id, location_id, vehicle_id, job_id, snapshot_date, odometer, job_total, health_status, created_at, updated_at) SELECT business_id, location_id, vehicle_id, id, job_date, odometer, total_amount, status, NOW(), NOW() FROM auto_service_jobs WHERE vehicle_id IS NOT NULL");
        }
    }

    public function down()
    {
        // Non-destructive rollback intentionally left empty for tenant safety.
    }
};

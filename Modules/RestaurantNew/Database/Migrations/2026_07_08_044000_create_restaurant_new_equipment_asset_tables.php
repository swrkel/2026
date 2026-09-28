<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewEquipmentAssetTables extends Migration
{
    public function up()
    {
        Schema::create('restaurant_new_equipment_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('name');
            $table->string('code', 80)->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('restaurant_new_equipment_assets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('asset_code', 80)->index();
            $table->string('asset_name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_no')->nullable()->index();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 22, 4)->default(0);
            $table->date('warranty_expiry')->nullable()->index();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->string('kitchen_section')->nullable()->index();
            $table->unsignedBigInteger('assigned_employee_id')->nullable()->index();
            $table->string('status', 50)->default('working')->index();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['business_id', 'asset_code'], 'rn_asset_code_unique');
        });
        Schema::create('restaurant_new_equipment_maintenance_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('asset_id')->index();
            $table->string('schedule_type', 50)->default('preventive')->index();
            $table->string('frequency', 50)->default('monthly')->index();
            $table->date('next_due_date')->nullable()->index();
            $table->integer('running_hours_due')->nullable();
            $table->integer('usage_count_due')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('checklist')->nullable();
            $table->timestamps();
        });
        Schema::create('restaurant_new_equipment_work_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('work_order_no', 80)->index();
            $table->unsignedBigInteger('asset_id')->index();
            $table->string('work_type', 50)->default('repair')->index();
            $table->string('priority', 30)->default('normal')->index();
            $table->string('status', 50)->default('open')->index();
            $table->unsignedBigInteger('assigned_technician_id')->nullable()->index();
            $table->dateTime('requested_at')->nullable()->index();
            $table->dateTime('expected_completion_at')->nullable();
            $table->dateTime('completed_at')->nullable()->index();
            $table->decimal('labour_cost', 22, 4)->default(0);
            $table->decimal('parts_cost', 22, 4)->default(0);
            $table->decimal('downtime_hours', 12, 2)->default(0);
            $table->text('problem_description')->nullable();
            $table->text('resolution_note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('restaurant_new_equipment_spare_parts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('part_code', 80)->index();
            $table->string('part_name');
            $table->string('unit', 30)->default('nos');
            $table->decimal('current_stock', 22, 4)->default(0);
            $table->decimal('minimum_stock', 22, 4)->default(0);
            $table->decimal('reorder_level', 22, 4)->default(0);
            $table->decimal('last_purchase_cost', 22, 4)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['business_id', 'part_code'], 'rn_part_code_unique');
        });
        Schema::create('restaurant_new_equipment_work_order_parts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('work_order_id')->index();
            $table->unsignedBigInteger('spare_part_id')->index();
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('unit_cost', 22, 4)->default(0);
            $table->decimal('total_cost', 22, 4)->default(0);
            $table->timestamps();
        });
        Schema::create('restaurant_new_equipment_alerts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('asset_id')->nullable()->index();
            $table->unsignedBigInteger('spare_part_id')->nullable()->index();
            $table->string('alert_type', 60)->index();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('severity', 30)->default('info')->index();
            $table->string('status', 30)->default('open')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('restaurant_new_equipment_alerts');
        Schema::dropIfExists('restaurant_new_equipment_work_order_parts');
        Schema::dropIfExists('restaurant_new_equipment_spare_parts');
        Schema::dropIfExists('restaurant_new_equipment_work_orders');
        Schema::dropIfExists('restaurant_new_equipment_maintenance_schedules');
        Schema::dropIfExists('restaurant_new_equipment_assets');
        Schema::dropIfExists('restaurant_new_equipment_categories');
    }
}

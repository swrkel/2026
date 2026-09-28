<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disnew_drivers', function (Blueprint $table) {
            $table->id(); $table->unsignedInteger('business_id'); $table->unsignedInteger('business_location_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable(); $table->string('driver_code',50)->nullable(); $table->string('name',150);
            $table->string('mobile',30)->nullable(); $table->string('nic_no',60)->nullable(); $table->string('license_no',80)->nullable(); $table->date('license_expiry_date')->nullable();
            $table->enum('commission_type',['none','fixed','percentage','per_trip'])->default('none'); $table->decimal('commission_value',22,4)->default(0);
            $table->enum('status',['active','inactive','blocked'])->default('active'); $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps();
            $table->index(['business_id','business_location_id','status'],'disnew_drivers_business_idx'); $table->index('license_expiry_date','disnew_drivers_license_expiry_idx');
        });
        Schema::create('disnew_helpers', function (Blueprint $table) {
            $table->id(); $table->unsignedInteger('business_id'); $table->unsignedInteger('business_location_id')->nullable(); $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('helper_code',50)->nullable(); $table->string('name',150); $table->string('mobile',30)->nullable();
            $table->enum('commission_type',['none','fixed','percentage','per_trip'])->default('none'); $table->decimal('commission_value',22,4)->default(0);
            $table->enum('status',['active','inactive','blocked'])->default('active'); $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps();
            $table->index(['business_id','business_location_id','status'],'disnew_helpers_business_idx');
        });
        Schema::create('disnew_vehicle_odometer_histories', function (Blueprint $table) {
            $table->id(); $table->unsignedInteger('business_id'); $table->unsignedInteger('business_location_id')->nullable(); $table->unsignedBigInteger('vehicle_id'); $table->unsignedBigInteger('trip_id')->nullable();
            $table->date('reading_date'); $table->time('reading_time')->nullable(); $table->decimal('opening_odometer',22,3)->default(0); $table->decimal('closing_odometer',22,3)->nullable(); $table->decimal('distance',22,3)->default(0);
            $table->string('source_type',50)->nullable(); $table->text('note')->nullable(); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps();
            $table->index(['business_id','vehicle_id','reading_date'],'disnew_odometer_business_vehicle_idx');
        });
        Schema::create('disnew_vehicle_fuel_entries', function (Blueprint $table) {
            $table->id(); $table->unsignedInteger('business_id'); $table->unsignedInteger('business_location_id')->nullable(); $table->unsignedBigInteger('vehicle_id'); $table->unsignedBigInteger('driver_id')->nullable(); $table->unsignedBigInteger('trip_id')->nullable();
            $table->date('fuel_date'); $table->time('fuel_time')->nullable(); $table->string('fuel_type',50)->nullable(); $table->decimal('litres',22,3)->default(0); $table->decimal('unit_price',22,4)->default(0); $table->decimal('total_amount',22,4)->default(0); $table->decimal('odometer_reading',22,3)->nullable();
            $table->string('supplier_name',150)->nullable(); $table->string('receipt_no',80)->nullable(); $table->string('payment_method',50)->nullable(); $table->text('note')->nullable(); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps();
            $table->index(['business_id','vehicle_id','fuel_date'],'disnew_fuel_business_vehicle_idx');
        });
        Schema::create('disnew_vehicle_maintenances', function (Blueprint $table) {
            $table->id(); $table->unsignedInteger('business_id'); $table->unsignedInteger('business_location_id')->nullable(); $table->unsignedBigInteger('vehicle_id'); $table->date('maintenance_date');
            $table->string('maintenance_type',80); $table->decimal('odometer_reading',22,3)->nullable(); $table->date('next_due_date')->nullable(); $table->decimal('next_due_odometer',22,3)->nullable();
            $table->string('garage_name',150)->nullable(); $table->string('invoice_no',80)->nullable(); $table->decimal('cost_amount',22,4)->default(0); $table->enum('status',['scheduled','completed','cancelled'])->default('completed');
            $table->text('note')->nullable(); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps();
            $table->index(['business_id','vehicle_id','maintenance_date'],'disnew_maint_business_vehicle_idx'); $table->index('next_due_date','disnew_maint_due_idx');
        });
        Schema::create('disnew_vehicle_documents', function (Blueprint $table) {
            $table->id(); $table->unsignedInteger('business_id'); $table->unsignedInteger('business_location_id')->nullable(); $table->unsignedBigInteger('vehicle_id');
            $table->string('document_type',80); $table->string('document_no',100)->nullable(); $table->date('issue_date')->nullable(); $table->date('expiry_date')->nullable(); $table->integer('renewal_reminder_days')->default(30); $table->string('file_path',255)->nullable();
            $table->enum('status',['active','expired','renewed','cancelled'])->default('active'); $table->text('note')->nullable(); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps();
            $table->index(['business_id','vehicle_id','document_type'],'disnew_doc_business_vehicle_idx'); $table->index(['expiry_date','status'],'disnew_doc_expiry_idx');
        });
        Schema::create('disnew_trip_expenses', function (Blueprint $table) {
            $table->id(); $table->unsignedInteger('business_id'); $table->unsignedInteger('business_location_id')->nullable(); $table->unsignedBigInteger('trip_id')->nullable(); $table->unsignedBigInteger('vehicle_id')->nullable(); $table->unsignedBigInteger('driver_id')->nullable();
            $table->date('expense_date'); $table->string('expense_category',80); $table->decimal('amount',22,4)->default(0); $table->string('reference_no',100)->nullable(); $table->string('payment_method',50)->nullable(); $table->boolean('is_reimbursable')->default(false); $table->enum('status',['pending','approved','rejected','paid'])->default('pending');
            $table->text('note')->nullable(); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('approved_by')->nullable(); $table->timestamp('approved_at')->nullable(); $table->timestamps();
            $table->index(['business_id','expense_date','status'],'disnew_trip_exp_business_idx'); $table->index(['vehicle_id','trip_id'],'disnew_trip_exp_vehicle_idx');
        });
        Schema::create('disnew_trip_commissions', function (Blueprint $table) {
            $table->id(); $table->unsignedInteger('business_id'); $table->unsignedInteger('business_location_id')->nullable(); $table->unsignedBigInteger('trip_id')->nullable(); $table->unsignedBigInteger('vehicle_id')->nullable(); $table->unsignedBigInteger('driver_id')->nullable(); $table->unsignedBigInteger('helper_id')->nullable();
            $table->enum('commission_for',['driver','helper','sales_rep'])->default('driver'); $table->decimal('base_amount',22,4)->default(0); $table->enum('commission_type',['fixed','percentage','per_trip'])->default('fixed'); $table->decimal('commission_value',22,4)->default(0); $table->decimal('commission_amount',22,4)->default(0); $table->enum('status',['calculated','approved','paid','cancelled'])->default('calculated');
            $table->text('note')->nullable(); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('approved_by')->nullable(); $table->timestamp('approved_at')->nullable(); $table->timestamps();
            $table->index(['business_id','status'],'disnew_trip_comm_business_idx'); $table->index(['trip_id','vehicle_id'],'disnew_trip_comm_trip_idx');
        });
        $permissions = ['distributionnew.drivers.view','distributionnew.drivers.create','distributionnew.drivers.update','distributionnew.helpers.view','distributionnew.helpers.create','distributionnew.helpers.update','distributionnew.fuel.view','distributionnew.fuel.create','distributionnew.odometer.view','distributionnew.odometer.create','distributionnew.maintenance.view','distributionnew.maintenance.create','distributionnew.vehicle_documents.view','distributionnew.vehicle_documents.create','distributionnew.trip_expenses.view','distributionnew.trip_expenses.create','distributionnew.trip_commissions.view','distributionnew.trip_commissions.approve'];
        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(['name'=>$permission,'guard_name'=>'web'], ['created_at'=>now(),'updated_at'=>now()]);
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('disnew_trip_commissions'); Schema::dropIfExists('disnew_trip_expenses'); Schema::dropIfExists('disnew_vehicle_documents'); Schema::dropIfExists('disnew_vehicle_maintenances'); Schema::dropIfExists('disnew_vehicle_fuel_entries'); Schema::dropIfExists('disnew_vehicle_odometer_histories'); Schema::dropIfExists('disnew_helpers'); Schema::dropIfExists('disnew_drivers');
    }
};

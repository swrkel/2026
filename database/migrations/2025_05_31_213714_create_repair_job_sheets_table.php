<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('repair_job_sheets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('location_id')->nullable()->index('location_id');
            $table->unsignedInteger('contact_id')->index('contact_id');
            $table->string('job_sheet_no');
            $table->enum('service_type', ['carry_in', 'pick_up', 'on_site']);
            $table->text('pick_up_on_site_addr')->nullable();
            $table->unsignedInteger('brand_id')->nullable()->index('brand_id');
            $table->unsignedInteger('device_id')->nullable()->index('device_id');
            $table->unsignedInteger('device_model_id')->nullable()->index('device_model_id');
            $table->text('checklist')->nullable();
            $table->string('security_pwd')->nullable();
            $table->string('security_pattern')->nullable();
            $table->string('serial_no');
            $table->integer('status_id')->index('status_id');
            $table->dateTime('delivery_date')->nullable();
            $table->text('product_configuration')->nullable();
            $table->text('defects')->nullable();
            $table->text('product_condition')->nullable();
            $table->unsignedInteger('service_staff')->nullable();
            $table->text('comment_by_ss')->nullable()->comment('comment made by technician');
            $table->decimal('estimated_cost', 22, 4)->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();
            $table->string('custom_field_1')->nullable();
            $table->string('custom_field_2')->nullable();
            $table->string('custom_field_3')->nullable();
            $table->string('custom_field_4')->nullable();
            $table->string('custom_field_5')->nullable();
            $table->text('parts')->nullable();
            $table->string('warranty_number', 255)->nullable();
            $table->integer('reportStatus')->default(0)->comment('0 = repair , 1 = autorepairservices');
            $table->integer('vehicle_id')->nullable()->index('vehicle_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('repair_job_sheets');
    }
};

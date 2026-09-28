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
        Schema::create('form_f22_headers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('form_no');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('location_id')->index('location_id');
            $table->string('manager_name', 250)->nullable();
            $table->string('pre_field', 250)->nullable();
            $table->string('last_bill', 100)->nullable();
            $table->date('form_date')->nullable();
            $table->string('start_time', 200)->nullable();
            $table->string('last_time', 200)->nullable();
            $table->decimal('purchase_price1', 15)->nullable();
            $table->decimal('purchase_price2', 15)->nullable();
            $table->decimal('purchase_price3', 15)->nullable();
            $table->decimal('sales_price1', 15)->nullable();
            $table->decimal('sales_price2', 15)->nullable();
            $table->decimal('sales_price3', 15)->nullable();
            $table->integer('created_by');
            $table->integer('status');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_f22_headers');
    }
};

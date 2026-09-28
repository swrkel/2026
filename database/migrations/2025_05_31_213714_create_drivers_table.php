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
        Schema::create('drivers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->date('joined_date');
            $table->string('employee_no');
            $table->string('driver_name');
            $table->string('nic_number');
            $table->string('dl_number');
            $table->string('dl_type');
            $table->date('expiry_date');
            $table->boolean('hrm_enabled')->nullable()->default(false);
            $table->integer('department')->nullable();
            $table->integer('designation')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->string('pass_no', 45)->nullable();
            $table->date('pass_expiry_date')->nullable();
            $table->integer('advance_expense_category')->nullable();
            $table->integer('salary_expense_category')->nullable();
            $table->integer('bata_expense_category')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drivers');
    }
};

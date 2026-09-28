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
        Schema::create('hrm_employee_ledgers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('form_number', 45)->nullable();
            $table->integer('employee_id')->nullable();
            $table->date('date')->nullable();
            $table->decimal('amount', 10, 3)->nullable();
            $table->text('note')->nullable();
            $table->string('add_by', 100)->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->default('0000-00-00 00:00:00');
            $table->integer('business_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hrm_employee_ledgers');
    }
};

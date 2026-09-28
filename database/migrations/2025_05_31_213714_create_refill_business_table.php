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
        Schema::create('refill_business', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->string('type', 200)->default('business');
            $table->integer('package_id')->index('package_id');
            $table->timestamp('date')->useCurrent();
            $table->timestamp('expiry_date')->useCurrentOnUpdate()->useCurrent();
            $table->text('note')->nullable();
            $table->string('payment_method', 200)->nullable();
            $table->string('bank_name', 200)->nullable();
            $table->string('cheque_no', 200)->nullable();
            $table->timestamp('cheque_date')->nullable();
            $table->integer('created_by');
            $table->timestamp('created_at')->default('0000-00-00 00:00:00');
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('refill_business');
    }
};

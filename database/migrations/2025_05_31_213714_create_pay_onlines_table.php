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
        Schema::create('pay_onlines', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('order_id')->index('order_id');
            $table->date('date');
            $table->string('pay_online_no');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('note')->nullable();
            $table->enum('type', ['security_deposit']);
            $table->decimal('amount', 15, 4);
            $table->string('reference_no')->nullable();
            $table->string('currency', 10)->nullable();
            $table->enum('paid_via', ['payhere', 'offline']);
            $table->integer('payment_transaction_id')->nullable()->index('payment_transaction_id');
            $table->enum('status', ['approved', 'declined', 'pending'])->default('pending');
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
        Schema::dropIfExists('pay_onlines');
    }
};

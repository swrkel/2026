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
        Schema::create('family_subscriptions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('package_id')->index('package_id');
            $table->string('order_id')->nullable()->index('order_id');
            $table->unsignedInteger('no_of_family_members');
            $table->integer('option_variable_id')->index('option_variable_id');
            $table->decimal('amount_to_pay', 15, 4);
            $table->string('paid_via')->nullable();
            $table->string('payment_transaction_id')->nullable()->index('payment_transaction_id');
            $table->enum('status', ['approved', 'waiting', 'declined'])->default('waiting');
            $table->unsignedInteger('created_by');
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
        Schema::dropIfExists('family_subscriptions');
    }
};

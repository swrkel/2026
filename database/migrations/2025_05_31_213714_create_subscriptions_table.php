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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('package_id')->index('package_id');
            $table->date('start_date')->nullable();
            $table->date('trial_end_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('package_price', 20);
            $table->longText('package_details');
            $table->unsignedInteger('created_id')->index('created_id');
            $table->string('paid_via')->nullable();
            $table->string('payment_transaction_id')->nullable()->index('payment_transaction_id');
            $table->enum('status', ['approved', 'waiting', 'declined'])->default('waiting');
            $table->softDeletes();
            $table->timestamps();
            $table->text('module_activation_details');
            $table->text('customer_credit_notification_type')->nullable();

            $table->index(['business_id'], 'subscriptions_business_id_foreign');
            $table->index(['created_id']);
            $table->index(['package_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
};

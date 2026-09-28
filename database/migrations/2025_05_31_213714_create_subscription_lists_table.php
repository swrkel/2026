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
        Schema::create('subscription_lists', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->integer('contact_id')->index('contact_id');
            $table->integer('settings_id')->index('settings_id');
            $table->timestamp('transaction_date')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('expiry_date')->default('0000-00-00 00:00:00');
            $table->integer('send_sms')->default(0);
            $table->text('note')->nullable();
            $table->integer('created_by');
            $table->timestamp('created_at')->default('0000-00-00 00:00:00');
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
            $table->integer('status')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_lists');
    }
};

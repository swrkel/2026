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
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('list_id')->index('list_id');
            $table->decimal('amount', 15, 5);
            $table->timestamp('expiry_date')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('transaction_date')->default('0000-00-00 00:00:00');
            $table->text('note')->nullable();
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
        Schema::dropIfExists('subscription_payments');
    }
};

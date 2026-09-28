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
        Schema::create('settlement_shortage_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('transaction_id', 255);
            $table->string('settlement_no');
            $table->unsignedInteger('business_id');
            $table->decimal('amount', 15, 6);
            $table->decimal('current_shortage', 15, 6)->default(0);
            $table->text('note')->nullable();
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
        Schema::dropIfExists('settlement_shortage_payments');
    }
};

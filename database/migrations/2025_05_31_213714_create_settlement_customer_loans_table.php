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
        Schema::create('settlement_customer_loans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('settlement_no', 255);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('customer_id')->index('customer_id');
            $table->decimal('amount', 15, 6);
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
        Schema::dropIfExists('settlement_customer_loans');
    }
};

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
        Schema::create('installments_table', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('installment_id')->index('installment_id');
            $table->integer('business_id')->index('business_id');
            $table->integer('contact_id')->index('contact_id');
            $table->integer('transaction_id')->index('transaction_id');
            $table->integer('payment_id')->nullable()->index('payment_id');
            $table->integer('system_id')->index('system_id');
            $table->integer('installment_number')->nullable();
            $table->decimal('installment_value', 20);
            $table->integer('number');
            $table->integer('period');
            $table->string('type', 10);
            $table->decimal('benefit', 5);
            $table->string('benefit_type', 10);
            $table->decimal('benefit_value', 10)->nullable();
            $table->decimal('latfines', 10)->nullable();
            $table->string('latfinestype', 10)->nullable();
            $table->decimal('latfines_value', 10)->nullable();
            $table->decimal('paid_value', 10);
            $table->date('paid_date')->nullable();
            $table->date('installmentdate')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('installments_table');
    }
};

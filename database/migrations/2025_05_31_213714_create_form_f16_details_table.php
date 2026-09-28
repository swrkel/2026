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
        Schema::create('form_f16_details', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('transaction_id')->index('transaction_id');
            $table->integer('form_no');
            $table->string('supplier', 50);
            $table->string('invoice_no', 20);
            $table->string('this_form_total', 20);
            $table->string('last_form_total', 20);
            $table->string('grand_total', 20);
            $table->string('book_no', 20);
            $table->string('book_stock', 20);
            $table->string('this_book', 50);
            $table->string('prev_book', 20);
            $table->string('grand_book', 20);
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
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
        Schema::dropIfExists('form_f16_details');
    }
};

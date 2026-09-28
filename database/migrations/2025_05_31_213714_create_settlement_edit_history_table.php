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
        Schema::create('settlement_edit_history', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('settlement_id')->index('settlement_id');
            $table->string('settlement_date', 200);
            $table->string('pump_operator_name', 200);
            $table->text('settlement_pumps');
            $table->string('total_sale_amount', 200);
            $table->string('total_cash', 200);
            $table->string('total_cards', 200);
            $table->string('total_credit_sales', 200);
            $table->string('total_short', 200);
            $table->string('total_loans', 200);
            $table->string('total_cheques', 200);
            $table->string('editted_by', 200);
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
        Schema::dropIfExists('settlement_edit_history');
    }
};

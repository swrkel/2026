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
        Schema::create('cheque_numbers_m_entries', function (Blueprint $table) {
            $table->integer('id', true);
            $table->dateTime('date_time')->nullable();
            $table->integer('bank_id')->nullable();
            $table->integer('cheque_number_id')->nullable();
            $table->integer('next_cheque_number_to_print')->nullable();
            $table->string('new_cheque_number_to_print', 50)->nullable();
            $table->string('next_cheque_number_to_auto_print', 50)->nullable();
            $table->text('note')->nullable();
            $table->integer('business_id')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('edited_by')->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cheque_numbers_m_entries');
    }
};

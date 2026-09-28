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
        Schema::create('essentials_allowances_and_deductions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index('business_id');
            $table->string('description');
            $table->enum('type', ['allowance', 'deduction']);
            $table->decimal('amount', 22, 4);
            $table->enum('amount_type', ['fixed', 'percent']);
            $table->date('applicable_date')->nullable();
            $table->timestamps();

            $table->index(['business_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_allowances_and_deductions');
    }
};

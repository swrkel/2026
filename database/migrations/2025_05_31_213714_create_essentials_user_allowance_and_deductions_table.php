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
        Schema::create('essentials_user_allowance_and_deductions', function (Blueprint $table) {
            $table->integer('user_id')->index();
            $table->integer('allowance_deduction_id')->index('allow_deduct_index');

            $table->index(['allowance_deduction_id'], 'allowance_deduction_id');
            $table->index(['user_id'], 'user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_user_allowance_and_deductions');
    }
};

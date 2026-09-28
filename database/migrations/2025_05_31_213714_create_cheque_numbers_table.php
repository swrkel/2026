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
        Schema::create('cheque_numbers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->dateTime('date_time');
            $table->string('reference_no', 100)->nullable();
            $table->unsignedInteger('business_id')->default(0)->index('business_id');
            $table->string('account_no', 100)->nullable();
            $table->string('first_cheque_no', 100)->nullable();
            $table->string('last_cheque_no', 100)->nullable();
            $table->string('no_of_cheque_leaves', 50)->nullable();
            $table->bigInteger('latest_cheque_issue')->nullable();
            $table->enum('status', ['active', 'stop', 'inused', 'used'])->default('active');
            $table->integer('user_id')->default(0)->index('user_id');
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
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
        Schema::dropIfExists('cheque_numbers');
    }
};

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
        Schema::create('cancel_cheque', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id')->index('user_id');
            $table->integer('account_id')->index('account_id');
            $table->integer('business_id')->index('business_id');
            $table->bigInteger('cheque_bk_id')->nullable();
            $table->string('cheque_no', 20);
            $table->timestamp('reg_datetime')->useCurrent();
            $table->string('note', 100);
            $table->timestamp('updated_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cancel_cheque');
    }
};

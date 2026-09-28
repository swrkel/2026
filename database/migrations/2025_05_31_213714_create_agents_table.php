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
        if (Schema::hasTable('agents')) {
            return;
        }

        Schema::create('agents', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date');
            $table->string('name');
            $table->text('address');
            $table->string('mobile_number');
            $table->string('land_number')->nullable();
            $table->string('email');
            $table->string('username');
            $table->string('password');
            $table->string('nic_number');
            $table->string('nic_copy')->nullable();
            $table->string('agent_photo')->nullable();
            $table->string('referral_code');
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('branch');
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
        Schema::dropIfExists('agents');
    }
};

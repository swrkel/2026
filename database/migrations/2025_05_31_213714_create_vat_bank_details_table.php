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
        Schema::create('vat_bank_details', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id');
            $table->string('bank_name', 255);
            $table->string('bank_branch', 255);
            $table->string('account_number', 255);
            $table->string('account_name', 255);
            $table->text('special_instructions')->nullable();
            $table->integer('status');
            $table->integer('user_id');
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
        Schema::dropIfExists('vat_bank_details');
    }
};

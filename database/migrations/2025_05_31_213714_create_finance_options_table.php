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
        Schema::create('finance_options', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('location_id')->index('location_id');
            $table->date('date');
            $table->string('finance_option');
            $table->enum('custom_payments', ['no', 'yes']);
            $table->unsignedInteger('created_by');
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
        Schema::dropIfExists('finance_options');
    }
};

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
        Schema::create('chequer_suppliers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('name', 4999);
            $table->string('email', 255)->nullable();
            $table->string('password', 255);
            $table->longText('address')->nullable();
            $table->string('tel', 255)->nullable();
            $table->string('fax', 255)->nullable();
            $table->integer('created_user_id')->default(0)->index('created_user_id');
            $table->dateTime('created_datetime');
            $table->integer('updated_user_id')->nullable()->index('updated_user_id');
            $table->dateTime('updated_datetime')->nullable();
            $table->integer('status')->default(0);
            $table->string('balance', 255);
            $table->dateTime('transaction_date')->nullable();
            $table->integer('isPayee')->nullable();
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
        Schema::dropIfExists('chequer_suppliers');
    }
};

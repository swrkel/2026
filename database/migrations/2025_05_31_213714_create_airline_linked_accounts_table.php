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
        Schema::create('airline_linked_accounts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('AccountType', 20);
            $table->string('AcoountName', 50);
            $table->string('AccountNumber', 20);
            $table->string('user', 20);
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
            $table->dateTime('date')->nullable();
            $table->integer('supplier_id')->nullable();
            $table->integer('business_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('airline_linked_accounts');
    }
};

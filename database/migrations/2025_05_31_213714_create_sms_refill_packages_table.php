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
        Schema::create('sms_refill_packages', function (Blueprint $table) {
            $table->integer('id', true);
            $table->timestamp('date')->useCurrentOnUpdate()->useCurrent();
            $table->string('name', 200);
            $table->decimal('unit_cost', 15, 3);
            $table->decimal('amount', 15, 5);
            $table->integer('no_of_sms');
            $table->integer('created_by');
            $table->timestamp('created_at')->default('0000-00-00 00:00:00');
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
        Schema::dropIfExists('sms_refill_packages');
    }
};

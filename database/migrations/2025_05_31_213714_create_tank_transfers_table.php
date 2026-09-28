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
        Schema::create('tank_transfers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->string('transfer_no', 30)->nullable();
            $table->integer('from_tank');
            $table->integer('to_tank');
            $table->timestamp('date')->useCurrentOnUpdate()->useCurrent();
            $table->decimal('quantity', 22, 5);
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
        Schema::dropIfExists('tank_transfers');
    }
};

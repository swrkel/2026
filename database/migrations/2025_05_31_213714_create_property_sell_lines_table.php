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
        Schema::create('property_sell_lines', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('transaction_id')->index('transaction_id');
            $table->unsignedInteger('property_id')->index('property_id');
            $table->unsignedInteger('block_id')->index('block_id');
            $table->string('block_number');
            $table->string('unit');
            $table->decimal('size', 15, 4);
            $table->decimal('block_value', 15, 6);
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
        Schema::dropIfExists('property_sell_lines');
    }
};

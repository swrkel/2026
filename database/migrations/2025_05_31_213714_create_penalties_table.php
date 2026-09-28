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
        Schema::create('penalties', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('transaction_id')->index('transaction_id');
            $table->unsignedInteger('sell_line_id')->index('sell_line_id');
            $table->unsignedInteger('block_id')->index('block_id');
            $table->date('date');
            $table->decimal('amount', 15, 6);
            $table->text('note');
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
        Schema::dropIfExists('penalties');
    }
};

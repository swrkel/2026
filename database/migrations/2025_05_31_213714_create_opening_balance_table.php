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
        Schema::create('opening_balance', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('fleets_id')->index('fleets_id');
            $table->string('contact_id', 255)->index('contact_id');
            $table->string('opening_amount', 255);
            $table->timestamp('invoice_date')->nullable();
            $table->string('invoice_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('opening_balance');
    }
};

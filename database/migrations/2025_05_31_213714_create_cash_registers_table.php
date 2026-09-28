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
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->integer('location_id')->nullable()->index();
            $table->unsignedInteger('user_id')->nullable()->index('cash_registers_user_id_foreign');
            $table->enum('status', ['close', 'open'])->default('open');
            $table->dateTime('closed_at')->nullable();
            $table->decimal('closing_amount', 22, 4)->default(0);
            $table->integer('total_card_slips')->default(0);
            $table->integer('total_cheques')->default(0);
            $table->decimal('total_credit_sale', 15, 4)->default(0);
            $table->text('closing_note')->nullable();
            $table->timestamps();

            $table->index(['business_id'], 'cash_registers_business_id_foreign');
            $table->index(['location_id'], 'location_id');
            $table->index(['user_id'], 'user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash_registers');
    }
};

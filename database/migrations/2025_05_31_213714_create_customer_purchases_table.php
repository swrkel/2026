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
        Schema::create('customer_purchases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable()->index('customer_purchases_user_id_foreign');
            $table->char('purchase_code', 36);
            $table->string('buyer')->nullable();
            $table->double('amount', 8, 2);
            $table->dateTime('sold_at');
            $table->string('license');
            $table->double('support_amount', 8, 2);
            $table->dateTime('supported_until');
            $table->bigInteger('item_id');
            $table->string('item_name');
            $table->timestamps();
            $table->text('item_icon')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_purchases');
    }
};

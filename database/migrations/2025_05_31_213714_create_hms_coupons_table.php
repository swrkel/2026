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
        Schema::create('hms_coupons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('hms_room_type_id')->index('hms_room_type_id');
            $table->integer('business_id')->index('business_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('coupon_code');
            $table->decimal('discount');
            $table->string('discount_type');
            $table->timestamps();
            $table->longText('room_type_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hms_coupons');
    }
};

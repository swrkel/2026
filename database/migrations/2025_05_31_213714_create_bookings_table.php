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
        Schema::create('bookings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('contact_id')->index('bookings_contact_id_foreign');
            $table->unsignedInteger('waiter_id')->nullable()->index();
            $table->unsignedInteger('table_id')->nullable()->index();
            $table->integer('correspondent_id')->nullable()->index('correspondent_id');
            $table->unsignedInteger('business_id')->index('bookings_business_id_foreign');
            $table->unsignedInteger('location_id')->index();
            $table->dateTime('booking_start');
            $table->dateTime('booking_end');
            $table->unsignedInteger('created_by')->index('bookings_created_by_foreign');
            $table->enum('booking_status', ['booked', 'completed', 'cancelled']);
            $table->text('booking_note')->nullable();
            $table->timestamps();

            $table->index(['business_id'], 'business_id');
            $table->index(['contact_id'], 'contact_id');
            $table->index(['location_id'], 'location_id');
            $table->index(['table_id'], 'table_id');
            $table->index(['waiter_id'], 'waiter_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormF25DeliveryLocationsTable extends Migration
{
    public function up()
    {
        Schema::create('mpcs_f25_delivery_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->string('location_code', 10);
            $table->string('location_name');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'location_code']);
            $table->index(['business_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mpcs_f25_delivery_locations');
    }
}


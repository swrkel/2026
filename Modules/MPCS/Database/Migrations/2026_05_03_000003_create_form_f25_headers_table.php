<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormF25HeadersTable extends Migration
{
    public function up()
    {
        Schema::create('mpcs_f25_forms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->string('form_no');
            $table->date('transaction_date');
            $table->unsignedInteger('supplier_id')->nullable();
            $table->string('bill_no')->nullable();
            $table->unsignedBigInteger('delivery_location_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'transaction_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mpcs_f25_forms');
    }
}


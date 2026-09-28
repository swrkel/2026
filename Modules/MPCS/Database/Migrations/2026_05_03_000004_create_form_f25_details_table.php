<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormF25DetailsTable extends Migration
{
    public function up()
    {
        Schema::create('mpcs_f25_form_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('f25_form_id');
            $table->string('bill_no')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->string('description')->nullable();
            $table->string('pcs')->nullable();
            $table->decimal('qty', 22, 4)->default(0);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->decimal('received_qty', 22, 4)->default(0);
            $table->decimal('short_qty', 22, 4)->default(0);
            $table->decimal('excess_qty', 22, 4)->default(0);
            $table->decimal('short_amount', 22, 4)->default(0);
            $table->decimal('excess_amount', 22, 4)->default(0);
            $table->string('short_signature')->nullable();
            $table->date('line_date')->nullable();
            $table->time('line_time')->nullable();
            $table->string('field_21')->nullable();
            $table->string('field_22')->nullable();
            $table->timestamps();

            $table->index(['f25_form_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mpcs_f25_form_lines');
    }
}

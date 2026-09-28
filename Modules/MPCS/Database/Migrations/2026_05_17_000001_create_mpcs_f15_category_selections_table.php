<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMpcsF15CategorySelectionsTable extends Migration
{
    public function up()
    {
        Schema::create('mpcs_f15_category_selections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->text('category_ids'); // Stores JSON array of selected category IDs
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['business_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mpcs_f15_category_selections');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistributionLoadingLinesTable extends Migration
{
    public function up()
    {
        Schema::create('distribution_loading_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loading_id')->index();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->decimal('requested_qty', 22, 4)->default(0);
            $table->decimal('issued_qty', 22, 4)->default(0);
            $table->decimal('sale_price', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->timestamps();

            $table->foreign('loading_id')->references('id')->on('distribution_loadings')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('distribution_loading_lines');
    }
}

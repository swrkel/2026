<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistributionDailySummaryLinesTable extends Migration
{
    public function up()
    {
        Schema::create('distribution_daily_summary_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daily_summary_id')->index();
            $table->integer('page_no')->default(1);

            $table->unsignedBigInteger('bill_id')->nullable(); // link to distribution_invoices.id
            $table->unsignedBigInteger('customer_id')->nullable();

            $table->decimal('gross_sale', 18, 4)->default(0);
            $table->decimal('discount', 18, 4)->default(0);
            $table->decimal('net_sale', 18, 4)->default(0);

            $table->json('products_json')->nullable(); // { product_id: qty, ... } dynamic product quantities
            $table->integer('row_number')->default(0);

            $table->timestamps();

            $table->foreign('daily_summary_id')->references('id')->on('distribution_daily_summaries')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('distribution_daily_summary_lines');
    }
}

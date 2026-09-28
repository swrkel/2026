<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistributionDailySummariesTable extends Migration
{
    public function up()
    {
        Schema::create('distribution_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->string('sheet_number')->index(); // unique-ish from numbering settings
            $table->unsignedBigInteger('sales_rep_id')->nullable();
            $table->string('agent_name')->nullable();
            $table->date('date')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('product_category_id')->nullable();
            $table->decimal('distance_km', 10, 2)->nullable();

            // per-sheet (page-level) summary fields (last page state)
            $table->decimal('total_this_page', 18, 4)->default(0);
            $table->decimal('previous_page_gt', 18, 4)->default(0);
            $table->decimal('grand_total', 18, 4)->default(0);

            $table->integer('page_no')->default(1);
            $table->integer('total_pages')->default(1);

            $table->json('product_list')->nullable(); // stores array of product ids & names for the sheet
            $table->string('status')->default('draft'); // draft / finalized
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('distribution_daily_summaries');
    }
}

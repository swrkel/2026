<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehicleServiceLinesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('vehicle_service_lines')) {
            Schema::create('vehicle_service_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('vehicle_service_job_id')->index();
                $table->unsignedInteger('product_id')->nullable()->index();
                $table->unsignedInteger('variation_id')->nullable()->index();
                $table->string('item_type')->default('custom')->index();
                $table->string('item_name');
                $table->text('description')->nullable();
                $table->decimal('quantity', 22, 4)->default(1);
                $table->decimal('unit_price', 22, 4)->default(0);
                $table->decimal('discount_amount', 22, 4)->default(0);
                $table->decimal('tax_amount', 22, 4)->default(0);
                $table->decimal('line_total', 22, 4)->default(0);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('vehicle_service_lines');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mpcs_f10_headers')) {
            Schema::create('mpcs_f10_headers', function (Blueprint $table) {
                $table->id();
                $table->integer('business_id');
                $table->integer('location_id');
                $table->string('form_no');
                $table->date('form_date');
                $table->string('status')->default('active');
                $table->integer('created_by');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mpcs_f10_details')) {
            Schema::create('mpcs_f10_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('header_id');
                $table->integer('product_id');
                $table->integer('variation_id');
                $table->decimal('quantity', 22, 4)->default(0);
                $table->decimal('unit_purchase_price', 22, 4)->default(0);
                $table->decimal('unit_sale_price', 22, 4)->default(0);
                $table->decimal('purchase_price_total', 22, 4)->default(0);
                $table->decimal('sales_price_total', 22, 4)->default(0);
                $table->timestamps();
                $table->foreign('header_id')->references('id')->on('mpcs_f10_headers')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mpcs_f10_details');
        Schema::dropIfExists('mpcs_f10_headers');
    }
};

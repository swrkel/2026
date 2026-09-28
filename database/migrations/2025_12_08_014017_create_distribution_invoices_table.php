<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('distribution_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('customer_id');
            $table->date('date');
            $table->unsignedBigInteger('sales_rep_id')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('invoice_no')->unique();
            $table->decimal('total', 22, 4)->default(0);
            $table->decimal('discount', 22, 4)->default(0);
            $table->decimal('grand_total', 22, 4)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_invoices');
    }
};

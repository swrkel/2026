<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tailoring_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('tailoring_customer_id')->index();
            $table->string('order_no')->unique();
            $table->date('order_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->decimal('advance_amount', 22, 4)->default(0);
            $table->decimal('balance_amount', 22, 4)->default(0);
            $table->string('status')->default('order_received')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tailoring_orders'); }
};

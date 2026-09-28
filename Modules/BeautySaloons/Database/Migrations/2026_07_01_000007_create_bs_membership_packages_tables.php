<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bs_membership_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('name');
            $table->decimal('price', 22, 4)->default(0);
            $table->integer('valid_days')->default(30);
            $table->decimal('discount_percent', 8, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('bs_service_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('package_code')->nullable()->index();
            $table->string('name');
            $table->decimal('price', 22, 4)->default(0);
            $table->integer('valid_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('bs_package_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('package_id')->nullable()->index();
            $table->date('sale_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('bs_package_sales');
        Schema::dropIfExists('bs_service_packages');
        Schema::dropIfExists('bs_membership_plans');
    }
};

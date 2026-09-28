<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('beauty_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->string('package_code')->nullable()->index();
            $table->string('name');
            $table->decimal('price', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('beauty_package_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('beauty_packages')->cascadeOnDelete();
            $table->unsignedBigInteger('service_id')->index();
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('line_price', 22, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('beauty_staff_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedBigInteger('staff_id')->index();
            $table->unsignedBigInteger('service_id')->nullable()->index();
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('commission_value', 22, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('beauty_customer_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('appointment_id')->nullable()->index();
            $table->unsignedBigInteger('sale_id')->nullable()->index();
            $table->dateTime('visit_datetime')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_customer_visits');
        Schema::dropIfExists('beauty_staff_commissions');
        Schema::dropIfExists('beauty_package_lines');
        Schema::dropIfExists('beauty_packages');
    }
};

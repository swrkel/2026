<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bs_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->string('customer_code')->nullable()->index();
            $table->string('name');
            $table->string('mobile')->nullable()->index();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('bs_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('service_code')->nullable()->index();
            $table->string('name');
            $table->string('category')->nullable()->index();
            $table->integer('duration_minutes')->default(30);
            $table->decimal('price', 22, 4)->default(0);
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('bs_staffs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('staff_code')->nullable()->index();
            $table->string('name');
            $table->string('mobile')->nullable();
            $table->string('commission_type')->nullable();
            $table->decimal('commission_value', 22, 4)->default(0);
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('bs_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->string('room_code')->nullable()->index();
            $table->string('name');
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bs_rooms');
        Schema::dropIfExists('bs_staffs');
        Schema::dropIfExists('bs_services');
        Schema::dropIfExists('bs_customers');
    }
};

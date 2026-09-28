<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bkg_ib_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedInteger('contact_id')->nullable()->index();
            $table->string('customer_code')->nullable()->index();
            $table->string('username')->unique();
            $table->string('email')->nullable()->index();
            $table->string('mobile')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_ib_customers');
    }
};

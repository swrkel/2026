<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bkg_ib_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('internet_customer_id')->index();
            $table->string('device_name')->nullable();
            $table->string('device_token')->nullable()->index();
            $table->string('ip_address')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('trusted_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_ib_devices');
    }
};

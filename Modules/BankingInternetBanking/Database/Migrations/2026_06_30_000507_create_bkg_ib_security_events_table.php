<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bkg_ib_security_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('internet_customer_id')->nullable()->index();
            $table->string('event_type')->index();
            $table->string('severity')->default('info')->index();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_ib_security_events');
    }
};

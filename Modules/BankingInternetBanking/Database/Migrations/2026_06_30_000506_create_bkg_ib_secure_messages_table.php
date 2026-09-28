<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bkg_ib_secure_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('internet_customer_id')->nullable()->index();
            $table->string('ticket_no')->unique();
            $table->string('subject')->nullable();
            $table->string('category')->nullable()->index();
            $table->string('priority')->default('normal')->index();
            $table->string('status')->default('open')->index();
            $table->longText('message')->nullable();
            $table->unsignedInteger('assigned_to')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_ib_secure_messages');
    }
};

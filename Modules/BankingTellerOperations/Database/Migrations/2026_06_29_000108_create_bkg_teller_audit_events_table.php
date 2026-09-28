<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bkg_teller_audit_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->index();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable()->index();
            $table->ipAddress('ip_address')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_teller_audit_events');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('membership_signatures')) {
            Schema::create('membership_signatures', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id');
                $table->string('signature_path')->comment('Path to signature image file');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_signatures');
    }
};


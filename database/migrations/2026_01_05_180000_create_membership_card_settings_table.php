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
        if (!Schema::hasTable('membership_card_settings')) {
            Schema::create('membership_card_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id');
                $table->decimal('length', 8, 2)->comment('Card length in millimeters');
                $table->decimal('width', 8, 2)->comment('Card width in millimeters');
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
        Schema::dropIfExists('membership_card_settings');
    }
};


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
        if (!Schema::hasTable('membership_statuses')) {
            Schema::create('membership_statuses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0);
                $table->string('status_name', 255);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                
                $table->index('business_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_statuses');
    }
};


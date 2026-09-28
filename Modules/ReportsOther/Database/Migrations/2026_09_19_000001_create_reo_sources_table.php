<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reo_sources', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('scope_key', 120);
            $table->string('source_name', 120);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name', 190)->nullable();
            $table->timestamps();
            $table->index(['business_id', 'location_id', 'store_id'], 'reo_sources_scope_idx');
            $table->index(['scope_key', 'source_name'], 'reo_sources_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reo_sources');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reo_number_sequences', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('scope_key', 120);
            $table->string('document_key', 60);
            $table->string('prefix', 30)->nullable();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['scope_key', 'document_key'], 'reo_number_sequence_scope_unique');
            $table->index(['business_id', 'location_id', 'store_id'], 'reo_number_sequence_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reo_number_sequences');
    }
};

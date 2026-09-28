<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reo_source_mappings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('source_id');
            $table->string('item_type', 30);
            $table->unsignedBigInteger('item_id');
            $table->string('item_name_snapshot', 190);
            $table->timestamp('created_at')->nullable();
            $table->foreign('source_id', 'reo_src_map_source_fk')->references('id')->on('reo_sources')->onDelete('cascade');
            $table->unique(['source_id', 'item_type', 'item_id'], 'reo_src_map_unique');
            $table->index(['item_type', 'item_id'], 'reo_src_map_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reo_source_mappings');
    }
};

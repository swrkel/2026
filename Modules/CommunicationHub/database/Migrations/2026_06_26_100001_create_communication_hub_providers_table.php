<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { if (! Schema::hasTable('communication_hub_providers')) { Schema::create('communication_hub_providers', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('channel', 50); $table->string('driver', 100)->nullable(); $table->unsignedInteger('priority')->default(1); $table->boolean('is_active')->default(true); $table->json('provider_config')->nullable(); $table->json('meta')->nullable(); $table->timestamps(); }); } }
    public function down(): void { Schema::dropIfExists('communication_hub_providers'); }
};

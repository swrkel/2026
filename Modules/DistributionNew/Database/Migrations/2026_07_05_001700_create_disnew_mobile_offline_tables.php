<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('disnew_mobile_tokens', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger('user_id')->nullable();
            $table->string('device_uuid',100); $table->string('token',128)->unique(); $table->string('status',30)->default('active');
            $table->dateTime('expires_at')->nullable(); $table->timestamps(); $table->index(['business_id','device_uuid']);
        });
        Schema::create('disnew_offline_devices', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger('location_id')->nullable(); $table->unsignedBigInteger('user_id')->nullable();
            $table->string('device_uuid',100)->unique(); $table->string('device_name')->nullable(); $table->string('platform',50)->nullable(); $table->string('app_version',50)->nullable();
            $table->string('status',30)->default('active'); $table->dateTime('last_seen_at')->nullable(); $table->timestamps(); $table->index(['business_id','location_id']);
        });
        Schema::create('disnew_sync_batches', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger('location_id')->nullable(); $table->string('device_uuid',100)->nullable();
            $table->string('batch_uuid',100)->unique(); $table->string('direction',20); $table->string('status',30)->default('received'); $table->integer('payload_count')->default(0); $table->integer('conflict_count')->default(0);
            $table->unsignedBigInteger('created_by')->nullable(); $table->dateTime('processed_at')->nullable(); $table->timestamps(); $table->index(['business_id','location_id','status']);
        });
        Schema::create('disnew_sync_items', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('sync_batch_id'); $table->string('entity_type',100); $table->string('entity_uuid',100)->nullable(); $table->string('operation',30)->default('upsert');
            $table->longText('payload_json')->nullable(); $table->string('status',30)->default('queued'); $table->text('conflict_reason')->nullable(); $table->dateTime('processed_at')->nullable(); $table->timestamps();
            $table->index('sync_batch_id'); $table->index(['entity_type','entity_uuid']);
        });
        Schema::create('disnew_delivery_checkpoints', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger('location_id')->nullable(); $table->unsignedBigInteger('trip_id')->nullable(); $table->unsignedBigInteger('delivery_id')->nullable();
            $table->string('checkpoint_type',50); $table->decimal('latitude',12,8)->nullable(); $table->decimal('longitude',12,8)->nullable(); $table->text('remarks')->nullable(); $table->timestamps(); $table->index(['business_id','trip_id','delivery_id']);
        });
        Schema::create('disnew_epods', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger('location_id')->nullable(); $table->unsignedBigInteger('trip_id')->nullable(); $table->unsignedBigInteger('delivery_id')->nullable();
            $table->string('receiver_name')->nullable(); $table->string('receiver_mobile',50)->nullable(); $table->string('signature_path')->nullable(); $table->string('photo_path')->nullable(); $table->text('remarks')->nullable(); $table->timestamps(); $table->index(['business_id','delivery_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('disnew_epods'); Schema::dropIfExists('disnew_delivery_checkpoints'); Schema::dropIfExists('disnew_sync_items'); Schema::dropIfExists('disnew_sync_batches'); Schema::dropIfExists('disnew_offline_devices'); Schema::dropIfExists('disnew_mobile_tokens');
    }
};

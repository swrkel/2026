<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atn_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('business_location_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('key', 100);
            $table->text('value_text')->nullable();
            $table->json('value_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(
                ['business_id', 'business_location_id', 'store_id', 'key'],
                'atn_settings_scope_key_unique'
            );
            $table->index(['business_id', 'is_active'], 'atn_settings_business_active_idx');
        });

        Schema::create('atn_sequences', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('business_location_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('document_type', 50);
            $table->string('prefix', 20)->nullable();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedInteger('padding')->default(6);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(
                ['business_id', 'business_location_id', 'store_id', 'document_type'],
                'atn_sequences_scope_type_unique'
            );
        });

        Schema::create('atn_audit_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('business_location_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event', 80);
            $table->string('auditable_type', 190)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['business_id', 'auditable_type', 'auditable_id'],
                'atn_audit_lookup_idx'
            );
            $table->index(['business_id', 'created_at'], 'atn_audit_business_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atn_audit_logs');
        Schema::dropIfExists('atn_sequences');
        Schema::dropIfExists('atn_settings');
    }
};

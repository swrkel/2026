<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restaurant_new_qr_menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('title');
            $table->string('public_token', 80)->unique();
            $table->boolean('allow_self_order')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'location_id']);
        });

        Schema::create('restaurant_new_customer_order_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('restaurant_order_id');
            $table->string('purpose', 40)->default('status');
            $table->string('public_token', 100)->unique();
            $table->string('status', 30)->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'restaurant_order_id']);
        });

        Schema::create('restaurant_new_digital_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('restaurant_order_id');
            $table->unsignedBigInteger('restaurant_bill_id')->nullable();
            $table->string('receipt_token', 100)->unique();
            $table->string('sent_to')->nullable();
            $table->string('delivery_channel', 40)->nullable();
            $table->string('delivery_status', 40)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'restaurant_order_id']);
        });

        Schema::create('restaurant_new_customer_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('restaurant_order_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_mobile', 50)->nullable();
            $table->unsignedTinyInteger('rating_food')->nullable();
            $table->unsignedTinyInteger('rating_service')->nullable();
            $table->unsignedTinyInteger('rating_overall')->nullable();
            $table->text('comments')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_new_customer_feedback');
        Schema::dropIfExists('restaurant_new_digital_receipts');
        Schema::dropIfExists('restaurant_new_customer_order_links');
        Schema::dropIfExists('restaurant_new_qr_menus');
    }
};

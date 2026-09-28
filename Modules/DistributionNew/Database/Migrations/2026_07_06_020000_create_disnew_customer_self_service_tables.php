<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('disnew_customer_portal_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedInteger('customer_id')->index();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('portal_code', 50)->unique();
            $table->string('mobile', 30)->nullable();
            $table->string('email')->nullable();
            $table->boolean('can_place_order')->default(true);
            $table->boolean('can_view_invoice')->default(true);
            $table->boolean('can_view_statement')->default(true);
            $table->boolean('can_request_return')->default(true);
            $table->boolean('can_raise_complaint')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('disnew_customer_return_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedInteger('customer_id')->index();
            $table->unsignedBigInteger('sales_order_id')->nullable()->index();
            $table->unsignedBigInteger('sales_invoice_id')->nullable()->index();
            $table->string('request_no', 60)->index();
            $table->date('request_date');
            $table->enum('status', ['draft','submitted','approved','rejected','picked','credited','closed'])->default('submitted')->index();
            $table->text('reason')->nullable();
            $table->text('customer_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('disnew_customer_return_request_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('return_request_id')->index();
            $table->unsignedInteger('product_id')->index();
            $table->decimal('qty', 22, 4)->default(0);
            $table->decimal('approved_qty', 22, 4)->default(0);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->text('line_note')->nullable();
            $table->timestamps();
        });

        Schema::create('disnew_customer_complaints', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedInteger('customer_id')->index();
            $table->string('complaint_no', 60)->index();
            $table->enum('category', ['delivery','invoice','product','payment','service','other'])->default('other')->index();
            $table->enum('priority', ['low','normal','high','urgent'])->default('normal')->index();
            $table->enum('status', ['open','assigned','in_progress','resolved','closed','cancelled'])->default('open')->index();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->unsignedInteger('assigned_to')->nullable()->index();
            $table->unsignedInteger('resolved_by')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('disnew_customer_delivery_tracking_views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('customer_id')->index();
            $table->unsignedBigInteger('delivery_id')->nullable()->index();
            $table->unsignedBigInteger('sales_order_id')->nullable()->index();
            $table->unsignedInteger('viewed_by')->nullable()->index();
            $table->timestamp('viewed_at')->nullable();
            $table->string('ip_address', 80)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disnew_customer_delivery_tracking_views');
        Schema::dropIfExists('disnew_customer_complaints');
        Schema::dropIfExists('disnew_customer_return_request_lines');
        Schema::dropIfExists('disnew_customer_return_requests');
        Schema::dropIfExists('disnew_customer_portal_users');
    }
};

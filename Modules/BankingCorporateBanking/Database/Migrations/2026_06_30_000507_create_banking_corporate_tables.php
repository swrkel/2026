<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('banking_corporate_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('corporate_code')->unique();
            $table->string('name');
            $table->string('registration_no')->nullable();
            $table->string('tax_no')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('banking_corporate_signatories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('corporate_customer_id')->index();
            $table->string('name');
            $table->string('designation')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->decimal('approval_limit', 22, 4)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('banking_corporate_bulk_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('corporate_customer_id')->index();
            $table->string('batch_no')->unique();
            $table->string('batch_type')->default('payment');
            $table->unsignedInteger('record_count')->default(0);
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->string('status')->default('uploaded');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('banking_corporate_approval_items', function (Blueprint $table) {
            $table->id();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('corporate_customer_id')->nullable()->index();
            $table->string('approval_level')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('maker_id')->nullable();
            $table->unsignedBigInteger('checker_id')->nullable();
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banking_corporate_approval_items');
        Schema::dropIfExists('banking_corporate_bulk_batches');
        Schema::dropIfExists('banking_corporate_signatories');
        Schema::dropIfExists('banking_corporate_customers');
    }
};

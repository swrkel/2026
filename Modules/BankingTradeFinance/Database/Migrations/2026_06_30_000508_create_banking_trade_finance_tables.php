<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bkg_tf_letters_of_credit', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('lc_no')->unique(); $table->string('customer_name')->nullable(); $table->string('lc_type')->nullable();
            $table->decimal('amount', 22, 4)->default(0); $table->string('currency', 10)->default('LKR'); $table->date('issue_date')->nullable(); $table->date('expiry_date')->nullable();
            $table->string('status')->default('draft'); $table->timestamps();
        });
        Schema::create('bkg_tf_bank_guarantees', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('guarantee_no')->unique(); $table->string('beneficiary_name')->nullable(); $table->decimal('amount',22,4)->default(0); $table->date('expiry_date')->nullable(); $table->string('status')->default('draft'); $table->timestamps();
        });
        Schema::create('bkg_tf_bills', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('bill_no')->unique(); $table->string('bill_type')->index(); $table->string('party_name')->nullable(); $table->decimal('amount',22,4)->default(0); $table->date('due_date')->nullable(); $table->string('status')->default('draft'); $table->timestamps();
        });
        Schema::create('bkg_tf_documents', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->string('document_ref')->nullable(); $table->string('document_type')->nullable(); $table->string('status')->default('pending'); $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('bkg_tf_documents'); Schema::dropIfExists('bkg_tf_bills'); Schema::dropIfExists('bkg_tf_bank_guarantees'); Schema::dropIfExists('bkg_tf_letters_of_credit');
    }
};

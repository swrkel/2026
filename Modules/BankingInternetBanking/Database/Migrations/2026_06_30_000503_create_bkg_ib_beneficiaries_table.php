<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bkg_ib_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('internet_customer_id')->index();
            $table->string('beneficiary_type')->default('internal')->index();
            $table->string('nickname')->nullable();
            $table->string('account_number')->nullable()->index();
            $table->string('bank_code')->nullable();
            $table->string('branch_code')->nullable();
            $table->decimal('daily_limit', 22, 4)->default(0);
            $table->string('status')->default('pending')->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_ib_beneficiaries');
    }
};

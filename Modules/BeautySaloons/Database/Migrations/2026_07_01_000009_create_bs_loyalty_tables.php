<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bs_loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->string('name');
            $table->decimal('minimum_spend', 22, 4)->default(0);
            $table->decimal('earn_rate', 10, 4)->default(0);
            $table->decimal('redeem_rate', 10, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bs_loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->unsignedInteger('customer_id')->index();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->enum('type', ['earn', 'redeem', 'expire', 'adjustment']);
            $table->decimal('points', 22, 4)->default(0);
            $table->decimal('amount_value', 22, 4)->default(0);
            $table->dateTime('transaction_date')->index();
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bs_loyalty_transactions');
        Schema::dropIfExists('bs_loyalty_tiers');
    }
};

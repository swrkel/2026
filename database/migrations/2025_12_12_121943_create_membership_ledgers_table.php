<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('membership_ledgers', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('business_id');

            // Dates
            $table->date('transaction_date')->nullable();
            $table->dateTime('system_entered_at')->nullable();

            // Extra info
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('description')->nullable();

            // Bill information
            $table->string('bill_number')->nullable();
            $table->decimal('bill_amount', 12, 2)->default(0);

            // Points
            $table->integer('points_earned')->default(0);
            $table->integer('points_redeemed')->default(0);
            $table->integer('balance_points')->default(0);

            // All payment methods stored in JSON
            $table->json('payment_methods')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_ledgers');
    }
};

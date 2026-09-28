<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bkg_ib_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('internet_customer_id')->index();
            $table->unsignedBigInteger('beneficiary_id')->nullable()->index();
            $table->string('transfer_no')->unique();
            $table->string('transfer_type')->default('own')->index();
            $table->string('from_account')->nullable();
            $table->string('to_account')->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->decimal('charge_amount', 22, 4)->default(0);
            $table->string('currency', 8)->default('LKR');
            $table->date('scheduled_date')->nullable();
            $table->string('status')->default('draft')->index();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_ib_transfers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bkg_payment_items')) {
            Schema::create('bkg_payment_items', function (Blueprint $table) {
                $table->id();
            $table->string('payment_no')->nullable()->index();
            $table->string('rail')->nullable()->index();
            $table->string('payment_type')->nullable()->index();
            $table->string('source_account')->nullable()->index();
            $table->string('beneficiary_name')->nullable()->index();
            $table->string('beneficiary_account')->nullable()->index();
            $table->string('currency')->nullable()->index();
            $table->decimal('amount', 22, 6)->default(0);
            $table->string('status')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_payment_items');
    }
};

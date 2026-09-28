<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('subscription_invoices')) {
            return;
        }

        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('invoice_no');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('customer_code')->nullable();
            $table->date('from_date');
            $table->date('to_date');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->unsignedBigInteger('banner_id')->nullable();
            $table->text('payment_details')->nullable();
            $table->decimal('total', 15, 2);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
    }
};

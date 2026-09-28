<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bkg_teller_slips', function (Blueprint $table) {
            $table->id();
            $table->string('slip_no')->unique();
            $table->unsignedBigInteger('drawer_id')->index();
            $table->string('transaction_type')->index();
            $table->string('account_no')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->decimal('amount', 22, 4);
            $table->string('currency', 3)->default('LKR');
            $table->string('status')->default('draft')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->json('payload')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_teller_slips');
    }
};

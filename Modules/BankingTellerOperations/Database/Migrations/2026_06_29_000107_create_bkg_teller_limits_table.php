<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bkg_teller_limits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->decimal('single_deposit_limit', 22, 4)->default(0);
            $table->decimal('single_withdrawal_limit', 22, 4)->default(0);
            $table->decimal('daily_cash_limit', 22, 4)->default(0);
            $table->boolean('requires_dual_auth')->default(false);
            $table->json('rules')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_teller_limits');
    }
};

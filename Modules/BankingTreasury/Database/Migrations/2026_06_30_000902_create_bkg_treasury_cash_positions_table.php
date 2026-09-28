<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bkg_treasury_cash_positions')) {
            Schema::create('bkg_treasury_cash_positions', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('vault_id')->nullable()->index();
            $table->date('position_date')->nullable();
            $table->decimal('opening_balance', 22, 6)->default(0);
            $table->decimal('cash_in', 22, 6)->default(0);
            $table->decimal('cash_out', 22, 6)->default(0);
            $table->decimal('closing_balance', 22, 6)->default(0);
            $table->string('status')->nullable()->index();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_treasury_cash_positions');
    }
};

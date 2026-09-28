<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bkg_treasury_deals')) {
            Schema::create('bkg_treasury_deals', function (Blueprint $table) {
                $table->id();
            $table->string('deal_no')->nullable()->index();
            $table->string('deal_type')->nullable()->index();
            $table->string('currency')->nullable()->index();
            $table->string('counterparty')->nullable()->index();
            $table->date('deal_date')->nullable();
            $table->date('maturity_date')->nullable();
            $table->decimal('amount', 22, 6)->default(0);
            $table->decimal('rate', 22, 6)->default(0);
            $table->string('status')->nullable()->index();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_treasury_deals');
    }
};

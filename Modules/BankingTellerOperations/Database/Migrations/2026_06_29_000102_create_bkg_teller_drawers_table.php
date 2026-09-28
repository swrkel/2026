<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bkg_teller_drawers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('counter_id')->index();
            $table->unsignedBigInteger('teller_user_id')->index();
            $table->date('business_date')->index();
            $table->decimal('opening_cash', 22, 4)->default(0);
            $table->decimal('cash_in', 22, 4)->default(0);
            $table->decimal('cash_out', 22, 4)->default(0);
            $table->decimal('system_cash', 22, 4)->default(0);
            $table->decimal('physical_cash', 22, 4)->default(0);
            $table->decimal('cash_difference', 22, 4)->default(0);
            $table->string('status')->default('open')->index();
            $table->json('denominations')->nullable();
            $table->text('difference_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_teller_drawers');
    }
};

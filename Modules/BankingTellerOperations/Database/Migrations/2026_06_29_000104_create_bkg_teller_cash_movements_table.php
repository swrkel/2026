<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bkg_teller_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drawer_id')->index();
            $table->string('movement_type')->index();
            $table->decimal('amount', 22, 4);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('denominations')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable()->index();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_teller_cash_movements');
    }
};

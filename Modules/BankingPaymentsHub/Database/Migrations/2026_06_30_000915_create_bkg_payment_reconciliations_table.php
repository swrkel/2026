<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bkg_payment_reconciliations')) {
            Schema::create('bkg_payment_reconciliations', function (Blueprint $table) {
                $table->id();
            $table->string('reference_no')->nullable()->index();
            $table->string('rail')->nullable()->index();
            $table->date('recon_date')->nullable();
            $table->unsignedBigInteger('matched_count')->nullable()->index();
            $table->unsignedBigInteger('unmatched_count')->nullable()->index();
            $table->decimal('total_amount', 22, 6)->default(0);
            $table->string('status')->nullable()->index();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_payment_reconciliations');
    }
};

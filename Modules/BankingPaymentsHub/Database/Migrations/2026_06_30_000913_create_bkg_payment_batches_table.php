<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bkg_payment_batches')) {
            Schema::create('bkg_payment_batches', function (Blueprint $table) {
                $table->id();
            $table->string('batch_no')->nullable()->index();
            $table->string('batch_type')->nullable()->index();
            $table->unsignedBigInteger('uploaded_by')->nullable()->index();
            $table->unsignedBigInteger('total_count')->nullable()->index();
            $table->decimal('total_amount', 22, 6)->default(0);
            $table->string('status')->nullable()->index();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_payment_batches');
    }
};

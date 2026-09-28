<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bkg_payment_queue')) {
            Schema::create('bkg_payment_queue', function (Blueprint $table) {
                $table->id();
            $table->unsignedBigInteger('payment_id')->nullable()->index();
            $table->string('queue_status')->nullable()->index();
            $table->unsignedBigInteger('attempts')->nullable()->index();
            $table->timestamp('next_retry_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_payment_queue');
    }
};

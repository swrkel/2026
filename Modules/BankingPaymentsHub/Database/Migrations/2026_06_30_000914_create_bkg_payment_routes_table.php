<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bkg_payment_routes')) {
            Schema::create('bkg_payment_routes', function (Blueprint $table) {
                $table->id();
            $table->string('rail')->nullable()->index();
            $table->string('route_code')->nullable()->index();
            $table->unsignedBigInteger('priority')->nullable()->index();
            $table->string('currency')->nullable()->index();
            $table->decimal('min_amount', 22, 6)->default(0);
            $table->decimal('max_amount', 22, 6)->default(0);
            $table->string('status')->nullable()->index();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_payment_routes');
    }
};

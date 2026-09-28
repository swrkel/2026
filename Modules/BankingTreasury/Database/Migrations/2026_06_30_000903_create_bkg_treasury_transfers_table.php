<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bkg_treasury_transfers')) {
            Schema::create('bkg_treasury_transfers', function (Blueprint $table) {
                $table->id();
            $table->string('reference_no')->nullable()->index();
            $table->unsignedBigInteger('from_location_id')->nullable()->index();
            $table->unsignedBigInteger('to_location_id')->nullable()->index();
            $table->string('currency')->nullable()->index();
            $table->decimal('amount', 22, 6)->default(0);
            $table->string('status')->nullable()->index();
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_treasury_transfers');
    }
};

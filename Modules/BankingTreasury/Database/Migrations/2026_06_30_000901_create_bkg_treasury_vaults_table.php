<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bkg_treasury_vaults')) {
            Schema::create('bkg_treasury_vaults', function (Blueprint $table) {
                $table->id();
            $table->string('code')->nullable()->index();
            $table->string('name')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('currency')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_treasury_vaults');
    }
};

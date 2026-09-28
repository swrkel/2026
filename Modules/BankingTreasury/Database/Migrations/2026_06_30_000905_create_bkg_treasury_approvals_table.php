<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bkg_treasury_approvals')) {
            Schema::create('bkg_treasury_approvals', function (Blueprint $table) {
                $table->id();
            $table->string('source_type')->nullable()->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->unsignedBigInteger('level')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->string('remarks')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_treasury_approvals');
    }
};

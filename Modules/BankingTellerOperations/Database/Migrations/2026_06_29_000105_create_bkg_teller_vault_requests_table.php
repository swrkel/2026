<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bkg_teller_vault_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no')->unique();
            $table->unsignedBigInteger('drawer_id')->index();
            $table->string('request_type')->index();
            $table->decimal('amount', 22, 4);
            $table->string('status')->default('pending')->index();
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->json('denominations')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_teller_vault_requests');
    }
};

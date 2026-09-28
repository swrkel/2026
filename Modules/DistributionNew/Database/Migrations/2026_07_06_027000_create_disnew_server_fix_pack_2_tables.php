<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disnew_server_fix_pack_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->string('check_key', 150)->index();
            $table->string('status', 50)->default('pending')->index();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disnew_server_fix_pack_logs');
    }
};

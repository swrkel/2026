<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('disnew_server_checks')) {
            Schema::create('disnew_server_checks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->string('check_code', 150)->index();
                $table->string('check_name', 191);
                $table->string('status', 30)->default('pending')->index();
                $table->text('message')->nullable();
                $table->text('repair_hint')->nullable();
                $table->unsignedBigInteger('checked_by')->nullable()->index();
                $table->timestamp('checked_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('disnew_server_checks');
    }
};

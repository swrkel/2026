<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('disnew_server_testing_runs')) {
            Schema::create('disnew_server_testing_runs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('overall_status', 50)->default('pending')->index();
                $table->longText('payload')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('disnew_server_testing_runs');
    }
};

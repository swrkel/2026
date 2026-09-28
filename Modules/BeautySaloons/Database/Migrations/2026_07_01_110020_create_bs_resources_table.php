<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('bs_resources')) {
            Schema::create('bs_resources', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('beauty_branch_id')->nullable()->index();
                $table->string('resource_code')->nullable()->index();
                $table->string('resource_name');
                $table->string('resource_type')->nullable();
                $table->integer('capacity')->default(1);
                $table->string('status')->default('active')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('bs_resources'); }
};

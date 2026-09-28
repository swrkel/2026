<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('pcn_number_sequences')) return;
        Schema::create('pcn_number_sequences', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->unique();
            $table->string('prefix', 20)->default('PCN');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(6);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('pcn_number_sequences'); }
};

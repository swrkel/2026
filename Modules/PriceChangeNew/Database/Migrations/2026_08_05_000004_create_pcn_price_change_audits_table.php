<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('pcn_price_change_audits')) return;
        Schema::create('pcn_price_change_audits', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('price_change_id')->index();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('action', 80)->index();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->longText('payload')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->index(['business_id','price_change_id','created_at'], 'pcn_audits_business_change_created_idx');
        });
    }
    public function down(): void { Schema::dropIfExists('pcn_price_change_audits'); }
};

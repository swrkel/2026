<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('pcn_price_change_scopes')) return;
        Schema::create('pcn_price_change_scopes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('price_change_id')->index();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->index();
            $table->string('location_name', 191);
            $table->unsignedInteger('store_id')->nullable()->index();
            $table->timestamps();
            $table->unique(['price_change_id','location_id'], 'pcn_scopes_change_location_unique');
            $table->index(['business_id','location_id'], 'pcn_scopes_business_location_idx');
        });
    }
    public function down(): void { Schema::dropIfExists('pcn_price_change_scopes'); }
};

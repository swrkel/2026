<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('pcn_price_change_settings')) return;
        Schema::create('pcn_price_change_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->default(0)->index();
            $table->string('setting_key', 120);
            $table->longText('setting_value')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['business_id','location_id','setting_key'], 'pcn_settings_business_location_key_unique');
        });
    }
    public function down(): void { Schema::dropIfExists('pcn_price_change_settings'); }
};

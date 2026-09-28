<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('myhealth_settings')) {
            Schema::create('myhealth_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->string('setting_group', 100)->index();
                $table->string('setting_key', 150)->index();
                $table->text('setting_value')->nullable();
                $table->string('value_type', 50)->default('text');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['business_id', 'location_id', 'setting_group', 'setting_key'], 'mh_settings_unique_key');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('myhealth_settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('hm_module_permissions')) {
            Schema::create('hm_module_permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('module', 100)->default('HotelManagement');
                $table->string('permission_key', 150)->unique();
                $table->string('label', 150)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hm_menu_registry')) {
            Schema::create('hm_menu_registry', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('module', 100)->default('HotelManagement');
                $table->string('menu_key', 120);
                $table->string('label', 150);
                $table->string('route_name', 150)->nullable();
                $table->string('permission_key', 150)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['business_id','business_location_id','menu_key'], 'hm_menu_registry_scope_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_menu_registry');
        Schema::dropIfExists('hm_module_permissions');
    }
};

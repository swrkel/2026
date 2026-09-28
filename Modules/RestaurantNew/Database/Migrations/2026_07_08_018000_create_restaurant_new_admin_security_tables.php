<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewAdminSecurityTables extends Migration
{
    public function up()
    {
        Schema::create('restaurant_new_feature_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('feature_key', 100)->index();
            $table->boolean('is_enabled')->default(true)->index();
            $table->json('settings')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['business_id', 'location_id', 'feature_key'], 'restnew_feature_scope_unique');
        });

        Schema::create('restaurant_new_user_access_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('access_area', 80)->index();
            $table->json('allowed_actions')->nullable();
            $table->boolean('is_allowed')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['business_id', 'location_id', 'user_id', 'access_area'], 'restnew_user_access_unique');
        });

        Schema::create('restaurant_new_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('module_area', 80)->index();
            $table->string('action', 80)->index();
            $table->string('entity_type', 120)->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_direct_url_blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('route_name', 160)->nullable()->index();
            $table->string('url_path', 255)->nullable()->index();
            $table->string('required_permission', 160)->nullable();
            $table->string('block_reason', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_new_direct_url_blocks');
        Schema::dropIfExists('restaurant_new_audit_logs');
        Schema::dropIfExists('restaurant_new_user_access_rules');
        Schema::dropIfExists('restaurant_new_feature_settings');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewHardeningTables extends Migration
{
    public function up()
    {
        Schema::create('restaurant_new_integrity_checks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('check_code', 120)->index();
            $table->string('check_group', 80)->index();
            $table->enum('status', ['passed', 'warning', 'failed'])->default('passed')->index();
            $table->text('message')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('checked_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_tenant_scope_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('route_name', 160)->nullable()->index();
            $table->string('model_name', 180)->nullable()->index();
            $table->string('operation', 80)->nullable()->index();
            $table->enum('status', ['allowed', 'blocked'])->default('allowed')->index();
            $table->string('reason', 255)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::table('restaurant_new_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurant_new_orders', 'is_hardened')) {
                $table->boolean('is_hardened')->default(true)->after('order_status')->index();
            }
        });
    }

    public function down()
    {
        if (Schema::hasTable('restaurant_new_orders') && Schema::hasColumn('restaurant_new_orders', 'is_hardened')) {
            Schema::table('restaurant_new_orders', function (Blueprint $table) {
                $table->dropColumn('is_hardened');
            });
        }
        Schema::dropIfExists('restaurant_new_tenant_scope_logs');
        Schema::dropIfExists('restaurant_new_integrity_checks');
    }
}

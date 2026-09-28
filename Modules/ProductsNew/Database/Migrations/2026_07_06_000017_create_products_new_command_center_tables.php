<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsNewCommandCenterTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('products_new_command_center_preferences')) {
            Schema::create('products_new_command_center_preferences', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('user_id')->index();
                $table->json('visible_widgets')->nullable();
                $table->json('quick_actions')->nullable();
                $table->json('saved_searches')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('products_new_command_center_snapshots')) {
            Schema::create('products_new_command_center_snapshots', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('product_id')->index();
                $table->json('summary_payload')->nullable();
                $table->json('inventory_payload')->nullable();
                $table->json('finance_payload')->nullable();
                $table->json('sales_payload')->nullable();
                $table->json('purchase_payload')->nullable();
                $table->json('alerts_payload')->nullable();
                $table->json('integration_payload')->nullable();
                $table->unsignedInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }
    }
    public function down()
    {
        Schema::dropIfExists('products_new_command_center_snapshots');
        Schema::dropIfExists('products_new_command_center_preferences');
    }
}

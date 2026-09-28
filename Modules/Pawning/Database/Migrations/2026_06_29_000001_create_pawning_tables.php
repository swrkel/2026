<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePawningTables extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('pawning_collateral_types')) {
            Schema::create('pawning_collateral_types', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->string('name');
                $table->string('code')->nullable()->index();
                $table->text('description')->nullable();
                $table->boolean('requires_weight')->default(false);
                $table->boolean('requires_purity')->default(false);
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pawning_products')) {
            Schema::create('pawning_products', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('collateral_type_id')->nullable()->index();
                $table->string('name');
                $table->string('code')->nullable()->index();
                $table->decimal('interest_rate', 10, 4)->default(0);
                $table->decimal('penalty_rate', 10, 4)->default(0);
                $table->integer('term_days')->default(30);
                $table->decimal('advance_percentage', 10, 4)->default(0);
                $table->decimal('minimum_amount', 22, 4)->default(0);
                $table->decimal('maximum_amount', 22, 4)->default(0);
                $table->string('status')->default('active')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pawning_vault_locations')) {
            Schema::create('pawning_vault_locations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->string('vault_name');
                $table->string('shelf_no')->nullable();
                $table->string('box_no')->nullable();
                $table->string('tray_no')->nullable();
                $table->string('status')->default('active')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pawning_articles')) {
            Schema::create('pawning_articles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('collateral_type_id')->nullable()->index();
                $table->unsignedBigInteger('vault_location_id')->nullable()->index();
                $table->string('article_no')->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('gross_weight', 22, 4)->default(0);
                $table->decimal('net_weight', 22, 4)->default(0);
                $table->decimal('purity', 10, 4)->default(0);
                $table->decimal('estimated_value', 22, 4)->default(0);
                $table->string('qr_code')->nullable();
                $table->string('barcode')->nullable();
                $table->string('status')->default('available')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pawning_pledges')) {
            Schema::create('pawning_pledges', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('pawning_product_id')->nullable()->index();
                $table->unsignedBigInteger('banking_customer_id')->nullable()->index();
                $table->string('pledge_no')->index();
                $table->string('customer_name')->nullable();
                $table->string('customer_mobile')->nullable();
                $table->date('pledged_on')->nullable();
                $table->date('due_on')->nullable();
                $table->decimal('assessed_value', 22, 4)->default(0);
                $table->decimal('advance_amount', 22, 4)->default(0);
                $table->decimal('interest_rate', 10, 4)->default(0);
                $table->decimal('interest_amount', 22, 4)->default(0);
                $table->decimal('penalty_amount', 22, 4)->default(0);
                $table->decimal('outstanding_amount', 22, 4)->default(0);
                $table->string('workflow_status')->default('draft')->index();
                $table->string('status')->default('active')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pawning_pledge_articles')) {
            Schema::create('pawning_pledge_articles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('pawning_pledge_id')->index();
                $table->unsignedBigInteger('pawning_article_id')->index();
                $table->decimal('article_value', 22, 4)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pawning_transactions')) {
            Schema::create('pawning_transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('pawning_pledge_id')->index();
                $table->string('transaction_no')->nullable()->index();
                $table->string('type')->index();
                $table->date('transaction_date')->nullable();
                $table->decimal('amount', 22, 4)->default(0);
                $table->decimal('balance_after', 22, 4)->default(0);
                $table->string('reference_no')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pawning_settings')) {
            Schema::create('pawning_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->string('key')->index();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('pawning_settings');
        Schema::dropIfExists('pawning_transactions');
        Schema::dropIfExists('pawning_pledge_articles');
        Schema::dropIfExists('pawning_pledges');
        Schema::dropIfExists('pawning_articles');
        Schema::dropIfExists('pawning_vault_locations');
        Schema::dropIfExists('pawning_products');
        Schema::dropIfExists('pawning_collateral_types');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeasingTables extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('leasing_lease_asset_types')) {
            Schema::create('leasing_lease_asset_types', function (Blueprint $table) {
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

        if (! Schema::hasTable('leasing_products')) {
            Schema::create('leasing_products', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('lease_asset_type_id')->nullable()->index();
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

        if (! Schema::hasTable('leasing_asset_locations')) {
            Schema::create('leasing_asset_locations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->string('asset_name');
                $table->string('shelf_no')->nullable();
                $table->string('box_no')->nullable();
                $table->string('tray_no')->nullable();
                $table->string('status')->default('active')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('leasing_lease_assets')) {
            Schema::create('leasing_lease_assets', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('lease_asset_type_id')->nullable()->index();
                $table->unsignedBigInteger('asset_location_id')->nullable()->index();
                $table->string('lease_asset_no')->nullable()->index();
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

        if (! Schema::hasTable('leasing_lease_contracts')) {
            Schema::create('leasing_lease_contracts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('leasing_product_id')->nullable()->index();
                $table->unsignedBigInteger('banking_customer_id')->nullable()->index();
                $table->string('lease_contract_no')->index();
                $table->string('customer_name')->nullable();
                $table->string('customer_mobile')->nullable();
                $table->date('lease_contractd_on')->nullable();
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

        if (! Schema::hasTable('leasing_lease_contract_lease_assets')) {
            Schema::create('leasing_lease_contract_lease_assets', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('leasing_lease_contract_id')->index();
                $table->unsignedBigInteger('leasing_lease_asset_id')->index();
                $table->decimal('lease_asset_value', 22, 4)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('leasing_transactions')) {
            Schema::create('leasing_transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('leasing_lease_contract_id')->index();
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

        if (! Schema::hasTable('leasing_settings')) {
            Schema::create('leasing_settings', function (Blueprint $table) {
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
        Schema::dropIfExists('leasing_settings');
        Schema::dropIfExists('leasing_transactions');
        Schema::dropIfExists('leasing_lease_contract_lease_assets');
        Schema::dropIfExists('leasing_lease_contracts');
        Schema::dropIfExists('leasing_lease_assets');
        Schema::dropIfExists('leasing_asset_locations');
        Schema::dropIfExists('leasing_products');
        Schema::dropIfExists('leasing_lease_asset_types');
    }
}

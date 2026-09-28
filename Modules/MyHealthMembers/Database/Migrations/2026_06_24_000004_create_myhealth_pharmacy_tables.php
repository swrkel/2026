<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyhealthPharmacyTables extends Migration
{
    public function up(): void
    {
        $connection = config('myhealthmembers.central_connection');

        Schema::connection($connection)->table('myhealth_business_permissions', function (Blueprint $table) {
            if (!Schema::connection(config('myhealthmembers.central_connection'))->hasColumn('myhealth_business_permissions', 'can_access_pharmacy')) {
                $table->boolean('can_access_pharmacy')->default(false)->after('can_create_prescription');
            }
            if (!Schema::connection(config('myhealthmembers.central_connection'))->hasColumn('myhealth_business_permissions', 'can_dispense_medicine')) {
                $table->boolean('can_dispense_medicine')->default(false)->after('can_access_pharmacy');
            }
            if (!Schema::connection(config('myhealthmembers.central_connection'))->hasColumn('myhealth_business_permissions', 'can_manage_pharmacy_stock')) {
                $table->boolean('can_manage_pharmacy_stock')->default(false)->after('can_dispense_medicine');
            }
        });

        Schema::connection($connection)->create('myhealth_pharmacies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->string('pharmacy_code')->nullable()->index();
            $table->string('name');
            $table->string('license_no')->nullable();
            $table->string('contact_no')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::connection($connection)->create('myhealth_medicines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pharmacy_id')->nullable()->index();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('medicine_code')->unique();
            $table->string('medicine_name')->index();
            $table->string('generic_name')->nullable()->index();
            $table->string('brand')->nullable();
            $table->string('category')->nullable();
            $table->string('dosage_form')->nullable();
            $table->string('strength')->nullable();
            $table->string('manufacturer')->nullable();
            $table->decimal('reorder_level', 18, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::connection($connection)->create('myhealth_medicine_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pharmacy_id')->nullable()->index();
            $table->unsignedBigInteger('medicine_id')->index();
            $table->string('batch_no')->index();
            $table->date('expiry_date')->nullable()->index();
            $table->decimal('purchase_cost', 18, 4)->default(0);
            $table->decimal('selling_price', 18, 4)->default(0);
            $table->decimal('opening_qty', 18, 4)->default(0);
            $table->decimal('available_qty', 18, 4)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['medicine_id', 'batch_no']);
        });

        Schema::connection($connection)->create('myhealth_pharmacy_stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pharmacy_id')->nullable()->index();
            $table->unsignedBigInteger('medicine_id')->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->date('transaction_date')->index();
            $table->string('transaction_type')->index();
            $table->string('reference_no')->nullable()->index();
            $table->decimal('qty_in', 18, 4)->default(0);
            $table->decimal('qty_out', 18, 4)->default(0);
            $table->decimal('balance_qty', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::connection($connection)->create('myhealth_dispenses', function (Blueprint $table) {
            $table->id();
            $table->string('dispense_no')->unique();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('pharmacy_id')->nullable()->index();
            $table->unsignedBigInteger('prescription_id')->nullable()->index();
            $table->unsignedBigInteger('pharmacist_user_id')->nullable()->index();
            $table->date('dispense_date')->index();
            $table->string('status')->default('pending');
            $table->decimal('total_amount', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::connection($connection)->create('myhealth_dispense_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dispense_id')->index();
            $table->unsignedBigInteger('medicine_id')->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->string('medicine_name');
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('line_total', 18, 4)->default(0);
            $table->text('instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $connection = config('myhealthmembers.central_connection');

        Schema::connection($connection)->dropIfExists('myhealth_dispense_items');
        Schema::connection($connection)->dropIfExists('myhealth_dispenses');
        Schema::connection($connection)->dropIfExists('myhealth_pharmacy_stock_ledger');
        Schema::connection($connection)->dropIfExists('myhealth_medicine_batches');
        Schema::connection($connection)->dropIfExists('myhealth_medicines');
        Schema::connection($connection)->dropIfExists('myhealth_pharmacies');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyhealthInsuranceTables extends Migration
{
    public function up(): void
    {
        $connection = config('myhealthmembers.central_connection');

        Schema::connection($connection)->table('myhealth_business_permissions', function (Blueprint $table) use ($connection) {
            if (!Schema::connection($connection)->hasColumn('myhealth_business_permissions', 'can_access_insurance')) {
                $table->boolean('can_access_insurance')->default(false)->after('can_manage_pharmacy_stock');
            }
            if (!Schema::connection($connection)->hasColumn('myhealth_business_permissions', 'can_manage_claims')) {
                $table->boolean('can_manage_claims')->default(false)->after('can_access_insurance');
            }
        });

        Schema::connection($connection)->create('myhealth_insurance_companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('company_code')->unique();
            $table->string('company_name')->index();
            $table->string('contact_no')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::connection($connection)->create('myhealth_insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_no')->unique();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('insurance_company_id')->index();
            $table->string('policy_type')->nullable()->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable()->index();
            $table->decimal('coverage_amount', 18, 4)->default(0);
            $table->decimal('available_balance', 18, 4)->default(0);
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::connection($connection)->create('myhealth_insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_no')->unique();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('policy_id')->index();
            $table->date('claim_date')->index();
            $table->decimal('claim_amount', 18, 4)->default(0);
            $table->decimal('approved_amount', 18, 4)->default(0);
            $table->decimal('settled_amount', 18, 4)->default(0);
            $table->string('status')->default('submitted')->index();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::connection($connection)->create('myhealth_insurance_claim_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('claim_id')->index();
            $table->string('item_type')->nullable();
            $table->string('description');
            $table->date('service_date')->nullable();
            $table->decimal('amount', 18, 4)->default(0);
            $table->decimal('approved_amount', 18, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $connection = config('myhealthmembers.central_connection');
        Schema::connection($connection)->dropIfExists('myhealth_insurance_claim_items');
        Schema::connection($connection)->dropIfExists('myhealth_insurance_claims');
        Schema::connection($connection)->dropIfExists('myhealth_insurance_policies');
        Schema::connection($connection)->dropIfExists('myhealth_insurance_companies');
    }
}

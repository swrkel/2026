<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected function connectionName(): ?string
    {
        return config('myhealthmembers.central_connection');
    }

    public function up(): void
    {
        Schema::connection($this->connectionName())->table('myhealth_business_permissions', function (Blueprint $table) {
            if (!Schema::connection($this->connectionName())->hasColumn('myhealth_business_permissions', 'can_access_billing')) {
                $table->boolean('can_access_billing')->default(false)->after('can_manage_telemedicine');
            }
            if (!Schema::connection($this->connectionName())->hasColumn('myhealth_business_permissions', 'can_manage_billing')) {
                $table->boolean('can_manage_billing')->default(false)->after('can_access_billing');
            }
        });

        Schema::connection($this->connectionName())->create('myhealth_billing_services', function (Blueprint $table) {
            $table->id();
            $table->string('service_code')->unique();
            $table->string('service_name')->index();
            $table->string('service_type')->index();
            $table->decimal('default_amount', 22, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connectionName())->create('myhealth_billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('consultation_id')->nullable()->index();
            $table->unsignedBigInteger('appointment_id')->nullable()->index();
            $table->unsignedBigInteger('dispense_id')->nullable()->index();
            $table->unsignedBigInteger('lab_request_id')->nullable()->index();
            $table->date('invoice_date')->index();
            $table->decimal('gross_amount', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('insurance_amount', 22, 4)->default(0);
            $table->decimal('paid_amount', 22, 4)->default(0);
            $table->decimal('balance_amount', 22, 4)->default(0);
            $table->string('status')->default('unpaid')->index();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['member_id', 'invoice_date'], 'mh_bill_inv_member_date_idx');
        });

        Schema::connection($this->connectionName())->create('myhealth_billing_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->index();
            $table->unsignedBigInteger('service_id')->nullable()->index();
            $table->string('item_type')->index();
            $table->string('description');
            $table->decimal('qty', 22, 4)->default(1);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->timestamps();
        });

        Schema::connection($this->connectionName())->create('myhealth_billing_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no')->unique();
            $table->unsignedBigInteger('invoice_id')->index();
            $table->date('payment_date')->index();
            $table->string('payment_method')->default('cash');
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('reference_no')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connectionName())->create('myhealth_claim_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_no')->unique();
            $table->unsignedBigInteger('claim_id')->index();
            $table->date('settlement_date')->index();
            $table->decimal('approved_amount', 22, 4)->default(0);
            $table->decimal('settled_amount', 22, 4)->default(0);
            $table->string('status')->default('approved')->index();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connectionName())->dropIfExists('myhealth_claim_settlements');
        Schema::connection($this->connectionName())->dropIfExists('myhealth_billing_payments');
        Schema::connection($this->connectionName())->dropIfExists('myhealth_billing_invoice_items');
        Schema::connection($this->connectionName())->dropIfExists('myhealth_billing_invoices');
        Schema::connection($this->connectionName())->dropIfExists('myhealth_billing_services');
    }
};

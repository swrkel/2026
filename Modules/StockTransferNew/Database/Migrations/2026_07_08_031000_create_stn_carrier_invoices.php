<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stn_carrier_invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('transfer_id')->nullable()->index();
            $table->unsignedBigInteger('vehicle_id')->nullable()->index();
            $table->string('driver_name')->nullable();
            $table->string('carrier_name')->nullable()->index();
            $table->string('invoice_no')->index();
            $table->date('invoice_date')->nullable()->index();
            $table->decimal('freight_amount', 22, 4)->default(0);
            $table->decimal('loading_charge', 22, 4)->default(0);
            $table->decimal('unloading_charge', 22, 4)->default(0);
            $table->decimal('other_charge', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->string('status')->default('draft')->index();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'invoice_no'], 'stn_carrier_inv_unique');
        });

        Schema::create('stn_carrier_invoice_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('carrier_invoice_id')->index();
            $table->string('charge_type')->default('other')->index();
            $table->string('description')->nullable();
            $table->decimal('qty', 22, 4)->default(1);
            $table->decimal('rate', 22, 4)->default(0);
            $table->decimal('amount', 22, 4)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stn_carrier_invoice_lines');
        Schema::dropIfExists('stn_carrier_invoices');
    }
};

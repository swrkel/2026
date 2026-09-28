<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('auto_service_service_packages')) {
            Schema::create('auto_service_service_packages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->string('package_code')->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('labour_amount', 22, 4)->default(0);
                $table->decimal('parts_amount', 22, 4)->default(0);
                $table->decimal('total_amount', 22, 4)->default(0);
                $table->tinyInteger('is_active')->default(1)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('auto_service_labour_items')) {
            Schema::create('auto_service_labour_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->string('labour_code')->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('standard_hours', 12, 4)->default(0);
                $table->decimal('rate', 22, 4)->default(0);
                $table->tinyInteger('is_active')->default(1)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('auto_service_invoices')) {
            Schema::create('auto_service_invoices', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('contact_id')->nullable()->index();
                $table->unsignedBigInteger('vehicle_id')->nullable()->index();
                $table->unsignedBigInteger('job_id')->nullable()->index();
                $table->string('invoice_no')->unique();
                $table->date('invoice_date')->nullable()->index();
                $table->string('status')->default('draft')->index();
                $table->decimal('subtotal', 22, 4)->default(0);
                $table->decimal('discount_amount', 22, 4)->default(0);
                $table->decimal('tax_amount', 22, 4)->default(0);
                $table->decimal('total_amount', 22, 4)->default(0);
                $table->decimal('paid_amount', 22, 4)->default(0);
                $table->decimal('balance_amount', 22, 4)->default(0);
                $table->text('terms')->nullable();
                $table->text('internal_note')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('auto_service_invoice_lines')) {
            Schema::create('auto_service_invoice_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('invoice_id')->index();
                $table->string('line_type')->default('service')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('description');
                $table->decimal('quantity', 22, 4)->default(1);
                $table->decimal('unit_price', 22, 4)->default(0);
                $table->decimal('line_total', 22, 4)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('auto_service_invoice_lines');
        Schema::dropIfExists('auto_service_invoices');
        Schema::dropIfExists('auto_service_labour_items');
        Schema::dropIfExists('auto_service_service_packages');
    }
};

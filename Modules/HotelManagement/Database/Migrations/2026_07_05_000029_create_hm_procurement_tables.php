<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('hm_procurement_suppliers')) {
            Schema::create('hm_procurement_suppliers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('supplier_code', 80)->nullable()->index();
                $table->string('supplier_name', 160);
                $table->string('contact_person', 120)->nullable();
                $table->string('mobile', 40)->nullable();
                $table->string('email', 160)->nullable();
                $table->text('address')->nullable();
                $table->string('category', 80)->nullable();
                $table->integer('credit_days')->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id', 'supplier_name'], 'hm_proc_sup_biz_name_unique');
            });
        }

        if (!Schema::hasTable('hm_purchase_requests')) {
            Schema::create('hm_purchase_requests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('request_no', 80)->index();
                $table->date('request_date')->nullable()->index();
                $table->string('department', 100)->index();
                $table->string('requested_by', 120)->nullable();
                $table->date('required_date')->nullable();
                $table->string('priority', 30)->default('normal')->index();
                $table->string('item_name', 160);
                $table->text('description')->nullable();
                $table->decimal('qty', 20, 3)->default(0);
                $table->decimal('estimated_unit_cost', 20, 4)->default(0);
                $table->decimal('estimated_total', 20, 4)->default(0);
                $table->string('status', 30)->default('submitted')->index();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id', 'request_no'], 'hm_pr_biz_no_unique');
            });
        }

        if (!Schema::hasTable('hm_purchase_orders')) {
            Schema::create('hm_purchase_orders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('po_no', 80)->index();
                $table->date('po_date')->nullable()->index();
                $table->unsignedBigInteger('supplier_id')->index();
                $table->unsignedBigInteger('request_id')->nullable()->index();
                $table->string('item_name', 160);
                $table->text('description')->nullable();
                $table->decimal('qty', 20, 3)->default(0);
                $table->decimal('unit_cost', 20, 4)->default(0);
                $table->decimal('gross_amount', 20, 4)->default(0);
                $table->decimal('discount_amount', 20, 4)->default(0);
                $table->decimal('tax_amount', 20, 4)->default(0);
                $table->decimal('net_amount', 20, 4)->default(0);
                $table->decimal('received_qty', 20, 3)->default(0);
                $table->date('expected_delivery_date')->nullable();
                $table->string('status', 30)->default('ordered')->index();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id', 'po_no'], 'hm_po_biz_no_unique');
            });
        }

        if (!Schema::hasTable('hm_goods_received_notes')) {
            Schema::create('hm_goods_received_notes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('grn_no', 80)->index();
                $table->unsignedBigInteger('po_id')->index();
                $table->date('received_date')->nullable()->index();
                $table->decimal('received_qty', 20, 3)->default(0);
                $table->decimal('accepted_qty', 20, 3)->default(0);
                $table->decimal('rejected_qty', 20, 3)->default(0);
                $table->decimal('accepted_value', 20, 4)->default(0);
                $table->string('received_by', 120)->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id', 'grn_no'], 'hm_grn_biz_no_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_goods_received_notes');
        Schema::dropIfExists('hm_purchase_orders');
        Schema::dropIfExists('hm_purchase_requests');
        Schema::dropIfExists('hm_procurement_suppliers');
    }
};

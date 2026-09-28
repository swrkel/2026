<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('rcm_packaging_materials')) {
            Schema::create('rcm_packaging_materials', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('business_id')->index();
                $t->string('code',40)->nullable();
                $t->string('name',150);
                $t->string('unit',30)->default('pcs');
                $t->decimal('current_qty',20,4)->default(0);
                $t->boolean('active')->default(true)->index();
                $t->text('note')->nullable();
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamps();
                $t->unique(['business_id','name'],'rcm_pack_material_business_name_unique');
                $t->index(['business_id','code'],'rcm_pack_material_business_code_idx');
            });
        }

        if (!Schema::hasTable('rcm_packaging_material_mappings')) {
            Schema::create('rcm_packaging_material_mappings', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('business_id')->index();
                $t->unsignedBigInteger('product_id')->index();
                $t->decimal('bag_size_kg',12,3);
                $t->unsignedBigInteger('material_id')->index();
                $t->decimal('usage_per_bag',20,4);
                $t->boolean('active')->default(true)->index();
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamps();
                $t->unique(['business_id','product_id','bag_size_kg','material_id'],'rcm_pack_material_mapping_unique');
                $t->index(['business_id','product_id','bag_size_kg','active'],'rcm_pack_material_mapping_lookup_idx');
            });
        }

        if (!Schema::hasTable('rcm_packaging_material_movements')) {
            Schema::create('rcm_packaging_material_movements', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('business_id')->index();
                $t->unsignedBigInteger('material_id')->index();
                $t->unsignedBigInteger('location_id')->nullable()->index();
                $t->unsignedBigInteger('store_id')->nullable()->index();
                $t->date('movement_date')->index();
                $t->string('movement_type',40)->index();
                $t->decimal('quantity',20,4);
                $t->decimal('signed_quantity',20,4);
                $t->unsignedBigInteger('packing_batch_id')->nullable()->index();
                $t->string('reference_type',60)->nullable();
                $t->unsignedBigInteger('reference_id')->nullable();
                $t->text('note')->nullable();
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamps();
                $t->index(['business_id','material_id','movement_date'],'rcm_pack_material_move_ledger_idx');
                $t->index(['reference_type','reference_id'],'rcm_pack_material_move_ref_idx');
            });
        }

        if (!Schema::hasTable('rcm_packing_sources')) {
            Schema::create('rcm_packing_sources', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('business_id')->index();
                $t->unsignedBigInteger('packing_line_id')->index();
                $t->unsignedBigInteger('production_batch_id')->nullable()->index();
                $t->decimal('quantity',20,3);
                $t->timestamps();
                $t->index(['business_id','production_batch_id'],'rcm_packing_source_batch_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rcm_packing_sources');
        Schema::dropIfExists('rcm_packaging_material_movements');
        Schema::dropIfExists('rcm_packaging_material_mappings');
        Schema::dropIfExists('rcm_packaging_materials');
    }
};

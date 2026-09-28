<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('products_new_inventory_movements')) {
            Schema::create('products_new_inventory_movements', function(Blueprint $table): void {
                $table->bigIncrements('id'); $table->unsignedInteger('business_id'); $table->unsignedInteger('product_id'); $table->unsignedInteger('variation_id')->nullable(); $table->unsignedInteger('location_id')->nullable(); $table->unsignedBigInteger('store_id')->nullable(); $table->string('movement_type',50); $table->dateTime('movement_date'); $table->decimal('qty',22,3)->default(0); $table->decimal('unit_cost',22,4)->default(0); $table->decimal('total_cost',22,4)->default(0); $table->string('reference_no',100)->nullable(); $table->text('notes')->nullable(); $table->string('source_table',100)->nullable(); $table->unsignedBigInteger('source_id')->nullable(); $table->unsignedInteger('created_by')->nullable(); $table->timestamps();
            });
        } else {
            foreach (['store_id','source_table','source_id'] as $column) {
                if (!Schema::hasColumn('products_new_inventory_movements',$column)) {
                    Schema::table('products_new_inventory_movements', function(Blueprint $table) use($column): void {
                        if ($column === 'store_id' || $column === 'source_id') $table->unsignedBigInteger($column)->nullable(); else $table->string($column,100)->nullable();
                    });
                }
            }
        }
        $this->ensureIndex('pn_im_business_idx',['business_id']); $this->ensureIndex('pn_im_product_idx',['product_id']); $this->ensureIndex('pn_im_variation_location_idx',['variation_id','location_id']); $this->ensureIndex('pn_im_type_date_idx',['movement_type','movement_date']); $this->ensureIndex('pn_im_store_idx',['business_id','store_id']); $this->ensureIndex('pn_im_source_idx',['source_table','source_id']); $this->ensureIndex('pn_im_history_scope_idx',['business_id','product_id','location_id','movement_date']);
    }
    public function down(): void {}
    private function ensureIndex(string $index,array $columns): void {
        foreach($columns as $column) if(!Schema::hasColumn('products_new_inventory_movements',$column)) return;
        $exists=DB::table('information_schema.statistics')->whereRaw('table_schema = DATABASE()')->where('table_name','products_new_inventory_movements')->where('index_name',$index)->exists();
        if(!$exists) Schema::table('products_new_inventory_movements',function(Blueprint $table) use($columns,$index): void {$table->index($columns,$index);});
    }
};

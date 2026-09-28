<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateProductsNewFoundationTables extends Migration
{
 public function up(): void
 {
  if(!Schema::hasTable('products_new_product_meta')){Schema::create('products_new_product_meta',function(Blueprint $table){$table->bigIncrements('id');$table->unsignedInteger('business_id')->nullable()->index();$table->unsignedInteger('product_id')->unique();$table->string('primary_image')->nullable();$table->json('gallery')->nullable();$table->json('attachments')->nullable();$table->unsignedTinyInteger('health_score')->default(0)->index();$table->json('health_payload')->nullable();$table->json('settings')->nullable();$table->unsignedInteger('created_by')->nullable();$table->unsignedInteger('updated_by')->nullable();$table->timestamps();});}
  if(!Schema::hasTable('products_new_timeline')){Schema::create('products_new_timeline',function(Blueprint $table){$table->bigIncrements('id');$table->unsignedInteger('business_id')->nullable()->index();$table->unsignedInteger('product_id')->index();$table->string('event',80)->index();$table->json('payload')->nullable();$table->unsignedInteger('created_by')->nullable();$table->timestamps();});}
  if(!Schema::hasTable('products_new_price_history')){Schema::create('products_new_price_history',function(Blueprint $table){$table->bigIncrements('id');$table->unsignedInteger('business_id')->nullable()->index();$table->unsignedInteger('product_id')->index();$table->unsignedInteger('variation_id')->nullable();$table->string('price_type',80)->default('selling');$table->decimal('old_price',22,4)->nullable();$table->decimal('new_price',22,4)->nullable();$table->string('currency',10)->nullable();$table->date('effective_from')->nullable()->index();$table->unsignedInteger('created_by')->nullable();$table->timestamps();});}
  if(!Schema::hasTable('products_new_barcode_queue')){Schema::create('products_new_barcode_queue',function(Blueprint $table){$table->bigIncrements('id');$table->unsignedInteger('business_id')->nullable()->index();$table->unsignedInteger('product_id')->index();$table->unsignedInteger('variation_id')->nullable();$table->string('template_code',80)->nullable();$table->decimal('qty',22,3)->default(1);$table->string('status',30)->default('pending')->index();$table->unsignedInteger('created_by')->nullable();$table->timestamp('printed_at')->nullable();$table->timestamps();});}
 }
 public function down(): void { Schema::dropIfExists('products_new_barcode_queue'); Schema::dropIfExists('products_new_price_history'); Schema::dropIfExists('products_new_timeline'); Schema::dropIfExists('products_new_product_meta'); }
}

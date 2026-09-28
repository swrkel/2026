<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hm_minibar_items')) {
            Schema::create('hm_minibar_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('item_code', 80);
                $table->string('item_name', 150);
                $table->string('category', 80)->nullable()->index();
                $table->string('unit', 30)->nullable();
                $table->decimal('selling_price', 22, 4)->default(0);
                $table->decimal('cost_price', 22, 4)->default(0);
                $table->decimal('current_stock', 22, 4)->default(0);
                $table->decimal('reorder_level', 22, 4)->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id','item_code'], 'hm_minibar_items_business_code_unique');
                $table->index(['business_id','business_location_id'], 'hm_minibar_items_business_location_index');
            });
        }

        if (!Schema::hasTable('hm_minibar_consumptions')) {
            Schema::create('hm_minibar_consumptions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('consumption_no', 80);
                $table->unsignedBigInteger('room_id')->nullable()->index();
                $table->string('room_no', 30)->nullable();
                $table->unsignedBigInteger('reservation_id')->nullable()->index();
                $table->unsignedBigInteger('folio_id')->nullable()->index();
                $table->string('guest_name', 150)->nullable();
                $table->unsignedBigInteger('item_id')->nullable()->index();
                $table->string('item_code', 80)->nullable();
                $table->string('item_name', 150)->nullable();
                $table->date('consumption_date')->nullable()->index();
                $table->decimal('qty', 22, 4)->default(0);
                $table->decimal('unit_price', 22, 4)->default(0);
                $table->decimal('discount_amount', 22, 4)->default(0);
                $table->decimal('tax_amount', 22, 4)->default(0);
                $table->decimal('total_amount', 22, 4)->default(0);
                $table->string('status', 30)->default('draft')->index();
                $table->unsignedBigInteger('posted_charge_id')->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id','consumption_no'], 'hm_minibar_consumptions_business_no_unique');
                $table->index(['business_id','business_location_id'], 'hm_minibar_consumptions_business_location_index');
                $table->index(['consumption_date','status'], 'hm_minibar_consumptions_date_status_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_minibar_consumptions');
        Schema::dropIfExists('hm_minibar_items');
    }
};

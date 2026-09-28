<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('property_blocks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('property_id')->index('property_id');
            $table->date('transaction_date');
            $table->string('block_number');
            $table->decimal('block_sale_price', 15, 6);
            $table->decimal('block_sold_price', 15, 6)->default(0);
            $table->decimal('block_extent', 15, 4);
            $table->unsignedInteger('unit_id')->index('unit_id');
            $table->integer('customer_id')->nullable()->index('customer_id')->comment('customer who bought block');
            $table->boolean('is_sold')->default(false);
            $table->integer('sold_by')->nullable();
            $table->boolean('is_finalized')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->unsignedInteger('added_by');
            $table->boolean('all_payments_completed')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->enum('commission_approval', ['pending', 'approved'])->nullable()->default('pending');
            $table->integer('commission_approved_by')->nullable();
            $table->integer('commission_entered_by')->nullable();
            $table->integer('commission_status_updated_by')->nullable();
            $table->enum('commission_status', ['pending', 'paid'])->nullable()->default('pending');
            $table->decimal('sale_commission', 15, 4)->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('property_blocks');
    }
};

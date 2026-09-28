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
        Schema::create('assets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('assets_business_id_foreign');
            $table->string('asset_code');
            $table->string('name');
            $table->decimal('quantity', 22, 4);
            $table->string('model')->nullable();
            $table->string('serial_no')->nullable();
            $table->unsignedInteger('category_id')->nullable()->index('assets_category_id_foreign');
            $table->unsignedInteger('location_id')->nullable()->index('location_id');
            $table->date('purchase_date')->nullable();
            $table->string('purchase_type')->nullable();
            $table->decimal('unit_price', 22, 4);
            $table->decimal('depreciation', 22, 4)->nullable();
            $table->boolean('is_allocatable')->default(false);
            $table->text('description')->nullable();
            $table->unsignedInteger('created_by')->index('assets_created_by_foreign');
            $table->timestamps();

            $table->index(['business_id'], 'business_id');
            $table->index(['category_id'], 'category_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assets');
    }
};

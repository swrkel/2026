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
        Schema::create('discounts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('business_id')->index('business_id');
            $table->integer('brand_id')->nullable()->index('brand_id');
            $table->integer('category_id')->nullable()->index('category_id');
            $table->integer('location_id')->nullable()->index();
            $table->integer('priority')->nullable()->index();
            $table->string('discount_type')->nullable();
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('applicable_in_spg')->nullable()->default(false);
            $table->boolean('applicable_in_cg')->nullable()->default(false);
            $table->timestamps();

            $table->index(['brand_id']);
            $table->index(['business_id']);
            $table->index(['category_id']);
            $table->index(['location_id'], 'location_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discounts');
    }
};

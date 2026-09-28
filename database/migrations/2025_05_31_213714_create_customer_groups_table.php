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
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('name');
            $table->double('amount', 5, 2);
            $table->string('price_calculation_type')->nullable()->default('percentage')->index();
            $table->integer('selling_price_group_id')->nullable()->index();
            $table->unsignedInteger('created_by')->index();
            $table->timestamps();

            $table->index(['business_id'], 'customer_groups_business_id_foreign');
            $table->index(['selling_price_group_id'], 'selling_price_group_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_groups');
    }
};

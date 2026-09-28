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
        Schema::create('issue_customer_bill_details', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->bigInteger('issue_bill_id')->index('issue_bill_id');
            $table->string('product_id', 255)->index('product_id');
            $table->decimal('unit_price', 15);
            $table->integer('qty');
            $table->integer('discount');
            $table->integer('tax')->nullable();
            $table->decimal('sub_total', 15);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_customer_bill_details');
    }
};

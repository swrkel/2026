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
        Schema::create('contact_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->enum('type', ['customer', 'supplier', 'both'])->default('customer');
            $table->string('name');
            $table->double('amount', 5, 2);
            $table->integer('supplier_group_id')->nullable()->index('supplier_group_id');
            $table->decimal('maximum_discount', 10)->nullable();
            $table->decimal('last_maximum_discount', 10, 5)->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();
            $table->integer('account_type_id')->nullable()->index('account_type_id');
            $table->integer('interest_account_id')->nullable()->index('interest_account_id');

            $table->index(['business_id'], 'customer_groups_business_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_groups');
    }
};

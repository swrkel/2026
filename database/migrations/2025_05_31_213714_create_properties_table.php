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
        Schema::create('properties', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('location_id')->index('location_id');
            $table->integer('account_id')->nullable()->index('account_id');
            $table->string('name');
            $table->unsignedInteger('supplier_id')->index('supplier_id');
            $table->unsignedInteger('unit_id')->index('unit_id');
            $table->decimal('extent', 15, 4)->comment('size');
            $table->enum('status', ['open', 'close']);
            $table->unsignedInteger('transaction_id')->nullable()->index('transaction_id');
            $table->unsignedInteger('added_by');
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
        Schema::dropIfExists('properties');
    }
};

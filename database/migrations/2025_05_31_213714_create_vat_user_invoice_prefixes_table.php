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
        Schema::create('vat_user_invoice_prefixes', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id');
            $table->integer('location_id');
            $table->integer('prefix_id')->nullable();
            $table->integer('prefix_id2')->nullable();
            $table->integer('user_id');
            $table->integer('created_by');
            $table->timestamp('date_time')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vat_user_invoice_prefixes');
    }
};

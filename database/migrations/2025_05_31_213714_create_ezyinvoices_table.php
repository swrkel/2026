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
        Schema::create('ezyinvoices', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('invoice_no', 255);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->date('transaction_date');
            $table->date('finish_date')->nullable();
            $table->unsignedInteger('location_id')->index('location_id');
            $table->string('total_amount', 25)->default('0');
            $table->boolean('status')->default(true)->comment('1: Active , 0: Inactive');
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
        Schema::dropIfExists('ezyinvoices');
    }
};

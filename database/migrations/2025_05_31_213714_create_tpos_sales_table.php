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
        Schema::create('tpos_sales', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->integer('location_id')->nullable()->index('location_id');
            $table->timestamp('date')->useCurrentOnUpdate()->useCurrent();
            $table->string('tpos_no')->nullable();
            $table->string('fpos_no')->nullable();
            $table->integer('customer_id')->index('customer_id');
            $table->integer('user_id')->index('user_id');
            $table->integer('status')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->default('0000-00-00 00:00:00');
            $table->softDeletes();
            $table->integer('reprint_no')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tpos_sales');
    }
};

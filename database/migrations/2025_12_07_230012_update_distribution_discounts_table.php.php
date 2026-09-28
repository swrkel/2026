<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('distribution_discounts', function (Blueprint $table) {
            $table->dropColumn(['unit_id', 'qty', 'discount_type', 'max_discount']);
        });
    }

    public function down()
    {
        Schema::table('distribution_discounts', function (Blueprint $table) {
            $table->bigInteger('unit_id')->nullable();
            $table->decimal('qty', 20, 4)->nullable();
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('max_discount', 20, 4)->nullable();
        });
    }
};

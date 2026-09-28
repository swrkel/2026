<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('distribution_discount_products', function (Blueprint $table) {
            $table->bigInteger('unit_id')->unsigned()->nullable()->after('product_id');
            $table->decimal('qty', 20, 4)->nullable()->after('unit_id');
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable()->after('qty');
            $table->decimal('max_discount', 20, 4)->nullable()->after('discount_type');
        });
    }

    public function down()
    {
        Schema::table('distribution_discount_products', function (Blueprint $table) {
            $table->dropColumn(['unit_id', 'qty', 'discount_type', 'max_discount']);
        });
    }
};

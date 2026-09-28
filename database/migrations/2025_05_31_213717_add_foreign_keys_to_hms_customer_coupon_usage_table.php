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
        Schema::table('hms_customer_coupon_usage', function (Blueprint $table) {
            $table->foreign(['coupon_id'])->references(['id'])->on('hms_coupons')->onUpdate('NO ACTION')->onDelete('CASCADE');
            $table->foreign(['customer_id'])->references(['id'])->on('customers')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hms_customer_coupon_usage', function (Blueprint $table) {
            $table->dropForeign('hms_customer_coupon_usage_coupon_id_foreign');
            $table->dropForeign('hms_customer_coupon_usage_customer_id_foreign');
        });
    }
};

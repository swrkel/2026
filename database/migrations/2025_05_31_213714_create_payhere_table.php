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
        Schema::create('payhere', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('order_id', 10)->index('order_id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('package_id')->nullable()->index('package_id');
            $table->unsignedInteger('transaction_id')->nullable()->index('transaction_id');
            $table->unsignedInteger('user_id')->index('user_id');
            $table->float('price', 10, 0);
            $table->string('currency', 10)->nullable();
            $table->string('status', 20)->nullable();
            $table->integer('status_code')->nullable();
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
        Schema::dropIfExists('payhere');
    }
};

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
        Schema::create('close_current_sales', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('property_finalize_id')->index('property_finalize_id');
            $table->unsignedInteger('property_id')->index('property_id');
            $table->unsignedInteger('block_id')->index('block_id');
            $table->boolean('is_closed')->default(false);
            $table->unsignedInteger('closed_by');
            $table->string('reason_id')->index('reason_id');
            $table->unsignedInteger('transaction_id')->index('transaction_id');
            $table->boolean('all_payments_completed')->default(false);
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
        Schema::dropIfExists('close_current_sales');
    }
};

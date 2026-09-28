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
        Schema::create('pump_operator_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->nullable()->index('business_id');
            $table->timestamp('date_and_time')->useCurrent();
            $table->unsignedInteger('pump_operator_id')->nullable()->index('pump_operator_id');
            $table->string('payment_type');
            $table->string('payment_amount');
            $table->text('note')->nullable();
            $table->unsignedInteger('edited_by')->nullable();
            $table->unsignedInteger('created_by');
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->string('settlement_no', 30)->nullable();
            $table->integer('is_used')->nullable()->default(0);
            $table->integer('parent_id')->nullable()->index('parent_id');
            $table->integer('shift_id')->nullable()->index('shift_id');
            $table->string('collection_form_no', 255)->nullable();

            $table->index(['business_id'], 'pump_operator_payments_business_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pump_operator_payments');
    }
};

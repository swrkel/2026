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
        Schema::create('subscription_sms_templates', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->text('sms_body');
            $table->integer('days_1')->nullable();
            $table->integer('days_1_status')->nullable();
            $table->integer('days_2')->nullable();
            $table->integer('days_2_status')->nullable();
            $table->integer('days_3')->nullable();
            $table->integer('days_3_status')->nullable();
            $table->integer('days_4')->nullable();
            $table->integer('days_4_status')->nullable();
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
        Schema::dropIfExists('subscription_sms_templates');
    }
};

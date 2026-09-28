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
        Schema::create('income_methods', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date');
            $table->unsignedInteger('referral_group_id')->index('referral_group_id');
            $table->string('income_method');
            $table->enum('status', ['enable', 'disable'])->default('enable');
            $table->enum('income_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('value', 15, 6);
            $table->integer('minimum_new_signups');
            $table->integer('minimum_active_subscriptions');
            $table->enum('comission_eligible_conditions', ['minimum_signups_only', 'minimum_subscription_only', 'both'])->nullable();
            $table->unsignedInteger('created_by');
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
        Schema::dropIfExists('income_methods');
    }
};

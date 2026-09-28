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
        Schema::create('crm_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->integer('contact_id')->nullable()->index('contact_id');
            $table->string('title');
            $table->string('status')->nullable();
            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('end_datetime')->nullable();
            $table->text('description')->nullable();
            $table->enum('schedule_type', ['call', 'sms', 'meeting', 'email'])->default('email')->index();
            $table->integer('followup_category_id')->nullable()->index('followup_category_id');
            $table->boolean('allow_notification')->default(true);
            $table->text('notify_via')->nullable();
            $table->integer('notify_before')->nullable();
            $table->enum('notify_type', ['minute', 'hour', 'day'])->default('hour')->index();
            $table->integer('created_by')->index();
            $table->boolean('is_recursive')->default(false);
            $table->integer('recursion_days')->nullable();
            $table->text('followup_additional_info')->nullable();
            $table->string('follow_up_by')->nullable();
            $table->string('follow_up_by_value')->nullable();
            $table->timestamps();

            $table->index(['business_id']);
            $table->index(['contact_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_schedules');
    }
};

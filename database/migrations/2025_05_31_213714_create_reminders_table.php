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
        Schema::create('reminders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('name');
            $table->enum('options', ['when_login', 'in_dashboard', 'in_other_page'])->default('when_login');
            $table->text('other_pages')->nullable();
            $table->boolean('snooze')->default(false);
            $table->unsignedInteger('time');
            $table->enum('time_type', ['minutes', 'hours', 'days', 'weeks', 'months'])->nullable();
            $table->boolean('cancel')->default(false);
            $table->dateTime('snoozed_at')->nullable();
            $table->integer('crm_reminder_id')->nullable()->index('crm_reminder_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->boolean('crm_reminder')->default(false);
            $table->timestamp('reminder_date')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reminders');
    }
};

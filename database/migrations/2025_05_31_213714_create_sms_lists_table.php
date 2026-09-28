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
        Schema::create('sms_lists', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->text('numbers');
            $table->integer('count_numbers')->default(0);
            $table->enum('is_group', ['yes', 'no'])->default('no');
            $table->unsignedInteger('member_group')->nullable();
            $table->unsignedInteger('balamandala')->nullable();
            $table->unsignedInteger('gramseva_vasama')->nullable();
            $table->boolean('remove_duplicates')->default(false);
            $table->enum('message_type', ['text', 'unicode'])->default('text');
            $table->text('message');
            $table->integer('count_message')->default(0);
            $table->integer('characters');
            $table->boolean('schedule')->default(false);
            $table->string('timezone')->nullable();
            $table->dateTime('schedule_date_time')->nullable();
            $table->dateTime('sent_on');
            $table->boolean('is_unicode')->default(false);
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
        Schema::dropIfExists('sms_lists');
    }
};

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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->string('business_type', 200)->default('business');
            $table->string('recipient', 200);
            $table->text('message');
            $table->integer('no_of_characters');
            $table->integer('no_of_sms');
            $table->string('sms_type', 200);
            $table->decimal('unit_cost', 10, 3);
            $table->decimal('total_cost', 10, 3);
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
            $table->string('default_gateway', 200)->nullable();
            $table->string('sender_name', 200)->nullable();
            $table->string('sms_type_', 200)->nullable();
            $table->string('sms_status', 200)->nullable();
            $table->string('username', 200)->nullable();
            $table->string('uuid', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sms_logs');
    }
};

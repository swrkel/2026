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
        Schema::create('sms_api_clients', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 200);
            $table->string('ultimate_token', 200)->nullable();
            $table->string('default_gateway', 200)->nullable();
            $table->string('hutch_password', 200)->nullable();
            $table->string('hutch_username', 200)->nullable();
            $table->string('address', 200);
            $table->string('contact_mobile', 200);
            $table->string('land_no', 200)->nullable();
            $table->string('contact_name', 200)->nullable();
            $table->string('api_key', 200);
            $table->string('sender_names', 200);
            $table->string('username', 200);
            $table->string('password', 200);
            $table->timestamp('date')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('created_at')->default('0000-00-00 00:00:00');
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
        Schema::dropIfExists('sms_api_clients');
    }
};

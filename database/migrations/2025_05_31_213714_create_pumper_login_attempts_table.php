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
        Schema::create('pumper_login_attempts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->nullable();
            $table->string('company_number', 255)->nullable();
            $table->string('ip_address', 255)->nullable();
            $table->string('last_entered_passcode', 255)->nullable();
            $table->integer('attempt_count')->nullable();
            $table->string('status', 255)->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pumper_login_attempts');
    }
};

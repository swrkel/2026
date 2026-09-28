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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('provider', 30);
            $table->string('name', 95)->nullable();
            $table->string('email', 95)->nullable();
            $table->string('avatar', 1024)->nullable();
            $table->string('provider_user_id', 100)->nullable();
            $table->string('provider_username', 100)->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->unsignedInteger('expires_in')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_accounts');
    }
};

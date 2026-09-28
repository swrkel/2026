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
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('guard_name');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_service_staff')->default(false);
            $table->boolean('is_superadmin_default')->default(false);
            $table->timestamps();

            $table->index(['business_id'], 'roles_business_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles');
    }
};

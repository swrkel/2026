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
        Schema::create('visitors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('unique_code', 50)->nullable();
            $table->string('email');
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->integer('district_id')->nullable()->index('district_id');
            $table->integer('town_id')->nullable()->index('town_id');
            $table->string('business_name', 250);
            $table->string('mobile_number', 250);
            $table->string('land_number', 250)->nullable();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->text('address')->nullable();
            $table->text('details')->nullable();
            $table->integer('created_by');
            $table->softDeletes();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('visitors');
    }
};

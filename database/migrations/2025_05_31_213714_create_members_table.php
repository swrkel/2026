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
        Schema::create('members', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('username');
            $table->string('password')->nullable();
            $table->string('address');
            $table->string('district')->nullable();
            $table->string('town')->nullable();
            $table->string('mobile_number_1', 15);
            $table->string('mobile_number_2', 15)->nullable();
            $table->string('mobile_number_3', 15)->nullable();
            $table->string('land_number')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->integer('gramasevaka_area')->nullable();
            $table->bigInteger('electrorate_id')->nullable()->index('electrorate_id');
            $table->integer('bala_mandalaya_area')->nullable();
            $table->integer('member_group')->nullable();
            $table->string('give_away_gifts')->nullable();
            $table->bigInteger('parent_id')->nullable()->index('parent_id');
            $table->bigInteger('created_by')->nullable()->unique('members_users_id_foreign');
            $table->string('relation_name', 250)->nullable();
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
        Schema::dropIfExists('members');
    }
};

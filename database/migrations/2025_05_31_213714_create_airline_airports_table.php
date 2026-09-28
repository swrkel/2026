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
        if (Schema::hasTable('airline_airports')) {
            return;
        }

        Schema::create('airline_airports', function (Blueprint $table) {
            $table->integer('id', true);
            $table->date('date_added');
            $table->string('country', 50);
            $table->string('province', 50);
            $table->string('airport_name', 50);
            $table->boolean('status');
            $table->integer('user_id')->index('user_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('airline_airports');
    }
};

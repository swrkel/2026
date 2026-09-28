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
        Schema::create('denominations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable()->index('user_id');
            $table->double('denomination')->nullable();
            $table->integer('count')->nullable();
            $table->double('total')->nullable();
            $table->bigInteger('denominations_belongs_id')->nullable()->index('denominations_belongs_id');
            $table->enum('module', ['pos'])->nullable();
            $table->enum('type', ['open', 'close'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('denominations');
    }
};

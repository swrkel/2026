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
        Schema::create('tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('tickets_user_id_foreign');
            $table->string('status');
            $table->string('title');
            $table->boolean('starred')->default(false);
            $table->text('note')->nullable();
            $table->integer('category_id');
            $table->integer('assigned_to');
            $table->boolean('is_public')->default(false);
            $table->enum('priority', ['urgent', 'high', 'medium', 'low']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tickets');
    }
};

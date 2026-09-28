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
        Schema::create('essentials_document_shares', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('document_id')->index('document_id');
            $table->enum('value_type', ['user', 'role'])->index();
            $table->integer('value');
            $table->timestamps();

            $table->index(['document_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_document_shares');
    }
};

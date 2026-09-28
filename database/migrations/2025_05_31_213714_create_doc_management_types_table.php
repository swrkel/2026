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
        Schema::create('doc_management_types', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('type', 50);
            $table->string('user', 30);
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('doc_management_types');
    }
};

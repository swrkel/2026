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
        Schema::create('base_change_log', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('table_name', 100)->nullable();
            $table->integer('row_id')->nullable();
            $table->enum('change_type', ['create', 'update', 'delete'])->nullable();
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('base_change_log');
    }
};

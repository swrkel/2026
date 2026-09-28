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
        Schema::create('mfg_settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('type', 255);
            $table->string('name', 255)->nullable();
            $table->enum('is_active', ['0', '1'])->default('1');
            $table->timestamp('created_at')->useCurrent();
            $table->integer('created_by');
            $table->dateTime('updated_at')->nullable();
            $table->integer('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mfg_settings');
    }
};

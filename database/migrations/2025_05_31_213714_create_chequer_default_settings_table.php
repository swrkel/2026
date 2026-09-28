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
        Schema::create('chequer_default_settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('def_tempid', 255)->default('');
            $table->string('def_curnctname', 255)->default('');
            $table->string('def_stampid', 255)->default('');
            $table->string('def_entrydt', 255)->default('');
            $table->integer('def_status')->default(1);
            $table->string('def_currency', 255);
            $table->string('def_stamp', 100)->nullable();
            $table->integer('def_cheque_templete');
            $table->string('def_bank_account', 100)->nullable();
            $table->integer('def_autostart_chbk_no')->nullable();
            $table->string('def_font', 100);
            $table->string('def_font_size', 15);
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
        Schema::dropIfExists('chequer_default_settings');
    }
};

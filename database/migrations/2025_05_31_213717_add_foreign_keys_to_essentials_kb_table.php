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
        Schema::table('essentials_kb', function (Blueprint $table) {
            $table->foreign(['parent_id'])->references(['id'])->on('essentials_kb')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('essentials_kb', function (Blueprint $table) {
            $table->dropForeign('essentials_kb_parent_id_foreign');
        });
    }
};

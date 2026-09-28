<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('membership_members', function (Blueprint $table) {
            $table->string('member_name_other', 255)->nullable()->after('member_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('membership_members', function (Blueprint $table) {
            $table->dropColumn('member_name_other');
        });
    }
};

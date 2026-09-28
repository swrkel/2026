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
        Schema::table('sheet_spreadsheet_shares', function (Blueprint $table) {
            $table->foreign(['sheet_spreadsheet_id'])->references(['id'])->on('sheet_spreadsheets')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sheet_spreadsheet_shares', function (Blueprint $table) {
            $table->dropForeign('sheet_spreadsheet_shares_sheet_spreadsheet_id_foreign');
        });
    }
};

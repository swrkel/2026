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
        Schema::create('sheet_spreadsheet_shares', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sheet_spreadsheet_id')->index('sheet_spreadsheet_id');
            $table->string('shared_with')->index()->comment('Shared with like user/role/todo');
            $table->integer('shared_id')->index('shared_id')->comment('Id of shared with like user_id/role_id/todo_id');
            $table->timestamps();

            $table->index(['shared_id']);
            $table->index(['sheet_spreadsheet_id'], 'sheet_spreadsheet_shares_sheet_spreadsheet_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sheet_spreadsheet_shares');
    }
};

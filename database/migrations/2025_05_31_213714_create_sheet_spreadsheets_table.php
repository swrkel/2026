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
        Schema::create('sheet_spreadsheets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('name');
            $table->longText('sheet_data');
            $table->integer('created_by')->index();
            $table->integer('folder_id')->nullable()->index('folder_id');
            $table->timestamps();
            $table->timestamp('last_opened_on')->useCurrent();
            $table->integer('status')->default(1);
            $table->integer('last_updated_by')->default(0);

            $table->index(['business_id'], 'sheet_spreadsheets_business_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sheet_spreadsheets');
    }
};

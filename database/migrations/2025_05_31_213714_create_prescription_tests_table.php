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
        Schema::create('prescription_tests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('prescription_id')->index('prescription_id');
            $table->unsignedInteger('test_id')->index('test_id');
            $table->string('notes')->nullable();
            $table->dateTime('test_date');
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
        Schema::dropIfExists('prescription_tests');
    }
};

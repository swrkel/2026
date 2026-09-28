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
        Schema::create('reviewed_changes_description', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('review_id')->index('review_id');
            $table->integer('created_by');
            $table->text('description');
            $table->timestamp('created_at')->useCurrent();
            $table->string('module', 40);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reviewed_changes_description');
    }
};

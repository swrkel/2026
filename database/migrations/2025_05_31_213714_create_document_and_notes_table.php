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
        Schema::create('document_and_notes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index('business_id');
            $table->integer('notable_id')->index();
            $table->string('notable_type');
            $table->text('heading')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_private')->default(false);
            $table->integer('created_by')->index();
            $table->timestamps();

            $table->index(['business_id']);
            $table->index(['notable_id'], 'notable_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_and_notes');
    }
};

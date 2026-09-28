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
        Schema::create('essentials_kb', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('business_id');
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('status');
            $table->string('kb_type');
            $table->unsignedBigInteger('parent_id')->nullable()->index()->comment('id from essentials_kb table');
            $table->string('share_with')->nullable()->comment('public, private, only_with');
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamps();

            $table->index(['business_id']);
            $table->index(['parent_id'], 'parent_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_kb');
    }
};

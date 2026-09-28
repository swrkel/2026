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
        Schema::create('brands', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('brands_business_id_foreign');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->integer('serial_nos')->nullable();
            $table->smallInteger('is_auto_repair')->nullable()->default(0);
            $table->unsignedInteger('created_by')->index('brands_created_by_foreign');
            $table->boolean('use_for_repair')->default(false)->comment('brands to be used on repair module');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['business_id'], 'business_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('brands');
    }
};

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
        Schema::create('gramaseva_vasamas', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('province_id')->nullable()->index('province_id');
            $table->bigInteger('electrorate_id')->nullable()->index('electrorate_id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->date('date');
            $table->bigInteger('created_by')->nullable();
            $table->string('gramaseva_vasama');
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gramaseva_vasamas');
    }
};

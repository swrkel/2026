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
        Schema::create('day_ends', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->timestamp('day_end_date')->useCurrentOnUpdate()->useCurrent();
            $table->string('sold_pumps', 200)->nullable();
            $table->string('pumps', 200);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by');
            $table->timestamp('created_at')->default('0000-00-00 00:00:00');
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('day_ends');
    }
};

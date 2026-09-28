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
        if (Schema::hasTable('ad_page_slots')) {
            return;
        }

        Schema::create('ad_page_slots', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('slot', 255);
            $table->string('slot_no');
            $table->integer('ad_page_id')->index('ad_page_id');
            $table->integer('width');
            $table->integer('height');
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
        Schema::dropIfExists('ad_page_slots');
    }
};

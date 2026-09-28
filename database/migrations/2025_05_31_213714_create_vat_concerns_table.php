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
        Schema::create('vat_concerns', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id');
            $table->text('line_1')->nullable();
            $table->text('line_2')->nullable();
            $table->text('line_3')->nullable();
            $table->text('line_4')->nullable();
            $table->text('line_5')->nullable();
            $table->integer('status');
            $table->integer('user_id');
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
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
        Schema::dropIfExists('vat_concerns');
    }
};

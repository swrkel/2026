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
        Schema::create('cheque_templates', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('image_url', 50);
            $table->string('template_name', 50);
            $table->string('template_size', 20);
            $table->string('pay_name', 20);
            $table->string('date_pos', 255);
            $table->string('date_format', 255)->nullable();
            $table->string('amount', 20);
            $table->string('amount_in_w1', 20);
            $table->string('amount_in_w2', 20);
            $table->string('amount_in_w3', 20);
            $table->string('template_cross', 20);
            $table->string('pay_only', 20);
            $table->string('not_negotiable', 20);
            $table->string('signature_stamp', 20);
            $table->dateTime('created_date', 6);
            $table->string('created_by', 20);
            $table->string('seprator', 225)->nullable();
            $table->string('words2', 225)->nullable();
            $table->string('words3', 225)->nullable();
            $table->string('is_dublecross', 225)->nullable();
            $table->string('dublecross', 225)->nullable();
            $table->string('is_stamp', 225)->nullable();
            $table->string('is_strikeBearer', 255)->nullable();
            $table->string('strikeBearer', 255)->nullable();
            $table->string('d1', 225)->nullable();
            $table->string('d2', 255)->nullable();
            $table->string('m1', 255)->nullable();
            $table->string('m2', 255)->nullable();
            $table->string('y1', 255)->nullable();
            $table->string('y2', 255)->nullable();
            $table->string('y3', 255)->nullable();
            $table->string('y4', 255)->nullable();
            $table->string('ds1', 225)->nullable();
            $table->string('ds2', 255)->nullable();
            $table->string('signature_stamp_area', 255);
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
        Schema::dropIfExists('cheque_templates');
    }
};

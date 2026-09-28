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
        Schema::create('customer_statement_logos', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->integer('created_by');
            $table->string('logo', 100)->nullable();
            $table->string('alignment', 20);
            $table->string('image_name', 100);
            $table->integer('business_name')->default(0);
            $table->integer('business_address')->default(0);
            $table->integer('contact_no')->default(0);
            $table->integer('email')->default(0);
            $table->integer('mobile_no')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->text('statement_note')->nullable();
            $table->string('text_position', 60)->default('above');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_statement_logos');
    }
};

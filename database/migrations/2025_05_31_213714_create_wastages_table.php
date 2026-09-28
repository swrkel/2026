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
        Schema::create('wastages', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->dateTime('date_and_time');
            $table->string('wastage_form_no');
            $table->unsignedInteger('location_id')->index('location_id');
            $table->unsignedInteger('goldsmith_id')->index('goldsmith_id');
            $table->decimal('wastage', 15, 4);
            $table->unsignedInteger('category_id')->index('category_id');
            $table->unsignedInteger('sub_category_id')->index('sub_category_id');
            $table->unsignedInteger('created_by');
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
        Schema::dropIfExists('wastages');
    }
};

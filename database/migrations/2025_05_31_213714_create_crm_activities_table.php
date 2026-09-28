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
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->date('date');
            $table->string('name');
            $table->string('email');
            $table->string('mobile');
            $table->string('alternate_number');
            $table->string('landline');
            $table->string('city');
            $table->string('district');
            $table->string('country');
            $table->unsignedInteger('contact_id')->nullable()->index('contact_id');
            $table->time('time_connected');
            $table->boolean('add_in_customer_page')->default(false)->comment('0 => no, 1=>yes');
            $table->boolean('discontinue_follow_up')->default(false)->comment('0 => no, 1 => yes');
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
        Schema::dropIfExists('crm_activities');
    }
};

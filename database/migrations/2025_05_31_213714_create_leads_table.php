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
        Schema::create('leads', function (Blueprint $table) {
            $table->increments('id');
            $table->string('lead_no', 20);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->date('date');
            $table->time('time');
            $table->enum('sector', ['private', 'government']);
            $table->unsignedInteger('category_id')->index('category_id');
            $table->string('main_organization')->nullable();
            $table->string('business')->nullable();
            $table->string('address')->nullable();
            $table->string('town');
            $table->string('district');
            $table->string('mobile_no_1', 15);
            $table->string('mobile_no_2', 15)->nullable();
            $table->string('mobile_no_3', 15)->nullable();
            $table->string('land_number', 15)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('client_response')->nullable();
            $table->date('follow_up_date');
            $table->enum('status', ['valid', 'invalid'])->default('valid');
            $table->unsignedInteger('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->text('note')->nullable();
            $table->integer('label_id')->nullable()->index('label_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leads');
    }
};

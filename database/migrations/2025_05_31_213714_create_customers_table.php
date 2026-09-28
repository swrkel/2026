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
        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->nullable()->index('business_id');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('username')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->string('mobile')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('landline')->nullable();
            $table->string('geo_location')->nullable();
            $table->text('address')->nullable();
            $table->string('town')->nullable();
            $table->string('district')->nullable();
            $table->string('give_away_gifts')->nullable();
            $table->boolean('is_company_customer')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->string('nic_number', 20);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
};

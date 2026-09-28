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
        Schema::create('bakery_users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('location_id')->index('location_id');
            $table->string('name', 100)->default('');
            $table->string('cnic', 100)->default('');
            $table->text('address');
            $table->date('dob');
            $table->string('landline', 20)->nullable();
            $table->string('mobile', 20)->default('');
            $table->integer('status')->nullable();
            $table->enum('commission_type', ['fixed', 'percentage', 'none']);
            $table->decimal('commission_ap', 15)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->decimal('short_amount', 12, 5)->nullable();
            $table->decimal('excess_amount', 12, 5)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bakery_users');
    }
};

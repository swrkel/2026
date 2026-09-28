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
        Schema::create('crms', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('business_name', 255);
            $table->string('email')->nullable();
            $table->string('contact_id')->nullable()->index('contact_id');
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('country')->nullable();
            $table->string('mobile');
            $table->string('landline')->nullable();
            $table->string('alternate_number')->nullable();
            $table->unsignedInteger('created_by')->index('contacts_created_by_foreign');
            $table->integer('total_rp')->default(0)->comment('rp is the short form of reward points');
            $table->integer('total_rp_used')->default(0)->comment('rp is the short form of reward points');
            $table->integer('total_rp_expired')->default(0)->comment('rp is the short form of reward points');
            $table->boolean('is_default')->default(false);
            $table->integer('crm_group_id')->nullable()->index('crm_group_id');
            $table->string('custom_field1')->nullable();
            $table->string('custom_field2')->nullable();
            $table->string('custom_field3')->nullable();
            $table->string('custom_field4')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['business_id'], 'contacts_business_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crms');
    }
};

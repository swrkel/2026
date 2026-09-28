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
        Schema::create('crm_campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('name');
            $table->enum('campaign_type', ['sms', 'email'])->default('email');
            $table->string('subject')->nullable();
            $table->text('email_body')->nullable();
            $table->text('sms_body')->nullable();
            $table->dateTime('sent_on')->nullable();
            $table->text('contact_ids');
            $table->text('additional_info')->nullable();
            $table->integer('created_by')->index();
            $table->timestamps();

            $table->index(['business_id'], 'crm_campaigns_business_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_campaigns');
    }
};

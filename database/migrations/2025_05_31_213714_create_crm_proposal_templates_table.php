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
        Schema::create('crm_proposal_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->text('subject');
            $table->longText('body');
            $table->text('cc')->nullable();
            $table->text('bcc')->nullable();
            $table->integer('created_by')->index();
            $table->timestamps();

            $table->index(['business_id'], 'crm_proposal_templates_business_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_proposal_templates');
    }
};

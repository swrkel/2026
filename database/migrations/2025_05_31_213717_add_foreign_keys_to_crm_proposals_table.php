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
        Schema::table('crm_proposals', function (Blueprint $table) {
            $table->foreign(['business_id'])->references(['id'])->on('business')->onUpdate('NO ACTION')->onDelete('CASCADE');
            $table->foreign(['contact_id'])->references(['id'])->on('contacts')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crm_proposals', function (Blueprint $table) {
            $table->dropForeign('crm_proposals_business_id_foreign');
            $table->dropForeign('crm_proposals_contact_id_foreign');
        });
    }
};

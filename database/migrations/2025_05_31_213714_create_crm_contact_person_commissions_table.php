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
        Schema::create('crm_contact_person_commissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('contact_person_id')->index('contact_person_id');
            $table->integer('transaction_id')->nullable()->index('transaction_id');
            $table->decimal('commission_amount', 22, 4)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_contact_person_commissions');
    }
};

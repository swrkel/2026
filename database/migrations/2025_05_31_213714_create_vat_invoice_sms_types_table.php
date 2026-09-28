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
        Schema::create('vat_invoice_sms_types', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id');
            $table->string('sms_to', 200);
            $table->integer('created_by');
            $table->timestamp('date_time')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vat_invoice_sms_types');
    }
};

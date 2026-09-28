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
        Schema::create('vat_settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id');
            $table->string('vat_period', 200);
            $table->date('effective_date');
            $table->integer('status');
            $table->integer('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->default('0000-00-00 00:00:00');
            $table->integer('is_custom_date')->default(0);
            $table->string('tax_report_name', 20)->default('vat');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vat_settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessModuleDateSettingsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('business_module_date_settings')) {
            Schema::create('business_module_date_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id');
                $table->date('global_date')->nullable();
                $table->longText('module_settings')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique('business_id', 'bm_date_settings_business_unique');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('business_module_date_settings');
    }
}

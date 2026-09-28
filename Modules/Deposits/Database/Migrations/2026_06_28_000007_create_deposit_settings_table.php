<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepositSettingsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('deposit_settings')) {
            Schema::create('deposit_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->string('key')->index();
                $table->text('value')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'key']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('deposit_settings');
    }
}

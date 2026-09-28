<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadsNewRc6ReleaseValidationTables extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('leads_new_release_validations')) {
            Schema::create('leads_new_release_validations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->string('release_version', 30)->default('RC6')->index();
                $table->string('check_group', 100)->index();
                $table->string('check_key', 191)->index();
                $table->string('status', 50)->default('pending')->index();
                $table->json('details')->nullable();
                $table->unsignedBigInteger('checked_by')->nullable()->index();
                $table->timestamp('checked_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('leads_new_release_validations');
    }
}

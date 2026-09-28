<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadsNewRc5OperationalSupportTables extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('leads_new_saved_filters')) {
            Schema::create('leads_new_saved_filters', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('name');
                $table->string('context')->default('leads')->index();
                $table->json('filters')->nullable();
                $table->boolean('is_default')->default(false)->index();
                $table->boolean('is_shared')->default(false)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('leads_new_ui_preferences')) {
            Schema::create('leads_new_ui_preferences', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('page')->index();
                $table->json('preferences')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('leads_new_bulk_action_logs')) {
            Schema::create('leads_new_bulk_action_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('action')->index();
                $table->unsignedInteger('record_count')->default(0);
                $table->json('payload')->nullable();
                $table->json('result')->nullable();
                $table->string('status')->default('completed')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('leads_new_system_checks')) {
            Schema::create('leads_new_system_checks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('check_key')->index();
                $table->string('status')->default('ok')->index();
                $table->json('details')->nullable();
                $table->timestamp('checked_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('leads_new_system_checks');
        Schema::dropIfExists('leads_new_bulk_action_logs');
        Schema::dropIfExists('leads_new_ui_preferences');
        Schema::dropIfExists('leads_new_saved_filters');
    }
}

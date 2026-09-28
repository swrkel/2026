<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateLeadsNewTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('leads_new_categories')) {
            Schema::create('leads_new_categories', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('business_id')->unsigned()->index();
                $table->string('name')->nullable();
                $table->text('description')->nullable();
                $table->integer('created_by')->unsigned()->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('leads_new_labels')) {
            Schema::create('leads_new_labels', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('business_id')->unsigned()->index();
                $table->string('label_1')->nullable();
                $table->string('label_2')->nullable();
                $table->string('label_3')->nullable();
                $table->integer('created_by')->unsigned()->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('leads_new_districts')) {
            Schema::create('leads_new_districts', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('business_id')->unsigned()->index();
                $table->integer('country_id')->unsigned()->nullable()->index();
                $table->string('name')->nullable();
                $table->integer('created_by')->unsigned()->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('leads_new_towns')) {
            Schema::create('leads_new_towns', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('business_id')->unsigned()->index();
                $table->integer('district_id')->unsigned()->nullable()->index();
                $table->string('name')->nullable();
                $table->integer('created_by')->unsigned()->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('leads_new_settings')) {
            Schema::create('leads_new_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('business_id')->unsigned()->index();
                $table->string('key')->nullable();
                $table->text('value')->nullable();
            });
        }

        if (!Schema::hasTable('leads_new')) {
            Schema::create('leads_new', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('business_id')->unsigned()->index();
                $table->string('lead_no')->nullable()->index();
                $table->date('date')->nullable()->index();
                $table->time('time')->nullable();
                $table->date('transaction_date')->nullable()->index();
                $table->string('sector')->nullable()->index();
                $table->integer('category_id')->unsigned()->nullable()->index();
                $table->integer('label_id')->unsigned()->nullable()->index();
                $table->string('main_organization')->nullable();
                $table->string('business')->nullable();
                $table->text('address')->nullable();
                $table->string('country')->nullable();
                $table->string('district')->nullable()->index();
                $table->string('town')->nullable()->index();
                $table->string('mobile_no_1')->nullable()->index();
                $table->string('mobile_no_2')->nullable()->index();
                $table->string('mobile_no_3')->nullable()->index();
                $table->string('land_number')->nullable();
                $table->string('email')->nullable();
                $table->text('client_response')->nullable();
                $table->date('follow_up_date')->nullable()->index();
                $table->text('note')->nullable();
                $table->string('status')->default('valid')->index();
                $table->integer('created_by')->unsigned()->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('leads_new_client_responses')) {
            Schema::create('leads_new_client_responses', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('business_id')->unsigned()->nullable()->index();
                $table->integer('lead_id')->unsigned()->index();
                $table->text('client_response')->nullable();
                $table->date('follow_up_date')->nullable();
                $table->integer('created_by')->unsigned()->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('permissions')) {
            foreach ([
                'leads_new.access',
                'leads_new.view',
                'leads_new.create',
                'leads_new.edit',
                'leads_new.delete',
                'leads_new.import',
                'leads_new.day_count',
                'leads_new.settings',
            ] as $permission) {
                DB::table('permissions')->updateOrInsert(
                    ['name' => $permission, 'guard_name' => 'web'],
                    ['name' => $permission, 'guard_name' => 'web']
                );
            }
        }

    }
    public function down()
    {
        Schema::dropIfExists('leads_new_client_responses');
        Schema::dropIfExists('leads_new');
        Schema::dropIfExists('leads_new_settings');
        Schema::dropIfExists('leads_new_towns');
        Schema::dropIfExists('leads_new_districts');
        Schema::dropIfExists('leads_new_labels');
        Schema::dropIfExists('leads_new_categories');
    }
}

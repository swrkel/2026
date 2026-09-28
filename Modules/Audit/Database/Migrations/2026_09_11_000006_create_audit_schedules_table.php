<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
class CreateAuditSchedulesTable extends Migration
{public function up(){if(!Schema::hasTable('audit_schedules')) Schema::create('audit_schedules',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('business_id')->nullable()->index();$t->string('name',150);$t->string('frequency',30);$t->string('run_time',10)->nullable();$t->longText('modules')->nullable();$t->boolean('is_enabled')->default(1)->index();$t->dateTime('last_run_at')->nullable();$t->dateTime('next_run_at')->nullable();$t->unsignedBigInteger('created_by')->nullable();$t->timestamps();});}public function down(){Schema::dropIfExists('audit_schedules');}}

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateAuditRunsTable extends Migration
{
    public function up(){if(!Schema::hasTable('audit_runs')) Schema::create('audit_runs',function(Blueprint $t){$t->bigIncrements('id');$t->string('run_no',50)->unique();$t->string('tenant_key',100)->nullable()->index();$t->unsignedBigInteger('business_id')->nullable()->index();$t->unsignedBigInteger('location_id')->nullable()->index();$t->unsignedBigInteger('started_by')->nullable();$t->string('status',30)->default('running')->index();$t->dateTime('started_at')->nullable()->index();$t->dateTime('finished_at')->nullable();$t->longText('context')->nullable();$t->longText('summary')->nullable();$t->timestamps();});}
    public function down(){Schema::dropIfExists('audit_runs');}
}

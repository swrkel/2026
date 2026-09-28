<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
class CreateAuditFindingHistoryTable extends Migration
{public function up(){if(!Schema::hasTable('audit_finding_history')) Schema::create('audit_finding_history',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('audit_finding_id')->index();$t->string('from_status',30)->nullable();$t->string('to_status',30);$t->text('note')->nullable();$t->unsignedBigInteger('changed_by')->nullable();$t->longText('meta')->nullable();$t->timestamps();});}public function down(){Schema::dropIfExists('audit_finding_history');}}

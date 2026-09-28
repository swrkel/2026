<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
class CreateAuditResolutionsTable extends Migration
{public function up(){if(!Schema::hasTable('audit_resolutions')) Schema::create('audit_resolutions',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('audit_finding_id')->index();$t->string('action',100);$t->text('note')->nullable();$t->longText('before_data')->nullable();$t->longText('after_data')->nullable();$t->unsignedBigInteger('performed_by')->nullable();$t->dateTime('performed_at')->nullable();$t->timestamps();});}public function down(){Schema::dropIfExists('audit_resolutions');}}

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(){ Schema::create('leads_new_workflows', function(Blueprint $t){ $t->id(); $t->unsignedBigInteger('business_id')->index(); $t->string('name'); $t->string('trigger_event')->index(); $t->json('conditions')->nullable(); $t->json('actions')->nullable(); $t->boolean('is_active')->default(true); $t->unsignedInteger('priority')->default(0); $t->timestamps(); $t->softDeletes(); }); }
    public function down(){ Schema::dropIfExists('leads_new_workflows'); }
};

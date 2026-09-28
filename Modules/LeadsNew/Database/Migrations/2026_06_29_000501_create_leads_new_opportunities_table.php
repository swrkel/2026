<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(){ Schema::create('leads_new_opportunities', function(Blueprint $t){ $t->id(); $t->unsignedBigInteger('business_id')->index(); $t->unsignedBigInteger('lead_id')->nullable()->index(); $t->string('opportunity_no')->nullable()->index(); $t->string('title'); $t->decimal('estimated_value',22,4)->default(0); $t->unsignedTinyInteger('probability')->default(0); $t->string('stage')->default('new'); $t->date('expected_close_date')->nullable(); $t->string('status')->default('open')->index(); $t->string('lost_reason')->nullable(); $t->unsignedBigInteger('assigned_to')->nullable()->index(); $t->timestamps(); $t->softDeletes(); }); } public function down(){ Schema::dropIfExists('leads_new_opportunities'); } };

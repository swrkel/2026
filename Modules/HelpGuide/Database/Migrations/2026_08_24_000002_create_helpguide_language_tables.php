<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function connection(): string { return config('helpguide.central_connection', config('database.default')); }
    public function up(): void
    {
        $c=$this->connection();
        if (!Schema::connection($c)->hasTable('hg_languages')) Schema::connection($c)->create('hg_languages', function(Blueprint $t){$t->bigIncrements('id');$t->string('code',12)->unique();$t->string('name',100);$t->string('native_name',100)->nullable();$t->boolean('is_system_default')->default(false);$t->boolean('is_active')->default(true);$t->unsignedInteger('sort_order')->default(0);$t->timestamps();$t->index(['is_active','sort_order']);});
        if (!Schema::connection($c)->hasTable('hg_user_language_preferences')) Schema::connection($c)->create('hg_user_language_preferences', function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('user_id')->unique();$t->string('language_code',12)->default('en');$t->timestamps();$t->index('language_code');});
        if (!Schema::connection($c)->hasTable('hg_article_translations')) Schema::connection($c)->create('hg_article_translations', function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('article_id');$t->string('language_code',12);$t->string('title');$t->text('summary')->nullable();$t->longText('content')->nullable();$t->char('source_hash',64)->default('');$t->string('translation_status',20)->default('ready');$t->text('translation_error')->nullable();$t->timestamp('translated_at')->nullable();$t->timestamps();$t->unique(['article_id','language_code']);$t->index(['language_code','translation_status']);});
        $rows=[['en','English','English',1,1],['si','Sinhala','සිංහල',0,2],['ta','Tamil','தமிழ்',0,3],['hi','Hindi','हिन्दी',0,4],['ar','Arabic','العربية',0,5],['fr','French','Français',0,6],['de','German','Deutsch',0,7],['es','Spanish','Español',0,8],['zh-CN','Chinese (Simplified)','简体中文',0,9],['ja','Japanese','日本語',0,10]]; foreach($rows as $r) DB::connection($c)->table('hg_languages')->updateOrInsert(['code'=>$r[0]],['name'=>$r[1],'native_name'=>$r[2],'is_system_default'=>$r[3],'is_active'=>1,'sort_order'=>$r[4],'updated_at'=>now(),'created_at'=>now()]);
    }
    public function down(): void {}
};

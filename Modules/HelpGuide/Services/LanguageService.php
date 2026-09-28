<?php
namespace Modules\HelpGuide\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LanguageService
{
    public function __construct(private TenantDatabaseService $databases) {}
    private function c(): string { return $this->databases->centralConnection(); }
    public function ready(): bool { return Schema::connection($this->c())->hasTable('hg_languages') && Schema::connection($this->c())->hasTable('hg_user_language_preferences'); }
    public function active()
    {
        if(!$this->ready()) return collect([(object)['code'=>'en','name'=>'English','native_name'=>'English','is_system_default'=>1]]);
        return DB::connection($this->c())->table('hg_languages')->where('is_active',1)->orderBy('sort_order')->orderBy('name')->get();
    }
    public function defaultCodeForUser(?int $userId): string
    {
        if($this->ready() && $userId){$code=DB::connection($this->c())->table('hg_user_language_preferences')->where('user_id',$userId)->value('language_code');if($code && $this->isActive($code)) return (string)$code;}
        if($this->ready()){$code=DB::connection($this->c())->table('hg_languages')->where('is_active',1)->where('is_system_default',1)->value('code');if($code)return (string)$code;}
        return 'en';
    }
    public function isActive(string $code): bool { return $code==='en' || ($this->ready() && DB::connection($this->c())->table('hg_languages')->where('code',$code)->where('is_active',1)->exists()); }
    public function saveUserDefault(int $userId,string $code): void
    {
        abort_unless($this->isActive($code),422,'Selected Help Guide language is not active.');
        DB::connection($this->c())->table('hg_user_language_preferences')->updateOrInsert(['user_id'=>$userId],['language_code'=>$code,'updated_at'=>now(),'created_at'=>now()]);
    }
}

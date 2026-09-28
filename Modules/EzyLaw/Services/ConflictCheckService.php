<?php
namespace Modules\EzyLaw\Services;
use Illuminate\Support\Facades\DB;
use Modules\EzyLaw\Entities\{LawClient,LawMatter,LawMatterParty,LawConflictCheck,LawConflictMatch};
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class ConflictCheckService
{
    public function run(string $name,?string $identifiers=null,?string $notes=null): LawConflictCheck
    {
        return DB::transaction(function() use($name,$identifiers,$notes){
            $check=LawConflictCheck::create([
                'business_id'=>EzyLawTenantGuard::businessId(),'check_no'=>app(SettingsService::class)->nextNumber('conflict'),
                'query_name'=>trim($name),'identifiers'=>$identifiers,'requested_by'=>auth()->id(),'result_status'=>'clear','notes'=>$notes,'checked_at'=>now(),
            ]);
            $needle=mb_strtolower(trim($name));
            $this->matchClients($check,$needle,$identifiers);
            $this->matchParties($check,$needle);
            $this->matchMatters($check,$needle);
            $count=$check->matches()->count();
            $check->update(['result_status'=>$count>0?'review':'clear']);
            app(ActivityService::class)->log('run','conflict_check',$check->id,'Conflict check completed',['matches'=>$count]);
            return $check->fresh('matches');
        });
    }
    private function matchClients(LawConflictCheck $check,string $needle,?string $identifiers): void
    {
        $parts=$identifiers?array_filter(array_map('trim',preg_split('/[,;\n]+/',$identifiers))):[];
        $q=LawClient::query()->where(function($x)use($needle,$parts){
            $x->whereRaw('LOWER(name) LIKE ?',['%'.$needle.'%'])->orWhereRaw('LOWER(company_name) LIKE ?',['%'.$needle.'%']);
            foreach($parts as $p){$x->orWhere('nic_passport','like','%'.$p.'%')->orWhere('registration_no','like','%'.$p.'%')->orWhere('mobile','like','%'.$p.'%')->orWhere('email','like','%'.$p.'%');}
        });
        foreach($q->limit(50)->get() as $r)$this->saveMatch($check,'client',$r->id,$r->name,'name / identifier',strcasecmp(trim($r->name),$check->query_name)===0?1:.8,'Existing client');
    }
    private function matchParties(LawConflictCheck $check,string $needle): void
    {
        foreach(LawMatterParty::whereRaw('LOWER(name) LIKE ?',['%'.$needle.'%'])->limit(50)->get() as $r)$this->saveMatch($check,'matter_party',$r->id,$r->name,'party name',strcasecmp(trim($r->name),$check->query_name)===0?1:.8,$r->role ?: $r->party_type);
    }
    private function matchMatters(LawConflictCheck $check,string $needle): void
    {
        foreach(LawMatter::where(function($q)use($needle){$q->whereRaw('LOWER(opposing_party) LIKE ?',['%'.$needle.'%'])->orWhereRaw('LOWER(opposing_counsel) LIKE ?',['%'.$needle.'%']);})->limit(50)->get() as $r){
            $name=stripos((string)$r->opposing_party,$check->query_name)!==false?$r->opposing_party:$r->opposing_counsel;
            $this->saveMatch($check,'matter',$r->id,$name,'opposing party / counsel',.8,'Matter '.$r->matter_no);
        }
    }
    private function saveMatch(LawConflictCheck $check,string $type,int $id,?string $name,string $field,float $score,string $relationship): void
    {
        LawConflictMatch::create(['business_id'=>EzyLawTenantGuard::businessId(),'conflict_check_id'=>$check->id,'source_type'=>$type,'source_id'=>$id,'matched_name'=>$name,'matched_field'=>$field,'match_score'=>$score,'relationship'=>$relationship]);
    }
}

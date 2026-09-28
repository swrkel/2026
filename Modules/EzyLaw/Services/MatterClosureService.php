<?php
namespace Modules\EzyLaw\Services;

use Illuminate\Support\Facades\DB;
use Modules\EzyLaw\Entities\{LawMatter,LawMatterClosure,LawTask,LawDeadline,LawInvoice,LawTrustTransaction};
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;

class MatterClosureService
{
    public function blockers(LawMatter $matter): array
    {
        $trustIn=(float)LawTrustTransaction::where('matter_id',$matter->id)->where('direction','credit')->sum('amount');
        $trustOut=(float)LawTrustTransaction::where('matter_id',$matter->id)->where('direction','debit')->sum('amount');
        return [
            'open_tasks'=>LawTask::where('matter_id',$matter->id)->whereNotIn('status',['completed','cancelled'])->count(),
            'open_deadlines'=>LawDeadline::where('matter_id',$matter->id)->where('status','open')->count(),
            'invoice_balance'=>(float)LawInvoice::where('matter_id',$matter->id)->sum('balance'),
            'trust_balance'=>round($trustIn-$trustOut,4),
        ];
    }

    public function close(LawMatter $matter, array $data, bool $force=false): LawMatterClosure
    {
        return DB::transaction(function() use($matter,$data,$force){
            $blockers=$this->blockers($matter);
            $blocked=$blockers['open_tasks']>0||$blockers['open_deadlines']>0||abs($blockers['invoice_balance'])>0.0001||abs($blockers['trust_balance'])>0.0001;
            if($blocked&&!$force) throw new \RuntimeException('Matter cannot be closed until tasks, deadlines, billing and trust balances are cleared.');
            $closure=LawMatterClosure::updateOrCreate(
                ['business_id'=>EzyLawTenantGuard::businessId(),'matter_id'=>$matter->id],
                $data+['closed_by'=>auth()->id(),'created_by'=>auth()->id(),'trust_cleared'=>abs($blockers['trust_balance'])<=0.0001,'billing_cleared'=>abs($blockers['invoice_balance'])<=0.0001,'reopened_at'=>null,'reopened_by'=>null,'reopen_reason'=>null]
            );
            $matter->update(['status'=>'closed','closed_on'=>$data['closure_date']]);
            app(ActivityService::class)->log('close','matter',$matter->id,'Matter closed',['blockers'=>$blockers,'forced'=>$force]);
            return $closure;
        });
    }

    public function reopen(LawMatterClosure $closure, string $reason): void
    {
        DB::transaction(function() use($closure,$reason){
            $closure->update(['reopened_at'=>now(),'reopened_by'=>auth()->id(),'reopen_reason'=>$reason]);
            $closure->matter()->update(['status'=>'open','closed_on'=>null]);
            app(ActivityService::class)->log('reopen','matter',$closure->matter_id,'Matter reopened',['reason'=>$reason]);
        });
    }
}

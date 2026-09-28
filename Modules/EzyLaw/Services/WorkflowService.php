<?php
namespace Modules\EzyLaw\Services;
use Illuminate\Support\Facades\DB;
use Modules\EzyLaw\Entities\{LawMatter,LawWorkflowTemplate,LawWorkflowStage,LawMatterStageHistory};
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class WorkflowService {
    public function start(LawMatter $matter, LawWorkflowStage $stage, array $data=[]): LawMatterStageHistory {
        return DB::transaction(function() use($matter,$stage,$data){
            LawMatterStageHistory::where('matter_id',$matter->id)->where('status','active')->update(['status'=>'superseded','completed_at'=>now()]);
            $due=$data['due_at']??($stage->default_days ? now()->addDays((int)$stage->default_days) : null);
            $row=LawMatterStageHistory::create([
                'business_id'=>EzyLawTenantGuard::businessId(),'matter_id'=>$matter->id,'workflow_stage_id'=>$stage->id,
                'stage_name'=>$stage->name,'status'=>'active','started_at'=>now(),'due_at'=>$due,'notes'=>$data['notes']??null,'created_by'=>auth()->id()
            ]);
            app(ActivityService::class)->log('workflow_stage','matter',$matter->id,'Matter stage started',['stage_id'=>$stage->id,'stage_name'=>$stage->name]);
            return $row;
        });
    }
    public function complete(LawMatterStageHistory $history, ?string $notes=null): void {
        $history->update(['status'=>'completed','completed_at'=>now(),'notes'=>$notes ?: $history->notes]);
        app(ActivityService::class)->log('workflow_stage_complete','matter',$history->matter_id,'Matter stage completed',['history_id'=>$history->id]);
    }
    public function currentForMatter(int $matterId){return LawMatterStageHistory::where('matter_id',$matterId)->where('status','active')->latest('started_at')->first();}
}

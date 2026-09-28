<?php
namespace Modules\EzyLaw\Services;
use Modules\EzyLaw\Entities\LawDeadline;
use Modules\EzyLaw\Utilities\{EzyLawTenantGuard,EzyLawReferenceGuard};
class DeadlineService {
    public function create(array $data): LawDeadline {
        if(!empty($data['client_id'])) EzyLawReferenceGuard::client($data['client_id']);
        if(!empty($data['matter_id'])) {
            if(!empty($data['client_id'])) EzyLawReferenceGuard::matterForClient($data['matter_id'],$data['client_id']);
            else EzyLawReferenceGuard::matter($data['matter_id']);
        }
        $row=LawDeadline::create($data+['business_id'=>EzyLawTenantGuard::businessId(),'status'=>'open','created_by'=>auth()->id()]);
        app(ActivityService::class)->log('create','deadline',$row->id,'Legal deadline created',['matter_id'=>$row->matter_id,'due_at'=>$row->due_at]);
        return $row;
    }
    public function complete(LawDeadline $deadline): void {$deadline->update(['status'=>'completed','completed_at'=>now()]);}
}

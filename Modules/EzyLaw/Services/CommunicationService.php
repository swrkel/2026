<?php
namespace Modules\EzyLaw\Services;
use Modules\EzyLaw\Entities\{LawCommunication,LawIntegrationQueue,LawMatter};
use Modules\EzyLaw\Utilities\{EzyLawTenantGuard,EzyLawReferenceGuard};
class CommunicationService
{
    public function log(array $data): LawCommunication
    {
        if(!empty($data['client_id'])) EzyLawReferenceGuard::client($data['client_id']);
        if(!empty($data['matter_id'])) {
            if(!empty($data['client_id'])) EzyLawReferenceGuard::matterForClient($data['matter_id'], $data['client_id']);
            else EzyLawReferenceGuard::matter($data['matter_id']);
        }
        $data['sent_at']=$data['sent_at']??($data['status']==='sent'?now():null);
        $row=LawCommunication::create($data+['business_id'=>EzyLawTenantGuard::businessId(),'created_by'=>auth()->id()]);
        if(($data['status']??'logged')==='queued'){
            LawIntegrationQueue::create(['business_id'=>EzyLawTenantGuard::businessId(),'target_module'=>'communication','event_type'=>'communication.send','aggregate_type'=>'communication','aggregate_id'=>$row->id,'payload_json'=>json_encode(['channel'=>$row->channel,'recipient'=>$row->recipient,'subject'=>$row->subject,'body'=>$row->body]),'status'=>'pending','attempts'=>0]);
        }
        app(ActivityService::class)->log('create','communication',$row->id,'Client communication logged');
        return $row;
    }
}

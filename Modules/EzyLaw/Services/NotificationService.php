<?php
namespace Modules\EzyLaw\Services;
use Modules\EzyLaw\Entities\LawNotification;
use Modules\EzyLaw\Utilities\{EzyLawTenantGuard,EzyLawReferenceGuard};
class NotificationService {
    public function queue(array $data): LawNotification {
        if(!empty($data['client_id'])) EzyLawReferenceGuard::client($data['client_id']);
        if(!empty($data['matter_id'])) {
            if(!empty($data['client_id'])) EzyLawReferenceGuard::matterForClient($data['matter_id'],$data['client_id']);
            else EzyLawReferenceGuard::matter($data['matter_id']);
        }
        return LawNotification::create($data+['business_id'=>EzyLawTenantGuard::businessId(),'status'=>'pending']);
    }
    public function dispatchDue(): int {
        $count=0;
        foreach(LawNotification::where('status','pending')->where(function($q){$q->whereNull('scheduled_at')->orWhere('scheduled_at','<=',now());})->limit(100)->get() as $n){
            if($n->channel==='in_app'){$n->update(['status'=>'sent','sent_at'=>now()]);$count++;continue;}
            $n->update(['status'=>'queued']); $count++;
        }
        return $count;
    }
}

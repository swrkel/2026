<?php
namespace Modules\EzyLaw\Services;
use Modules\EzyLaw\Entities\{LawRetainer,LawMatter};
use Modules\EzyLaw\Utilities\{EzyLawTenantGuard,EzyLawReferenceGuard};
class RetainerService
{
    public function create(array $data): LawRetainer
    {
        EzyLawReferenceGuard::client($data['client_id'] ?? null);
        if(!empty($data['matter_id'])) EzyLawReferenceGuard::matterForClient($data['matter_id'], $data['client_id']);
        $data['retainer_no']=$data['retainer_no'] ?? app(SettingsService::class)->nextNumber('retainer');
        $retainer=LawRetainer::create($data+['business_id'=>EzyLawTenantGuard::businessId(),'created_by'=>auth()->id()]);
        app(ActivityService::class)->log('create','retainer',$retainer->id,'Retainer agreement created');
        return $retainer;
    }
    public function close(LawRetainer $retainer): void
    {
        $retainer->update(['status'=>'closed','end_date'=>$retainer->end_date ?: now()->toDateString()]);
        app(ActivityService::class)->log('close','retainer',$retainer->id,'Retainer agreement closed');
    }
}

<?php
namespace Modules\EzyLaw\Services;
use Illuminate\Support\Str;
use Modules\EzyLaw\Entities\{LawPortalAccess,LawPortalMessage,LawMatter};
use Modules\EzyLaw\Utilities\{EzyLawTenantGuard, EzyLawReferenceGuard};
class PortalService {
    public function createAccess(array $data): array {
        EzyLawReferenceGuard::client($data['client_id'] ?? null);
        $plain=Str::random(48);
        $row=LawPortalAccess::updateOrCreate(
            ['business_id'=>EzyLawTenantGuard::businessId(),'client_id'=>$data['client_id'],'email'=>$data['email']],
            ['contact_id'=>$data['contact_id']??null,'token_hash'=>hash('sha256',$plain),'status'=>'active','expires_at'=>$data['expires_at']??null,'created_by'=>auth()->id()]
        );
        return ['access'=>$row,'token'=>$plain];
    }
    public function logMessage(array $data): LawPortalMessage {
        $clientId=(int)$data['client_id'];$matterId=!empty($data['matter_id'])?(int)$data['matter_id']:null;
        EzyLawReferenceGuard::client($clientId);
        if($matterId){EzyLawReferenceGuard::matterForClient($matterId,$clientId);}
        return LawPortalMessage::create($data+['business_id'=>EzyLawTenantGuard::businessId(),'status'=>'logged','sent_at'=>now(),'created_by'=>auth()->id()]);
    }
}

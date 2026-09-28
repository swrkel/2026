<?php
namespace Modules\EzyLaw\Services;

use Illuminate\Support\Str;
use Modules\EzyLaw\Entities\{LawDocument,LawDocumentApproval,LawEsignRequest};
use Modules\EzyLaw\Utilities\{EzyLawTenantGuard, EzyLawReferenceGuard};

class DocumentApprovalService
{
    public function requestApproval(LawDocument $document, int $approverUserId, ?int $versionNo=null, ?string $comments=null): LawDocumentApproval
    {
        return LawDocumentApproval::create(['business_id'=>EzyLawTenantGuard::businessId(),'document_id'=>$document->id,'matter_id'=>$document->matter_id,
            'requested_by'=>auth()->id(),'approver_user_id'=>$approverUserId,'version_no'=>$versionNo?:$document->version,'status'=>'pending','requested_at'=>now(),'comments'=>$comments]);
    }
    public function respond(LawDocumentApproval $approval,string $status,?string $comments=null): LawDocumentApproval
    {
        if(!in_array($status,['approved','rejected','changes_requested'],true)) throw new \InvalidArgumentException('Invalid approval response.');
        $approval->update(['status'=>$status,'responded_at'=>now(),'comments'=>$comments]); return $approval->fresh();
    }
    public function requestEsign(LawDocument $document,array $data): array
    {
        if (!empty($data['client_id'])) {
            EzyLawReferenceGuard::assertDocumentClient($document, $data['client_id']);
        } elseif (!empty($document->client_id)) {
            EzyLawReferenceGuard::client($document->client_id);
        } elseif (!empty($document->matter_id)) {
            $matter = EzyLawReferenceGuard::matter($document->matter_id);
            $data['client_id'] = $matter->client_id;
        } else {
            throw new \RuntimeException('The document must be linked to a client or matter before requesting an e-signature.');
        }
        $plain=Str::random(48);
        $row=LawEsignRequest::create([
            'business_id'=>EzyLawTenantGuard::businessId(),'document_id'=>$document->id,'matter_id'=>$document->matter_id,'client_id'=>$data['client_id']??$document->client_id,
            'recipient_name'=>$data['recipient_name'],'recipient_email'=>$data['recipient_email'],'token_hash'=>hash('sha256',$plain),'version_no'=>$data['version_no']??$document->version,
            'status'=>'pending','requested_at'=>now(),'expires_at'=>$data['expires_at']??now()->addDays(14),'created_by'=>auth()->id(),
        ]);
        return ['request'=>$row,'token'=>$plain];
    }
}

<?php
namespace Modules\EzyLaw\Services;

use Illuminate\Http\Request;
use Modules\EzyLaw\Entities\{LawPortalAccess,LawPortalAuditLog,LawMatter,LawInvoice,LawPortalMessage,LawHearing,LawEsignRequest};

class PortalPublicService
{
    public function resolve(string $token): LawPortalAccess
    {
        $hash=hash('sha256',$token);
        $access=LawPortalAccess::withoutGlobalScopes()->where('token_hash',$hash)->where('status','active')->firstOrFail();
        if($access->expires_at && $access->expires_at->isPast()) abort(403,'Portal access has expired.');
        return $access;
    }
    public function matterIds(LawPortalAccess $access): array
    {
        return LawMatter::withoutGlobalScopes()->where('business_id',$access->business_id)->where('client_id',$access->client_id)->pluck('id')->map(fn($v)=>(int)$v)->all();
    }
    public function dashboard(LawPortalAccess $access): array
    {
        $matterIds=$this->matterIds($access);
        return [
            'client'=>$access->client()->withoutGlobalScopes()->first(),
            'matters'=>LawMatter::withoutGlobalScopes()->where('business_id',$access->business_id)->where('client_id',$access->client_id)->orderByDesc('id')->get(),
            'invoices'=>LawInvoice::withoutGlobalScopes()->where('business_id',$access->business_id)->where('client_id',$access->client_id)->orderByDesc('invoice_date')->limit(50)->get(),
            'hearings'=>LawHearing::withoutGlobalScopes()->where('business_id',$access->business_id)->whereIn('matter_id',$matterIds?:[0])->where('hearing_at','>=',now())->orderBy('hearing_at')->limit(20)->get(),
            'messages'=>LawPortalMessage::withoutGlobalScopes()->where('business_id',$access->business_id)->where('client_id',$access->client_id)->orderByDesc('id')->limit(100)->get(),
            'esign'=>LawEsignRequest::withoutGlobalScopes()->where('business_id',$access->business_id)->where('client_id',$access->client_id)->where('recipient_email',$access->email)->whereIn('status',['pending','viewed'])->orderByDesc('id')->get(),
        ];
    }
    public function validateMatter(LawPortalAccess $access, ?int $matterId): ?int
    {
        if(!$matterId) return null;
        $ok=LawMatter::withoutGlobalScopes()->where('business_id',$access->business_id)->where('client_id',$access->client_id)->whereKey($matterId)->exists();
        if(!$ok) abort(422,'The selected matter does not belong to this portal client.');
        return $matterId;
    }
    public function audit(LawPortalAccess $access,string $action,Request $request,?int $matterId=null,array $metadata=[]): void
    {
        LawPortalAuditLog::withoutGlobalScopes()->create(['business_id'=>$access->business_id,'client_id'=>$access->client_id,'portal_access_id'=>$access->id,'matter_id'=>$matterId,'action'=>$action,'ip_address'=>$request->ip(),'user_agent'=>substr((string)$request->userAgent(),0,1000),'metadata_json'=>$metadata?json_encode($metadata):null,'created_at'=>now()]);
    }
}

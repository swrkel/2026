<?php
namespace Modules\EggManagement\Services;
use Illuminate\Support\Str;
use Modules\EggManagement\Models\ShareLink;
class ShareService
{
    protected $context; public function __construct(EggContext $context){$this->context=$context;}
    public function create($resourceType,$resourceId,$format='html',array $parameters=[])
    {
        $token=Str::random(64);$hash=hash('sha256',$token);
        ShareLink::create(['business_id'=>$this->context->businessId(),'token_hash'=>$hash,'resource_type'=>$resourceType,'resource_id'=>$resourceId,'format'=>$format,'parameters'=>$parameters,'expires_at'=>now()->addHours(config('egg.sharing.link_expiry_hours',168)),'created_by'=>$this->context->userId()]);
        return route('egg.share.public',['token'=>$token]);
    }
    public function resolve($token){return ShareLink::where('token_hash',hash('sha256',$token))->where('expires_at','>',now())->firstOrFail();}
}

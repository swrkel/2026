<?php
namespace Modules\EggManagement\Services;
use Modules\EggManagement\Models\AuditLog;
class AuditService
{
    protected $context;
    public function __construct(EggContext $context){$this->context=$context;}
    public function log($action,$type,$id,$before=null,$after=null)
    {
        AuditLog::create(['business_id'=>$this->context->businessId(),'user_id'=>$this->context->userId(),'action'=>$action,'auditable_type'=>$type,'auditable_id'=>$id,'before_data'=>$before,'after_data'=>$after,'ip_address'=>request()->ip(),'user_agent'=>substr((string)request()->userAgent(),0,500)]);
    }
}

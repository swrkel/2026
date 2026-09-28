<?php
namespace Modules\Audit\Rules\UserManagement;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Services\Adapters\UserManagementAdapter;
use Modules\Audit\Services\AuditContext;
use Modules\Audit\Support\BaseAuditRule;

class InactiveUserRoleRule extends BaseAuditRule
{
    protected $module='User Management'; protected $severity='warning'; protected $description='Flags inactive users that still retain role assignments when compatible user status columns exist.';
    protected $adapter;
    public function __construct(UserManagementAdapter $adapter){$this->adapter=$adapter;}
    public function code(): string{return 'USR-ROL-002';}
    public function title(): string{return 'Inactive user still has role';}
    public function supports(AuditContext $context): bool { $u=$this->adapter->users();$a=$this->adapter->assignments(); return $u&&$a&&$this->adapter->hasColumn($a,'model_id')&&($this->adapter->hasColumn($u,'is_active')||$this->adapter->hasColumn($u,'status')); }
    public function run(AuditContext $context): array
    {
        $u=$this->adapter->users();$a=$this->adapter->assignments();$select=['u.id'];
        if($this->adapter->hasColumn($u,'business_id'))$select[]='u.business_id';
        if($this->adapter->hasColumn($u,'location_id'))$select[]='u.location_id';
        $q=DB::table($a.' as ar')->join($u.' as u','u.id','=','ar.model_id')->select($select);
        if($this->adapter->hasColumn($a,'model_type'))$q->where('ar.model_type','like','%User%');
        if($this->adapter->hasColumn($u,'is_active'))$q->where('u.is_active',0);else $q->whereRaw('LOWER(u.status) IN (?, ?)', ['inactive','disabled']);
        if($context->businessId&&$this->adapter->hasColumn($u,'business_id'))$q->where('u.business_id',$context->businessId);
        if($context->locationId&&$this->adapter->hasColumn($u,'location_id'))$q->where('u.location_id',$context->locationId);
        return $q->distinct()->limit(1000)->get()->map(function($r)use($u,$context){
            $finding=$this->finding($u,$r->id,'Inactive user retains role assignment','User #'.$r->id.' is inactive but still has at least one role assignment.','No active role assignment','Role assignment exists');
            return $this->scopeFinding($finding,$r->business_id??$context->businessId,$r->location_id??$context->locationId);
        })->all();
    }
}

<?php
namespace Modules\Audit\Rules\UserManagement;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Services\Adapters\UserManagementAdapter;
use Modules\Audit\Services\AuditContext;
use Modules\Audit\Support\BaseAuditRule;

class OrphanRoleAssignmentRule extends BaseAuditRule
{
    protected $module='User Management'; protected $severity='high'; protected $description='Detects user role assignments referencing a missing role.';
    protected $adapter;
    public function __construct(UserManagementAdapter $adapter){$this->adapter=$adapter;}
    public function code(): string{return 'USR-ROL-001';}
    public function title(): string{return 'Orphan role assignment';}
    public function supports(AuditContext $context): bool { $a=$this->adapter->assignments();$r=$this->adapter->roles(); return $a&&$r&&$this->adapter->hasColumn($a,'role_id')&&$this->adapter->hasColumn($r,'id'); }
    public function run(AuditContext $context): array
    {
        $a=$this->adapter->assignments();$r=$this->adapter->roles();$u=$this->adapter->users();
        $select=['ar.role_id'];
        $q=DB::table($a.' as ar')->leftJoin($r.' as r','r.id','=','ar.role_id')->whereNull('r.id');
        if($this->adapter->hasColumn($a,'model_id'))$select[]='ar.model_id';
        $hasUserJoin=$u&&$this->adapter->hasColumn($a,'model_id')&&$this->adapter->hasColumn($u,'id');
        if($hasUserJoin){
            $q->leftJoin($u.' as u','u.id','=','ar.model_id');
            if($this->adapter->hasColumn($u,'business_id'))$select[]='u.business_id';
            if($this->adapter->hasColumn($u,'location_id'))$select[]='u.location_id';
            if($this->adapter->hasColumn($a,'model_type'))$q->where('ar.model_type','like','%User%');
            if($context->businessId&&$this->adapter->hasColumn($u,'business_id'))$q->where('u.business_id',$context->businessId);
            if($context->locationId&&$this->adapter->hasColumn($u,'location_id'))$q->where('u.location_id',$context->locationId);
        }
        $q->select($select);
        return $q->limit(1000)->get()->map(function($x)use($a,$context){
            $sid=isset($x->model_id)?$x->model_id:$x->role_id;
            $finding=$this->finding($a,$sid,'Role assignment has missing role','A user/role assignment references missing role #'.$x->role_id,'Existing role','Missing role',['role_id'=>$x->role_id]);
            return $this->scopeFinding($finding,$x->business_id??$context->businessId,$x->location_id??$context->locationId);
        })->all();
    }
}

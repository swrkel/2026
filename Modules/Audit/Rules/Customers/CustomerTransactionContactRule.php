<?php
namespace Modules\Audit\Rules\Customers;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Services\Adapters\CustomerAdapter;
use Modules\Audit\Services\AuditContext;
use Modules\Audit\Support\BaseAuditRule;

class CustomerTransactionContactRule extends BaseAuditRule
{
    protected $module='Customers'; protected $severity='high'; protected $description='Detects customer sales linked to missing or non-customer contacts.';
    protected $adapter;
    public function __construct(CustomerAdapter $adapter){$this->adapter=$adapter;}
    public function code(): string{return 'CUS-CON-001';}
    public function title(): string{return 'Invalid customer transaction contact';}
    public function supports(AuditContext $context): bool { $t=$this->adapter->transactions();$c=$this->adapter->contacts(); return $t&&$c&&$this->adapter->hasColumn($t,'contact_id')&&$this->adapter->hasColumn($c,'id')&&$this->adapter->hasColumn($c,'type')&&$this->adapter->hasColumn($t,'type'); }
    public function run(AuditContext $context): array
    {
        $t=$this->adapter->transactions();$c=$this->adapter->contacts();
        $select=['t.id','t.contact_id','c.type as contact_type'];
        if($this->adapter->hasColumn($t,'business_id'))$select[]='t.business_id';
        if($this->adapter->hasColumn($t,'location_id'))$select[]='t.location_id';
        $q=DB::table($t.' as t')->leftJoin($c.' as c','c.id','=','t.contact_id')->whereIn('t.type',['sell','sales'])->where(function($x){$x->whereNull('c.id')->orWhereNotIn('c.type',['customer','both']);})->select($select);
        $q=$this->adapter->scopeContext($q,$t,$context,'t');
        return $q->limit(1000)->get()->map(function($r)use($t,$context){
            $finding=$this->finding($t,$r->id,'Sale has invalid customer link','Sale transaction #'.$r->id.' is not linked to a valid customer contact.','Customer contact','Contact '.($r->contact_id?:'NULL').' / type '.($r->contact_type?:'missing'),['contact_id'=>$r->contact_id]);
            return $this->scopeFinding($finding,$r->business_id??$context->businessId,$r->location_id??$context->locationId);
        })->all();
    }
}

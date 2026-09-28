<?php
namespace Modules\Audit\Rules\Suppliers;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Services\Adapters\SupplierAdapter;
use Modules\Audit\Services\AuditContext;
use Modules\Audit\Support\BaseAuditRule;

class SupplierTransactionContactRule extends BaseAuditRule
{
    protected $module='Suppliers'; protected $severity='high'; protected $description='Detects purchase transactions linked to missing or non-supplier contacts.';
    protected $adapter;
    public function __construct(SupplierAdapter $adapter){$this->adapter=$adapter;}
    public function code(): string{return 'SUP-CON-001';}
    public function title(): string{return 'Invalid supplier transaction contact';}
    public function supports(AuditContext $context): bool { $t=$this->adapter->transactions();$c=$this->adapter->contacts(); return $t&&$c&&$this->adapter->hasColumn($t,'contact_id')&&$this->adapter->hasColumn($c,'id')&&$this->adapter->hasColumn($c,'type')&&$this->adapter->hasColumn($t,'type'); }
    public function run(AuditContext $context): array
    {
        $t=$this->adapter->transactions();$c=$this->adapter->contacts();
        $select=['t.id','t.contact_id','c.type as contact_type'];
        if($this->adapter->hasColumn($t,'business_id'))$select[]='t.business_id';
        if($this->adapter->hasColumn($t,'location_id'))$select[]='t.location_id';
        $q=DB::table($t.' as t')->leftJoin($c.' as c','c.id','=','t.contact_id')->where('t.type','purchase')->where(function($x){$x->whereNull('c.id')->orWhereNotIn('c.type',['supplier','both']);})->select($select);
        $q=$this->adapter->scopeContext($q,$t,$context,'t');
        return $q->limit(1000)->get()->map(function($r)use($t,$context){
            $finding=$this->finding($t,$r->id,'Purchase has invalid supplier link','Purchase transaction #'.$r->id.' is not linked to a valid supplier contact.','Supplier contact','Contact '.($r->contact_id?:'NULL').' / type '.($r->contact_type?:'missing'),['contact_id'=>$r->contact_id]);
            return $this->scopeFinding($finding,$r->business_id??$context->businessId,$r->location_id??$context->locationId);
        })->all();
    }
}

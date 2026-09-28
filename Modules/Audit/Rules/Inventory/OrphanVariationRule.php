<?php
namespace Modules\Audit\Rules\Inventory;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Services\Adapters\InventoryAdapter;
use Modules\Audit\Services\AuditContext;
use Modules\Audit\Support\BaseAuditRule;

class OrphanVariationRule extends BaseAuditRule
{
    protected $module='Inventory'; protected $severity='high'; protected $description='Detects product variations with no parent product.';
    protected $adapter;
    public function __construct(InventoryAdapter $adapter){$this->adapter=$adapter;}
    public function code(): string{return 'INV-VAR-001';}
    public function title(): string{return 'Orphan product variation';}
    public function supports(AuditContext $context): bool { $v=$this->adapter->variations();$p=$this->adapter->products(); return $v&&$p&&$this->adapter->hasColumn($v,'product_id')&&$this->adapter->hasColumn($p,'id'); }
    public function run(AuditContext $context): array
    {
        $v=$this->adapter->variations();$p=$this->adapter->products();$select=['v.id','v.product_id'];
        if($this->adapter->hasColumn($v,'business_id'))$select[]='v.business_id';
        if($this->adapter->hasColumn($v,'location_id'))$select[]='v.location_id';
        $q=DB::table($v.' as v')->leftJoin($p.' as p','p.id','=','v.product_id')->whereNull('p.id')->select($select);
        $q=$this->adapter->scopeContext($q,$v,$context,'v');
        return $q->limit(1000)->get()->map(function($r)use($v,$context){
            $finding=$this->finding($v,$r->id,'Variation without product','Variation #'.$r->id.' references missing product #'.$r->product_id,'Existing product','Missing product',['product_id'=>$r->product_id]);
            return $this->scopeFinding($finding,$r->business_id??$context->businessId,$r->location_id??$context->locationId);
        })->all();
    }
}

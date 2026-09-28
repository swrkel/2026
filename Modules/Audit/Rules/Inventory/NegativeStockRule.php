<?php
namespace Modules\Audit\Rules\Inventory;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Services\Adapters\InventoryAdapter;
use Modules\Audit\Services\AuditContext;
use Modules\Audit\Support\BaseAuditRule;

class NegativeStockRule extends BaseAuditRule
{
    protected $module='Inventory'; protected $severity='warning'; protected $description='Detects negative quantity in the location stock table.';
    protected $adapter;
    public function __construct(InventoryAdapter $adapter){$this->adapter=$adapter;}
    public function code(): string{return 'INV-STK-001';}
    public function title(): string{return 'Negative location stock';}
    public function supports(AuditContext $context): bool { $t=$this->adapter->locationStock(); return $t&&$this->adapter->hasColumn($t,'id')&&$this->adapter->hasColumn($t,'qty_available'); }
    public function run(AuditContext $context): array
    {
        $t=$this->adapter->locationStock();$select=['id','qty_available'];
        if($this->adapter->hasColumn($t,'business_id'))$select[]='business_id';
        if($this->adapter->hasColumn($t,'location_id'))$select[]='location_id';
        $q=DB::table($t)->where('qty_available','<',0)->select($select);$q=$this->adapter->scopeContext($q,$t,$context);
        return $q->limit(1000)->get()->map(function($r)use($t,$context){
            $finding=$this->finding($t,$r->id,'Negative stock detected','Location stock row #'.$r->id.' has negative available quantity.','>= 0',(string)$r->qty_available);
            return $this->scopeFinding($finding,$r->business_id??$context->businessId,$r->location_id??$context->locationId);
        })->all();
    }
}

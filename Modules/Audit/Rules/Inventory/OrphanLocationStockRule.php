<?php
namespace Modules\Audit\Rules\Inventory;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Services\Adapters\InventoryAdapter;
use Modules\Audit\Services\AuditContext;
use Modules\Audit\Support\BaseAuditRule;

class OrphanLocationStockRule extends BaseAuditRule
{
    protected $module='Inventory'; protected $severity='high'; protected $description='Detects stock rows with a missing variation.';
    protected $adapter;
    public function __construct(InventoryAdapter $adapter){$this->adapter=$adapter;}
    public function code(): string{return 'INV-STK-002';}
    public function title(): string{return 'Orphan location stock row';}
    public function supports(AuditContext $context): bool { $s=$this->adapter->locationStock();$v=$this->adapter->variations(); return $s&&$v&&$this->adapter->hasColumn($s,'variation_id')&&$this->adapter->hasColumn($v,'id'); }
    public function run(AuditContext $context): array
    {
        $s=$this->adapter->locationStock();$v=$this->adapter->variations();$select=['s.id','s.variation_id'];
        if($this->adapter->hasColumn($s,'business_id'))$select[]='s.business_id';
        if($this->adapter->hasColumn($s,'location_id'))$select[]='s.location_id';
        $q=DB::table($s.' as s')->leftJoin($v.' as v','v.id','=','s.variation_id')->whereNull('v.id')->select($select);$q=$this->adapter->scopeContext($q,$s,$context,'s');
        return $q->limit(1000)->get()->map(function($r)use($s,$context){
            $finding=$this->finding($s,$r->id,'Stock row without variation','Stock row #'.$r->id.' references missing variation #'.$r->variation_id,'Existing variation','Missing variation',['variation_id'=>$r->variation_id]);
            return $this->scopeFinding($finding,$r->business_id??$context->businessId,$r->location_id??$context->locationId);
        })->all();
    }
}

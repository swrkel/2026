<?php
namespace Modules\Audit\Rules\Finance;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Services\Adapters\FinanceAdapter;
use Modules\Audit\Services\AuditContext;
use Modules\Audit\Support\BaseAuditRule;

class MissingAccountReferenceRule extends BaseAuditRule
{
    protected $module='Finance'; protected $severity='critical'; protected $description='Detects ledger rows linked to missing accounts.';
    protected $adapter;
    public function __construct(FinanceAdapter $adapter){$this->adapter=$adapter;}
    public function code(): string{return 'FIN-ACC-001';}
    public function title(): string{return 'Missing account reference';}
    public function supports(AuditContext $context): bool { $a=$this->adapter->accounts();$t=$this->adapter->accountTransactions();return $a&&$t&&$this->adapter->hasColumn($t,'account_id')&&$this->adapter->hasColumn($a,'id'); }
    public function run(AuditContext $context): array
    {
        $a=$this->adapter->accounts();$t=$this->adapter->accountTransactions();
        $select=['atx.id','atx.account_id'];
        if($this->adapter->hasColumn($t,'business_id'))$select[]='atx.business_id';
        if($this->adapter->hasColumn($t,'location_id'))$select[]='atx.location_id';
        $q=DB::table($t.' as atx')->leftJoin($a.' as a','a.id','=','atx.account_id')->whereNull('a.id')->select($select);
        $q=$this->adapter->scopeContext($q,$t,$context,'atx');
        return $q->limit(1000)->get()->map(function($r)use($t,$context){
            $finding=$this->finding($t,$r->id,'Ledger row has missing account','Account transaction #'.$r->id.' references account #'.$r->account_id.' which does not exist.','Existing account','Missing account',['account_id'=>$r->account_id]);
            return $this->scopeFinding($finding,$r->business_id??$context->businessId,$r->location_id??$context->locationId);
        })->all();
    }
}

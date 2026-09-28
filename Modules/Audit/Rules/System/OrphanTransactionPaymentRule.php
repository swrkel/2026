<?php
namespace Modules\Audit\Rules\System;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Services\AuditContext;
use Modules\Audit\Support\BaseAuditRule;

class OrphanTransactionPaymentRule extends BaseAuditRule
{
    protected $module='Cross Module'; protected $severity='critical';
    protected $description='Detects transaction payments that explicitly reference a transaction ID which no longer exists.';
    public function code(): string { return 'XMOD-PAY-001'; }
    public function title(): string { return 'Orphan transaction payment'; }
    public function supports(AuditContext $context): bool { return $this->hasColumns('transaction_payments',['id','transaction_id']) && $this->hasColumns('transactions',['id']); }

    public function run(AuditContext $context): array
    {
        $select=['p.id','p.transaction_id'];
        if($this->hasColumns('transaction_payments',['business_id']))$select[]='p.business_id';
        if($this->hasColumns('transaction_payments',['location_id']))$select[]='p.location_id';
        $q=DB::table('transaction_payments as p')
            ->leftJoin('transactions as t','t.id','=','p.transaction_id')
            ->whereNotNull('p.transaction_id')
            ->whereNull('t.id')
            ->select($select);

        if ($this->hasColumns('transaction_payments',['deleted_at'])) $q->whereNull('p.deleted_at');
        if ($this->hasColumns('transaction_payments',['new_deleted_at'])) $q->whereNull('p.new_deleted_at');
        if ($context->businessId && $this->hasColumns('transaction_payments',['business_id'])) $q->where('p.business_id',$context->businessId);
        if ($context->locationId && $this->hasColumns('transaction_payments',['location_id'])) $q->where('p.location_id',$context->locationId);

        return $q->limit(1000)->get()->map(function($r)use($context){
            $finding=$this->finding(
                'transaction_payments',
                $r->id,
                'Payment without parent transaction',
                'Payment #'.$r->id.' explicitly references missing transaction #'.$r->transaction_id,
                'Referenced transaction exists',
                'Referenced transaction is missing',
                ['transaction_id'=>$r->transaction_id]
            );
            return $this->scopeFinding($finding,$r->business_id??$context->businessId,$r->location_id??$context->locationId);
        })->all();
    }
}

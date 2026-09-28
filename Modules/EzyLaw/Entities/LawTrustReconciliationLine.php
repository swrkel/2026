<?php
namespace Modules\EzyLaw\Entities;
class LawTrustReconciliationLine extends LawModel {
    protected $table='law_trust_reconciliation_lines'; protected $guarded=['id']; protected $casts=['amount'=>'decimal:4','matched'=>'boolean'];
    public function reconciliation(){return $this->belongsTo(LawTrustReconciliation::class,'reconciliation_id');}
    public function transaction(){return $this->belongsTo(LawTrustTransaction::class,'trust_transaction_id');}
}

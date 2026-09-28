<?php
namespace Modules\EzyLaw\Entities;
class LawTrustReconciliation extends LawModel {
    protected $table='law_trust_reconciliations'; protected $guarded=['id'];
    protected $casts=['statement_date'=>'date','statement_balance'=>'decimal:4','book_balance'=>'decimal:4','difference'=>'decimal:4','reconciled_at'=>'datetime'];
    public function account(){return $this->belongsTo(LawTrustAccount::class,'trust_account_id');}
    public function lines(){return $this->hasMany(LawTrustReconciliationLine::class,'reconciliation_id');}
}

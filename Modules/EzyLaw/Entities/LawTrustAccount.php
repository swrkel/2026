<?php
namespace Modules\EzyLaw\Entities;
class LawTrustAccount extends LawModel
{
    protected $table='law_trust_accounts';
    protected $guarded=['id'];
    protected $casts=['current_balance'=>'decimal:4','active'=>'boolean'];
    public function transactions(){return $this->hasMany(LawTrustTransaction::class,'trust_account_id');}
    public function reconciliations(){return $this->hasMany(LawTrustReconciliation::class,'trust_account_id');}
}

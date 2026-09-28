<?php
namespace Modules\EzyLaw\Entities;
class LawTrustTransaction extends LawModel
{
    protected $table='law_trust_transactions';
    protected $guarded=['id'];
    protected $casts=['transaction_date'=>'date','amount'=>'decimal:4','running_balance'=>'decimal:4'];
    public function account(){return $this->belongsTo(LawTrustAccount::class,'trust_account_id');}
    public function client(){return $this->belongsTo(LawClient::class,'client_id');}
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
    public function invoice(){return $this->belongsTo(LawInvoice::class,'invoice_id');}
}

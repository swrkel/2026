<?php
namespace Modules\EzyLaw\Entities;
class LawClient extends LawModel
{
    protected $table='law_clients'; protected $guarded=['id'];
    protected $casts=[];
    public function matters(){ return $this->hasMany(LawMatter::class,'client_id'); }
    public function invoices(){ return $this->hasMany(LawInvoice::class,'client_id'); }
    public function retainers(){ return $this->hasMany(LawRetainer::class,'client_id'); }
    public function trustTransactions(){ return $this->hasMany(LawTrustTransaction::class,'client_id'); }
    public function communications(){ return $this->hasMany(LawCommunication::class,'client_id'); }
    public function reminders(){ return $this->hasMany(LawReminder::class,'client_id'); }
}

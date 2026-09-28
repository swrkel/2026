<?php
namespace Modules\EzyLaw\Entities;
class LawRetainer extends LawModel
{
    protected $table='law_retainers';
    protected $guarded=['id'];
    protected $casts=['agreement_date'=>'date','start_date'=>'date','end_date'=>'date','agreed_amount'=>'decimal:4','replenishment_threshold'=>'decimal:4'];
    public function client(){return $this->belongsTo(LawClient::class,'client_id');}
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
}

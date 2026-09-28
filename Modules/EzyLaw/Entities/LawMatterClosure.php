<?php
namespace Modules\EzyLaw\Entities;
class LawMatterClosure extends LawModel {
    protected $table='law_matter_closures'; protected $guarded=['id'];
    protected $casts=['closure_date'=>'date','reopened_at'=>'datetime','final_fee_amount'=>'decimal:4','client_notified'=>'boolean','documents_archived'=>'boolean','trust_cleared'=>'boolean','billing_cleared'=>'boolean'];
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
}

<?php
namespace Modules\EzyLaw\Entities;
class LawAdvanceAllocation extends LawModel {
    protected $table='law_advance_allocations'; protected $guarded=['id'];
    protected $casts=['allocated_on'=>'date','amount'=>'decimal:4'];
    public function advance(){return $this->belongsTo(LawClientAdvance::class,'advance_id');}
    public function invoice(){return $this->belongsTo(LawInvoice::class,'invoice_id');}
}

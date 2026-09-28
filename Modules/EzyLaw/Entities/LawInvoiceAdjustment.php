<?php
namespace Modules\EzyLaw\Entities;
class LawInvoiceAdjustment extends LawModel {
    protected $table='law_invoice_adjustments'; protected $guarded=['id']; protected $casts=['amount'=>'decimal:4'];
    public function invoice(){return $this->belongsTo(LawInvoice::class,'invoice_id');}
}

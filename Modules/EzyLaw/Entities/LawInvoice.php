<?php
namespace Modules\EzyLaw\Entities;
class LawInvoice extends LawModel
{
    protected $table='law_invoices'; protected $guarded=['id'];
    protected $casts=['invoice_date'=>'date','due_date'=>'date','subtotal'=>'decimal:4','tax_amount'=>'decimal:4','discount_amount'=>'decimal:4','total'=>'decimal:4','paid_amount'=>'decimal:4','balance'=>'decimal:4'];
    public function client(){ return $this->belongsTo(LawClient::class,'client_id'); }
    public function matter(){ return $this->belongsTo(LawMatter::class,'matter_id'); }
    public function lines(){ return $this->hasMany(LawInvoiceLine::class,'invoice_id'); }
    public function payments(){ return $this->hasMany(LawPayment::class,'invoice_id'); }
    public function adjustments(){ return $this->hasMany(LawInvoiceAdjustment::class,'invoice_id'); }
    public function advanceAllocations(){return $this->hasMany(LawAdvanceAllocation::class,'invoice_id');}
}

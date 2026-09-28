<?php
namespace Modules\EzyLaw\Entities;
class LawFeeEstimate extends LawModel {
    protected $table='law_fee_estimates'; protected $guarded=['id'];
    protected $casts=['estimate_date'=>'date','valid_until'=>'date','accepted_at'=>'datetime','subtotal'=>'decimal:4','tax_amount'=>'decimal:4','discount_amount'=>'decimal:4','total'=>'decimal:4'];
    public function client(){return $this->belongsTo(LawClient::class,'client_id');}
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
    public function invoice(){return $this->belongsTo(LawInvoice::class,'invoice_id');}
    public function lines(){return $this->hasMany(LawFeeEstimateLine::class,'estimate_id')->orderBy('sort_order');}
}

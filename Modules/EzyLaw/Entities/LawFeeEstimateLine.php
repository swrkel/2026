<?php
namespace Modules\EzyLaw\Entities;
class LawFeeEstimateLine extends LawModel {
    protected $table='law_fee_estimate_lines'; protected $guarded=['id'];
    protected $casts=['qty'=>'decimal:4','unit_price'=>'decimal:4','tax_rate'=>'decimal:4','line_total'=>'decimal:4'];
    public function estimate(){return $this->belongsTo(LawFeeEstimate::class,'estimate_id');}
}

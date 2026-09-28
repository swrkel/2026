<?php
namespace Modules\EzyLaw\Entities;
class LawBillingRate extends LawModel {
    protected $table='law_billing_rates'; protected $guarded=['id'];
    protected $casts=['hourly_rate'=>'decimal:4','fixed_rate'=>'decimal:4','effective_from'=>'date','effective_to'=>'date','active'=>'boolean'];
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
    public function practiceArea(){return $this->belongsTo(LawPracticeArea::class,'practice_area_id');}
}

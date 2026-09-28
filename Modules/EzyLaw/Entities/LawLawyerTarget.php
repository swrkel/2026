<?php
namespace Modules\EzyLaw\Entities;
class LawLawyerTarget extends LawModel {
    protected $table='law_lawyer_targets'; protected $guarded=['id'];
    protected $casts=['period_start'=>'date','period_end'=>'date','target_hours'=>'decimal:2','target_billing'=>'decimal:4','target_collections'=>'decimal:4','cost_rate'=>'decimal:4'];
}

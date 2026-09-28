<?php

namespace Modules\PetroPD\Entities;

use Illuminate\Database\Eloquent\Model;

class PetroPdPumpOperatorOtherSale extends Model
{
    protected $table = 'pump_operator_other_sales';
    protected $guarded = ['id'];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForShift($query, int $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }
}

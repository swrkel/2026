<?php

namespace Modules\PetroPD\Entities;

use Illuminate\Database\Eloquent\Model;

class PetroPdPumpOperatorAssignment extends Model
{
    protected $table = 'pump_operator_assignments';
    protected $guarded = ['id'];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForShift($query, int $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    public function scopePendingSettlement($query)
    {
        return $query->where(function ($q) {
                $q->whereNull('settlement_id')->orWhere('settlement_id', 0);
            })
            ->where(function ($q) {
                $q->whereNull('closed_in_settlement')->orWhere('closed_in_settlement', 0);
            });
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'close');
    }
}

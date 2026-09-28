<?php

namespace Modules\PetroPD\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PetroPdPumpOperatorPayment extends Model
{
    use SoftDeletes;

    protected $table = 'pump_operator_payments';
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
                $q->whereNull('is_used')->orWhere('is_used', 0);
            })
            ->where(function ($q) {
                $q->whereNull('settlement_no')->orWhere('settlement_no', '');
            });
    }
}

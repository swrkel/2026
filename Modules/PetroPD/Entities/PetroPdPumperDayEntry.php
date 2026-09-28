<?php

namespace Modules\PetroPD\Entities;

use Illuminate\Database\Eloquent\Model;

class PetroPdPumperDayEntry extends Model
{
    protected $table = 'pumper_day_entries';
    protected $guarded = ['id'];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForShiftAssignments($query, array $assignmentIds)
    {
        return $query->whereIn('pumper_assignment_id', $assignmentIds);
    }

    public function scopePendingSettlement($query)
    {
        return $query->where(function ($q) {
                $q->whereNull('settlement_no')->orWhere('settlement_no', '');
            })
            ->where(function ($q) {
                $q->whereNull('closed_in_settlement')->orWhere('closed_in_settlement', 0);
            });
    }
}

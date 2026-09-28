<?php

namespace Modules\Poultry\Entities;

/**
 * One row per batch per day. The unique key on (batch_id, record_date) is
 * intentional - staff re-submitting the same day must update, never duplicate.
 * Backdated corrections are expected, so every derived KPI is recomputed from
 * these rows rather than incrementally accumulated.
 */
class DailyRecord extends PoultryModel
{
    protected $table = 'poultry_daily_records';

    protected $dates = ['record_date'];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function scopeBetween($query, $from, $to)
    {
        return $query->whereBetween('record_date', [$from, $to]);
    }

    public function getTotalLossAttribute()
    {
        return (int) $this->mortality + (int) $this->culls;
    }

    /** Daily mortality as a percentage of the head count at the start of day. */
    public function mortalityPct($openingQty)
    {
        if ($openingQty <= 0) {
            return 0.0;
        }

        return round(($this->total_loss / $openingQty) * 100, 3);
    }
}

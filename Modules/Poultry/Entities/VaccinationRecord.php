<?php

namespace Modules\Poultry\Entities;

use Modules\Poultry\Entities\Shared\Variation;

class VaccinationRecord extends PoultryModel
{
    protected $table = 'poultry_vaccination_records';

    protected $dates = ['administered_on'];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function schedule()
    {
        return $this->belongsTo(VaccinationSchedule::class, 'schedule_id');
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class, 'variation_id');
    }

    /** Coverage as a percentage of the head count at the time. */
    public function coveragePct($headCount)
    {
        if ($headCount <= 0) {
            return null;
        }

        return round(($this->birds_covered / $headCount) * 100, 1);
    }
}

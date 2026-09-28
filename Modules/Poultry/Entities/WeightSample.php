<?php

namespace Modules\Poultry\Entities;

class WeightSample extends PoultryModel
{
    protected $table = 'poultry_weight_samples';

    protected $dates = ['sample_date'];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    /** Actual average as a percentage of the breed standard for this age. */
    public function getPctOfTargetAttribute()
    {
        if (empty($this->target_weight_g) || $this->target_weight_g <= 0) {
            return null;
        }

        return round(($this->avg_weight_g / $this->target_weight_g) * 100, 1);
    }

    /**
     * Uniformity band. Below 10 percent CV is good, 10-15 acceptable, above
     * that the flock is splitting and needs grading.
     */
    public function getUniformityBandAttribute()
    {
        if ($this->uniformity_cv === null) {
            return null;
        }

        if ($this->uniformity_cv < 10) {
            return 'good';
        }

        return $this->uniformity_cv <= 15 ? 'acceptable' : 'poor';
    }
}

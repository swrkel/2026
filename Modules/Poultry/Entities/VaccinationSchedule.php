<?php

namespace Modules\Poultry\Entities;

/**
 * A template row - "Newcastle at day 7 by eye drop". Applied to a batch to
 * generate its due list. Null breed_id means the row applies to every breed of
 * the given bird_type.
 */
class VaccinationSchedule extends PoultryModel
{
    protected $table = 'poultry_vaccination_schedules';

    protected $casts = ['is_mandatory' => 'boolean', 'is_active' => 'boolean'];

    public const ROUTES = [
        'drinking_water' => 'Drinking water',
        'eye_drop'       => 'Eye drop',
        'spray'          => 'Spray',
        'injection_sc'   => 'Injection (subcutaneous)',
        'injection_im'   => 'Injection (intramuscular)',
        'wing_web'       => 'Wing web',
        'beak_dip'       => 'Beak dip',
    ];

    public function breed()
    {
        return $this->belongsTo(Breed::class, 'breed_id');
    }

    public function records()
    {
        return $this->hasMany(VaccinationRecord::class, 'schedule_id');
    }

    /** Schedule rows applicable to a given batch, in age order. */
    public function scopeForBatch($query, Batch $batch)
    {
        return $query->active()
            ->where(function ($q) use ($batch) {
                $q->where('bird_type', $batch->bird_type)->orWhere('bird_type', 'all');
            })
            ->where(function ($q) use ($batch) {
                $q->whereNull('breed_id')->orWhere('breed_id', $batch->breed_id);
            })
            ->orderBy('age_days');
    }
}

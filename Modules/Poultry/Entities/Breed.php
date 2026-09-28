<?php

namespace Modules\Poultry\Entities;

class Breed extends PoultryModel
{
    protected $table = 'poultry_breeds';

    protected $casts = ['is_active' => 'boolean'];

    public const BIRD_TYPES = [
        'broiler'      => 'Broiler',
        'layer'        => 'Layer',
        'breeder'      => 'Breeder',
        'dual_purpose' => 'Dual purpose',
    ];

    public function batches()
    {
        return $this->hasMany(Batch::class, 'breed_id');
    }

    /**
     * The breed standard, decoded. Stored as JSON text so a hatchery's
     * published curve can be loaded without a schema change.
     */
    public function getCurveAttribute()
    {
        if (empty($this->standard_curve)) {
            return [];
        }

        $decoded = json_decode($this->standard_curve, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** Target live weight in grams at a given age in days, or null. */
    public function targetWeightAt($ageDays)
    {
        $weights = $this->curve['weights'] ?? [];

        if (empty($weights)) {
            return null;
        }

        // Nearest published age at or below the requested one.
        $ages = array_map('intval', array_keys($weights));
        sort($ages);

        $match = null;
        foreach ($ages as $age) {
            if ($age <= $ageDays) {
                $match = $age;
            }
        }

        return $match === null ? null : (float) $weights[(string) $match];
    }

    /** Target hen-day production percentage at a given week of lay. */
    public function targetHenDayAt($weekOfLay)
    {
        $henDay = $this->curve['hen_day'] ?? [];

        return isset($henDay[(string) $weekOfLay]) ? (float) $henDay[(string) $weekOfLay] : null;
    }

    public function getTargetFcrAttribute()
    {
        return $this->curve['target_fcr'] ?? null;
    }

    public static function dropdown($birdType = null, $businessId = null)
    {
        $query = static::query()->forBusiness($businessId)->active();

        if ($birdType) {
            $query->where('bird_type', $birdType);
        }

        return $query->orderBy('name')->pluck('name', 'id')->toArray();
    }
}

<?php

namespace Modules\Poultry\Entities;

class House extends PoultryModel
{
    protected $table = 'poultry_houses';

    protected $casts = ['is_active' => 'boolean'];

    public const TYPES = [
        'deep_litter' => 'Deep litter',
        'cage'        => 'Cage',
        'free_range'  => 'Free range',
        'slatted'     => 'Slatted',
        'breeder'     => 'Breeder',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id');
    }

    public function batches()
    {
        return $this->hasMany(Batch::class, 'house_id');
    }

    /** The batch currently occupying this house, if any. */
    public function activeBatch()
    {
        return $this->hasOne(Batch::class, 'house_id')->where('status', 'active');
    }

    public function scopeVacant($query)
    {
        return $query->whereNotExists(function ($sub) {
            $sub->selectRaw(1)
                ->from('poultry_batches')
                ->whereColumn('poultry_batches.house_id', 'poultry_houses.id')
                ->where('poultry_batches.status', 'active');
        });
    }

    public static function dropdown($farmId = null, $businessId = null)
    {
        $query = static::query()->forBusiness($businessId)->active();

        if ($farmId) {
            $query->where('farm_id', $farmId);
        }

        return $query->orderBy('name')->pluck('name', 'id')->toArray();
    }

    /** Birds per square metre for the active batch - a welfare check. */
    public function getStockingDensityAttribute()
    {
        $batch = $this->activeBatch;

        if (! $batch || empty($this->floor_area_sqm) || $this->floor_area_sqm <= 0) {
            return null;
        }

        return round($batch->current_qty / $this->floor_area_sqm, 2);
    }
}

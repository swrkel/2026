<?php

namespace Modules\Poultry\Entities;

use Modules\Poultry\Entities\Shared\BusinessLocation;

class Farm extends PoultryModel
{
    protected $table = 'poultry_farms';

    protected $casts = ['is_active' => 'boolean'];

    public function houses()
    {
        return $this->hasMany(House::class, 'farm_id');
    }

    public function batches()
    {
        return $this->hasMany(Batch::class, 'farm_id');
    }

    /** The shared business location this farm operates from. */
    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public static function dropdown($businessId = null)
    {
        return static::query()->forBusiness($businessId)->active()
            ->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function getTotalCapacityAttribute()
    {
        return (int) $this->houses()->sum('capacity');
    }
}

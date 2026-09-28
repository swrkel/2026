<?php

namespace Modules\Poultry\Entities;

use Modules\Poultry\Entities\Shared\Variation;

/**
 * An egg grade, optionally mapped to a sellable variation in the shared
 * products tables. When variation_id is set, collecting eggs of this grade
 * adds stock the POS and Distribution modules can sell directly.
 */
class EggGrade extends PoultryModel
{
    protected $table = 'poultry_egg_grades';

    protected $casts = ['is_saleable' => 'boolean'];

    public function collections()
    {
        return $this->hasMany(EggCollection::class, 'grade_id');
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class, 'variation_id');
    }

    /** True when production of this grade should post to shared stock. */
    public function getIsStockedAttribute()
    {
        return $this->is_saleable && ! empty($this->variation_id);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public static function dropdown($businessId = null)
    {
        return static::query()->forBusiness($businessId)->ordered()
            ->pluck('name', 'id')->toArray();
    }
}
